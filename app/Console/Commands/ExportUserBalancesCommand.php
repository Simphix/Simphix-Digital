<?php

namespace App\Console\Commands;

use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportUserBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:export-balances
                            {--output= : Custom destination path for the exported CSV file}
                            {--role= : Filter users by role (e.g. partner, coordinator, business, personal)}
                            {--with-balance-only : Only export users who have a non-zero balance}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'user:export-balances',
        'balances:export',
        'simphix:export-balances',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all users and their balances (main balance, commission balance, hold balance) to a CSV file';

    public function handle(): int
    {
        $roleFilter = $this->option('role') ? strtolower(trim((string) $this->option('role'))) : null;
        $withBalanceOnly = (bool) $this->option('with-balance-only');
        $hasLienColumn = Schema::hasColumn('wallets', 'lien_amount');

        $this->info('Gathering user wallet balances across SimPhix platform...');

        $query = User::query()->with('wallet');

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($withBalanceOnly) {
            $query->whereHas('wallet', function ($q) {
                $q->where('balance', '>', 0)
                    ->orWhere('bonus', '>', 0)
                    ->orWhere('hold_amount', '>', 0);
            });
        }

        $totalUsers = (clone $query)->count();

        if ($totalUsers === 0) {
            $this->warn('No users found matching the given criteria.');
            return self::SUCCESS;
        }

        // Determine output file path
        $outputPath = $this->option('output');
        if ($outputPath) {
            $outputPath = trim((string) $outputPath, "'\"");
            $dir = dirname($outputPath);
            File::ensureDirectoryExists($dir);
        } else {
            $exportDir = storage_path('app/exports');
            File::ensureDirectoryExists($exportDir);
            $timestamp = now()->format('Y-m-d_His');
            $outputPath = "{$exportDir}/user_balances_{$timestamp}.csv";
        }

        $fileHandle = fopen($outputPath, 'w');
        if ($fileHandle === false) {
            $this->error("Failed to open output file for writing: {$outputPath}");
            return self::FAILURE;
        }

        // UTF-8 BOM for Excel compatibility
        fprintf($fileHandle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // CSV Header
        fputcsv($fileHandle, [
            'User ID',
            'Full Name',
            'Email',
            'Phone',
            'Role',
            'Wallet Number',
            'Main Balance (NGN)',
            'Commission Balance (NGN)',
            'Hold Balance (NGN)',
            'Lien Balance (NGN)',
            'Total User Balance (NGN)',
            'Total Credited (NGN)',
            'Total Debited (NGN)',
            'Wallet Status',
            'Last Activity',
        ]);

        $sumMain = 0.00;
        $sumCommission = 0.00;
        $sumHold = 0.00;
        $sumTotal = 0.00;
        $rowCount = 0;

        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        $query->chunk(500, function ($users) use ($fileHandle, $hasLienColumn, &$sumMain, &$sumCommission, &$sumHold, &$sumTotal, &$rowCount, $bar) {
            foreach ($users as $user) {
                $wallet = $user->wallet;

                $mainBal = $wallet ? (float) $wallet->balance : 0.00;
                $commBal = $wallet ? (float) $wallet->bonus : 0.00;
                $holdBal = $wallet ? (float) $wallet->hold_amount : 0.00;
                $lienBal = ($wallet && $hasLienColumn) ? (float) ($wallet->lien_amount ?? 0) : 0.00;
                $userTotal = $mainBal + $commBal;

                $sumMain += $mainBal;
                $sumCommission += $commBal;
                $sumHold += ($holdBal + $lienBal);
                $sumTotal += $userTotal;
                $rowCount++;

                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                if (empty($fullName)) {
                    $fullName = $user->name ?? 'N/A';
                }

                fputcsv($fileHandle, [
                    $user->id,
                    $fullName,
                    $user->email ?? 'N/A',
                    $user->phone ?? ($user->phone_number ?? 'N/A'),
                    ucwords(str_replace('_', ' ', $user->role ?? 'user')),
                    $wallet->wallet_number ?? 'N/A',
                    number_format($mainBal, 2, '.', ''),
                    number_format($commBal, 2, '.', ''),
                    number_format($holdBal, 2, '.', ''),
                    number_format($lienBal, 2, '.', ''),
                    number_format($userTotal, 2, '.', ''),
                    number_format($wallet ? (float) $wallet->total_credited : 0.00, 2, '.', ''),
                    number_format($wallet ? (float) $wallet->total_debited : 0.00, 2, '.', ''),
                    ucfirst($wallet->status ?? 'active'),
                    $wallet?->last_activity?->toDateTimeString() ?? 'N/A',
                ]);

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        fclose($fileHandle);

        // Display user balances summary
        $this->table(
            ['User Balance Metric', 'Amount (NGN)'],
            [
                ['Total Users Exported', number_format($rowCount)],
                ['Total Main Balance (`wallet.balance`)', '₦' . number_format($sumMain, 2)],
                ['Total Commission Balance (`wallet.bonus`)', '₦' . number_format($sumCommission, 2)],
                ['Total Hold & Lien Balances', '₦' . number_format($sumHold, 2)],
                ['Grand Total Active User Funds', '₦' . number_format($sumTotal, 2)],
            ]
        );

        // Also display Platform Wallets for full overview
        $platformWallets = PlatformWallet::all();
        if ($platformWallets->isNotEmpty()) {
            $this->newLine();
            $this->line('<fg=cyan;options=bold>Platform Revenue / Operating Wallets:</>');
            $pwRows = $platformWallets->map(function ($pw) {
                return [
                    $pw->name ?? ucwords(str_replace('_', ' ', $pw->type)),
                    $pw->type,
                    '₦' . number_format($pw->balance, 2),
                    '₦' . number_format($pw->total_credited, 2),
                ];
            })->all();

            $this->table(['Platform Wallet', 'Type', 'Current Balance', 'Total Credited'], $pwRows);
        }

        $this->info("Export completed successfully!");
        $this->line("CSV file saved to: <fg=green;options=bold>{$outputPath}</>");

        return self::SUCCESS;
    }
}
