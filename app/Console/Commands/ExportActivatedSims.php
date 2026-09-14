<?php

namespace App\Console\Commands;

use App\Mail\ActivatedSimsExportMail;
use App\Models\SimExportSettings;
use App\Services\ActivatedSimsExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a CSV of every currently-activated SIM number to whichever inbox
 * is configured in Admin > SIMs Operations — nothing happens if that
 * export is turned off or no recipient is set, so this is always safe to
 * run on a schedule regardless of the toggle's state.
 *
 * Meant to run nightly. Two equally valid ways to wire that up:
 *  1. Already registered with Laravel's own scheduler (see
 *     routes/console.php) — works automatically if the server's crontab
 *     has the one standard `* * * * * php artisan schedule:run` entry.
 *  2. Add it directly as its own crontab line instead, e.g.:
 *     0 0 * * * cd /path/to/app && php artisan sims:export-activated
 *     Both are safe to have at once — the on/off check below means a
 *     stray extra run never double-emails anything harmful.
 */
class ExportActivatedSims extends Command
{
    protected $signature = 'sims:export-activated';

    protected $description = 'Emails a CSV of every activated SIM number to the recipient configured in Admin > SIMs Operations, if that export is turned on.';

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

        $count = $exporter->count();
        $csv = $exporter->toCsvString();

        try {
            Mail::to($settings->recipient_email)->send(new ActivatedSimsExportMail($csv, $count, now()));
        } catch (\Exception $e) {
            $this->error('Failed to send the export email: ' . $e->getMessage());
            Log::error('sims:export-activated failed to send: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sent {$count} activated SIM number(s) to {$settings->recipient_email}.");

        return self::SUCCESS;
    }
}
