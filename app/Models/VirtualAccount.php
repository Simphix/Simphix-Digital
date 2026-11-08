<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'account_reference',
    'provider_customer_id',
    'provider_wallet_id',
    'provider_email',
    'provider_phone',
    'account_number',
    'account_name',
    'bank_name',
    'bank_code',
    'provider',
    'is_active',
])]
class VirtualAccount extends Model
{
    /** Every funding account is presented to users as a Loma Bank account. */
    public const BANK_NAME = 'Loma Bank';

    /**
     * Show Loma Bank for Fintava accounts, including rows saved before this
     * existed with "Fintava" or "PalmPay" as the bank name. Other providers keep their own.
     */
    protected function bankName(): Attribute
    {
        return Attribute::get(
            fn ($value, array $attributes) => in_array($attributes['provider'] ?? 'fintava', ['fintava', 'palmpay'], true) ? self::BANK_NAME : ($value ?? self::BANK_NAME)
        );
    }

    /** The random contact given to Fintava is internal, never serialized to a view or API response. */
    protected $hidden = ['provider_email', 'provider_phone'];
    /**
     * Get the user that owns this virtual account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the account is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Scope: only active accounts.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}

