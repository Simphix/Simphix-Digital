<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearWebhookLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:clear-logs
                            {--force : Clear without confirmation prompt}
                            {--provider= : Filter by webhook provider (e.g. fintava, palmpay, paystack, moniepoint)}
                            {--status= : Filter by status (e.g. processed, failed, pending)}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'webhook:clear-logs',
        'simphix:clear-webhook-logs',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all Webhook logs from the database';

    public function handle(): int
    {
        $provider = $this->option('provider') ? strtolower(trim((string) $this->option('provider'))) : null;
        $status = $this->option('status') ? strtolower(trim((string) $this->option('status'))) : null;

        $query = WebhookLog::query();

        if ($provider) {
            $query->where('provider', $provider);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $totalCount = (clone $query)->count();

        if ($totalCount === 0) {
            $this->warn('No webhook logs found matching criteria.');
            return self::SUCCESS;
        }

        // Summary table by provider
        $byProvider = (clone $query)
            ->select('provider', DB::raw('count(*) as count'))
            ->groupBy('provider')
            ->pluck('count', 'provider')
            ->toArray();

        $this->table(
            ['Provider', 'Webhook Log Count'],
            collect($byProvider)->map(fn ($cnt, $prov) => [strtoupper((string) ($prov ?: 'UNKNOWN')), number_format($cnt)])->values()->all()
        );

        $this->line("Total webhook logs found: <fg=yellow;options=bold>{$totalCount}</>");

        if (! $this->option('force')) {
            $filterNote = '';
            if ($provider || $status) {
                $parts = array_filter(["provider: {$provider}", "status: {$status}"]);
                $filterNote = ' (' . implode(', ', $parts) . ')';
            }

            $confirmed = $this->confirm(
                "Are you sure you want to permanently clear {$totalCount} webhook log(s){$filterNote}? This action cannot be undone.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No webhook logs were cleared.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing webhook logs... ');

        $deleted = $query->delete();

        $this->info('DONE.');
        $this->info("Successfully cleared {$deleted} webhook log(s) from the database.");

        return self::SUCCESS;
    }
}
