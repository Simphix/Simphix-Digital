<?php

namespace App\Jobs;

use App\Helpers\RequestIdHelper;
use App\Models\ActivationBonusSettings;
use App\Models\Report;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Services\SmeDataProviderFactory;
use App\Support\SimBonusPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Silent post-activation bonus: tops up the just-activated SIM's own number
 * with a free data bundle, using the same vendor call as a normal "Buy
 * Data" purchase (routed via SmeDataProviderFactory, by the bonus plan's
 * own vendor) — no wallet is debited for it. Runs in the background
 * (queued) so it never delays the activation response.
 */
class GrantActivationBonusData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Background profile — patient, spread out, since nothing user-facing is waiting on this. */
    public int $tries = 4;
    public array $backoff = [30, 120, 600];

    public function __construct(public Sim $sim, public ?Report $existingReport = null)
    {
    }

    public function middleware(): array
    {
        return [new RateLimited('sme-data-vendor')];
    }

    public function handle(): void
    {
        $settings = ActivationBonusSettings::forProvider($this->sim->provider);
        if (!$settings->is_active || !$settings->plan) {
            $this->resolveStaleRetry('Activation bonus is currently turned off for this network.');

            return;
        }

        $anchor = SimBonusPeriod::anchor($this->sim);
        if (!$anchor) {
            // Should be unreachable — this job only ever fires post-activation
            // — but fail closed rather than granting against an undefined
            // period if SimHistory somehow has no ACTIVATED row yet.
            Log::warning('GrantActivationBonusData: no activation history found, skipping.', ['sim' => $this->sim->number]);
            $this->resolveStaleRetry('No activation history found for this SIM.');

            return;
        }

        $periodIndex = SimBonusPeriod::indexFor($anchor, now());

        // App-level precheck: cheap, friendly early-exit before the vendor
        // call. The unique constraint on (sim_id, period_index) below is the
        // race-proof backstop for two jobs racing the same sim/period.
        if (!SimBonusPeriod::isDueForGrant($this->sim)) {
            Log::info('Activation bonus already granted for this period, skipping.', ['sim' => $this->sim->number, 'period_index' => $periodIndex]);
            // A retry against a report whose period was, in the meantime,
            // already fulfilled by a separate successful attempt — resolve
            // it rather than leaving it claimed ("retrying") forever with no
            // terminal outcome.
            $this->resolveStaleRetry('The bonus for this billing period was already granted through a separate attempt.');

            return;
        }

        [$periodStart, $periodEnd] = SimBonusPeriod::bounds($anchor, $periodIndex);

        $plan = $settings->plan;
        $requestId = RequestIdHelper::generateRequestId();

        // A ConnectionException here is intentionally left to propagate —
        // that lets Laravel retry the whole job per $tries/$backoff instead
        // of silently giving up on the first transient network blip. The
        // final give-up (after all retries) is recorded once in failed().
        // Resolve by the bonus plan's OWN vendor, not whichever vendor is
        // currently "active" for the storefront — switching the storefront's
        // active vendor must never silently break this job by sending a
        // plan id from one vendor's namespace to the other vendor's API.
        $result = SmeDataProviderFactory::forProvider($plan->provider)->purchase($this->sim->number, $plan->network, $plan->data_id, $requestId, $plan->vendor_amount);

        $report = $this->logReport($this->sim, $plan, $requestId, $result['success'], $result['success']
            ? "Activation bonus: {$plan->size} {$plan->plan_type} silently topped up on {$this->sim->number}"
            : ('Activation bonus data top-up failed: ' . ($result['message'] ?? 'Unknown error')));

        if ($result['success']) {
            try {
                SimActivationBonusGrant::create([
                    'sim_id' => $this->sim->id,
                    'period_index' => $periodIndex,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'granted_at' => now(),
                    'report_id' => $report->id,
                    'status' => 'completed',
                ]);
            } catch (QueryException $e) {
                if (!str_contains($e->getMessage(), 'sim_bonus_grant_period_unique')) {
                    throw $e;
                }

                Log::warning('Lost the race on activation bonus period — a concurrent job already recorded it.', ['sim' => $this->sim->number, 'period_index' => $periodIndex]);
            }
        }

        if (!$result['success']) {
            Log::warning('Activation bonus data top-up failed', ['sim' => $this->sim->number, 'result' => $result]);
        }
    }

    /**
     * Called once, after every retry attempt has been exhausted (e.g. a
     * sustained vendor outage) — records the final failure so support has
     * an audit trail even though nothing user-facing was ever blocked.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Activation bonus data top-up failed after all retries', [
            'sim'   => $this->sim->number,
            'error' => $exception->getMessage(),
        ]);

        $settings = ActivationBonusSettings::forProvider($this->sim->provider);
        if ($settings->plan) {
            $this->logReport(
                $this->sim,
                $settings->plan,
                RequestIdHelper::generateRequestId(),
                false,
                'Connection error: ' . $exception->getMessage()
            );
        }
    }

    private function logReport(Sim $sim, \App\Models\SmeData $plan, string $ref, bool $success, string $description): Report
    {
        // No wallet is touched for this bonus — old/new balance just reflect
        // the recipient's current balance, unchanged, to satisfy the audit trail.
        $balance = \App\Models\Wallet::where('user_id', $sim->user_id)->value('balance') ?? 0.00;

        $attributes = [
            'user_id'      => $sim->user_id,
            'phone_number' => $sim->number,
            'network'      => $plan->network,
            'ref'          => $ref,
            'amount'       => 0.00,
            'status'       => $success ? 'completed' : 'failed',
            'type'         => 'activation_bonus_data',
            'description'  => $description,
            'old_balance'  => $balance,
            'new_balance'  => $balance,
        ];

        // An admin retry is bound to the original failed Report — update
        // that same row in place instead of inserting a fresh one, so a
        // resolved retry actually disappears from the Failed Activations
        // list rather than leaving the stale row sitting there forever.
        if ($this->existingReport) {
            $existing = $this->existingReport->fresh();
            $existing->update($attributes + ['resolved_at' => $success ? now() : null]);

            return $existing;
        }

        return Report::create($attributes);
    }

    /**
     * Marks a claimed ("retrying") report resolved when handle() bails out
     * before ever reaching logReport() — e.g. the bonus was turned off, or
     * the current period was already fulfilled by a separate attempt.
     * Without this, a report claimed by retryActivationBonus() would be
     * stuck in "retrying" forever with no terminal outcome.
     */
    private function resolveStaleRetry(string $description): void
    {
        if (!$this->existingReport) {
            return;
        }

        $this->existingReport->fresh()->update([
            'status' => 'completed',
            'description' => $description,
            'resolved_at' => now(),
        ]);
    }
}
