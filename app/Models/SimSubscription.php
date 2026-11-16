<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'sim_id',
        'partner_id',
        'starts_at',
        'expires_at',
        'fee',
        'payment_method',
        'wallet_type',
        'payment_reference',
        'account_number',
        'account_name',
        'bank_name',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
        'paid_at'    => 'datetime',
        'fee'        => 'decimal:2',
    ];

    public function sim(): BelongsTo
    {
        return $this->belongsTo(Sim::class, 'sim_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere('expires_at', '<=', now());
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
