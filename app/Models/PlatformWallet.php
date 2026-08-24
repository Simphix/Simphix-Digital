<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformWallet extends Model
{
    public const TYPE_WELFARE = 'welfare';
    public const TYPE_UTILITIES_REVENUE = 'utilities_revenue';

    protected $fillable = [
        'type',
        'name',
        'balance',
        'total_credited',
        'total_debited',
        'currency',
        'is_locked',
        'last_activity',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_credited' => 'decimal:2',
        'total_debited' => 'decimal:2',
        'is_locked' => 'boolean',
        'last_activity' => 'datetime',
    ];

    /**
     * Get or create the wallet for a type, so callers never have to worry
     * about whether the row exists yet.
     */
    public static function forType(string $type, string $name): self
    {
        return static::firstOrCreate(
            ['type' => $type],
            ['name' => $name, 'balance' => 0.00]
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformWalletTransaction::class);
    }
}
