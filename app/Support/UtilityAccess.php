<?php

namespace App\Support;

use App\Models\User;

/**
 * Role gating for the consumer utility pages (Buy Airtime, Buy Data,
 * Verification Services). Individual end customers (personal, business) buy
 * Airtime/Data for themselves through this storefront flow. Agent lost
 * Airtime/Data when it became a discount-only tier (see
 * AgentUpgradeController) but keeps Verification — so canVerify() is a
 * fully independent role list, not derived from canUse(), to avoid that
 * cutoff silently taking Verification with it. Partners get Verification
 * only, as a paid side-service they offer their downline/customers.
 * Coordinators, regional managers, and back-office roles (staff, checker,
 * super_admin) manage the catalog/platform instead and get none of these.
 */
class UtilityAccess
{
    /**
     * Airtime and Data purchases are platform-wide suspended for every
     * role, independent of ALLOWED_ROLES below — flip to false to restore
     * access exactly as it was (personal/business only). Does not affect
     * canVerify()/Verification, which is intentionally independent (see
     * class docblock above). Endpoints/controllers/routes are untouched;
     * this is the only thing stopping them from being reachable.
     */
    public const UTILITIES_SUSPENDED = true;

    public const ALLOWED_ROLES = ['personal', 'business'];

    private const VERIFICATION_ROLES = ['personal', 'business', 'agent', 'partner'];

    public static function canUse(User $user): bool
    {
        if (self::UTILITIES_SUSPENDED) {
            return false;
        }

        return in_array($user->role, self::ALLOWED_ROLES, true);
    }

    public static function canVerify(User $user): bool
    {
        return in_array($user->role, self::VERIFICATION_ROLES, true);
    }
}
