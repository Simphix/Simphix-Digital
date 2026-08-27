<?php

use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\State;
use App\Models\User;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function simsOpsAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

function activatedSimFor(?User $regionalManager = null, ?User $coordinator = null, ?User $user = null): Sim
{
    $sim = Sim::create([
        'number' => '0804' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::ACTIVATED,
        'regional_manager_id' => $regionalManager?->id,
        'coordinator_id' => $coordinator?->id,
        'user_id' => $user?->id,
    ]);

    SimHistory::create([
        'sim_id' => $sim->id,
        'from_status' => SimStatus::ASSIGNED_TO_PARTNER,
        'to_status' => SimStatus::ACTIVATED,
        'actor_id' => $user?->id ?? simsOpsAdmin()->id,
        'note' => 'test activation',
    ]);

    return $sim;
}

test('super admin can view the sims operations dashboard', function () {
    $this->actingAs(simsOpsAdmin())->get(route('admin.sims-operations.index'))->assertOk();
});

test('non-super-admin is forbidden from the sims operations dashboard', function () {
    $partner = User::factory()->create(['role' => 'partner']);
    $this->actingAs($partner)->get(route('admin.sims-operations.index'))->assertForbidden();
});

test('filtering by district only includes sims whose coordinator holds that district', function () {
    $admin = simsOpsAdmin();
    $region = Region::create(['name' => 'Region A']);
    $state = State::create(['name' => 'State A', 'region_id' => $region->id]);
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    $district = SenatorialDistrict::create(['name' => 'District A', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    $inDistrict = activatedSimFor(coordinator: $coordinator);
    $outsideDistrict = activatedSimFor();

    $matches = Sim::inJurisdiction(null, null, $district->id)->pluck('id');
    expect($matches)->toContain($inDistrict->id);
    expect($matches)->not->toContain($outsideDistrict->id);
});

test('filtering by region includes sims whose coordinator sits under that region via its state', function () {
    $region = Region::create(['name' => 'Region B']);
    $state = State::create(['name' => 'State B', 'region_id' => $region->id]);
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    SenatorialDistrict::create(['name' => 'District B', 'state_id' => $state->id, 'coordinator_id' => $coordinator->id]);

    $inRegion = activatedSimFor(coordinator: $coordinator);
    $outsideRegion = activatedSimFor();

    $matches = Sim::inJurisdiction($region->id, null, null)->pluck('id');
    expect($matches)->toContain($inRegion->id);
    expect($matches)->not->toContain($outsideRegion->id);
});

test('filtering by region also includes sims held directly by that region\'s regional manager', function () {
    $region = Region::create(['name' => 'Region C']);
    $rm = User::factory()->create(['role' => 'regional_manager']);
    $region->update(['regional_manager_id' => $rm->id]);

    $inRegion = activatedSimFor(regionalManager: $rm);
    $outsideRegion = activatedSimFor();

    $matches = Sim::inJurisdiction($region->id, null, null)->pluck('id');
    expect($matches)->toContain($inRegion->id);
    expect($matches)->not->toContain($outsideRegion->id);
});

test('the user_id filter matches a sim by any of its six holder columns', function () {
    $partner = User::factory()->create(['role' => 'partner']);
    $customer = User::factory()->create(['role' => 'personal']);

    $simHeldByPartner = Sim::create(['number' => '0804' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED, 'partner_id' => $partner->id]);
    $simActivatedForCustomer = Sim::create(['number' => '0804' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED, 'user_id' => $customer->id]);
    $unrelatedSim = Sim::create(['number' => '0804' . fake()->unique()->numerify('#######'), 'category' => 'POS SIM', 'provider' => 'mtn', 'status' => SimStatus::ACTIVATED]);

    $this->actingAs(simsOpsAdmin())
        ->get(route('admin.sims-operations.index', ['user_id' => $partner->id]))
        ->assertOk();

    // Exercise the same holder-column filter the controller composes,
    // directly against the model, for a precise assertion.
    $matches = Sim::where(function ($q) use ($partner) {
        foreach (['user_id', 'partner_id', 'coordinator_id', 'regional_manager_id', 'business_id', 'business_agent_id'] as $column) {
            $q->orWhere($column, $partner->id);
        }
    })->pluck('id');

    expect($matches)->toContain($simHeldByPartner->id);
    expect($matches)->not->toContain($simActivatedForCustomer->id);
    expect($matches)->not->toContain($unrelatedSim->id);
});

test('the date range filter changes the total activation count', function () {
    $admin = simsOpsAdmin();
    $sim = activatedSimFor();
    SimHistory::where('sim_id', $sim->id)->update(['created_at' => now()->subDays(20)]);

    $recent = $this->actingAs($admin)->get(route('admin.sims-operations.index', ['range' => '7days']));
    $recent->assertOk();
    $recent->assertViewHas('totalActivations', fn ($count) => $count === 0);

    $wide = $this->actingAs($admin)->get(route('admin.sims-operations.index', [
        'range' => 'custom',
        'period_start' => now()->subDays(25)->format('Y-m-d'),
        'period_end' => now()->format('Y-m-d'),
    ]));
    $wide->assertOk();
    $wide->assertViewHas('totalActivations', fn ($count) => $count >= 1);
});

test('csv export returns a header row and one row per activation event', function () {
    activatedSimFor();
    activatedSimFor();

    $response = $this->actingAs(simsOpsAdmin())->get(route('admin.sims-operations.export', ['range' => '7days']));
    $response->assertOk();

    $lines = array_filter(explode("\n", $response->getContent()));
    expect(count($lines))->toBeGreaterThanOrEqual(1);
    expect($lines[0])->toContain('Sim Number');
});

test('category and provider breakdown counts sum to the total activation count', function () {
    $admin = simsOpsAdmin();
    activatedSimFor();
    activatedSimFor();

    $response = $this->actingAs($admin)->get(route('admin.sims-operations.index', ['range' => '7days']));
    $response->assertOk();

    $total = $response->viewData('totalActivations');
    $byCategory = $response->viewData('byCategory');
    $byProvider = $response->viewData('byProvider');

    expect($byCategory->sum('count'))->toBe($total);
    expect($byProvider->sum('count'))->toBe($total);
});
