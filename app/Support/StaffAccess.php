<?php

namespace App\Support;

use App\Models\User;

/**
 * Internal/back-office roles — support, business_sales_manager, and super_admin —
 * none of which are real customers. They have no wallet/cash-out to manage
 * and no upline in the referral sense (even if their underlying `users` row
 * still carries a `referred_by` from before they were promoted), so the
 * customer-facing profile self-service (withdrawal account, account
 * upgrade, delete account, editing personal info) and the sidebar's Upline
 * Manager panel are hidden for all of them — see profile/edit.blade.php,
 * ProfileController, and layouts/partials/sidebar.blade.php.
 */
class StaffAccess
{
    public const ROLES = ['support', 'business_sales_manager', 'super_admin'];

    public static function isInternal(User $user): bool
    {
        return in_array($user->role, self::ROLES, true);
    }
}
