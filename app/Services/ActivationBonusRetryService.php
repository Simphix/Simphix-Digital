<?php

namespace App\Services;

use App\Jobs\GrantActivationBonusData;
use App\Models\Report;
use App\Models\Sim;
use Illuminate\Support\Facades\DB;

/**
 * Re-dispatches a failed activation-bonus data top-up. Shared by the admin
 * SIM Plans page and the support desk so both retry through exactly the same
 * claim-then-dispatch path.
 *
 * The report is atomically claimed (failed -> retrying) under a row lock
 * before the job is ever dispatched, so a double-click (or two people
 * retrying the same stale row) can never result in more than one job — and
 * therefore more than one real vendor top-up — in flight for it.
 * GrantActivationBonusData resolves the claimed row in place (completed +
 * resolved_at on success, back to failed on a genuine retry-failure, or
 * resolved with an explanatory note if the retry turned out to be moot).
 */
class ActivationBonusRetryService
{
    /**
     * @return array{0: bool, 1: string} [succeeded, message for the flash]
     */
    public function retry(Report $report): array
    {
        if ($report->type !== 'activation_bonus_data') {
            return [false, 'This entry is not a retryable failed activation bonus.'];
        }

        $claimed = DB::transaction(function () use ($report) {
            $locked = Report::where('id', $report->id)->lockForUpdate()->first();

            if (!$locked || $locked->status !== 'failed' || $locked->resolved_at !== null) {
                return null;
            }

            $locked->update(['status' => 'retrying']);

            return $locked;
        });

        if (!$claimed) {
            return [false, 'This entry has already been retried or resolved.'];
        }

        $sim = Sim::where('number', $claimed->phone_number)->first();
        if (!$sim) {
            // Release the claim — nothing was dispatched, so it must not be
            // left stuck in "retrying" with no job ever coming to resolve it.
            $claimed->update(['status' => 'failed']);

            return [false, "No SIM found for {$claimed->phone_number}."];
        }

        GrantActivationBonusData::dispatch($sim, $claimed);

        return [true, "Retry queued for {$claimed->phone_number}."];
    }
}
