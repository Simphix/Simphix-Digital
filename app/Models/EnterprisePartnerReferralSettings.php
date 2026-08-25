<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterprisePartnerReferralSettings extends Model
{
    protected $fillable = [
        'regional_manager_amount',
        'coordinator_amount',
        'partner_amount',
        'is_active',
    ];

    protected $casts = [
        'regional_manager_amount' => 'decimal:2',
        'coordinator_amount' => 'decimal:2',
        'partner_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Single global settings row, created with safe defaults on first
     * access. Deliberately not `firstOrCreate(['id' => 1], ...)` — `id`
     * isn't mass-assignable, so once the table's auto-increment counter
     * drifts past 1 (any prior insert, even one later rolled back), that
     * lookup stops matching and silently creates a fresh default row on
     * every call. Mirrors PlatformWelfareSettings::current() exactly.
     */
    public static function current(): self
    {
        return static::first() ?? static::create([
            'regional_manager_amount' => 0.00,
            'coordinator_amount' => 0.00,
            'partner_amount' => 0.00,
            'is_active' => true,
        ]);
    }

    /**
     * The one-time referral bonus paid to whoever invited a newly-activated
     * Enterprise Partner, keyed by the inviter's role. Zero for any role
     * outside the three eligible inviter roles or while settings are
     * inactive.
     */
    public function amountForRole(string $role): float
    {
        if (!$this->is_active) {
            return 0.00;
        }

        return match ($role) {
            'regional_manager' => (float) $this->regional_manager_amount,
            'coordinator' => (float) $this->coordinator_amount,
            'partner' => (float) $this->partner_amount,
            default => 0.00,
        };
    }
}
