<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'service_id',
    'field_name',
    'field_code',
    'description',
    'base_price',
    'is_active',
    'activation_disabled',
])]
class ServiceField extends Model
{
    /**
     * Get the parent service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the custom prices configured for this field.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'service_fields_id');
    }

    /**
     * Resolve the effective price for a given user.
     * Priority: user-specific price → role price → base_price.
     */
    public function priceForUser(User $user): float
    {
        if ($this->relationLoaded('prices')) {
            $userPrice = $this->prices->where('user_id', $user->id)->first();

            if ($userPrice) {
                return (float) $userPrice->price;
            }

            $rolePrice = $this->prices
                ->whereNull('user_id')
                ->firstWhere('user_type', $user->role);

            if ($rolePrice) {
                return (float) $rolePrice->price;
            }

            return (float) $this->base_price;
        }

        // 1. Check for a price set specifically for this user
        $userPrice = $this->prices()
            ->where('user_id', $user->id)
            ->first();

        if ($userPrice) {
            return (float) $userPrice->price;
        }

        // 2. Check for a price set for the user's role
        $rolePrice = $this->prices()
            ->whereNull('user_id')
            ->where('user_type', $user->role)
            ->first();

        if ($rolePrice) {
            return (float) $rolePrice->price;
        }

        // 3. Fall back to the field's base price
        return (float) $this->base_price;
    }

    /**
     * Resolve the effective price for a given user type / role.
     * Priority: role price → base_price.
     */
    public function getPriceForUserType(?string $role): float
    {
        if ($role) {
            if ($this->relationLoaded('prices')) {
                $rolePrice = $this->prices
                    ->whereNull('user_id')
                    ->firstWhere('user_type', $role);

                if ($rolePrice) {
                    return (float) $rolePrice->price;
                }
            } else {
                $rolePrice = $this->prices()
                    ->whereNull('user_id')
                    ->where('user_type', $role)
                    ->first();

                if ($rolePrice) {
                    return (float) $rolePrice->price;
                }
            }
        }

        return (float) $this->base_price;
    }

    /**
     * Scope to only active fields.
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
            'base_price'           => 'decimal:2',
            'is_active'            => 'boolean',
            'activation_disabled'  => 'boolean',
        ];
    }
}
