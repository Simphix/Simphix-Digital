<?php

namespace App\Support;

/**
 * The 7 lifecycle states a SIM can be in as it cascades down the
 * Regional Manager -> Coordinator -> Partner -> User hierarchy and gets activated.
 *
 * Kept as plain string constants (not an Eloquent cast) so `sims.status`
 * stays a raw string attribute, matching every existing comparison
 * across the SIM controllers and Blade views.
 */
class SimStatus
{
    public const ASSIGNED_TO_BSM = 'ASSIGNED_TO_BSM';
    public const ASSIGNED_TO_RM = 'ASSIGNED_TO_RM';
    public const ASSIGNED_TO_COORDINATOR = 'ASSIGNED_TO_COORDINATOR';
    public const ASSIGNED_TO_PARTNER = 'ASSIGNED_TO_PARTNER';
    public const ASSIGNED_TO_BUSINESS = 'ASSIGNED_TO_BUSINESS';
    public const ASSIGNED_TO_BUSINESS_AGENT = 'ASSIGNED_TO_BUSINESS_AGENT';
    public const ACTIVATED = 'ACTIVATED';
    public const DEACTIVATED = 'DEACTIVATED';
    public const SUSPENDED = 'SUSPENDED';
    public const UNASSIGNED = 'UNASSIGNED';

    public static function all(): array
    {
        return [
            self::ASSIGNED_TO_BSM,
            self::ASSIGNED_TO_RM,
            self::ASSIGNED_TO_COORDINATOR,
            self::ASSIGNED_TO_PARTNER,
            self::ASSIGNED_TO_BUSINESS,
            self::ASSIGNED_TO_BUSINESS_AGENT,
            self::ACTIVATED,
            self::DEACTIVATED,
            self::SUSPENDED,
            self::UNASSIGNED,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::UNASSIGNED => 'Unassigned',
            self::ASSIGNED_TO_BSM => 'With Business Sales Manager',
            self::ASSIGNED_TO_RM => 'With Regional Manager',
            self::ASSIGNED_TO_COORDINATOR => 'With Coordinator',
            self::ASSIGNED_TO_PARTNER => 'With Partner',
            self::ASSIGNED_TO_BUSINESS => 'With Enterprise Partner',
            self::ASSIGNED_TO_BUSINESS_AGENT => 'With Business Agent',
            self::ACTIVATED => 'Activated',
            self::DEACTIVATED => 'Deactivated',
            self::SUSPENDED => 'Suspended',
            default => str_replace('_', ' ', $status),
        };
    }

    public static function badgeClasses(string $status): string
    {
        return match ($status) {
            self::ACTIVATED => 'bg-emerald-50 text-emerald-600 border-emerald-100',
            self::DEACTIVATED, self::SUSPENDED => 'bg-rose-50 text-rose-600 border-rose-100',
            self::UNASSIGNED => 'bg-slate-50 text-slate-500 border-slate-200',
            default => 'bg-primary/10 text-primary border-primary/10', // ASSIGNED_TO_*
        };
    }
}
