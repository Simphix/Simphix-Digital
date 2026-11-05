<?php

namespace App\Console\Commands;

use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ClearUserBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:clear
                            {--force : Clear without confirmation prompt}
                            {--main-only : Clear only main wallet balance (leave commission intact)}
                            {--commission-only : Clear only commission/bonus wallet balance (leave main intact)}
                            {--role= : Clear balances only for users with a specific role}
                            {--include-platform-wallets : Also reset platform revenue/operating wallets to 0.00}
                            {--record-transactions : Log a debit transaction for each user balance cleared}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'wallets:clear-balances',
        'users:clear-balances',
        'simphix:clear-balances',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear (debit to 0.00) user wallet balances (main, commission/bonus, hold) across the platform';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $mainOnly = (bool) $this->option('main-only');
        $commissionOnly = (bool) $this->option('commission-only');
        $roleFilter = $this->option('role') ? strtolower(trim((string) $this->option('role'))) : null;
        $includePlatformWallets = (bool) $this->option('include-platform-wallets');
        $recordTransactions = (bool) $this->option('record-transactions');

        if ($mainOnly && $commissionOnly) {
            $this->error('You cannot specify both --main-only and --commission-only simultaneously.');
            return self::FAILURE;
        }

        $hasLienColumn = Schema::hasColumn('wallets', 'lien_amount');

        // Build wallet query for users with positive balances
        $walletQuery = Wallet::query()->with('user');

        if ($roleFilter) {
            $walletQuery->whereHas('user', function ($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            });
        }

        if ($mainOnly) {
            $walletQuery->where('balance', '>', 0);
        } elseif ($commissionOnly) {
            $walletQuery->where('bonus', '>', 0);
        } else {
            $walletQuery->where(function ($q) use ($hasLienColumn) {
                $q->where('balance', '>', 0)
                    ->orWhere('bonus', '>', 0)
                    ->orWhere('hold_amount', '>', 0);

                if ($hasLienColumn) {
                    $q->orWhere('lien_amount', '>', 0);
                }
            });
        }

        $totalWalletsCount = (clone $walletQuery)->count();

        // Calculate sums to clear
        $sumMain = 0.00;
        $sumCommission = 0.00;
        $sumHold = 0.00;

        (clone $walletQuery)->select(['balance', 'bonus', 'hold_amount'])
            ->chunk(1000, function ($chunk) use (&$sumMain, &$sumCommission, &$sumHold) {
                foreach ($chunk as $w) {
                    $sumMain += (float) $w->balance;
                    $sumCommission += (float) $w->bonus;
                    $sumHold += (float) $w->hold_amount;
                }
            });

        $grandTotalUserFunds = 0.00;
        if ($mainOnly) {
            $grandTotalUserFunds = $sumMain;
        } elseif ($commissionOnly) {
            $grandTotalUserFunds = $sumCommission;
        } else {
            $grandTotalUserFunds = $sumMain + $sumCommission;
        }

        $platformWalletsBalance = 0.00;
        $platformWalletsCount = 0;
        if ($includePlatformWallets) {
            $platformWalletsCount = PlatformWallet::where('balance', '>', 0)->count();
            $platformWalletsBalance = (float) PlatformWallet::where('balance', '>', 0)->sum('balance');
        }

        if ($totalWalletsCount === 0 && (! $includePlatformWallets || $platformWalletsCount === 0)) {
            $this->warn('No non-zero balances found matching the specified criteria.');
            return self::SUCCESS;
        }

        // Display summary table
        $summaryRows = [
            ['Affected User Wallets', number_format($totalWalletsCount)],
        ];

        if (! $commissionOnly) {
            $summaryRows[] = ['Total Main Balances to Clear', '₦' . number_format($sumMain, 2)];
        }
        if (! $mainOnly) {
            $summaryRows[] = ['Total Commission Balances to Clear', '₦' . number_format($sumCommission, 2)];
        }
        if (! $mainOnly && ! $commissionOnly && $sumHold > 0) {
            $summaryRows[] = ['Total Hold/Lien Balances to Reset', '₦' . number_format($sumHold, 2)];
        }

        $summaryRows[] = ['Total User Funds to Clear', '₦' . number_format($grandTotalUserFunds, 2)];

        if ($includePlatformWallets) {
            $summaryRows[] = ['Platform Wallets with Balance', number_format($platformWalletsCount)];
            $summaryRows[] = ['Platform Balances to Clear', '₦' . number_format($platformWalletsBalance, 2)];
            $summaryRows[] = ['Overall Grand Total (User + Platform)', '₦' . number_format($grandTotalUserFunds + $platformWalletsBalance, 2)];
        }

        if ($roleFilter) {
            $summaryRows[] = ['Targeted User Role', ucfirst($roleFilter)];
        }

        $this->table(['Balance Clearance Metric', 'Value'], $summaryRows);

        // Confirmation prompt
        if (! $force) {
            $confirmPrompt = sprintf(
                'Are you sure you want to permanently clear balances for %s user wallet(s) totaling ₦%s? This action cannot be undone.',
                number_format($totalWalletsCount),
                number_format($grandTotalUserFunds, 2)
            );

            if ($includePlatformWallets && $platformWalletsCount > 0) {
                $confirmPrompt .= sprintf(
                    ' (Platform wallets totaling ₦%s will also be reset.)',
                    number_format($platformWalletsBalance, 2)
                );
            }

            if (! $this->confirm($confirmPrompt, false)) {
                $this->warn('Operation cancelled by user. No balances were cleared.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing user balances... ');

        $clearedWallets = 0;
        $clearedUserAmount = 0.00;

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use (
                $walletQuery,
                $mainOnly,
                $commissionOnly,
                $hasLienColumn,
                $recordTransactions,
                $includePlatformWallets,
                &$clearedWallets,
                &$clearedUserAmount
            ) {
                $walletQuery->chunk(500, function ($wallets) use (
                    $mainOnly,
                    $commissionOnly,
                    $hasLienColumn,
                    $recordTransactions,
                    &$clearedWallets,
                    &$clearedUserAmount
                ) {
                    $now = now();

                    foreach ($wallets as $wallet) {
                        $mainToDebit = $mainOnly || (! $commissionOnly) ? (float) $wallet->balance : 0.00;
                        $commToDebit = $commissionOnly || (! $mainOnly) ? (float) $wallet->bonus : 0.00;
                        $totalDebit = $mainToDebit + $commToDebit;

                        $updates = ['last_activity' => $now];

                        if (! $commissionOnly) {
                            $updates['balance'] = 0.00;
                            $updates['hold_amount'] = 0.00;
                            if ($hasLienColumn) {
                                $updates['lien_amount'] = 0.00;
                            }
                        }

                        if (! $mainOnly) {
                            $updates['bonus'] = 0.00;
                        }

                        if ($recordTransactions && $totalDebit > 0) {
                            $updates['total_debited'] = (float) $wallet->total_debited + $totalDebit;

                            Transaction::create([
                                'transaction_ref' => 'CLR-' . strtoupper(Str::random(12)),
                                'user_id' => $wallet->user_id,
                                'amount' => $totalDebit,
                                'fee' => 0.00,
                                'net_amount' => $totalDebit,
                                'type' => 'debit',
                                'status' => 'successful',
                                'description' => 'System balance clearance (Main: ₦' . number_format($mainToDebit, 2) . ', Commission: ₦' . number_format($commToDebit, 2) . ')',
                                'performed_by' => null,
                                'metadata' => [
                                    'cleared_main' => $mainToDebit,
                                    'cleared_bonus' => $commToDebit,
                                    'cleared_at' => $now->toIso8601String(),
                                ],
                            ]);
                        }

                        $wallet->forceFill($updates)->save();
                        $clearedWallets++;
                        $clearedUserAmount += $totalDebit;
                    }
                });

                if ($includePlatformWallets) {
                    PlatformWallet::query()->update([
                        'balance' => 0.00,
                    ]);
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->newLine();
        $this->info("Successfully cleared balances for {$clearedWallets} user wallet(s) (totaling ₦" . number_format($clearedUserAmount, 2) . ").");

        if ($includePlatformWallets) {
            $this->info("Platform operating/revenue wallets were also reset to ₦0.00.");
        }

        return self::SUCCESS;
    }
}
