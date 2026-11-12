<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\SimActivationBonusGrant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClearDataTopUpHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topups:clear-history
                            {--force : Clear without confirmation prompt}
                            {--dry-run : Preview matching record counts without deleting}
                            {--type= : Specific top-up type to clear: "all", "admin_data_topup", "data", or "activation_bonus_data" (default: all)}
                            {--admin-only : Clear only admin/support manual top-ups (type: admin_data_topup)}
                            {--bonus-only : Clear only activation bonus data top-ups (type: activation_bonus_data)}
                            {--purchases-only : Clear only customer SME data purchases (type: data)}
                            {--status= : Filter by status: "all", "completed", "failed", "processing" (default: all)}
                            {--failed-only : Clear only failed/cancelled top-ups}
                            {--network= : Filter by network (MTN, GLO, AIRTEL, 9MOBILE)}
                            {--phone= : Filter by specific phone/SIM number}
                            {--from= : Start date filter (Y-m-d)}
                            {--to= : End date filter (Y-m-d)}
                            {--days= : Clear top-ups older than given number of days}
                            {--include-bonus-grants : Also clear associated bonus grant records for deleted activation bonus reports}
                            {--all : Explicit flag to clear all matching data top-up history records}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'topups:clear',
        'data-topup:clear-history',
        'data-topups:clear',
        'sim-data-topup:clear-history',
        'sims:clear-topups',
        'simphix:clear-topups',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear recent data top-up history records (admin top-ups, paid data purchases, and activation bonus data) from reports';

    /**
     * Data top-up report types tracked across the application.
     */
    private const TRACKED_TYPES = [
        'admin_data_topup'      => 'Admin / Support Manual Top-Up',
        'data'                  => 'Customer Paid Data Purchase',
        'activation_bonus_data' => 'Activation Bonus Data Grant',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $includeBonusGrants = (bool) $this->option('include-bonus-grants');

        // 1. Resolve Target Types
        $targetTypes = array_keys(self::TRACKED_TYPES);
        if ($this->option('admin-only')) {
            $targetTypes = ['admin_data_topup'];
        } elseif ($this->option('bonus-only')) {
            $targetTypes = ['activation_bonus_data'];
        } elseif ($this->option('purchases-only')) {
            $targetTypes = ['data'];
        } elseif ($this->option('type')) {
            $typeInput = strtolower(trim((string) $this->option('type')));
            if ($typeInput !== 'all') {
                if (!array_key_exists($typeInput, self::TRACKED_TYPES)) {
                    $this->error("Invalid top-up type '{$typeInput}'. Allowed types: " . implode(', ', array_keys(self::TRACKED_TYPES)) . ', or "all".');
                    return self::FAILURE;
                }
                $targetTypes = [$typeInput];
            }
        }

        // 2. Build Query with Filters
        $query = Report::query()->whereIn('type', $targetTypes);

        // Status Filter
        if ($this->option('failed-only')) {
            $query->whereIn('status', ['failed', 'retrying']);
        } elseif ($this->option('status')) {
            $statusInput = strtolower(trim((string) $this->option('status')));
            if ($statusInput !== 'all') {
                $query->where('status', $statusInput);
            }
        }

        // Network Filter
        if ($this->option('network')) {
            $query->where('network', strtoupper(trim((string) $this->option('network'))));
        }

        // Phone Filter
        if ($this->option('phone')) {
            $phone = trim((string) $this->option('phone'));
            $query->where('phone_number', 'like', "%{$phone}%");
        }

        // Date Range Filters
        if ($this->option('days')) {
            $days = (int) $this->option('days');
            $query->where('created_at', '<=', now()->subDays($days));
        }
        if ($this->option('from')) {
            $query->where('created_at', '>=', Carbon::parse($this->option('from'))->startOfDay());
        }
        if ($this->option('to')) {
            $query->where('created_at', '<=', Carbon::parse($this->option('to'))->endOfDay());
        }

        // 3. Count matching records
        $matchingCount = (clone $query)->count();

        if ($matchingCount === 0) {
            $this->warn('No matching data top-up history records found to clear.');
            return self::SUCCESS;
        }

        // 4. Summarize Matching Records
        $summary = (clone $query)
            ->select('type', 'status', DB::raw('count(*) as aggregate'))
            ->groupBy('type', 'status')
            ->get();

        $tableRows = [];
        foreach ($summary as $item) {
            $typeName = self::TRACKED_TYPES[$item->type] ?? $item->type;
            $tableRows[] = [
                $typeName . " (`{$item->type}`)",
                ucfirst($item->status),
                number_format($item->aggregate),
            ];
        }

        $this->info('Matching Data Top-Up History Records:');
        $this->table(['Top-Up Category / Type', 'Status', 'Record Count'], $tableRows);
        $this->line("Total records matching criteria: <fg=yellow;options=bold>" . number_format($matchingCount) . "</>");

        if ($dryRun) {
            $this->info('[DRY-RUN] Preview complete. No records were deleted.');
            return self::SUCCESS;
        }

        // 5. Confirmation Prompt
        if (!$force) {
            $confirmed = $this->confirm(
                "Are you sure you want to permanently clear " . number_format($matchingCount) . " data top-up history record(s)? This action cannot be undone.",
                false
            );

            if (!$confirmed) {
                $this->warn('Operation cancelled by user. No data top-ups were cleared.');
                return self::SUCCESS;
            }
        }

        // 6. Execution with Transaction & Foreign Key Safety
        $this->output->write('Clearing data top-up history... ');

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($query, $includeBonusGrants, &$deletedBonusGrantsCount) {
                if ($includeBonusGrants) {
                    $reportIds = (clone $query)->where('type', 'activation_bonus_data')->pluck('id');
                    if ($reportIds->isNotEmpty()) {
                        $deletedBonusGrantsCount = SimActivationBonusGrant::whereIn('report_id', $reportIds)->delete();
                    }
                }

                $query->delete();
            });

            Schema::enableForeignKeyConstraints();
            $this->line('<fg=green>DONE</>');

            Log::info("Data top-up history cleared via artisan command. Records deleted: {$matchingCount}.");

            $this->info("✓ Successfully cleared " . number_format($matchingCount) . " data top-up history record(s).");
            if (!empty($deletedBonusGrantsCount)) {
                $this->info("✓ Cleared " . number_format($deletedBonusGrantsCount) . " associated activation bonus grant record(s).");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            $this->line('<fg=red>FAILED</>');
            $this->error('Failed to clear data top-up history: ' . $e->getMessage());
            Log::error('ClearDataTopUpHistoryCommand error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return self::FAILURE;
        }
    }
}
