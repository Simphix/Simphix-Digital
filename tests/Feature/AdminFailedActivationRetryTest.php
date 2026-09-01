<?php

use App\Jobs\GrantActivationBonusData;
use App\Models\Report;
use App\Models\Sim;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function failedActivationBonusReport(Sim $sim, string $status = 'failed'): Report
{
    return Report::create([
        'user_id' => $sim->user_id,
        'phone_number' => $sim->number,
        'network' => $sim->provider,
        'ref' => 'BONUS-' . fake()->unique()->numerify('########'),
        'amount' => 0.00,
        'status' => $status,
        'type' => 'activation_bonus_data',
        'description' => 'Activation bonus data top-up failed: vendor down',
        'old_balance' => 0.00,
        'new_balance' => 0.00,
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin']);
});

test('retrying a failed report claims it (failed -> retrying) and dispatches the job bound to that report', function () {
    Queue::fake();
    $sim = activateBonusSim(bonusSim(), $this->admin);
    $report = failedActivationBonusReport($sim);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.failed-activations.retry', $report));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($report->fresh()->status)->toBe('retrying');
    expect($report->fresh()->resolved_at)->toBeNull();

    Queue::assertPushed(GrantActivationBonusData::class, fn ($job) => $job->sim->id === $sim->id && $job->existingReport?->id === $report->id);
});

test('a second retry click while the first is still in flight is rejected, and only one job is ever dispatched', function () {
    Queue::fake();
    $sim = activateBonusSim(bonusSim(), $this->admin);
    $report = failedActivationBonusReport($sim);

    $first = $this->actingAs($this->admin)->post(route('admin.sim-plan.failed-activations.retry', $report));
    $first->assertSessionHas('success');

    $second = $this->actingAs($this->admin)->post(route('admin.sim-plan.failed-activations.retry', $report));
    $second->assertSessionHas('error');

    // SIM activation itself always auto-dispatches its own (unrelated) bonus
    // job, so the count assertion is scoped to jobs bound to this report.
    Queue::assertPushed(GrantActivationBonusData::class, fn ($job) => $job->existingReport?->id === $report->id);
    expect(Queue::pushed(GrantActivationBonusData::class, fn ($job) => $job->existingReport?->id === $report->id))->toHaveCount(1);
});

test('a report that is already resolved cannot be retried', function () {
    Queue::fake();
    $sim = activateBonusSim(bonusSim(), $this->admin);
    $report = failedActivationBonusReport($sim, 'completed');
    $report->update(['resolved_at' => now()]);

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.failed-activations.retry', $report));

    $response->assertSessionHas('error');
    Queue::assertNotPushed(GrantActivationBonusData::class, fn ($job) => $job->existingReport?->id === $report->id);
});

test('a report currently "retrying" cannot be retried again', function () {
    Queue::fake();
    $sim = activateBonusSim(bonusSim(), $this->admin);
    $report = failedActivationBonusReport($sim, 'retrying');

    $response = $this->actingAs($this->admin)->post(route('admin.sim-plan.failed-activations.retry', $report));

    $response->assertSessionHas('error');
    Queue::assertNotPushed(GrantActivationBonusData::class, fn ($job) => $job->existingReport?->id === $report->id);
});

test('GrantActivationBonusData updates the same report row in place on a successful retry instead of inserting a new one', function () {
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    enableBonusPlan('mtn');
    fakeSuccessfulVendorCall();
    $report = failedActivationBonusReport($sim);
    $report->update(['status' => 'retrying']);

    (new GrantActivationBonusData($sim, $report))->handle();

    expect(Report::where('type', 'activation_bonus_data')->count())->toBe(1);
    $fresh = $report->fresh();
    expect($fresh->status)->toBe('completed');
    expect($fresh->resolved_at)->not->toBeNull();
    Http::assertSentCount(1);
});

test('a retry that fails again reverts the report to failed with resolved_at cleared, so it stays retryable', function () {
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    enableBonusPlan('mtn');
    Http::fake(['*' => Http::response(['status' => 'failed', 'message' => 'vendor down'], 200)]);
    $report = failedActivationBonusReport($sim);
    $report->update(['status' => 'retrying']);

    (new GrantActivationBonusData($sim, $report))->handle();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe('failed');
    expect($fresh->resolved_at)->toBeNull();
    expect(Report::where('type', 'activation_bonus_data')->count())->toBe(1);
});

test('a stale retry against an already-fulfilled period is resolved without a new vendor call', function () {
    fakeSuccessfulVendorCall();
    enableBonusPlan('mtn');
    // Activating with the bonus plan already enabled auto-grants period 0
    // synchronously (QUEUE_CONNECTION=sync in this test env).
    $sim = activateBonusSim(bonusSim('mtn'), $this->admin);
    Http::assertSentCount(1);

    $report = failedActivationBonusReport($sim);
    $report->update(['status' => 'retrying']);

    (new GrantActivationBonusData($sim, $report))->handle();

    $fresh = $report->fresh();
    expect($fresh->status)->toBe('completed');
    expect($fresh->resolved_at)->not->toBeNull();
    // No additional vendor call was made for the stale retry.
    Http::assertSentCount(1);
});

test('the Failed Activations tab excludes resolved rows but still lists an in-flight retry without a retry button', function () {
    $failedSim = activateBonusSim(bonusSim(), $this->admin);
    $retryingSim = activateBonusSim(bonusSim(), $this->admin);
    $completedSim = activateBonusSim(bonusSim(), $this->admin);

    $failedReport = failedActivationBonusReport($failedSim);
    $retryingReport = failedActivationBonusReport($retryingSim, 'retrying');
    $completedReport = failedActivationBonusReport($completedSim, 'completed');
    $completedReport->update(['resolved_at' => now()]);

    $response = $this->actingAs($this->admin)->get(route('admin.sim-plan.index'));

    $response->assertOk();
    // The completed sim's own number legitimately appears elsewhere on this
    // page (the general SIM Inventory table lists every sim regardless of
    // report status), so the exclusion is verified against the view's own
    // $failedActivations data, not a blanket assertDontSee on the page body.
    $response->assertViewHas('failedActivations', function ($collection) use ($failedReport, $retryingReport, $completedReport) {
        return $collection->contains('id', $failedReport->id)
            && $collection->contains('id', $retryingReport->id)
            && !$collection->contains('id', $completedReport->id);
    });
    $response->assertSee('Retrying');
    $response->assertDontSee(route('admin.sim-plan.failed-activations.retry', $retryingReport), false);
});
