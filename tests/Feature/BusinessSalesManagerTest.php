<?php

use App\Models\Region;
use App\Models\Report;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\SimRequest;
use App\Models\State;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Setup SIM service & pricing
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $field = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-BSM', 'base_price' => 1000, 'is_active' => true]
    );

    ServicePrice::firstOrCreate(
        ['service_fields_id' => $field->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 200]
    );

    // Setup BSM user
    $this->bsm = User::factory()->create([
        'role'              => 'business_sales_manager',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    // Setup hierarchy: RM -> Coordinator -> Partner
    $this->rm = User::factory()->create(['role' => 'regional_manager', 'status' => 'active']);
    $this->coordinator = User::factory()->create([
        'role'        => 'coordinator',
        'status'      => 'active',
        'referred_by' => $this->rm->id,
    ]);
    $this->partner = User::factory()->create([
        'role'        => 'partner',
        'status'      => 'active',
        'referred_by' => $this->coordinator->id,
    ]);

    // Setup Region, State, District
    $this->region = Region::create([
        'name'                => 'North West ' . uniqid(),
        'regional_manager_id' => $this->rm->id,
    ]);
    $this->state = State::create([
        'name'      => 'Kano ' . uniqid(),
        'region_id' => $this->region->id,
    ]);
    $this->district = SenatorialDistrict::create([
        'name'           => 'Kano Central ' . uniqid(),
        'state_id'       => $this->state->id,
        'coordinator_id' => $this->coordinator->id,
    ]);
});

test('unauthenticated user cannot access BSM dashboard', function () {
    $this->get(route('bsm.dashboard'))
        ->assertRedirect(route('login'));
});

test('unauthorized customer cannot access BSM console', function () {
    $personal = User::factory()->create(['role' => 'personal', 'email_verified_at' => now()]);

    $this->actingAs($personal)
        ->get(route('bsm.dashboard'))
        ->assertStatus(403);
});

test('BSM user is redirected to BSM dashboard upon hitting /dashboard', function () {
    $this->actingAs($this->bsm)
        ->get(route('dashboard'))
        ->assertRedirect(route('bsm.dashboard'));
});

test('Business Sales Manager can access BSM dashboard and sees executive KPIs', function () {
    // Create some SIMs in various statuses
    Sim::create([
        'number'   => '080' . random_int(10000000, 99999999),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $simActive = Sim::create([
        'number'              => '080' . random_int(10000000, 99999999),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'user_id'             => $this->partner->id,
        'status'              => SimStatus::ACTIVATED,
    ]);

    SimCustomer::create([
        'sim_id'       => $simActive->id,
        'partner_id'   => $this->partner->id,
        'full_name'    => 'Aliyu Bello',
        'phone_number' => '08099887766',
        'email'        => 'aliyu@test.com',
    ]);

    $response = $this->actingAs($this->bsm)->get(route('bsm.dashboard'));

    $response->assertOk()
        ->assertSee('Business Sales Manager Console')
        ->assertSee('SIM Lifecycle Governance')
        ->assertSee('Active Regional Performance')
        ->assertSee($this->region->name);
});

test('Super Admin can also access BSM console', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('bsm.dashboard'))
        ->assertOk()
        ->assertSee('Business Sales Manager Console');
});

test('BSM can export jurisdiction report as CSV', function () {
    $response = $this->actingAs($this->bsm)
        ->get(route('bsm.reports.jurisdictions.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->getContent();

    expect($content)->toContain('SIMPHIX BUSINESS SALES MANAGER - TERRITORY & SALES REPORT');
    expect($content)->toContain('EXECUTIVE OVERVIEW');
    expect($content)->toContain('REGIONAL SALES BREAKDOWN');
    expect($content)->toContain('SENATORIAL DISTRICTS BREAKDOWN');
});

test('BSM can assign an unassigned SIM to a Regional Manager or Coordinator', function () {
    $sim = Sim::create([
        'number'   => '080' . random_int(10000000, 99999999),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.assign'), [
            'sim_id'    => $sim->id,
            'target_id' => $this->rm->id,
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_RM);
    expect($sim->fresh()->regional_manager_id)->toBe($this->rm->id);
});

test('BSM can execute lateral swap for a held SIM to unblock distribution', function () {
    $newCoordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $sim = Sim::create([
        'number'         => '080' . random_int(10000000, 99999999),
        'category'       => 'POS SIM',
        'provider'       => 'mtn',
        'coordinator_id' => $this->coordinator->id,
        'status'         => SimStatus::ASSIGNED_TO_COORDINATOR,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.reassign'), [
            'sim_id'        => $sim->id,
            'new_holder_id' => $newCoordinator->id,
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->coordinator_id)->toBe($newCoordinator->id);
});

test('BSM can revert delegated unactivated SIM back to partner', function () {
    $downlineUser = User::factory()->create(['role' => 'personal', 'status' => 'active']);

    $sim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $downlineUser->id,
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.revert'), [
            'sim_id' => $sim->id,
            'note'   => 'BSM unblocked stuck handoff',
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->user_id)->toBe($this->partner->id);
});

test('BSM can suspend and reactivate a SIM', function () {
    $sim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
        'status'     => SimStatus::ACTIVATED,
    ]);

    // Suspend
    $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.status', $sim), ['action' => 'suspend'])
        ->assertSessionHas('success');

    expect($sim->fresh()->status)->toBe(SimStatus::SUSPENDED);

    // Reactivate
    $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.status', $sim), ['action' => 'activate'])
        ->assertSessionHas('success');

    expect($sim->fresh()->status)->toBe(SimStatus::ACTIVATED);
});

test('BSM Customer Desk: quick onboard and activate customer', function () {
    $sim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.customers.quick-activate'), [
            'sim_id'       => $sim->id,
            'full_name'    => 'Fatima Aliyu',
            'phone_number' => '08033221100',
            'email'        => 'fatima@test.com',
            'address'      => '12 Marina Road, Lagos',
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->status)->toBe(SimStatus::ACTIVATED);

    $customer = SimCustomer::where('sim_id', $sim->id)->first();
    expect($customer)->not->toBeNull();
    expect($customer->full_name)->toBe('Fatima Aliyu');
    expect($customer->phone_number)->toBe('08033221100');
});

test('BSM can approve a pending partner SIM purchase request', function () {
    $sim = Sim::create([
        'number'   => '080' . random_int(10000000, 99999999),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $request = SimRequest::create([
        'user_id'      => $this->partner->id,
        'sim_id'       => $sim->id,
        'category'     => 'POS SIM',
        'provider'     => 'mtn',
        'quantity'     => 1,
        'request_type' => 'purchase',
        'status'       => 'pending',
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.requests.approve', $request));

    $response->assertSessionHas('success');
    expect($request->fresh()->status)->toBe('approved');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
});

test('BSM dashboard renders days held and idle days as rounded whole numbers without decimals', function () {
    $stuckSim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
    ]);
    $stuckSim->forceFill([
        'created_at' => now()->subDays(10)->subHours(5)->subMinutes(30),
        'updated_at' => now()->subDays(10)->subHours(5)->subMinutes(30),
    ])->save();

    expect($stuckSim->daysHeld())->toBe(10);

    $response = $this->actingAs($this->bsm)
        ->get(route('bsm.dashboard'));

    $response->assertOk();
    $response->assertSee('10d idle');
    $response->assertSee('10 days');
});
