<?php

namespace App\Console\Commands;

use App\Mail\ActivatedSimsExportMail;
use App\Models\SimExportSettings;
use App\Services\ActivatedSimsExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a CSV of SIM numbers activated during the previous day to whichever inbox
 * is configured in Admin > SIMs Operations — nothing happens if that
 * export is turned off or no recipient is set, so this is always safe to
 * run on a schedule regardless of the toggle's state. Intended to be
 * triggered by an external cron entry (e.g. nightly):
 *   0 0 * * * cd /path/to/app && php artisan sims:export-activated
 */
class ExportActivatedSims extends Command
{
    protected $signature = 'sims:export-activated 
        {--all : Export all currently activated SIMs instead of the previous day}
        {--date= : Specific date to export (Y-m-d in Africa/Lagos timezone, defaults to yesterday)}
        {--hours= : Export activations in the past N hours instead of the previous full day}';

    protected $description = 'Emails a CSV of activated SIM numbers to the recipient configured in Admin > SIMs Operations.';

    public function handle(ActivatedSimsExportService $exporter): int
    {
        $settings = SimExportSettings::current();

        if (!$settings->is_active) {
            $this->info('Nightly activated-SIMs export is turned off (Admin > SIMs Operations) — skipping.');

            return self::SUCCESS;
        }

        if (!$settings->recipient_email) {
            $this->warn('Nightly activated-SIMs export is on but no recipient email is set — skipping.');
            Log::warning('sims:export-activated skipped: no recipient email configured.');

            return self::SUCCESS;
        }

        $exportAll = (bool) $this->option('all');
        $specificDate = $this->option('date');
        $hours = $this->option('hours') ? (int) $this->option('hours') : null;

        $timezone = 'Africa/Lagos';

        if ($exportAll) {
            $since = null;
            $until = null;
            $periodLabel = 'all-time';
            $reportDate = null;
        } elseif ($hours) {
            $until = now();
            $since = now()->subHours($hours);
            $periodLabel = "past {$hours} hours";
            $reportDate = now($timezone);
        } else {
            // Default: strict activation events for the previous full calendar day (00:00:00 to 23:59:59 WAT / Africa/Lagos)
            $targetDate = $specificDate
                ? Carbon::parse($specificDate, $timezone)
                : now($timezone)->subDay();

            $since = $targetDate->copy()->startOfDay()->setTimezone('UTC');
            $until = $targetDate->copy()->endOfDay()->setTimezone('UTC');
            $periodLabel = $targetDate->format('M d, Y');
            $reportDate = $targetDate;
        }

        $count = $exporter->count($since, $until);
        $csv = $exporter->toCsvString($since, $until);

        try {
            Mail::to($settings->recipient_email)->send(
                new ActivatedSimsExportMail($csv, $count, now($timezone), $since, $until, $periodLabel, $reportDate)
            );
        } catch (\Exception $e) {
            $this->error('Failed to send the export email: ' . $e->getMessage());
            Log::error('sims:export-activated failed to send: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sent {$count} activated SIM number(s) ({$periodLabel}) to {$settings->recipient_email}.");

        return self::SUCCESS;
    }
}
