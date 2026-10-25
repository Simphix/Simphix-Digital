<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for the onboarding / distribution chain:
 * Regional Manager -> Coordinator -> Partner -> User (personal), plus a
 * second, independent chain for the Enterprise Partner tier:
 * Business (Enterprise Partner) -> Business Agent -> User (personal).
 *
 * Each role may only onboard, or assign a SIM to, the role directly
 * beneath it in its own chain. The two chains never intersect (nothing
 * maps to 'business' via nextRole() — that tier is entered only through
 * the dedicated invite/payment flow, not this hierarchy).
 */
class RoleHierarchy
{
    public const CHAIN = [
        'regional_manager' => 'coordinator',
        'coordinator' => 'partner',
        'partner' => 'personal',
        'business' => 'business_agent',
        'business_agent' => 'personal',
    ];

    /** Roles that bypass the hierarchy for admin operations. */
    public const BYPASS_ROLES = ['super_admin', 'staff', 'business_sales_manager'];

    /** The role directly beneath the given role in the chain, or null if it's the base level. */
    public static function nextRole(string $role): ?string
    {
        return self::CHAIN[$role] ?? null;
    }

    /** Can $onboarder create/onboard an account with role $targetRole? */
    public static function canOnboard(User $onboarder, string $targetRole): bool
    {
        if (in_array($onboarder->role, self::BYPASS_ROLES, true)) {
            return true;
        }

        return self::nextRole($onboarder->role) === $targetRole;
    }

    /** Roles that have a subordinate role beneath them and can therefore onboard someone. */
    public static function onboardingRoles(): array
    {
        return array_keys(self::CHAIN);
    }
}
