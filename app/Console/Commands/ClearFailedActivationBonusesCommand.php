<?php

namespace App\Console\Commands;

use App\Jobs\ClearFailedActivationBonuses;
use Illuminate\Console\Command;

/**
 * Artisan command to trigger ClearFailedActivationBonuses on demand.
 *
 * Usage:
 *   php artisan sim:clear-failed-activation-bonuses
 *   php artisan activation-bonus:clear-failed
 *   php artisan sim:clear-failed-activation-bonuses --days=7
 *   php artisan sim:clear-failed-activation-bonuses --resolve
 *   php artisan sim:clear-failed-activation-bonuses --network=mtn
 */
class ClearFailedActivationBonusesCommand extends Command
{
    protected $signature = 'sim:clear-failed-activation-bonuses
                            {--days= : Only clear records older than N days}
                            {--network= : Filter by network (e.g. mtn, airtel, glo, 9mobile)}
                            {--number= : Filter by SIM phone number}
                            {--resolve : Mark records as resolved instead of deleting them}
                            {--queue : Dispatch job to background queue instead of running synchronously}';

    protected $aliases = [
        'activation-bonus:clear-failed',
    ];

    protected $description = 'Clear failed and stuck retrying activation bonus data reports';

    public function handle(): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : null;
        $asResolved = (bool) $this->option('resolve');
        $network = $this->option('network') ? (string) $this->option('network') : null;
        $number = $this->option('number') ? (string) $this->option('number') : null;
        $shouldQueue = (bool) $this->option('queue');

        $actionWord = $asResolved ? 'Resolving' : 'Clearing';
        $filterNotes = [];

        if ($days !== null && $days > 0) {
            $filterNotes[] = "older than {$days} days";
        }
        if ($network) {
            $filterNotes[] = "network: {$network}";
        }
        if ($number) {
            $filterNotes[] = "number: {$number}";
        }

        $filterDesc = !empty($filterNotes) ? ' (' . implode(', ', $filterNotes) . ')' : '';
        $this->info("{$actionWord} failed activation bonus data reports{$filterDesc}...");

        $job = new ClearFailedActivationBonuses(
            olderThanDays: $days,
            asResolved: $asResolved,
            network: $network,
            number: $number,
        );

        if ($shouldQueue) {
            dispatch($job);
            $this->info('ClearFailedActivationBonuses job has been dispatched to the background queue.');
            return self::SUCCESS;
        }

        $cleared = $job->handle();

        if ($cleared === 0) {
            $this->line('No matching failed activation bonus reports found.');
        } else {
            $actionPast = $asResolved ? 'marked as resolved' : 'deleted';
            $this->info("Successfully {$actionPast} {$cleared} failed activation bonus report(s).");
        }

        return self::SUCCESS;
    }
}
