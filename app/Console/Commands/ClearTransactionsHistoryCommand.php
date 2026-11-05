<?php

namespace App\Console\Commands;

use App\Models\CommissionEarning;
use App\Models\PlatformWalletTransaction;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTransactionsHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:clear-history
                            {--force : Clear without confirmation prompt}
                            {--include-platform : Also clear platform wallet transactions}
                            {--all : Clear all transaction tables including platform wallet transactions and commission earnings}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'transactions:clear',
        'simphix:clear-transactions',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all transaction history records from the database';

    public function handle(): int
    {
        $includePlatform = $this->option('include-platform') || $this->option('all');
        $includeCommissions = (bool) $this->option('all');

        $transactionCount = Transaction::query()->count();
        $platformTxCount = $includePlatform ? PlatformWalletTransaction::query()->count() : 0;
        $commissionCount = $includeCommissions ? CommissionEarning::query()->count() : 0;

        $totalRecords = $transactionCount + $platformTxCount + $commissionCount;

        if ($totalRecords === 0) {
            $this->warn('No transaction records found to clear.');
            return self::SUCCESS;
        }

        $tableRows = [
            ['User Transactions (`transactions`)', number_format($transactionCount)],
        ];

        if ($includePlatform) {
            $tableRows[] = ['Platform Wallet Transactions (`platform_wallet_transactions`)', number_format($platformTxCount)];
        }
        if ($includeCommissions) {
            $tableRows[] = ['Commission Earnings (`commission_earnings`)', number_format($commissionCount)];
        }

        $this->table(['Transaction Table', 'Record Count'], $tableRows);
        $this->line("Total records to clear: <fg=yellow;options=bold>{$totalRecords}</>");

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to permanently clear all {$totalRecords} transaction record(s)? This action cannot be undone.",
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No transactions were cleared.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing transaction history... ');

        DB::transaction(function () use ($includePlatform, $includeCommissions) {
            Transaction::query()->delete();

            if ($includePlatform) {
                PlatformWalletTransaction::query()->delete();
            }

            if ($includeCommissions) {
                CommissionEarning::query()->delete();
            }
        });

        $this->info('DONE.');
        $this->info("Successfully cleared {$totalRecords} transaction record(s).");

        return self::SUCCESS;
    }
}
