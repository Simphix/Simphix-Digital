<?php

namespace App\Services\Reconciliation;

use App\Models\Report;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Generic pairing-integrity check across every purchase-type Report: for
 * each Report of one of these types, a matching Transaction (same
 * ref/transaction_ref, same amount — Report.amount is always the gross
 * pre-discount amount, matching Transaction.amount, never net_amount) should
 * exist, and their statuses should agree. Reused across airtime, SME data,
 * and SIM purchase/activation instead of one bespoke reconciler per purchase
 * type, since they all follow the identical Transaction+Report pairing
 * pattern (confirmed by reading each controller).
 */
class PurchaseReconciler implements Reconciler
{
    private const PURCHASE_REPORT_TYPES = ['airtime', 'data', 'sim_purchase_request', 'sim_activation'];

    private const MID_FLIGHT_GRACE_MINUTES = 15;

    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $reports = Report::whereIn('type', self::PURCHASE_REPORT_TYPES)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        foreach ($reports as $report) {
            $transaction = Transaction::where('transaction_ref', $report->ref)->first();

            if (!$transaction) {
                $findings->push(new ReconciliationFinding(
                    category: 'purchase',
                    severity: 'critical',
                    summary: "Purchase Report #{$report->id} ({$report->type}, ref {$report->ref}) has no matching Transaction",
                    details: ['report_id' => $report->id, 'type' => $report->type, 'ref' => $report->ref, 'amount' => (float) $report->amount],
                    related_ids: [$report->id],
                ));

                continue;
            }

            if ((float) $report->amount !== (float) $transaction->amount) {
                $findings->push(new ReconciliationFinding(
                    category: 'purchase',
                    severity: 'critical',
                    summary: "Purchase {$report->ref} amount mismatch: Report {$report->amount} vs Transaction {$transaction->amount}",
                    details: ['report_id' => $report->id, 'transaction_id' => $transaction->id, 'report_amount' => (float) $report->amount, 'transaction_amount' => (float) $transaction->amount],
                    related_ids: [$report->id, $transaction->id],
                ));
            }

            $bothSettled = $report->status === 'completed' && $transaction->status === 'completed';
            $bothFailed = $report->status === 'failed' && $transaction->status === 'failed';
            $transactionStillMidFlight = in_array($transaction->status, ['pending', 'processing'], true)
                && $transaction->created_at->gt(now()->subMinutes(self::MID_FLIGHT_GRACE_MINUTES));

            if (!$bothSettled && !$bothFailed && !$transactionStillMidFlight) {
                $findings->push(new ReconciliationFinding(
                    category: 'purchase',
                    severity: 'warning',
                    summary: "Purchase {$report->ref} status divergence: Report is '{$report->status}', Transaction is '{$transaction->status}'",
                    details: ['report_id' => $report->id, 'transaction_id' => $transaction->id, 'report_status' => $report->status, 'transaction_status' => $transaction->status],
                    related_ids: [$report->id, $transaction->id],
                ));
            }
        }

        return $findings;
    }
}
