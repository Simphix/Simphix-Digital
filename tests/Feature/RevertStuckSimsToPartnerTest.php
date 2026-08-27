<?php

use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function stuckPartnerSim(User $partner, User $customer): Sim
{
    return Sim::create([
        'number' => '0806' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_PARTNER,
        'partner_id' => $partner->id,
        'user_id' => $customer->id,
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->partner = User::factory()->create(['role' => 'partner']);
    $this->customer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->partner->id]);
});

// -----------------------------------------------------------------------
// SimAssignmentService::revertToHolder()
// -----------------------------------------------------------------------

test('revertToHolder puts a mid-handoff sim back with its partner and logs the same-status history entry', function () {
    $sim = stuckPartnerSim($this->partner, $this->customer);
    $service = app(SimAssignmentService::class);

    $service->revertToHolder($sim, $this->admin, 'Reverted for test.');
    $sim->refresh();

    expect($sim->user_id)->toBe($this->partner->id);
    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);

    $history = SimHistory::where('sim_id', $sim->id)->latest('id')->first();
    expect($history->from_status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect($history->to_status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect($history->note)->toBe('Reverted for test.');
});

test('revertToHolder refuses a sim already self-held by its partner', function () {
    $sim = stuckPartnerSim($this->partner, $this->partner); // user_id === partner_id already

    expect(fn () => app(SimAssignmentService::class)->revertToHolder($sim, $this->admin, 'note'))
        ->toThrow(RuntimeException::class, 'already sitting with its holder');
});

test('revertToHolder refuses an already-activated sim', function () {
    $sim = stuckPartnerSim($this->partner, $this->customer);
    $sim->update(['status' => SimStatus::ACTIVATED]);

    expect(fn () => app(SimAssignmentService::class)->revertToHolder($sim, $this->admin, 'note'))
        ->toThrow(RuntimeException::class, 'not in a delegated-but-unactivated state');
});

test('revertToHolder works for the business_agent chain via business_agent_id', function () {
    $ep = User::factory()->create(['role' => 'business']);
    $agent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $ep->id]);
    $customer = User::factory()->create(['role' => 'personal', 'referred_by' => $agent->id]);

    $sim = Sim::create([
        'number' => '0807' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
        'business_agent_id' => $agent->id,
        'user_id' => $customer->id,
    ]);

    app(SimAssignmentService::class)->revertToHolder($sim, $this->admin, 'note');
    $sim->refresh();

    expect($sim->user_id)->toBe($agent->id);
    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS_AGENT);
});

// -----------------------------------------------------------------------
// sim:revert-stuck-to-partner console command
// -----------------------------------------------------------------------

test('the command reverts a stuck, never-debited sim back to its partner', function () {
    $sim = stuckPartnerSim($this->partner, $this->customer);

    $this->artisan('sim:revert-stuck-to-partner', ['--force' => true])
        ->assertExitCode(0);

    expect($sim->fresh()->user_id)->toBe($this->partner->id);
    expect(SimHistory::where('sim_id', $sim->id)->exists())->toBeTrue();
});

test('the command skips a sim that already has a matching debit, leaving it untouched', function () {
    $sim = stuckPartnerSim($this->partner, $this->customer);
    Transaction::create([
        'transaction_ref' => 'ACT-TEST-1',
        'user_id' => $this->customer->id,
        'amount' => 1000,
        'fee' => 0,
        'net_amount' => 1000,
        'description' => "SIM Card Activation: {$sim->number} (Category: POS SIM)",
        'type' => 'debit',
        'status' => 'completed',
        'performed_by' => 'Test',
    ]);

    $this->artisan('sim:revert-stuck-to-partner', ['--force' => true])
        ->assertExitCode(0);

    expect($sim->fresh()->user_id)->toBe($this->customer->id);
    expect(SimHistory::where('sim_id', $sim->id)->exists())->toBeFalse();
});

test('dry-run makes no changes', function () {
    $sim = stuckPartnerSim($this->partner, $this->customer);

    $this->artisan('sim:revert-stuck-to-partner', ['--dry-run' => true])
        ->assertExitCode(0);

    expect($sim->fresh()->user_id)->toBe($this->customer->id);
});

test('a self-held sim (already sitting with its partner) is not touched', function () {
    $sim = stuckPartnerSim($this->partner, $this->partner);

    $this->artisan('sim:revert-stuck-to-partner', ['--force' => true])
        ->assertExitCode(0);

    expect($sim->fresh()->user_id)->toBe($this->partner->id);
    expect(SimHistory::where('sim_id', $sim->id)->exists())->toBeFalse();
});

test('an already-activated sim is not touched', function () {
    $sim = stuckPartnerSim($this->partner, $this->customer);
    $sim->update(['status' => SimStatus::ACTIVATED]);

    $this->artisan('sim:revert-stuck-to-partner', ['--force' => true])
        ->assertExitCode(0);

    expect($sim->fresh()->user_id)->toBe($this->customer->id);
});

test('the --sim option scopes the revert to only the given sim IDs', function () {
    $sim1 = stuckPartnerSim($this->partner, $this->customer);
    $otherCustomer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->partner->id]);
    $sim2 = stuckPartnerSim($this->partner, $otherCustomer);

    $this->artisan('sim:revert-stuck-to-partner', ['--sim' => [$sim1->id], '--force' => true])
        ->assertExitCode(0);

    expect($sim1->fresh()->user_id)->toBe($this->partner->id);
    expect($sim2->fresh()->user_id)->toBe($otherCustomer->id);
});
