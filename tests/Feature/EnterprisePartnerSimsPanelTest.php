<?php

use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function epSim(User $ep, ?User $agent, string $status, ?string $eventAt): Sim
{
    $sim = Sim::create([
        'number' => '0805' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'business_id' => $ep->id,
        'business_agent_id' => $agent?->id,
        'user_id' => $agent?->id,
        'status' => $status,
    ]);

    if ($eventAt) {
        $h = SimHistory::create(['sim_id' => $sim->id, 'from_status' => null, 'to_status' => $status, 'actor_id' => $ep->id, 'note' => 't']);
        $h->forceFill(['created_at' => $eventAt])->save();
    }

    return $sim;
}

beforeEach(function () {
    $this->ep = User::factory()->create(['role' => 'business']);
    $this->agent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id, 'first_name' => 'Ada', 'last_name' => 'Agent']);
    foreach ([$this->ep, $this->agent] as $u) {
        Wallet::create(['user_id' => $u->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $u->id, 7, '0', STR_PAD_LEFT)]);
    }
});

test('an enterprise partner sees activated and downline-held sims on the dashboard', function () {
    $activated = epSim($this->ep, $this->agent, SimStatus::ACTIVATED, '2026-09-10 10:00:00');
    $held = epSim($this->ep, $this->agent, SimStatus::ASSIGNED_TO_BUSINESS_AGENT, '2026-09-12 10:00:00');
    $other = epSim(User::factory()->create(['role' => 'business']), null, SimStatus::ACTIVATED, '2026-09-10 10:00:00');

    $res = $this->actingAs($this->ep)->get(route('dashboard'))->assertOk();

    $res->assertSee($activated->number)->assertSee($held->number)->assertDontSee($other->number)->assertSee('Ada Agent');
});

test('the date filter narrows both lists by their event date', function () {
    $old = epSim($this->ep, $this->agent, SimStatus::ACTIVATED, '2026-08-01 10:00:00');
    $new = epSim($this->ep, $this->agent, SimStatus::ACTIVATED, '2026-09-10 10:00:00');
    $oldHeld = epSim($this->ep, $this->agent, SimStatus::ASSIGNED_TO_BUSINESS_AGENT, '2026-08-02 10:00:00');
    $newHeld = epSim($this->ep, $this->agent, SimStatus::ASSIGNED_TO_BUSINESS_AGENT, '2026-09-11 10:00:00');

    $res = $this->actingAs($this->ep)
        ->get(route('dashboard', ['sims_from' => '2026-09-01', 'sims_to' => '2026-09-30']))
        ->assertOk();

    $res->assertSee($new->number)->assertSee($newHeld->number)
        ->assertDontSee($old->number)->assertDontSee($oldHeld->number);
});

test('an invalid date range is rejected', function () {
    $this->actingAs($this->ep)
        ->get(route('dashboard', ['sims_from' => '2026-09-30', 'sims_to' => '2026-09-01']))
        ->assertSessionHasErrors('sims_to');
});

test('other roles do not get the sims panel', function () {
    Wallet::create(['user_id' => ($u = User::factory()->create(['role' => 'personal']))->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $u->id, 7, '0', STR_PAD_LEFT)]);

    $this->actingAs($u)->get(route('dashboard'))->assertOk()->assertDontSee('Downline Held SIMs');
});
