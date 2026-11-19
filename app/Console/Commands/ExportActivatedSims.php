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
 * is configured in Admin > SIMs Operations (SimExportSettings).
 *
 * Runs automatically every night at 00:05 WAT via Laravel's scheduler (routes/console.php)
 * or can be invoked on-demand via CLI or Admin Operations panel:
 *   php artisan sims:export-activated
 *   php artisan sims:export-activated --to=ops@simphix.com --force
 */
class ExportActivatedSims extends Command
{
    protected $signature = 'sims:export-activated 
        {--all : Export all currently activated SIMs instead of the previous day}
        {--date= : Specific date to export (Y-m-d in Africa/Lagos timezone, defaults to yesterday)}
        {--today : Export activations for today so far instead of yesterday}
        {--hours= : Export activations in the past N hours instead of the previous full day}
        {--to= : Override recipient email address(es), comma-separated}
        {--force : Force execution even if the export toggle is turned off in settings}';

    protected $description = 'Emails a CSV of activated SIM numbers to the recipient configured in Admin > SIMs Operations.';

    public function handle(ActivatedSimsExportService $exporter): int
    {
        $settings = SimExportSettings::current();
        $isActive = $settings->isEffectivelyActive();

        if (!$isActive && !$this->option('force')) {
            $this->info('Nightly activated-SIMs export is turned off (Admin > SIMs Operations) — skipping. Use --force to override.');
            $settings->recordExportResult('skipped', null, null, 'Skipped: export is toggled off in settings.');

            return self::SUCCESS;
        }

        $rawRecipient = $this->option('to')
            ?: ($settings->getEffectiveRecipientEmail() ?: config('mail.from.address'));

        if ($rawRecipient === 'hello@example.com') {
            $rawRecipient = env('MAIL_USERNAME') ?: env('ADMIN_NOTIFICATION_EMAIL');
        }

        if (!$rawRecipient) {
            $this->warn('Nightly activated-SIMs export is on but no recipient email is set — skipping.');
            Log::warning('sims:export-activated skipped: no recipient email configured.');
            $settings->recordExportResult('skipped', null, null, 'Skipped: no recipient email configured.');

            return self::SUCCESS;
        }

        // Split comma, semicolon, space, or newline-delimited recipient emails
        $recipients = array_values(array_filter(
            array_map('trim', preg_split('/[,;\s]+/', (string) $rawRecipient)),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        ));

        if (empty($recipients)) {
            $this->error('No valid recipient email address found: ' . $rawRecipient);
            Log::error('sims:export-activated failed: invalid recipient email address format: ' . $rawRecipient);
            $settings->recordExportResult('failed', null, (string) $rawRecipient, 'Failed: invalid recipient email address format.');

            return self::FAILURE;
        }

        $exportAll = (bool) $this->option('all');
        $exportToday = (bool) $this->option('today');
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
        } elseif ($exportToday) {
            $targetDate = now($timezone);
            $since = $targetDate->copy()->startOfDay()->setTimezone('UTC');
            $until = now()->setTimezone('UTC');
            $periodLabel = $targetDate->format('M d, Y') . ' (Today)';
            $reportDate = $targetDate;
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

        $maxAttempts = 3;
        $attempt = 0;
        $sent = false;
        $lastError = null;

        while ($attempt < $maxAttempts && !$sent) {
            $attempt++;
            try {
                Mail::to($recipients)->send(
                    new ActivatedSimsExportMail($csv, $count, now($timezone), $since, $until, $periodLabel, $reportDate)
                );
                $sent = true;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("sims:export-activated attempt {$attempt}/{$maxAttempts} failed: {$lastError}");
                if ($attempt < $maxAttempts) {
                    sleep(2);
                }
            }
        }

        $recipientList = implode(', ', $recipients);

        if (!$sent) {
            $this->error('Failed to send the export email after ' . $maxAttempts . ' attempt(s): ' . $lastError);
            Log::error('sims:export-activated failed to send after retries: ' . $lastError);
            $settings->recordExportResult('failed', $count, $recipientList, 'Delivery failed: ' . $lastError);

            return self::FAILURE;
        }

        $settings->recordExportResult('success', $count, $recipientList, "Successfully exported {$count} number(s) ({$periodLabel}) to {$recipientList}");
        $this->info("Sent {$count} activated SIM number(s) ({$periodLabel}) to {$recipientList}.");

        return self::SUCCESS;
    }
}
