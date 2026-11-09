<?php

namespace App\Console\Commands;

use App\Models\EnterprisePartnerPayment;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearGeneratedAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:clear
                            {target? : Optional user ID, email, phone, or specific account number}
                            {--user= : Clear bank accounts for a specific user ID, email, or phone}
                            {--account= : Clear a specific bank account number}
                            {--provider= : Filter by provider (e.g. fintava, palmpay, monnify, paystack)}
                            {--bank= : Filter by bank name (e.g. Loma Bank, PalmPay, Wema Bank)}
                            {--all : Clear all generated bank accounts across the platform}
                            {--include-enterprise-payments : Also clear generated enterprise partner payment accounts}
                            {--force : Clear without confirmation prompt}
                            {--dry-run : Preview matching accounts without deleting}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = [
        'virtual-accounts:clear',
        'bank-accounts:clear',
        'accounts:clear-generated',
        'simphix:clear-accounts',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all or specific generated virtual bank accounts on the platform while preserving user KYC records';

    public function handle(): int
    {
        $targetArg = $this->argument('target') ? trim((string) $this->argument('target')) : null;
        $userOpt = $this->option('user') ? trim((string) $this->option('user')) : null;
        $accountOpt = $this->option('account') ? trim((string) $this->option('account')) : null;
        $providerFilter = $this->option('provider') ? strtolower(trim((string) $this->option('provider'))) : null;
        $bankFilter = $this->option('bank') ? trim((string) $this->option('bank')) : null;
        $isAll = (bool) $this->option('all');
        $includeEp = (bool) $this->option('include-enterprise-payments');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $targetUser = null;
        $targetAccountNumber = null;

        // Resolve target from argument or options
        if ($accountOpt) {
            $targetAccountNumber = $accountOpt;
        } elseif ($userOpt) {
            $targetUser = $this->resolveUser($userOpt);
            if (! $targetUser) {
                $this->error("User not found for identifier '{$userOpt}'.");
                return self::FAILURE;
            }
        } elseif ($targetArg) {
            // Determine if targetArg is an account number (10+ digits) or a user identifier
            if (ctype_digit($targetArg) && strlen($targetArg) >= 10 && VirtualAccount::where('account_number', $targetArg)->exists()) {
                $targetAccountNumber = $targetArg;
            } else {
                $targetUser = $this->resolveUser($targetArg);
                if (! $targetUser && VirtualAccount::where('account_number', $targetArg)->exists()) {
                    $targetAccountNumber = $targetArg;
                } elseif (! $targetUser) {
                    $this->error("Target '{$targetArg}' could not be resolved to a valid user or account number.");
                    return self::FAILURE;
                }
            }
        } elseif (! $isAll) {
            // Interactive Mode
            if (! $this->input->isInteractive()) {
                $this->error('Please specify --all, --user=, --account=, or provide a target argument.');
                return self::FAILURE;
            }

            $action = $this->choice(
                'How would you like to clear generated bank accounts?',
                [
                    'all'     => 'Clear ALL generated bank accounts across the platform',
                    'user'    => 'Clear bank accounts for a specific user (by ID, email, or phone)',
                    'account' => 'Clear a specific bank account number',
                    'cancel'  => 'Cancel operation',
                ],
                'cancel'
            );

            if ($action === 'cancel') {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            if ($action === 'all') {
                $isAll = true;
            } elseif ($action === 'user') {
                $userSearch = $this->ask('Enter user ID, email address, or phone number:');
                $targetUser = $this->resolveUser(trim((string) $userSearch));
                if (! $targetUser) {
                    $this->error("User not found for '{$userSearch}'.");
                    return self::FAILURE;
                }
            } elseif ($action === 'account') {
                $targetAccountNumber = trim((string) $this->ask('Enter the bank account number to clear:'));
            }
        }

        // Build VirtualAccount query
        $vaQuery = VirtualAccount::query();

        if ($targetUser) {
            $vaQuery->where('user_id', $targetUser->id);
        }

        if ($targetAccountNumber) {
            $vaQuery->where('account_number', $targetAccountNumber);
        }

        if ($providerFilter) {
            $vaQuery->where('provider', $providerFilter);
        }

        if ($bankFilter) {
            $vaQuery->where('bank_name', 'like', "%{$bankFilter}%");
        }

        $vaIds = (clone $vaQuery)->pluck('id');
        $vaCount = $vaIds->count();

        // Build EnterprisePartnerPayment query if requested or if single account/user match
        $epQuery = EnterprisePartnerPayment::query();
        if ($includeEp || $targetAccountNumber || $targetUser) {
            if ($targetUser) {
                $epQuery->where('user_id', $targetUser->id);
            }
            if ($targetAccountNumber) {
                $epQuery->where('account_number', $targetAccountNumber);
            }
            if ($bankFilter) {
                $epQuery->where('bank_name', 'like', "%{$bankFilter}%");
            }
        } else {
            $epQuery->whereRaw('1 = 0');
        }

        $epIds = (clone $epQuery)->pluck('id');
        $epCount = $epIds->count();

        $totalAccounts = $vaCount + $epCount;

        if ($totalAccounts === 0) {
            $this->newLine();
            $this->warn('No generated bank accounts found matching the specified criteria.');
            return self::SUCCESS;
        }

        // Inspect targets
        $affectedUserIds = (clone $vaQuery)->pluck('user_id')->merge((clone $epQuery)->pluck('user_id'))->filter()->unique();

        $this->newLine();
        $this->line('<fg=cyan;options=bold>Generated Bank Accounts Targeted for Clearing:</>');

        $summaryTable = [
            ['Virtual Accounts (Wallet Funding)', number_format($vaCount)],
        ];

        if ($epCount > 0 || $includeEp) {
            $summaryTable[] = ['Enterprise Partner Payment Accounts', number_format($epCount)];
        }

        $summaryTable[] = ['Total Generated Accounts', number_format($totalAccounts)];
        $summaryTable[] = ['Affected User Profiles', number_format($affectedUserIds->count())];

        $this->table(['Category', 'Count'], $summaryTable);

        // Preview sample of matching accounts
        $previewAccounts = (clone $vaQuery)->with('user')->latest()->take(5)->get();
        if ($previewAccounts->isNotEmpty()) {
            $previewRows = $previewAccounts->map(function ($va) {
                $userName = $va->user ? ($va->user->name ?: trim(($va->user->first_name ?? '') . ' ' . ($va->user->last_name ?? ''))) : 'Unlinked';
                return [
                    $va->id,
                    $userName,
                    $va->account_number,
                    $va->bank_name,
                    $va->provider,
                    $va->is_active ? 'Active' : 'Inactive',
                    $va->created_at ? $va->created_at->format('Y-m-d H:i') : '-',
                ];
            })->all();

            $this->line('<fg=yellow;options=bold>Sample Accounts Preview (Up to 5):</>');
            $this->table(['ID', 'User', 'Account Number', 'Bank Name', 'Provider', 'Status', 'Created At'], $previewRows);
        }

        // Target Context
        if ($targetUser) {
            $displayName = $targetUser->name ?: trim(($targetUser->first_name ?? '') . ' ' . ($targetUser->last_name ?? ''));
            $this->line("Targeted User: <fg=green;options=bold>{$displayName}</> (ID #{$targetUser->id}, {$targetUser->email})");
        } elseif ($targetAccountNumber) {
            $this->line("Targeted Account Number: <fg=green;options=bold>{$targetAccountNumber}</>");
        } else {
            $this->line("Scope: <fg=yellow;options=bold>ALL Users across the platform</>");
        }

        // Dry Run
        if ($isDryRun) {
            $this->newLine();
            $this->info("[Dry Run] No bank accounts were deleted. {$totalAccounts} account(s) match the criteria.");
            $this->line('<fg=green>✓ User KYC records (BVN, NIN, account_tier, addresses) remain intact.</>');
            return self::SUCCESS;
        }

        // Confirmation Prompt
        if (! $isForce) {
            $prompt = $targetUser
                ? "Are you sure you want to clear {$totalAccounts} generated bank account(s) for user '{$targetUser->email}'? User KYC status will NOT be affected."
                : ($targetAccountNumber
                    ? "Are you sure you want to clear generated bank account '{$targetAccountNumber}'? User KYC status will NOT be affected."
                    : "Are you sure you want to permanently clear ALL {$totalAccounts} generated bank account(s) across the platform? User KYC status will NOT be affected.");

            $confirmed = $this->confirm($prompt, false);

            if (! $confirmed) {
                $this->warn('Operation cancelled by user. No bank accounts were deleted.');
                return self::SUCCESS;
            }
        }

        $this->output->write('Clearing generated bank accounts... ');

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($vaIds, $epIds) {
                if ($vaIds->isNotEmpty()) {
                    VirtualAccount::whereIn('id', $vaIds)->delete();
                }
                if ($epIds->isNotEmpty()) {
                    EnterprisePartnerPayment::whereIn('id', $epIds)->delete();
                }
            });
            Schema::enableForeignKeyConstraints();
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            $this->output->writeln('<fg=red>FAILED</>');
            $this->error('Failed to clear bank accounts: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->output->writeln('<fg=green>DONE</>');
        $this->newLine();
        $this->info("Successfully cleared {$totalAccounts} generated bank account record(s).");
        $this->line('<fg=green;options=bold>✓ User KYC records (BVN, NIN, Tier level, identity details) are completely intact and preserved.</>');
        $this->line('<fg=cyan>✓ Users can re-generate new virtual bank accounts seamlessly at any time using their saved KYC profile.</>');

        return self::SUCCESS;
    }

    /**
     * Resolve user by ID, email, or phone number.
     */
    protected function resolveUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (is_numeric($identifier) && strlen($identifier) <= 9) {
            $user = User::find((int) $identifier);
            if ($user) {
                return $user;
            }
        }

        return User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('bvn', $identifier)
            ->orWhere('nin', $identifier)
            ->first();
    }
}
