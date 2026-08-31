<?php

namespace App\Services\Reconciliation;

use App\Models\PlatformWallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sanity check that total_credited - total_debited == balance on every
 * PlatformWallet row. Unlike the personal Wallet model (see
 * BalanceIntegrityReconciler), every PlatformWallet write funnels through
 * the single App\Services\PlatformWalletLedger service, which does keep
 * these in sync — so a hit here means a genuine bug (e.g. a direct DB write
 * bypassing the ledger), not routine drift from inconsistent call sites.
 */
class PlatformWalletReconciler implements Reconciler
{
    private const EPSILON = 0.01;

    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        foreach (PlatformWallet::all() as $wallet) {
            $expected = (float) $wallet->total_credited - (float) $wallet->total_debited;
            $drift = (float) $wallet->balance - $expected;

            if (abs($drift) > self::EPSILON) {
                $findings->push(new ReconciliationFinding(
                    category: 'platform_wallet',
                    severity: 'critical',
                    summary: "Platform wallet '{$wallet->type}' balance drift: stored {$wallet->balance}, expected {$expected} from totals",
                    details: [
                        'platform_wallet_id' => $wallet->id,
                        'type' => $wallet->type,
                        'stored_balance' => (float) $wallet->balance,
                        'total_credited' => (float) $wallet->total_credited,
                        'total_debited' => (float) $wallet->total_debited,
                        'expected_balance' => round($expected, 2),
                        'drift' => round($drift, 2),
                    ],
                    related_ids: [$wallet->id],
                ));
            }
        }

        return $findings;
    }
}
