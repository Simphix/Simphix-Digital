<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentUpgradeSettings extends Model
{
    protected $fillable = [
        'minimum_wallet_balance',
        'partner_commission_amount',
        'is_active',
    ];

    protected $casts = [
        'minimum_wallet_balance' => 'decimal:2',
        'partner_commission_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Single global settings row, created with safe defaults on first
     * access. Deliberately not `firstOrCreate(['id' => 1], ...)` — `id`
     * isn't mass-assignable, so once the table's auto-increment counter
     * drifts past 1, that lookup stops matching and silently creates a
     * fresh default row on every call. Mirrors
     * EnterprisePartnerReferralSettings::current() exactly.
     */
    public static function current(): self
    {
        return static::first() ?? static::create([
            'minimum_wallet_balance' => 10000.00,
            'partner_commission_amount' => 0.00,
            'is_active' => true,
        ]);
    }
}
