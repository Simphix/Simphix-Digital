<?php

namespace App\Services\Reconciliation;

use App\Models\Report;
use App\Models\Sim;
use App\Support\SimBonusPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Retroactive audit for the activation-bonus idempotency gap that existed
 * before App\Support\SimBonusPeriod/the guard in GrantActivationBonusData
 * were introduced: scans existing successful 'activation_bonus_data' Report
 * rows, buckets each into the rolling-anniversary period it falls in (using
 * the same math the guard now uses going forward), and flags any sim with
 * more than one grant landing in the same period. Pure detection — this
 * never writes or refunds anything.
 *
 * Report has no sim_id column, so the join back to Sim is by
 * phone_number == number, the same join SimPlanController::retryActivationBonus()
 * already uses.
 */
class SimBonusReconciler implements Reconciler
{
    public function findings(Carbon $from, Carbon $to): Collection
    {
        $findings = collect();

        $grantsByNumber = Report::where('type', 'activation_bonus_data')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('phone_number');

        if ($grantsByNumber->isEmpty()) {
            return $findings;
        }

        $simsByNumber = Sim::whereIn('number', $grantsByNumber->keys())->get()->keyBy('number');

        foreach ($grantsByNumber as $simNumber => $reports) {
            $sim = $simsByNumber[$simNumber] ?? null;
            $anchor = $sim ? SimBonusPeriod::anchor($sim) : null;

            if (!$sim || !$anchor) {
                $findings->push(new ReconciliationFinding(
                    category: 'sim_bonus',
                    severity: 'warning',
                    summary: "Activation bonus report(s) for {$simNumber} but no matching activated SIM was found",
                    details: ['phone_number' => $simNumber, 'report_ids' => $reports->pluck('id')->all()],
                    related_ids: $reports->pluck('id')->all(),
                ));

                continue;
            }

            $byPeriod = $reports->groupBy(fn (Report $r) => SimBonusPeriod::indexFor($anchor, Carbon::parse($r->created_at)));

            foreach ($byPeriod as $periodIndex => $group) {
                if ($group->count() > 1) {
                    $findings->push(new ReconciliationFinding(
                        category: 'sim_bonus',
                        severity: 'critical',
                        summary: "SIM {$simNumber} received {$group->count()} activation bonus grants in the same rolling period #{$periodIndex}",
                        details: [
                            'sim_id' => $sim->id,
                            'sim_number' => $simNumber,
                            'period_index' => $periodIndex,
                            'report_ids' => $group->pluck('id')->all(),
                            'dates' => $group->pluck('created_at')->map(fn ($d) => Carbon::parse($d)->toDateTimeString())->all(),
                        ],
                        related_ids: $group->pluck('id')->all(),
                    ));
                }
            }
        }

        return $findings;
    }
}
