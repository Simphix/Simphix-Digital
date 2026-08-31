<?php

namespace App\Services\Reconciliation;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Thin registry so callers (the admin controller, tests) don't hardcode
 * every reconciler class name — one lookup table from a stable tab/query key
 * to its implementing class.
 */
class ReconciliationEngine
{
    /** @var array<string, class-string<Reconciler>> */
    public const RECONCILERS = [
        'p2p' => P2pReconciler::class,
        'deposits' => DepositReconciler::class,
        'cashouts' => CashoutReconciler::class,
        'commissions' => CommissionReconciler::class,
        'purchases' => PurchaseReconciler::class,
        'sim_bonus' => SimBonusReconciler::class,
        'balance_integrity' => BalanceIntegrityReconciler::class,
        'platform_wallet' => PlatformWalletReconciler::class,
    ];

    /**
     * @return Collection<int, ReconciliationFinding>
     */
    public function run(string $key, Carbon $from, Carbon $to, array $extra = []): Collection
    {
        return app()->make(self::RECONCILERS[$key], $extra)->findings($from, $to);
    }
}
