<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_id',
    'service_fields_id',
    'user_id',
    'user_type',
    'price',
    'commission',
    'margin_percent',
])]
class ServicePrice extends Model
{
    /**
     * Get the parent service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the service field this price applies to.
     */
    public function serviceField(): BelongsTo
    {
        return $this->belongsTo(ServiceField::class, 'service_fields_id');
    }

    /**
     * Get the specific user this price is for (nullable — may be a role price).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: only user-specific prices.
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: only role-based prices.
     */
    public function scopeForRole(\Illuminate\Database\Eloquent\Builder $query, string $role): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('user_id')->where('user_type', $role);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'commission'     => 'decimal:2',
            'margin_percent' => 'decimal:2',
        ];
    }
}
