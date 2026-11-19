<?php

namespace App\Console\Commands;

use App\Models\ActivationBonusSettings;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Support\SimBonusPeriod;
use App\Support\SimStatus;
use Illuminate\Console\Command;

/**
 * Checks activated SIMs daily to see which have reached 28 days since their
 * last data top-up (or activation date), and triggers them for admin action
 * in the SME Data Top-Up table (Admin > SIM Data Top-Up).
 *
 * Does NOT dispatch data automatically — allows the admin to either complete
 * the data top-up via vendor or resolve silently.
 *
 * Runs automatically via Task Scheduler (routes/console.php) or manually:
 *   php artisan sim:grant-bulk-activation-bonus
 */
class GrantBulkActivationBonusData extends Command
{
    protected $signature = 'sim:grant-bulk-activation-bonus';

    protected $description = 'Checks activated SIMs reaching 28 days since last top-up and triggers them for admin approval';

    public function handle(): int
    {
        $totalTriggered = 0;
        $totalChecked = 0;

        foreach (ActivationBonusSettings::PROVIDERS as $provider) {
            $settings = ActivationBonusSettings::forProvider($provider);

            if (!$settings->is_active || !$settings->plan) {
                $this->line("Skipping {$provider}: activation bonus is inactive or has no plan configured.");
                continue;
            }

            $triggered = 0;
            $skipped = 0;

            Sim::where('provider', $provider)
                ->where('status', SimStatus::ACTIVATED)
                ->whereNotNull('number')
                ->where('number', '!=', '')
                ->chunkById(200, function ($sims) use (&$triggered, &$skipped) {
                    foreach ($sims as $sim) {
                        if (!SimBonusPeriod::isDueFor28DayTrigger($sim)) {
                            $skipped++;
                            continue;
                        }

                        $nextIndex = SimBonusPeriod::completedCount($sim);
                        $lastTopUp = SimBonusPeriod::lastTopUpDate($sim);

                        try {
                            SimActivationBonusGrant::create([
                                'sim_id' => $sim->id,
                                'period_index' => $nextIndex,
                                'period_start' => $lastTopUp ?? now(),
                                'period_end' => $lastTopUp ? $lastTopUp->copy()->addDays(28) : now(),
                                'triggered_at' => now(),
                                'granted_at' => null,
                                'status' => SimActivationBonusGrant::STATUS_PENDING,
                            ]);
                            $triggered++;
                        } catch (\Throwable $e) {
                            // If a race or concurrent execution already created this period, skip cleanly
                            $skipped++;
                        }
                    }
                });

            $this->info("Triggered {$triggered} SIM(s), skipped {$skipped} on {$provider} (bonus plan: {$settings->plan->size} {$settings->plan->plan_type}).");
            $totalTriggered += $triggered;
            $totalChecked += ($triggered + $skipped);
        }

        $this->info("Total: {$totalTriggered} SIM(s) triggered out of {$totalChecked} checked. Available for review in Admin > SIM Data Top-Up.");

        return self::SUCCESS;
    }
}
