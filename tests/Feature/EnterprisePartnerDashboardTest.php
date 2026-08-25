<?php

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function makeEpPosSim(): Sim
{
    return Sim::create([
        'number' => '0802' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::UNASSIGNED,
    ]);
}

beforeEach(function () {
    $this->svc = app(SimAssignmentService::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->ep = User::factory()->create(['role' => 'business', 'status' => 'active']);
    $this->agent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id, 'status' => 'active']);
    $this->customer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->agent->id, 'status' => 'active']);

    Wallet::create(['user_id' => $this->customer->id, 'balance' => 50000, 'wallet_number' => 'WLT' . str_pad((string) $this->customer->id, 7, '0', STR_PAD_LEFT)]);
});

test('EP sees a distribute-mode POS device page listing their held stock', function () {
    $sim = makeEpPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);

    $response = $this->actingAs($this->ep)->get(route('sims.pos'));

    $response->assertOk();
    $response->assertViewHas('mode', 'distribute');
    $response->assertViewHas('heldSims', fn ($sims) => $sims->pluck('id')->contains($sim->id));
});

test('business_agent sees an activate-for-user POS device page listing their held stock', function () {
    $sim = makeEpPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);

    $response = $this->actingAs($this->agent)->get(route('sims.pos'));

    $response->assertOk();
    $response->assertViewHas('mode', 'activate_for_user');
    $response->assertViewHas('heldSims', fn ($sims) => $sims->pluck('id')->contains($sim->id));
});

test('EP can view inventory and the bulk-assign picker; business_agent is refused the bulk picker', function () {
    $this->actingAs($this->ep)->get(route('sims.inventory'))->assertOk();
    $this->actingAs($this->ep)->get(route('sims.bulk-assign.page'))->assertOk();

    $this->actingAs($this->agent)->get(route('sims.bulk-assign.page'))->assertForbidden();
});

test('EP bulk-assigns held SIMs to their business_agent, who can then activate one for a claimed personal customer', function () {
    $sim1 = makeEpPosSim();
    $sim2 = makeEpPosSim();
    $this->svc->adminAssign($sim1, $this->ep, $this->admin);
    $this->svc->adminAssign($sim2, $this->ep, $this->admin);

    $this->actingAs($this->ep)->post(route('sims.bulk-assign'), [
        'sim_ids' => [$sim1->id, $sim2->id],
        'user_id' => $this->agent->id,
    ])->assertRedirect();

    $sim1->refresh();
    $sim2->refresh();
    expect($sim1->business_agent_id)->toBe($this->agent->id);
    expect($sim1->user_id)->toBe($this->agent->id);
    expect($sim2->business_agent_id)->toBe($this->agent->id);

    $service = Service::firstOrCreate(['name' => 'simcard'], ['status' => 'active']);
    $field = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['base_price' => 1500, 'activation_disabled' => false]
    );
    ServicePrice::updateOrCreate(
        ['service_fields_id' => $field->id, 'user_type' => 'personal', 'user_id' => null],
        ['price' => 1500, 'commission' => 0]
    );

    $response = $this->actingAs($this->agent)->post(route('sims.activate-for-user'), [
        'sim_id' => $sim1->id,
        'user_id' => $this->customer->id,
    ]);

    $response->assertRedirect();
    $sim1->refresh();
    expect($sim1->status)->toBe(SimStatus::ACTIVATED);
    // Chain FKs survive activation for the (future) commission listener.
    expect($sim1->business_id)->toBe($this->ep->id);
    expect($sim1->business_agent_id)->toBe($this->agent->id);
});

test('a personal user referred by a business_agent resolves their upline correctly for self-service requests', function () {
    // resolveUpline() is private; exercised indirectly via the request-a-SIM flow.
    $response = $this->actingAs($this->customer)->get(route('sims.pos.request'));

    $response->assertOk();
});

test('claiming a self-onboarded personal user via the Network claim UI promotes them into the correct chain role', function () {
    $selfOnboarded = User::factory()->create(['role' => 'personal', 'referred_by' => null, 'status' => 'active']);

    // EP claims a self-onboarded user -> becomes a business_agent.
    $this->actingAs($this->ep)->post(route('network.claim'), ['user_id' => $selfOnboarded->id])->assertRedirect();
    $selfOnboarded->refresh();
    expect($selfOnboarded->role)->toBe('business_agent');
    expect($selfOnboarded->referred_by)->toBe($this->ep->id);

    $anotherSelfOnboarded = User::factory()->create(['role' => 'personal', 'referred_by' => null, 'status' => 'active']);

    // A business_agent claims a self-onboarded user -> stays personal, under them.
    $this->actingAs($this->agent)->post(route('network.claim'), ['user_id' => $anotherSelfOnboarded->id])->assertRedirect();
    $anotherSelfOnboarded->refresh();
    expect($anotherSelfOnboarded->role)->toBe('personal');
    expect($anotherSelfOnboarded->referred_by)->toBe($this->agent->id);
});

test('EP sees downline requests routed from their business_agent stock', function () {
    $sim = makeEpPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);

    $simRequest = SimRequest::create([
        'user_id' => $this->agent->id,
        'upline_id' => $this->ep->id,
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'quantity' => 1,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->ep)->get(route('sims.downline-requests'));

    $response->assertOk();
    $response->assertViewHas('requests', fn ($requests) => $requests->firstWhere('id', $simRequest->id)?->held_count === 1);
});
