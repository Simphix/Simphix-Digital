<?php

namespace App\Console\Commands;

use App\Models\CommissionEarning;
use App\Models\PlatformWalletTransaction;
use App\Models\Transaction;
use App\Models\Verification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                            {--include-commissions : Also clear commission earnings}
                            {--keep-verifications : Keep verification search records without deleting them}
                            {--all : Clear all transaction tables including platform wallet transactions, commission earnings, and verifications}';

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
        $includeCommissions = $this->option('include-commissions') || $this->option('all');
        $clearVerifications = ! $this->option('keep-verifications');

        $transactionCount = Transaction::query()->count();
        $verificationCount = $clearVerifications ? Verification::query()->count() : 0;
        $platformTxCount = $includePlatform ? PlatformWalletTransaction::query()->count() : 0;
        $commissionCount = $includeCommissions ? CommissionEarning::query()->count() : 0;

        $totalRecords = $transactionCount + $verificationCount + $platformTxCount + $commissionCount;

        if ($totalRecords === 0) {
            $this->warn('No transaction records found to clear.');
            return self::SUCCESS;
        }

        $tableRows = [
            ['User Transactions (`transactions`)', number_format($transactionCount)],
        ];

        if ($clearVerifications) {
            $tableRows[] = ['Linked Verifications (`verifications`)', number_format($verificationCount)];
        }
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

        // Disable foreign key constraints to prevent MySQL 1451 errors on legacy schemas
        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($includePlatform, $includeCommissions, $clearVerifications) {
                if ($clearVerifications) {
                    Verification::query()->delete();
                } else {
                    // Try to nullify transaction_id if the column supports it
                    try {
                        Verification::query()->update(['transaction_id' => null]);
                    } catch (\Throwable $e) {
                        // If column is NOT NULL, leave as-is since foreign key checks are disabled
                    }
                }

                Transaction::query()->delete();

                if ($includePlatform) {
                    PlatformWalletTransaction::query()->delete();
                }

                if ($includeCommissions) {
                    CommissionEarning::query()->delete();
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('DONE.');
        $this->info("Successfully cleared {$totalRecords} transaction record(s).");

        return self::SUCCESS;
    }
}
