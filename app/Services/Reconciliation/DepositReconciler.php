<?php

namespace App\Services\Reconciliation;

use App\Models\Transaction;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Matches inbound gateway deposit webhooks to the Transaction they should
 * have produced. PaymentWebhookController logs every inbound call to
 * WebhookLog before touching anything else; on a matched payin, the
 * Transaction's transaction_ref is set to the gateway's own order number
 * (merchantOrderNo/orderNo) — that's the reconciliation key against the
 * external system. Deposits deliberately never write a Report row (confirmed
 * — the one flow in this app that breaks the otherwise-universal
 * Transaction+Report pairing), so this reconciler only ever checks Transaction.
 */
class DepositReconciler implements Reconciler
{
    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $successfulPayins = WebhookLog::where('status', 'success')
            ->where('event_type', 'payin')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        foreach ($successfulPayins as $log) {
            $orderNo = $log->payload['merchantOrderNo'] ?? $log->payload['orderNo'] ?? null;

            if (!$orderNo) {
                $findings->push(new ReconciliationFinding(
                    category: 'deposit',
                    severity: 'warning',
                    summary: "Successful payin webhook #{$log->id} has no extractable order number",
                    details: ['webhook_log_id' => $log->id],
                    related_ids: [$log->id],
                ));

                continue;
            }

            $transaction = Transaction::where('transaction_ref', $orderNo)->where('status', 'completed')->first();

            if (!$transaction) {
                $findings->push(new ReconciliationFinding(
                    category: 'deposit',
                    severity: 'critical',
                    summary: "Payin webhook #{$log->id} (order {$orderNo}) succeeded but no completed Transaction was found — money may have been received without being credited",
                    details: ['webhook_log_id' => $log->id, 'order_no' => $orderNo],
                    related_ids: [$log->id],
                ));
            }
        }

        return $findings;
    }
}
