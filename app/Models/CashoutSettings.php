<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashoutSettings extends Model
{
    protected $fillable = [
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
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
}
