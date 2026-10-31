<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformWallet extends Model
{
    public const TYPE_WELFARE = 'welfare';
    public const TYPE_UTILITIES_REVENUE = 'utilities_revenue';
    public const TYPE_SIM_ACTIVATION = 'sim_activation';

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
     * about whether the row exists yet. Safe under concurrency: two requests
     * racing to create the same type's row for the first time will have one
     * winner and one loser at the database's unique constraint on `type` —
     * the loser catches that and re-selects the winner's row instead of
     * letting the exception propagate (plain firstOrCreate() is not safe
     * here since its SELECT-then-INSERT isn't atomic).
     */
    public static function forType(string $type, string $name): self
    {
        $wallet = static::where('type', $type)->first();
        if ($wallet) {
            return $wallet;
        }

        try {
            return static::create(['type' => $type, 'name' => $name, 'balance' => 0.00]);
        } catch (\Illuminate\Database\QueryException $e) {
            return static::where('type', $type)->firstOrFail();
        }
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformWalletTransaction::class);
    }
}
