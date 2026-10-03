<?php

use App\Jobs\GrantActivationBonusData;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function acWallet(User $user, float $balance): Wallet
{
    return Wallet::create([
        'user_id' => $user->id,
        'balance' => $balance,
        'bonus' => 0,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);
}

/**
 * Per-test-unique category so the kill-switch only ever flips a field this
 * test created, never the dev DB's real "POS SIM" row.
 */
function acField(string $category, bool $disabled): ServiceField
{
    $service = Service::firstOrCreate(['name' => 'simcard'], ['description' => 'SIM Card']);
    $field = ServiceField::create([
        'service_id' => $service->id,
        'field_name' => $category,
        'field_code' => strtolower(str_replace(' ', '_', $category)),
        'base_price' => 1000,
        'is_active' => true,
        'activation_disabled' => $disabled,
    ]);
    ServicePrice::create([
        'service_fields_id' => $field->id,
        'user_type' => 'personal',
        'user_id' => null,
        'price' => 1000,
        'commission' => 0,
        'service_id' => $service->id,
    ]);

    return $field;
}

function acSim(string $category, array $attrs = []): Sim
{
    return Sim::create($attrs + [
        'number' => '0806' . fake()->unique()->numerify('#######'),
        'category' => $category,
        'provider' => 'mtn',
        'status' => SimStatus::UNASSIGNED,
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->support = User::factory()->create(['role' => 'support']);
    $this->category = 'ACTCTRL-' . uniqid();
});

/* ---------------- Activation control blocks activation everywhere ---------------- */

test('with activation off, a partner cannot activate a held SIM for a downline user and the wallet is untouched', function () {
    acField($this->category, true);
    $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $customer = User::factory()->create(['role' => 'personal', 'status' => 'active', 'referred_by' => $partner->id]);
    acWallet($customer, 5000);
    $sim = acSim($this->category, ['status' => SimStatus::ASSIGNED_TO_PARTNER, 'partner_id' => $partner->id, 'user_id' => $partner->id]);

    $response = $this->actingAs($partner)->post(route('sims.activate-for-user'), ['sim_id' => $sim->id, 'user_id' => $customer->id]);

    $response->assertSessionHas('error');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect((float) Wallet::where('user_id', $customer->id)->value('balance'))->toBe(5000.00);
});

test('with activation off, fulfilling a personal user request does not activate, debit, or assign anything', function () {
    acField($this->category, true);
    $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $customer = User::factory()->create(['role' => 'personal', 'status' => 'active', 'referred_by' => $partner->id]);
    acWallet($customer, 5000);
    $sim = acSim($this->category, ['status' => SimStatus::ASSIGNED_TO_PARTNER, 'partner_id' => $partner->id, 'user_id' => $partner->id]);
    $request = SimRequest::create([
        'user_id' => $customer->id, 'upline_id' => $partner->id, 'category' => $this->category,
        'provider' => 'mtn', 'quantity' => 1, 'status' => 'pending',
    ]);

    $response = $this->actingAs($partner)->post(route('sims.downline-requests.fulfill', $request), ['sim_ids' => [$sim->id]]);

    $response->assertSessionHas('error');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect($sim->fresh()->user_id)->toBe($partner->id);
    expect($request->fresh()->status)->toBe('pending');
    expect((float) Wallet::where('user_id', $customer->id)->value('balance'))->toBe(5000.00);
});

test('with activation off, an admin cannot activate a SIM directly', function () {
    acField($this->category, true);
    $holder = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $sim = acSim($this->category, ['status' => SimStatus::ASSIGNED_TO_PARTNER, 'partner_id' => $holder->id, 'user_id' => $holder->id]);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.activate', $sim));

    $response->assertSessionHas('error');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
});

test('with activation off, an admin cannot approve an activation request', function () {
    acField($this->category, true);
    $holder = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $sim = acSim($this->category, ['status' => SimStatus::ASSIGNED_TO_PARTNER, 'partner_id' => $holder->id, 'user_id' => $holder->id]);
    $request = SimRequest::create([
        'user_id' => $holder->id, 'sim_id' => $sim->id, 'category' => $this->category,
        'provider' => 'mtn', 'quantity' => 1, 'request_type' => 'activation', 'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.requests.approve', $request));

    $response->assertSessionHas('error');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect($request->fresh()->status)->toBe('pending');
});

test('turning activation back on lets the same partner activation go through (the switch works both ways)', function () {
    $field = acField($this->category, true);
    $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $customer = User::factory()->create(['role' => 'personal', 'status' => 'active', 'referred_by' => $partner->id]);
    acWallet($customer, 5000);
    $sim = acSim($this->category, ['status' => SimStatus::ASSIGNED_TO_PARTNER, 'partner_id' => $partner->id, 'user_id' => $partner->id]);

    $this->actingAs($partner)->post(route('sims.activate-for-user'), ['sim_id' => $sim->id, 'user_id' => $customer->id])
        ->assertSessionHas('error');

    // Admin flips the switch back on through the real route.
    $this->actingAs($this->admin)->post(route('admin.sim-plan.categories.toggle-activation', $field));
    expect($field->fresh()->activation_disabled)->toBeFalse();

    $this->actingAs($partner)->post(route('sims.activate-for-user'), ['sim_id' => $sim->id, 'user_id' => $customer->id])
        ->assertSessionHas('success');

    expect($sim->fresh()->status)->toBe(SimStatus::ACTIVATED);
    expect((float) Wallet::where('user_id', $customer->id)->value('balance'))->toBe(4000.00);
});

/* ---------------- Support desk: inventory + failed activations, no activation control ---------------- */

test('the support dashboard links to SIM Inventory and Failed Activations', function () {
    $response = $this->actingAs($this->support)->get(route('support-desk.dashboard'));

    $response->assertOk();
    $response->assertSee(route('support-desk.sim-inventory.index'), false);
    $response->assertSee(route('support-desk.failed-activations.index'), false);
});

test('support can view failed activations and retry one; a retry in flight cannot be queued twice', function () {
    Queue::fake();
    $holder = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $sim = acSim($this->category, ['status' => SimStatus::ACTIVATED, 'partner_id' => $holder->id, 'user_id' => $holder->id]);
    $report = Report::create([
        'user_id' => $holder->id, 'phone_number' => $sim->number, 'network' => 'mtn',
        'ref' => 'BONUS-' . fake()->unique()->numerify('########'), 'amount' => 0, 'status' => 'failed',
        'type' => 'activation_bonus_data', 'description' => 'vendor down', 'old_balance' => 0, 'new_balance' => 0,
    ]);

    $this->actingAs($this->support)->get(route('support-desk.failed-activations.index'))
        ->assertOk()->assertSee($sim->number);

    $this->actingAs($this->support)->post(route('support-desk.failed-activations.retry', $report))
        ->assertSessionHas('success');
    expect($report->fresh()->status)->toBe('retrying');
    Queue::assertPushed(GrantActivationBonusData::class, fn ($job) => $job->existingReport?->id === $report->id);

    $this->actingAs($this->support)->post(route('support-desk.failed-activations.retry', $report))
        ->assertSessionHas('error');
});

test('support has no access to the activation control or the admin SIM plans page', function () {
    $field = acField($this->category, false);

    $this->actingAs($this->support)
        ->post(route('admin.sim-plan.categories.toggle-activation', $field))
        ->assertForbidden();
    expect($field->fresh()->activation_disabled)->toBeFalse();

    $this->actingAs($this->support)->get(route('admin.sim-plan.index'))->assertForbidden();
});

test('support-desk pages never render the activation control', function () {
    acField($this->category, false);

    foreach (['support-desk.dashboard', 'support-desk.failed-activations.index', 'support-desk.sim-inventory.index'] as $name) {
        $this->actingAs($this->support)->get(route($name))
            ->assertOk()
            ->assertDontSee('toggle-activation', false)
            ->assertDontSee('Activation Controls');
    }
});

test('customers and other staff roles are forbidden from the support failed-activations pages', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    $this->actingAs($partner)->get(route('support-desk.failed-activations.index'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('support-desk.failed-activations.index'))->assertForbidden();
});
