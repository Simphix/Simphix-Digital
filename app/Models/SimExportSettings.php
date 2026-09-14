<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimExportSettings extends Model
{
    protected $fillable = [
        'recipient_email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Single global settings row, created with safe (off, no recipient yet)
     * defaults on first access. Deliberately not `firstOrCreate(['id' => 1],
     * ...)` — see PlatformWelfareSettings::current() for why.
     */
    public static function current(): self
    {
        return static::first() ?? static::create(['recipient_email' => null, 'is_active' => false]);
    }
}
