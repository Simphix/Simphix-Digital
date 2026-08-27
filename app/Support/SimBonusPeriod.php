<?php

namespace App\Support;

use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SimHistory;
use Carbon\Carbon;

/**
 * Rolling-anniversary period math for the SIM activation data bonus: a SIM
 * is eligible for one bonus per 1-month window measured from its own first
 * activation, not the calendar month — e.g. first-activated Jan 15 2pm means
 * period 0 is [Jan 15 2pm, Feb 15 2pm), period 1 is [Feb 15 2pm, Mar 15 2pm),
 * and so on. Deliberately a plain static-method class with no Eloquent
 * coupling beyond simple lookups, mirroring SimStatus's style.
 */
class SimBonusPeriod
{
    /**
     * The oldest ACTIVATED transition for this sim — a SIM can deactivate
     * and reactivate (SimAssignmentService::deactivate()/activate()), but
     * the bonus cycle always anchors to the very first activation, not the
     * most recent one.
     */
    public static function anchor(Sim $sim): ?Carbon
    {
        $timestamp = SimHistory::where('sim_id', $sim->id)
            ->where('to_status', SimStatus::ACTIVATED)
            ->oldest('created_at')
            ->value('created_at');

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * 0-based rolling-anniversary window index that $at falls into, relative
     * to $anchor. Walks addMonthsNoOverflow() rather than diffInMonths() to
     * avoid float/rounding edge cases around month-length differences.
     */
    public static function indexFor(Carbon $anchor, Carbon $at): int
    {
        $index = 0;
        $cursor = $anchor->copy();

        while ($cursor->copy()->addMonthsNoOverflow(1)->lte($at)) {
            $cursor->addMonthsNoOverflow(1);
            $index++;
        }

        return $index;
    }

    /** [start, end) datetime bounds of window $periodIndex, anchored at $anchor. */
    public static function bounds(Carbon $anchor, int $periodIndex): array
    {
        return [
            $anchor->copy()->addMonthsNoOverflow($periodIndex),
            $anchor->copy()->addMonthsNoOverflow($periodIndex + 1),
        ];
    }

    /**
     * Whether $sim currently has an ungranted bonus period to fill — the
     * single source of truth used both as GrantActivationBonusData's
     * internal guard and as GrantBulkActivationBonusData's pre-dispatch
     * filter, so a sim whose current period was already granted is never
     * even queued, not just silently no-op'd once dispatched.
     */
    public static function isDueForGrant(Sim $sim, ?Carbon $at = null): bool
    {
        $anchor = self::anchor($sim);
        if (!$anchor) {
            return false;
        }

        $periodIndex = self::indexFor($anchor, $at ?? now());

        return !SimActivationBonusGrant::where('sim_id', $sim->id)
            ->where('period_index', $periodIndex)
            ->where('status', 'completed')
            ->exists();
    }
}
