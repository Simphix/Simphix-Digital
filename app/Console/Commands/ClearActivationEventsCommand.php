<?php

namespace App\Console\Commands;

use App\Models\CommissionEarning;
use App\Models\Report;
use App\Models\SimActivationBonusGrant;
use App\Models\SimHistory;
use App\Support\SimStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearActivationEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activation-events:clear
                            {--force : Clear without confirmation prompt}
                            {--dry-run : Preview matching record counts without deleting}
                            {--all-history : Clear all SIM history logs, not only activation-specific events}
                            {--keep-histories : Preserve SIM history logs}
                            {--bonuses= : Filter activation bonus records: "all", "failed", or "success" (default: all)}
                            {--failed-bonuses : Clear only failed/retrying activation bonus reports}
                            {--success-bonuses : Clear only successful/completed activation bonus reports and grants}
                            {--all-bonuses : Clear all activation bonus reports and bonus grants}
                            {--bonuses-only : Clear only activation bonus records/reports without deleting SIM history or commission earnings}
                            {--keep-bonuses : Preserve all activation bonus records and reports}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'sims:clear-activation-events',
        'sim:clear-activation-events',
        'sims:clear-events',
        'simphix:clear-activation-events',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all SIM activation events, failed/success bonus reports, bonus grants, and activation history logs';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $allHistory = (bool) $this->option('all-history');
        $keepHistories = (bool) $this->option('keep-histories');
        $bonusesOnly = (bool) $this->option('bonuses-only');
        $keepBonuses = (bool) $this->option('keep-bonuses');
        $failedBonuses = (bool) $this->option('failed-bonuses');
        $successBonuses = (bool) $this->option('success-bonuses');
        $allBonuses = (bool) $this->option('all-bonuses');
        $bonusOption = strtolower((string) ($this->option('bonuses') ?? ''));

        // Determine bonus clearing mode: 'all', 'failed', 'success', or 'none'
        $bonusMode = 'all';
        if ($keepBonuses) {
            $bonusMode = 'none';
        } elseif ($failedBonuses || $bonusOption === 'failed') {
            $bonusMode = 'failed';
        } elseif ($successBonuses || $bonusOption === 'success') {
            $bonusMode = 'success';
        } elseif ($allBonuses || $bonusOption === 'all' || $bonusOption === '') {
            $bonusMode = 'all';
        }

        // 1. SIM Histories
        $historyQuery = null;
        $historyCount = 0;
        if (! $bonusesOnly && ! $keepHistories) {
            $historyQuery = SimHistory::query();
            if (! $allHistory) {
                $historyQuery->where(function ($q) {
                    $q->where('to_status', SimStatus::ACTIVATED)
                        ->orWhere('note', 'like', '%activat%');
                });
            }
            $historyCount = (clone $historyQuery)->count();
        }

        // 2. Commission Earnings
        $commissionsQuery = null;
        $activationCommissionsCount = 0;
        if (! $bonusesOnly) {
            $commissionsQuery = CommissionEarning::query();
            $activationCommissionsCount = (clone $commissionsQuery)->count();
        }

        // 3. Activation Bonus Grants (`sim_activation_bonus_grants`) - represents awarded bonuses
        $bonusGrantsQuery = null;
        $bonusGrantsCount = 0;
        if ($bonusMode === 'all' || $bonusMode === 'success') {
            $bonusGrantsQuery = SimActivationBonusGrant::query();
            $bonusGrantsCount = (clone $bonusGrantsQuery)->count();
        }

        // 4. Failed Activation Bonus Reports (`reports` where type='activation_bonus_data' and status in failed, retrying)
        $failedBonusReportsQuery = null;
        $failedBonusReportsCount = 0;
        if ($bonusMode === 'all' || $bonusMode === 'failed') {
            $failedBonusReportsQuery = Report::where('type', 'activation_bonus_data')
                ->whereIn('status', ['failed', 'retrying']);
            $failedBonusReportsCount = (clone $failedBonusReportsQuery)->count();
        }

        // 5. Successful Activation Bonus Reports (`reports` where type='activation_bonus_data' and status in completed, resolved)
        $successBonusReportsQuery = null;
        $successBonusReportsCount = 0;
        if ($bonusMode === 'all' || $bonusMode === 'success') {
            $successBonusReportsQuery = Report::where('type', 'activation_bonus_data')
                ->whereIn('status', ['completed', 'resolved']);
            $successBonusReportsCount = (clone $successBonusReportsQuery)->count();
        }

        $totalRecords = $historyCount + $activationCommissionsCount + $bonusGrantsCount + $failedBonusReportsCount + $successBonusReportsCount;

        if ($totalRecords === 0) {
            $this->warn('No activation event or bonus records found matching the specified criteria.');
            return self::SUCCESS;
        }

        $tableRows = [];
        if (! $bonusesOnly && ! $keepHistories) {
            $tableRows[] = [$allHistory ? 'All SIM History Logs' : 'Activation History Logs (`sim_histories`)', number_format($historyCount)];
        }
        if (! $bonusesOnly) {
            $tableRows[] = ['SIM Activation Commission Earnings (`commission_earnings`)', number_format($activationCommissionsCount)];
        }
        if ($bonusMode === 'all' || $bonusMode === 'success') {
            $tableRows[] = ['SIM Activation Bonus Grants (`sim_activation_bonus_grants`)', number_format($bonusGrantsCount)];
            $tableRows[] = ['Successful Bonus Reports (`reports` - completed/resolved)', number_format($successBonusReportsCount)];
        }
        if ($bonusMode === 'all' || $bonusMode === 'failed') {
            $tableRows[] = ['Failed Bonus Reports (`reports` - failed/retrying)', number_format($failedBonusReportsCount)];
        }

        $this->table(['Activation Record Type', 'Record Count'], $tableRows);
        $this->line("Total records found: <fg=yellow;options=bold>{$totalRecords}</>");

        if ($dryRun) {
            $this->warn("[DRY RUN] No records were deleted. {$totalRecords} record(s) would be cleared.");
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $desc = match ($bonusMode) {
                'failed' => 'failed activation bonus reports',
                'success' => 'successful activation bonus records and grants',
                default => 'activation events, history logs, and activation bonus records',
            };
            if ($bonusesOnly) {
                $desc = "activation bonus records ({$bonusMode})";
            }

            $confirmed = $this->confirm(
                "Are you sure you want to permanently delete {$totalRecords} {$desc}? This action cannot be undone.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No records were deleted.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing activation events and bonus ledgers... ');

        DB::transaction(function () use (
            $historyQuery,
            $commissionsQuery,
            $bonusGrantsQuery,
            $failedBonusReportsQuery,
            $successBonusReportsQuery
        ) {
            if ($historyQuery) {
                $historyQuery->delete();
            }
            if ($commissionsQuery) {
                $commissionsQuery->delete();
            }
            if ($bonusGrantsQuery) {
                $bonusGrantsQuery->delete();
            }
            if ($failedBonusReportsQuery) {
                $failedBonusReportsQuery->delete();
            }
            if ($successBonusReportsQuery) {
                $successBonusReportsQuery->delete();
            }
        });

        $this->info('DONE.');
        $this->info("Successfully cleared {$totalRecords} activation record(s).");

        return self::SUCCESS;
    }
}
