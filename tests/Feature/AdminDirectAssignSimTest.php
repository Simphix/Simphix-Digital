<?php

use App\Jobs\GrantActivationBonusData;
use App\Models\CommissionEarning;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function directAssignSimUser(string $role, float $balance = 0.00): User
{
    $user = User::factory()->create(['role' => $role, 'status' => 'active']);
    Wallet::create([
        'user_id' => $user->id,
        'balance' => $balance,
        'bonus' => 0,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);

    return $user->fresh();
}

function directAssignSim(string $category, string $provider = 'mtn'): Sim
{
    return Sim::create([
        'number' => '0807' . fake()->unique()->numerify('#######'),
        'category' => $category,
        'provider' => $provider,
        'status' => SimStatus::UNASSIGNED,
    ]);
}

/**
 * A fresh ServiceField under a per-test-unique category name (rather than
 * the real "POS SIM" the dev DB already has plenty of live inventory for),
 * so pricing can only ever resolve against a SIM this test itself created.
 */
function setDirectAssignPrice(string $category, float $price, float $commission = 0.00, string $provider = 'mtn'): ServiceField
{
    $service = Service::firstOrCreate(['name' => 'simcard'], ['description' => 'SIM Card']);
    $field = ServiceField::create([
        'service_id' => $service->id,
        'field_name' => $category,
        'field_code' => strtolower(str_replace(' ', '_', $category)),
        'base_price' => $price,
        'is_active' => true,
        'activation_disabled' => false,
    ]);

    ServicePrice::create([
        'service_fields_id' => $field->id,
        'user_type' => 'personal',
        'user_id' => null,
        'price' => $price,
        'commission' => $commission,
        'service_id' => $service->id,
    ]);

    return $field;
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->category = 'DIRECTASSIGN-' . uniqid();
});

test('non-super_admin actors are forbidden from the direct-assign route', function () {
    setDirectAssignPrice($this->category, 1000);
    $partner = User::factory()->create(['role' => 'partner']);
    $target = directAssignSimUser('personal', 5000);
    $sim = directAssignSim($this->category);

    $response = $this->actingAs($partner)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertForbidden();
});

test('admin can assign and activate a SIM directly to a personal user by its exact number, no commission paid even when a rate is configured', function () {
    setDirectAssignPrice($this->category, 1000, commission: 500);
    $target = directAssignSimUser('personal', 5000);
    $sim = directAssignSim($this->category);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $freshSim = $sim->fresh();
    expect($freshSim->status)->toBe(SimStatus::ACTIVATED);
    expect($freshSim->user_id)->toBe($target->id);
    expect($freshSim->partner_id)->toBeNull();

    $wallet = Wallet::where('user_id', $target->id)->first();
    expect((float) $wallet->balance)->toBe(4000.00);
    expect((float) $wallet->bonus)->toBe(0.00);

    expect(\App\Models\Transaction::where('user_id', $target->id)->where('type', 'debit')->count())->toBe(1);
    expect(\App\Models\Report::where('user_id', $target->id)->where('type', 'sim_activation')->count())->toBe(1);

    // Structural guarantee: nobody is paid, even though a non-zero personal
    // commission rate was explicitly configured above.
    expect(CommissionEarning::where('sim_id', $freshSim->id)->count())->toBe(0);
});

test('the admin gets the exact SIM number they typed, not just any available one in the category', function () {
    setDirectAssignPrice($this->category, 1000);
    $target = directAssignSimUser('personal', 5000);
    $sim1 = directAssignSim($this->category);
    $sim2 = directAssignSim($this->category);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim2->number,
    ]);

    $response->assertSessionHas('success');
    expect($sim2->fresh()->status)->toBe(SimStatus::ACTIVATED);
    expect($sim2->fresh()->user_id)->toBe($target->id);
    expect($sim1->fresh()->status)->toBe(SimStatus::UNASSIGNED);
});

test('platform welfare wallet and the silent activation data bonus still fire for a direct admin activation', function () {
    setDirectAssignPrice($this->category, 1000);
    $welfareBefore = (float) (PlatformWallet::where('type', PlatformWallet::TYPE_WELFARE)->value('balance') ?? 0.00);
    // The singleton settings row (PlatformWelfareSettings::current()) may
    // already exist in the dev DB — update it in place rather than
    // inserting a second, never-consulted row.
    PlatformWelfareSettings::current()->update(['amount_per_activation' => 50.00, 'is_active' => true]);
    $target = directAssignSimUser('personal', 5000);
    $sim = directAssignSim($this->category);
    Queue::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertSessionHas('success');

    $welfareAfter = (float) PlatformWallet::where('type', PlatformWallet::TYPE_WELFARE)->value('balance');
    expect($welfareAfter)->toBe($welfareBefore + 50.00);

    Queue::assertPushed(GrantActivationBonusData::class);
});

test('direct-assign is rejected when the target personal user has insufficient wallet balance, with no side effects', function () {
    setDirectAssignPrice($this->category, 1000);
    $target = directAssignSimUser('personal', 200.00);
    $sim = directAssignSim($this->category);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertSessionHas('error');
    expect($sim->fresh()->status)->toBe(SimStatus::UNASSIGNED);
    expect((float) Wallet::where('user_id', $target->id)->value('balance'))->toBe(200.00);
    expect(\App\Models\Transaction::where('user_id', $target->id)->count())->toBe(0);
    expect(CommissionEarning::where('sim_id', $sim->id)->count())->toBe(0);
});

test('direct-assign is rejected for a non-personal-role target', function () {
    setDirectAssignPrice($this->category, 1000);
    $target = directAssignSimUser('partner', 5000);
    $sim = directAssignSim($this->category);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertSessionHas('error');
    expect($sim->fresh()->status)->toBe(SimStatus::UNASSIGNED);
});

test('direct-assign fails cleanly when the submitted number does not exist, with no side effects', function () {
    $target = directAssignSimUser('personal', 5000);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => '08000000000',
    ]);

    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'No SIM found'));
    expect((float) Wallet::where('user_id', $target->id)->value('balance'))->toBe(5000.00);
    expect(\App\Models\Transaction::where('user_id', $target->id)->count())->toBe(0);
});

test('direct-assign is rejected when the submitted number is already assigned to someone else, with no double-assignment', function () {
    setDirectAssignPrice($this->category, 1000);
    $target = directAssignSimUser('personal', 5000);
    $other = directAssignSimUser('personal', 0);
    $sim = directAssignSim($this->category);
    $sim->update(['status' => SimStatus::ASSIGNED_TO_PARTNER, 'user_id' => $other->id]);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertSessionHas('error', fn ($message) => str_contains($message, 'not available'));
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect($sim->fresh()->user_id)->toBe($other->id);
    expect((float) Wallet::where('user_id', $target->id)->value('balance'))->toBe(5000.00);
    expect(\App\Models\Transaction::where('user_id', $target->id)->count())->toBe(0);
});

test('direct-assign respects the per-category activation kill-switch and rolls back the assignment too', function () {
    $field = setDirectAssignPrice($this->category, 1000);
    $field->update(['activation_disabled' => true]);
    $target = directAssignSimUser('personal', 5000);
    $sim = directAssignSim($this->category);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.direct-assign'), [
        'user_id' => $target->id, 'number' => $sim->number,
    ]);

    $response->assertSessionHas('error');
    // Not just "not activated" — the assignment itself must roll back too,
    // otherwise the SIM would be stuck ASSIGNED_TO_PARTNER but never active.
    expect($sim->fresh()->status)->toBe(SimStatus::UNASSIGNED);
    expect((float) Wallet::where('user_id', $target->id)->value('balance'))->toBe(5000.00);
});

test('a normal (non-admin-direct) activation still pays commission as before', function () {
    setDirectAssignPrice($this->category, 1000, commission: 300);
    $holder = directAssignSimUser('personal', 0);
    $sim = directAssignSim($this->category);
    $sim->update(['user_id' => $holder->id, 'status' => SimStatus::ASSIGNED_TO_PARTNER, 'partner_id' => null]);

    app(\App\Services\SimAssignmentService::class)->activate($sim->fresh(), $this->admin);

    expect(CommissionEarning::where('sim_id', $sim->fresh()->id)->count())->toBe(1);
});

test('the Admin SIM Activation button renders on the SIM Plan index page for a super_admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.sim-plan.index'));

    $response->assertOk();
    $response->assertSee('Admin SIM Activation');
});
