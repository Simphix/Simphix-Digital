<?php

namespace App\Services\Reconciliation;

use App\Models\CashOutRequest;
use App\Models\Report;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Surfaces cashout requests stuck at status=pending — the highest-value
 * finding in this whole feature, since it's an already-acknowledged gap
 * with zero prior visibility (see Admin\CashOutController::approve(), which
 * deliberately leaves a request pending forever on an ambiguous/timeout
 * gateway error "for manual reconciliation"). The wallet was already
 * debited at request time, so a request sitting here for a long time is
 * real money in limbo, not a benign delay.
 */
class CashoutReconciler implements Reconciler
{
    private const DEFAULT_STUCK_THRESHOLD_MINUTES = 60;

    public function __construct(private readonly int $stuckThresholdMinutes = self::DEFAULT_STUCK_THRESHOLD_MINUTES)
    {
    }

    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $stuckBefore = now()->subMinutes($this->stuckThresholdMinutes);

        $stuck = CashOutRequest::where('status', 'pending')
            ->where('created_at', '<', $stuckBefore)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        foreach ($stuck as $request) {
            $transaction = Transaction::where('transaction_ref', $request->transaction_ref)->first();
            $report = Report::where('ref', $request->transaction_ref)->first();

            $findings->push(new ReconciliationFinding(
                category: 'cashout',
                severity: 'critical',
                summary: "Cashout request #{$request->id} ({$request->transaction_ref}) has been pending for over {$this->stuckThresholdMinutes} minutes — needs manual review",
                details: [
                    'cash_out_request_id' => $request->id,
                    'transaction_ref' => $request->transaction_ref,
                    'amount' => (float) $request->amount,
                    'user_id' => $request->user_id,
                    'requested_at' => $request->created_at->toDateTimeString(),
                    'age_minutes' => (int) $request->created_at->diffInMinutes(now()),
                    'transaction_status' => $transaction?->status,
                    'report_status' => $report?->status,
                ],
                related_ids: array_filter([$request->id, $transaction?->id, $report?->id]),
            ));
        }

        return $findings;
    }
}
