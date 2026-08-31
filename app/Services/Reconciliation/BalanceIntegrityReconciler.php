<?php

namespace App\Services\Reconciliation;

use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Recomputes each wallet's expected balance/bonus directly from its
 * Transaction history and compares it to the stored balance/bonus columns.
 * Deliberately does NOT trust Wallet.total_credited/total_debited — confirmed
 * only 2 of ~10 money-moving flows in this app (deposit credit/levy,
 * AdminWalletController's manual adjustments) actually keep those running
 * totals in sync; every other flow (P2P, withdrawals, purchases, commission
 * credits) mutates balance/bonus directly and leaves them stale. Transaction
 * rows are the one reliably-written ledger every flow produces, so they're
 * the ground truth here instead.
 *
 * balance vs bonus classification (verified against every credit-producing
 * flow in this app):
 *   - balance is credited by: type=credit (except the bonus-flagged ones
 *     below), type=manual_credit, type=refund.
 *   - balance is debited by: type=debit, type=manual_debit.
 *   - bonus is credited by: type=credit rows that are either the SIM
 *     activation commission (metadata->type = activation_commission,
 *     double-JSON-encoded — see CommissionReconciler's docblock for why this
 *     is read via PHP json_decode, not a MySQL JSON path) or an
 *     AgentUpgrade/EnterprisePartner referral bonus (transaction_ref
 *     prefixed AGUP-/EPREF-).
 *   - bonus is "debited" only implicitly: WalletController::claimBonus()
 *     zeroes bonus and moves it to balance in one Transaction (type=credit,
 *     ref prefixed BONUS-) with no separate bonus-side row, so that amount
 *     is subtracted from the expected-bonus sum here.
 *
 * $from/$to here filter WHICH WALLETS to check (via wallet.last_activity),
 * not which transactions to sum — a correct running balance needs the
 * wallet's full history regardless of the requested window. This is a
 * deliberate, documented deviation from the Reconciler interface's literal
 * date-range-of-transactions semantics used by every other reconciler.
 */
class BalanceIntegrityReconciler implements Reconciler
{
    private const EPSILON = 0.01;

    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $wallets = Wallet::whereBetween('last_activity', [$from, $to])->get();

        foreach ($wallets as $wallet) {
            $transactions = Transaction::where('user_id', $wallet->user_id)
                ->where('status', 'completed')
                ->get();

            $expectedBalance = 0.0;
            $expectedBonus = 0.0;

            foreach ($transactions as $transaction) {
                $amount = (float) $transaction->amount;
                $ref = $transaction->transaction_ref;

                if ($transaction->type === 'debit' || $transaction->type === 'manual_debit') {
                    $expectedBalance -= $amount;
                    continue;
                }

                if ($transaction->type === 'manual_credit' || $transaction->type === 'refund') {
                    $expectedBalance += $amount;
                    continue;
                }

                if ($transaction->type === 'credit') {
                    if (str_starts_with($ref, 'BONUS-')) {
                        $expectedBalance += $amount;
                        $expectedBonus -= $amount;
                        continue;
                    }

                    if (str_starts_with($ref, 'AGUP-') || str_starts_with($ref, 'EPREF-')) {
                        $expectedBonus += $amount;
                        continue;
                    }

                    $metadata = $this->decodeMetadata($transaction->metadata);
                    if (($metadata['type'] ?? null) === 'activation_commission') {
                        $expectedBonus += $amount;
                        continue;
                    }

                    $expectedBalance += $amount;
                }
            }

            $balanceDrift = (float) $wallet->balance - $expectedBalance;
            $bonusDrift = (float) $wallet->bonus - $expectedBonus;

            if (abs($balanceDrift) > self::EPSILON) {
                $findings->push(new ReconciliationFinding(
                    category: 'balance_integrity',
                    severity: 'critical',
                    summary: "Wallet #{$wallet->id} (user #{$wallet->user_id}) balance drift: stored {$wallet->balance}, expected {$expectedBalance}",
                    details: ['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'stored_balance' => (float) $wallet->balance, 'expected_balance' => round($expectedBalance, 2), 'drift' => round($balanceDrift, 2)],
                    related_ids: [$wallet->id],
                ));
            }

            if (abs($bonusDrift) > self::EPSILON) {
                $findings->push(new ReconciliationFinding(
                    category: 'balance_integrity',
                    severity: 'critical',
                    summary: "Wallet #{$wallet->id} (user #{$wallet->user_id}) bonus drift: stored {$wallet->bonus}, expected {$expectedBonus}",
                    details: ['wallet_id' => $wallet->id, 'user_id' => $wallet->user_id, 'stored_bonus' => (float) $wallet->bonus, 'expected_bonus' => round($expectedBonus, 2), 'drift' => round($bonusDrift, 2)],
                    related_ids: [$wallet->id],
                ));
            }
        }

        return $findings;
    }

    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
