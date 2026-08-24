<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformWelfareSettings extends Model
{
    protected $fillable = [
        'amount_per_activation',
        'is_active',
    ];

    protected $casts = [
        'amount_per_activation' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Single global settings row, created with safe defaults on first access.
     * Deliberately not `firstOrCreate(['id' => 1], ...)` — `id` isn't
     * mass-assignable, so once the table's auto-increment counter drifts
     * past 1 (any prior insert, even one later rolled back), that lookup
     * stops matching and silently creates a fresh default row on every call.
     */
    public static function current(): self
    {
        return static::first() ?? static::create(['amount_per_activation' => 0.00, 'is_active' => true]);
    }
}
