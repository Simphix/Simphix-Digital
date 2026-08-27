<?php

use App\Jobs\GrantActivationBonusData;
use App\Models\ActivationBonusSettings;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SmeData;
use App\Models\User;
use App\Services\SimAssignmentService;
use App\Support\SimBonusPeriod;
use App\Support\SimStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function bonusSim(string $provider = 'mtn'): Sim
{
    return Sim::create([
        'number' => '0809' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => $provider,
        'status' => SimStatus::UNASSIGNED,
    ]);
}

function activateBonusSim(Sim $sim, User $actor): Sim
{
    app(SimAssignmentService::class)->adminAssign($sim, $actor, $actor);
    app(SimAssignmentService::class)->activate($sim->fresh(), $actor);

    return $sim->fresh();
}

function enableBonusPlan(string $provider = 'mtn'): void
{
    $plan = SmeData::create([
        'data_id' => 'PLAN-' . fake()->unique()->numerify('####'),
        'provider' => 'fadeel', // routes to the generic SmeDataPurchaseApi adapter
        'network' => $provider,
        'plan_type' => 'SME',
        'personal_price' => 0,
        'agent_price' => 0,
        'partner_price' => 0,
        'business_price' => 0,
        'vendor_amount' => 100,
        'size' => '1GB',
        'validity' => '30 days',
        'status' => 'enabled',
    ]);

    $settings = ActivationBonusSettings::forProvider($provider);
    $settings->update(['is_active' => true, 'sme_data_id' => $plan->id]);
}

function fakeSuccessfulVendorCall(): void
{
    // Http::fake() merges rather than replaces stubs — the FIRST wildcard
    // registered in a test wins for every subsequent request, so each test
    // needing HTTP mocking calls this exactly once itself rather than
    // relying on a shared beforeEach fake that a later in-test override
    // could never actually take effect over.
    Http::fake(['*' => Http::response(['status' => 'success', 'transid' => 'VEND-1'], 200)]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin']);
});

test('SimBonusPeriod::indexFor computes rolling anniversary windows correctly', function () {
    $anchor = Carbon\Carbon::parse('2026-01-15 14:00:00');

    expect(SimBonusPeriod::indexFor($anchor, $anchor->copy()))->toBe(0);
    expect(SimBonusPeriod::indexFor($anchor, $anchor->copy()->addDays(20)))->toBe(0);
    expect(SimBonusPeriod::indexFor($anchor, $anchor->copy()->addMonthsNoOverflow(1)))->toBe(1);
    expect(SimBonusPeriod::indexFor($anchor, $anchor->copy()->addMonthsNoOverflow(1)->subMinute()))->toBe(0);
    expect(SimBonusPeriod::indexFor($anchor, $anchor->copy()->addMonthsNoOverflow(3)->addDays(2)))->toBe(3);
});

test('SimBonusPeriod::indexFor handles a month-end anchor correctly (no-overflow month math)', function () {
    $anchor = Carbon\Carbon::parse('2026-01-31 10:00:00');

    // Jan 31 + 1 month (no overflow) = Feb 28 (2026 is not a leap year)
    expect(SimBonusPeriod::indexFor($anchor, Carbon\Carbon::parse('2026-02-28 10:00:00')))->toBe(1);
    expect(SimBonusPeriod::indexFor($anchor, Carbon\Carbon::parse('2026-02-27 23:59:59')))->toBe(0);
});

test('SimBonusPeriod::bounds returns the correct [start, end) window', function () {
    $anchor = Carbon\Carbon::parse('2026-03-10 09:00:00');
    [$start, $end] = SimBonusPeriod::bounds($anchor, 2);

    expect($start->toDateTimeString())->toBe('2026-05-10 09:00:00');
    expect($end->toDateTimeString())->toBe('2026-06-10 09:00:00');
});

test('isDueForGrant is true with no prior grant and false once one exists for the current period', function () {
    $sim = activateBonusSim(bonusSim(), $this->admin);

    expect(SimBonusPeriod::isDueForGrant($sim))->toBeTrue();

    $anchor = SimBonusPeriod::anchor($sim);
    [$start, $end] = SimBonusPeriod::bounds($anchor, 0);
    SimActivationBonusGrant::create([
        'sim_id' => $sim->id, 'period_index' => 0, 'period_start' => $start, 'period_end' => $end,
        'granted_at' => now(), 'status' => 'completed',
    ]);

    expect(SimBonusPeriod::isDueForGrant($sim))->toBeFalse();
});

test('dispatching the job twice for the same sim within one window grants only once', function () {
    // Activate BEFORE enabling the bonus plan — the SimActivated listener
    // fires synchronously in this test env (QUEUE_CONNECTION=sync) and
    // would otherwise auto-grant period 0 itself, before this test gets to
    // control the attempts manually.
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    enableBonusPlan('mtn');
    fakeSuccessfulVendorCall();

    (new GrantActivationBonusData($sim))->handle();
    (new GrantActivationBonusData($sim))->handle();

    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->count())->toBe(1);
    Http::assertSentCount(1);
});

test('advancing past the window boundary allows a fresh grant', function () {
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    enableBonusPlan('mtn');
    fakeSuccessfulVendorCall();

    (new GrantActivationBonusData($sim))->handle();
    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->count())->toBe(1);

    $this->travel(32)->days();

    (new GrantActivationBonusData($sim))->handle();
    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->count())->toBe(2);
    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->pluck('period_index')->sort()->values()->all())->toBe([0, 1]);
});

test('a direct double-insert on (sim_id, period_index) throws a query exception', function () {
    $sim = activateBonusSim(bonusSim(), $this->admin);
    $anchor = SimBonusPeriod::anchor($sim);
    [$start, $end] = SimBonusPeriod::bounds($anchor, 0);

    SimActivationBonusGrant::create(['sim_id' => $sim->id, 'period_index' => 0, 'period_start' => $start, 'period_end' => $end, 'granted_at' => now(), 'status' => 'completed']);

    expect(fn () => SimActivationBonusGrant::create(['sim_id' => $sim->id, 'period_index' => 0, 'period_start' => $start, 'period_end' => $end, 'granted_at' => now(), 'status' => 'completed']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('a retry of a failed grant is still allowed', function () {
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    enableBonusPlan('mtn');

    // A single fake registration with a two-response sequence — Http::fake()
    // merges rather than replaces stubs, so a second Http::fake() call
    // mid-test would never actually override this first one; fakeSequence()
    // is the correct way to vary the response across successive calls.
    Http::fake(['*' => Http::sequence()
        ->push(['status' => 'failed', 'message' => 'vendor down'], 200)
        ->push(['status' => 'success'], 200)]);

    (new GrantActivationBonusData($sim))->handle();
    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->where('status', 'completed')->count())->toBe(0);

    (new GrantActivationBonusData($sim))->handle();
    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->where('status', 'completed')->count())->toBe(1);
});

test('the bulk command only grants once per sim across two back-to-back runs', function () {
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    enableBonusPlan('mtn');
    fakeSuccessfulVendorCall();

    $this->artisan('sim:grant-bulk-activation-bonus')->assertExitCode(0);
    $this->artisan('sim:grant-bulk-activation-bonus')->assertExitCode(0);

    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->count())->toBe(1);
});

test('the bulk command does not even dispatch a job for a sim whose current period was already granted', function () {
    enableBonusPlan('mtn');
    fakeSuccessfulVendorCall();
    // Activating with the bonus plan already enabled auto-grants period 0
    // via the real SimActivated -> GrantActivationBonus listener (fires
    // synchronously here, QUEUE_CONNECTION=sync) — no need to seed the
    // grant row manually.
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    expect(SimActivationBonusGrant::where('sim_id', $sim->id)->where('period_index', 0)->exists())->toBeTrue();

    Queue::fake();
    $this->artisan('sim:grant-bulk-activation-bonus')->assertExitCode(0);

    Queue::assertNotPushed(GrantActivationBonusData::class, fn ($job) => $job->sim->id === $sim->id);
});
