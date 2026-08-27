<?php

namespace App\Console\Commands;

use App\Jobs\GrantActivationBonusData;
use App\Models\ActivationBonusSettings;
use App\Models\Sim;
use App\Support\SimBonusPeriod;
use App\Support\SimStatus;
use Illuminate\Console\Command;

/**
 * Bulk, on-demand re-run of the per-activation data bonus: for every
 * network with an active bonus plan configured, queues a
 * GrantActivationBonusData job for every already-activated SIM on that
 * network — same job, same vendor routing, same rate limiting as the
 * normal single-SIM activation path. Intended to be triggered by an
 * external cron entry (no Laravel scheduler wired up in this app), e.g.:
 *   0 9 * * * cd /path/to/app && php artisan sim:grant-bulk-activation-bonus
 */
class GrantBulkActivationBonusData extends Command
{
    protected $signature = 'sim:grant-bulk-activation-bonus';

    protected $description = 'Queue the currently configured activation data bonus for every already-activated SIM, per network';

    public function handle(): int
    {
        $totalQueued = 0;

        foreach (ActivationBonusSettings::PROVIDERS as $provider) {
            $settings = ActivationBonusSettings::forProvider($provider);

            if (!$settings->is_active || !$settings->plan) {
                $this->line("Skipping {$provider}: bonus is off or no plan selected.");
                continue;
            }

            $queued = 0;
            $skipped = 0;
            Sim::where('provider', $provider)
                ->where('status', SimStatus::ACTIVATED)
                ->chunkById(200, function ($sims) use (&$queued, &$skipped) {
                    foreach ($sims as $sim) {
                        // Don't queue a job that's guaranteed to no-op — a sim
                        // whose current rolling-anniversary period was already
                        // granted (or hasn't reached a new one since the last
                        // run) has nothing to do here. GrantActivationBonusData
                        // still carries its own internal guard regardless, so
                        // this is a pure noise/queue-churn reduction, not the
                        // authoritative check.
                        if (!SimBonusPeriod::isDueForGrant($sim)) {
                            $skipped++;
                            continue;
                        }

                        GrantActivationBonusData::dispatch($sim);
                        $queued++;
                    }
                });

            $this->info("Queued {$queued}, skipped {$skipped} (not yet due) on {$provider} for \"{$settings->plan->size} ({$settings->plan->plan_type})\".");
            $totalQueued += $queued;
        }

        $this->info("Total: {$totalQueued} activation bonus job(s) queued.");

        return self::SUCCESS;
    }
}
