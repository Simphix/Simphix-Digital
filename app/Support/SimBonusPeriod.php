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

        return $timestamp ? Carbon::parse($timestamp) : ($sim->activated_at ?? $sim->created_at);
    }

    /**
     * Date of the most recent completed or resolved data top-up for this SIM.
     * Falls back to the activation anchor if no prior grant row exists.
     */
    public static function lastTopUpDate(Sim $sim): ?Carbon
    {
        $lastGrant = SimActivationBonusGrant::where('sim_id', $sim->id)
            ->whereIn('status', [SimActivationBonusGrant::STATUS_COMPLETED, SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY])
            ->latest('granted_at')
            ->first();

        if ($lastGrant && $lastGrant->granted_at) {
            return $lastGrant->granted_at;
        }

        return self::anchor($sim);
    }

    /**
     * Number of full calendar days elapsed since the SIM's last data top-up (or activation).
     */
    public static function daysSinceLastTopUp(Sim $sim, ?Carbon $at = null): int
    {
        $last = self::lastTopUpDate($sim);

        return $last ? (int) $last->diffInDays($at ?? now()) : 0;
    }

    /**
     * Number of fulfilled bonus cycles (completed or resolved silently), out of 12.
     */
    public static function completedCount(Sim $sim): int
    {
        return SimActivationBonusGrant::where('sim_id', $sim->id)
            ->whereIn('status', [SimActivationBonusGrant::STATUS_COMPLETED, SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY])
            ->count();
    }

    /**
     * The next target 1-based cycle number (e.g. 1 for initial, 2 after 28 days, up to 12).
     */
    public static function nextCycleNumber(Sim $sim): int
    {
        return min(12, self::completedCount($sim) + 1);
    }

    /**
     * Checks if this SIM has reached at least 28 days since its last top-up,
     * has not exceeded the 12-month lifetime cap, and does not already have
     * a pending trigger.
     */
    public static function isDueFor28DayTrigger(Sim $sim, ?Carbon $at = null): bool
    {
        if ($sim->status !== SimStatus::ACTIVATED) {
            return false;
        }

        $now = $at ?? now();

        $completed = self::completedCount($sim);
        if ($completed >= 12) {
            return false;
        }

        $hasPending = SimActivationBonusGrant::where('sim_id', $sim->id)
            ->where('status', SimActivationBonusGrant::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return false;
        }

        $lastTopUp = self::lastTopUpDate($sim);
        if (!$lastTopUp) {
            return false;
        }

        $days = (int) $lastTopUp->diffInDays($now);

        return $days >= 28;
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
            ->whereIn('status', [SimActivationBonusGrant::STATUS_COMPLETED, SimActivationBonusGrant::STATUS_RESOLVED_SILENTLY])
            ->exists();
    }
}
