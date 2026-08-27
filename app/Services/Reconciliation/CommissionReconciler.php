<?php

namespace App\Services\Reconciliation;

use App\Models\CommissionEarning;
use App\Models\Sim;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cross-checks CommissionEarning rows (written by AwardCommissions /
 * AwardBusinessAgentCommissions on every SimActivated event) against the
 * matching commission-credit Transaction rows. CommissionEarning has no
 * transaction_ref/report_id column at all, so this is inherently a heuristic
 * match by (user_id, sim_id) group counts against Transactions carrying that
 * sim's number in metadata — not a strict 1:1 join.
 *
 * KNOWN QUIRK worth documenting: Transaction.metadata is double-JSON-encoded
 * for these rows — AwardCommissions.php/AwardBusinessAgentCommissions.php
 * both `json_encode()` a value before assigning it to a column cast as
 * 'array', so Eloquent's array cast re-encodes it, and reading it back gives
 * a PHP STRING containing JSON text, not an array. This reconciler reads
 * metadata in PHP and defensively json_decode()s it again when it comes back
 * as a string, rather than relying on MySQL JSON path operators (which would
 * silently return nothing against a double-encoded string). This is a
 * pre-existing bug in those two listeners, left unfixed here deliberately —
 * fixing the source is a separate, unrelated change.
 */
class CommissionReconciler implements Reconciler
{
    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $earnings = CommissionEarning::whereBetween('created_at', [$from, $to])->get();
        if ($earnings->isEmpty()) {
            return $findings;
        }

        $simNumbersById = Sim::whereIn('id', $earnings->pluck('sim_id')->unique())->pluck('number', 'id');

        $candidateTransactionsByUser = Transaction::whereIn('user_id', $earnings->pluck('user_id')->unique())
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('user_id');

        $groups = $earnings->groupBy(fn (CommissionEarning $e) => $e->user_id . ':' . $e->sim_id);

        foreach ($groups as $key => $group) {
            [$userId, $simId] = explode(':', $key);
            $userId = (int) $userId;
            $simId = (int) $simId;
            $simNumber = $simNumbersById[$simId] ?? null;
            $expectedCount = $group->count();

            $matchingCount = ($candidateTransactionsByUser[$userId] ?? collect())
                ->filter(function (Transaction $transaction) use ($simNumber) {
                    $metadata = $this->decodeMetadata($transaction->metadata);

                    return ($metadata['type'] ?? null) === 'activation_commission'
                        && $simNumber !== null
                        && ($metadata['sim_number'] ?? null) === $simNumber;
                })
                ->count();

            if ($matchingCount < $expectedCount) {
                $findings->push(new ReconciliationFinding(
                    category: 'commission',
                    severity: 'critical',
                    summary: "User #{$userId} has {$expectedCount} CommissionEarning row(s) for sim #{$simId} but only {$matchingCount} matching credit Transaction(s) in range",
                    details: [
                        'user_id' => $userId,
                        'sim_id' => $simId,
                        'sim_number' => $simNumber,
                        'expected_count' => $expectedCount,
                        'matching_count' => $matchingCount,
                        'commission_earning_ids' => $group->pluck('id')->all(),
                    ],
                    related_ids: $group->pluck('id')->all(),
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
