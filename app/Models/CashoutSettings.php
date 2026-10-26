<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashoutSettings extends Model
{
    public const CONFIGURABLE_ROLES = [
        'business' => 'Enterprise Business',
        'business_agent' => 'Business Agent',
        'partner' => 'Partner',
        'coordinator' => 'Coordinator',
        'regional_manager' => 'Regional Manager',
        'agent' => 'Agent',
        'personal' => 'Personal',
    ];

    public const DEFAULT_ROLE_LIMITS = [
        'business' => ['min' => 5000, 'max' => 5000000],
        'business_agent' => ['min' => 1000, 'max' => 500000],
        'partner' => ['min' => 1000, 'max' => 1000000],
        'coordinator' => ['min' => 1000, 'max' => 1000000],
        'regional_manager' => ['min' => 1000, 'max' => 1000000],
        'agent' => ['min' => 500, 'max' => 100000],
        'personal' => ['min' => 500, 'max' => 50000],
    ];

    protected $fillable = [
        'is_enabled',
        'role_limits',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'role_limits' => 'array',
    ];

    /**
     * Single global settings row, created on first access with cashouts
     * enabled (so existing behaviour is unchanged until an admin switches
     * them off). See PlatformWelfareSettings::current() for why this isn't
     * `firstOrCreate(['id' => 1], ...)`.
     */
    public static function current(): self
    {
        return static::first() ?? static::create(['is_enabled' => true]);
    }

    /**
     * Master switch for every cashout path (user + platform wallet), checked
     * by the `cashout_enabled` middleware and by the views that link to them.
     */
    public static function enabled(): bool
    {
        return static::current()->is_enabled;
    }

    /**
     * Get min and max cashout thresholds for a specific role.
     */
    public function getLimitsForRole(?string $role): array
    {
        $limits = ($this->role_limits ?? [])[$role] ?? null;
        $default = self::DEFAULT_ROLE_LIMITS[$role] ?? ['min' => 100, 'max' => 500000];

        return [
            'min' => isset($limits['min']) && is_numeric($limits['min']) ? (float) $limits['min'] : (float) $default['min'],
            'max' => isset($limits['max']) && is_numeric($limits['max']) ? (float) $limits['max'] : (float) $default['max'],
        ];
    }

    /**
     * Helper to get limits statically for a role.
     */
    public static function limitsForRole(?string $role): array
    {
        return static::current()->getLimitsForRole($role);
    }

    /**
     * Get all configurable roles and their active limits.
     */
    public function getAllRoleLimits(): array
    {
        $all = [];
        foreach (self::CONFIGURABLE_ROLES as $role => $label) {
            $limits = $this->getLimitsForRole($role);
            $all[$role] = [
                'role' => $role,
                'label' => $label,
                'min' => $limits['min'],
                'max' => $limits['max'],
            ];
        }

        return $all;
    }
}
