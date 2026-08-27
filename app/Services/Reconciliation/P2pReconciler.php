<?php

namespace App\Services\Reconciliation;

use App\Models\Report;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Matches P2P transfer legs. WalletController::processTransfer() writes a
 * sender Transaction+Report pair (ref = 'TRF'+random) and a receiver
 * Transaction+Report pair (ref = same + '-REC') — linked only by that string
 * convention, no FK. No fee is ever applied to a P2P transfer, so the
 * sender's debited amount must exactly equal the receiver's credited amount.
 */
class P2pReconciler implements Reconciler
{
    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $senderReports = Report::where('type', 'transfer')
            ->where('ref', 'not like', '%-REC')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        foreach ($senderReports as $senderReport) {
            $recRef = $senderReport->ref . '-REC';
            $receiverReport = Report::where('type', 'transfer')->where('ref', $recRef)->first();

            if (!$receiverReport) {
                $findings->push(new ReconciliationFinding(
                    category: 'p2p',
                    severity: 'critical',
                    summary: "P2P transfer {$senderReport->ref} has a sender leg but no matching receiver leg ({$recRef})",
                    details: ['sender_report_id' => $senderReport->id, 'expected_receiver_ref' => $recRef, 'amount' => (float) $senderReport->amount],
                    related_ids: [$senderReport->id],
                ));

                continue;
            }

            if ((float) $senderReport->amount !== (float) $receiverReport->amount) {
                $findings->push(new ReconciliationFinding(
                    category: 'p2p',
                    severity: 'critical',
                    summary: "P2P transfer {$senderReport->ref} amount mismatch: sender {$senderReport->amount} vs receiver {$receiverReport->amount}",
                    details: [
                        'sender_report_id' => $senderReport->id,
                        'receiver_report_id' => $receiverReport->id,
                        'sender_amount' => (float) $senderReport->amount,
                        'receiver_amount' => (float) $receiverReport->amount,
                    ],
                    related_ids: [$senderReport->id, $receiverReport->id],
                ));
            }

            $senderTxn = Transaction::where('transaction_ref', $senderReport->ref)->first();
            $receiverTxn = Transaction::where('transaction_ref', $recRef)->first();

            if (!$senderTxn) {
                $findings->push(new ReconciliationFinding(
                    category: 'p2p',
                    severity: 'critical',
                    summary: "P2P transfer {$senderReport->ref} has a Report but no matching sender Transaction",
                    details: ['report_id' => $senderReport->id],
                    related_ids: [$senderReport->id],
                ));
            }

            if (!$receiverTxn) {
                $findings->push(new ReconciliationFinding(
                    category: 'p2p',
                    severity: 'critical',
                    summary: "P2P transfer {$recRef} has a Report but no matching receiver Transaction",
                    details: ['report_id' => $receiverReport->id],
                    related_ids: [$receiverReport->id],
                ));
            }
        }

        return $findings;
    }
}
