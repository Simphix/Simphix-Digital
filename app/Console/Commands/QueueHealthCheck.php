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
    protected $signature = 'queue:health-check 
                            {--show : Display detailed table of all pending and stuck jobs}
                            {--clear : Clear and purge stuck pending jobs from the jobs table}
                            {--force : Force execution without confirmation}';

    protected $description = 'Log a critical alert if queued jobs are stuck unprocessed or piling up in failed_jobs';

    /** A healthy worker picks up a job within seconds; 15 minutes is a generous margin before treating it as stuck. */
    private const STUCK_THRESHOLD_MINUTES = 15;

    public function handle(): int
    {
        if ($this->option('clear')) {
            $count = DB::table('jobs')->count();
            if ($count === 0) {
                $this->info('No pending jobs to clear.');
                return self::SUCCESS;
            }

            if (!$this->option('force') && !$this->confirm("Are you sure you want to purge all {$count} pending job(s) from the jobs table?")) {
                $this->warn('Action aborted.');
                return self::SUCCESS;
            }

            DB::table('jobs')->truncate();
            $this->info("Successfully cleared {$count} job(s) from the queue.");
            return self::SUCCESS;
        }

        $stuckCutoff = now()->subMinutes(self::STUCK_THRESHOLD_MINUTES)->getTimestamp();

        $stuckJobs = DB::table('jobs')->where('created_at', '<', $stuckCutoff)->get();
        $stuckCount = $stuckJobs->count();
        $totalJobsCount = DB::table('jobs')->count();
        $oldestCreatedAt = DB::table('jobs')->min('created_at');
        $failedCount = DB::table('failed_jobs')->count();

        if ($stuckCount === 0 && $failedCount === 0 && $totalJobsCount === 0) {
            $this->info('Queue healthy: no pending, stuck, or failed jobs.');
            return self::SUCCESS;
        }

        $oldestAge = $oldestCreatedAt ? now()->diffForHumans(now()->setTimestamp($oldestCreatedAt), true) : 'unknown';

        if ($stuckCount > 0) {
            Log::critical("Queue health check: {$stuckCount} job(s) unprocessed for over " . self::STUCK_THRESHOLD_MINUTES . " minutes (oldest: {$oldestAge}). This usually means the queue worker isn't running — check Supervisor/cron per docs/cron-jobs.md.");
            $this->error("{$stuckCount} job(s) stuck, oldest queued {$oldestAge} ago.");
        } elseif ($totalJobsCount > 0) {
            $this->warn("{$totalJobsCount} job(s) pending in queue (all within the 15-minute threshold).");
        }

        if ($failedCount > 0) {
            Log::critical("Queue health check: {$failedCount} row(s) in failed_jobs.");
            $this->error("{$failedCount} job(s) in failed_jobs. Run 'php artisan queue:failed' to view them.");
        }

        if ($this->option('show') || $stuckCount > 0) {
            $allJobs = DB::table('jobs')->get();
            if ($allJobs->isNotEmpty()) {
                $rows = $allJobs->map(function ($j) {
                    $payload = json_decode($j->payload, true);
                    $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown');
                    // Shorten namespace if needed
                    $shortName = class_basename($displayName);

                    return [
                        'ID'          => $j->id,
                        'Queue'       => $j->queue,
                        'Job'         => $shortName !== 'Unknown' ? $shortName : substr($displayName, 0, 35),
                        'Attempts'    => $j->attempts,
                        'Status'      => $j->reserved_at ? 'Reserved (Running/Hanging)' : 'Waiting for Worker',
                        'Reserved At' => $j->reserved_at ? date('Y-m-d H:i:s', $j->reserved_at) : '-',
                        'Queued At'   => date('Y-m-d H:i:s', $j->created_at),
                    ];
                });

                $this->newLine();
                $this->table(['ID', 'Queue', 'Job', 'Attempts', 'Status', 'Reserved At', 'Queued At'], $rows);
            }
        }

        return self::SUCCESS;
    }
}
