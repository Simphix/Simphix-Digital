<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cheap probe for a stalled/never-started queue worker — the app relies
 * entirely on the `database` queue (activation bonuses, payment
 * notification emails, Fintava bank-list sync, ...), and a dead worker
 * fails completely silently: jobs just accumulate in the `jobs` table with
 * zero log output, discovered only when a user notices something never
 * arrived. Intended to be run on a schedule (e.g. every 15-30 minutes) via
 * the same external cron entry pattern documented in docs/cron-jobs.md.
 */
class QueueHealthCheck extends Command
{
    protected $signature = 'queue:health-check';

    protected $description = 'Log a critical alert if queued jobs are stuck unprocessed or piling up in failed_jobs';

    /** A healthy worker picks up a job within seconds; 15 minutes is a generous margin before treating it as stuck. */
    private const STUCK_THRESHOLD_MINUTES = 15;

    public function handle(): int
    {
        $stuckCutoff = now()->subMinutes(self::STUCK_THRESHOLD_MINUTES)->getTimestamp();

        $stuckCount = DB::table('jobs')->where('created_at', '<', $stuckCutoff)->count();
        $oldestCreatedAt = DB::table('jobs')->min('created_at');
        $failedCount = DB::table('failed_jobs')->count();

        if ($stuckCount === 0 && $failedCount === 0) {
            $this->info('Queue healthy: no stuck or failed jobs.');
            return self::SUCCESS;
        }

        $oldestAge = $oldestCreatedAt ? now()->diffForHumans(now()->setTimestamp($oldestCreatedAt), true) : 'unknown';

        if ($stuckCount > 0) {
            Log::critical("Queue health check: {$stuckCount} job(s) unprocessed for over " . self::STUCK_THRESHOLD_MINUTES . " minutes (oldest: {$oldestAge}). This usually means the queue worker isn't running — check Supervisor/cron per docs/cron-jobs.md.");
            $this->error("{$stuckCount} job(s) stuck, oldest queued {$oldestAge} ago.");
        }

        if ($failedCount > 0) {
            Log::critical("Queue health check: {$failedCount} row(s) in failed_jobs.");
            $this->error("{$failedCount} job(s) in failed_jobs.");
        }

        return self::SUCCESS;
    }
}
