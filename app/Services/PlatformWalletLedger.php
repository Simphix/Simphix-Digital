<?php

namespace App\Services;

use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The single place every platform-wallet credit/debit goes through, so the
 * row-locking + ledger-writing shape isn't duplicated at every call site
 * (SIM activation, each revenue-generating service, cash-out requests, ...).
 * See docs/platform-wallets.md for how a new caller hooks in.
 */
class PlatformWalletLedger
{
    public function credit(
        string $walletType,
        string $walletName,
        float $amount,
        string $source,
        string $description,
        ?Model $related = null,
        array $metadata = []
    ): PlatformWalletTransaction {
        return DB::transaction(function () use ($walletType, $walletName, $amount, $source, $description, $related, $metadata) {
            PlatformWallet::firstOrCreate(['type' => $walletType], ['name' => $walletName, 'balance' => 0.00]);
            $wallet = PlatformWallet::where('type', $walletType)->lockForUpdate()->first();

            $oldBalance = $wallet->balance;
            $wallet->increment('balance', $amount);
            $wallet->increment('total_credited', $amount);
            $wallet->update(['last_activity' => now()]);

            return PlatformWalletTransaction::create([
                'platform_wallet_id' => $wallet->id,
                'transaction_ref' => 'PWLT-' . time() . '-' . rand(1000, 9999),
                'amount' => $amount,
                'type' => 'credit',
                'source' => $source,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
                'description' => $description,
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->balance,
                'performed_by' => 'System',
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Throws \RuntimeException if the wallet doesn't exist or has insufficient balance.
     */
    public function debit(
        string $walletType,
        float $amount,
        string $type,
        string $source,
        string $description,
        ?Model $related = null,
        array $metadata = []
    ): PlatformWalletTransaction {
        return DB::transaction(function () use ($walletType, $amount, $type, $source, $description, $related, $metadata) {
            $wallet = PlatformWallet::where('type', $walletType)->lockForUpdate()->first();
            if (!$wallet) {
                throw new \RuntimeException("Platform wallet [{$walletType}] does not exist.");
            }
            if ($wallet->balance < $amount) {
                throw new \RuntimeException('Insufficient platform wallet balance.');
            }

            $oldBalance = $wallet->balance;
            $wallet->decrement('balance', $amount);
            $wallet->increment('total_debited', $amount);
            $wallet->update(['last_activity' => now()]);

            return PlatformWalletTransaction::create([
                'platform_wallet_id' => $wallet->id,
                'transaction_ref' => 'PWLT-' . time() . '-' . rand(1000, 9999),
                'amount' => $amount,
                'type' => $type,
                'source' => $source,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
                'description' => $description,
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->balance,
                'performed_by' => 'System',
                'metadata' => $metadata,
            ]);
        });
    }
}
