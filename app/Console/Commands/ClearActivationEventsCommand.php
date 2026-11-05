<?php

namespace App\Console\Commands;

use App\Models\CommissionEarning;
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
                            {--all-history : Clear all SIM history logs, not only activation-specific events}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'sims:clear-activation-events',
        'sim:clear-activation-events',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all SIM activation events, bonus grants, and activation history logs from the database';

    public function handle(): int
    {
        $allHistory = (bool) $this->option('all-history');

        $historyQuery = SimHistory::query();
        if (! $allHistory) {
            $historyQuery->where(function ($q) {
                $q->where('to_status', SimStatus::ACTIVATED)
                    ->orWhere('note', 'like', '%activat%');
            });
        }

        $historyCount = (clone $historyQuery)->count();
        $bonusGrantsCount = SimActivationBonusGrant::query()->count();
        $activationCommissionsCount = CommissionEarning::query()->count();

        $totalRecords = $historyCount + $bonusGrantsCount + $activationCommissionsCount;

        if ($totalRecords === 0) {
            $this->warn('No activation event records found to clear.');
            return self::SUCCESS;
        }

        $this->table(
            ['Activation Event Type', 'Record Count'],
            [
                [$allHistory ? 'All SIM History Logs' : 'Activation History Logs (`sim_histories`)', number_format($historyCount)],
                ['SIM Activation Bonus Grants (`sim_activation_bonus_grants`)', number_format($bonusGrantsCount)],
                ['SIM Activation Commission Earnings (`commission_earnings`)', number_format($activationCommissionsCount)],
            ]
        );

        $this->line("Total activation events found: <fg=yellow;options=bold>{$totalRecords}</>");

        if (! $this->option('force')) {
            $label = $allHistory ? 'all SIM history logs and activation events' : 'all activation events and bonus grant ledgers';
            $confirmed = $this->confirm(
                "Are you sure you want to permanently delete {$totalRecords} {$label}? This action cannot be undone.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No records were deleted.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing activation events and ledgers... ');

        DB::transaction(function () use ($historyQuery) {
            $historyQuery->delete();
            SimActivationBonusGrant::query()->delete();
            CommissionEarning::query()->delete();
        });

        $this->info('DONE.');
        $this->info("Successfully cleared {$totalRecords} activation event record(s).");

        return self::SUCCESS;
    }
}
