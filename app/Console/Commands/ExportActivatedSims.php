<?php

namespace App\Console\Commands;

use App\Mail\ActivatedSimsExportMail;
use App\Models\SimExportSettings;
use App\Services\ActivatedSimsExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails a CSV of SIM numbers activated in the past 24 hours to whichever inbox
 * is configured in Admin > SIMs Operations — nothing happens if that
 * export is turned off or no recipient is set, so this is always safe to
 * run on a schedule regardless of the toggle's state. Intended to be
 * triggered by an external cron entry (e.g. nightly):
 *   0 0 * * * cd /path/to/app && php artisan sims:export-activated
 */
class ExportActivatedSims extends Command
{
    protected $signature = 'sims:export-activated {--all : Export all currently activated SIMs instead of only those activated in the past 24 hours}';

    protected $description = 'Emails a CSV of SIM numbers activated in the past 24 hours to the recipient configured in Admin > SIMs Operations.';

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
        $since = $exportAll ? null : now()->subHours(24);

        $count = $exporter->count($since);
        $csv = $exporter->toCsvString($since);

        try {
            Mail::to($settings->recipient_email)->send(new ActivatedSimsExportMail($csv, $count, now(), $since));
        } catch (\Exception $e) {
            $this->error('Failed to send the export email: ' . $e->getMessage());
            Log::error('sims:export-activated failed to send: ' . $e->getMessage());

            return self::FAILURE;
        }

        $periodLabel = $exportAll ? 'all-time' : 'past 24 hours';
        $this->info("Sent {$count} activated SIM number(s) ({$periodLabel}) to {$settings->recipient_email}.");

        return self::SUCCESS;
    }
}
