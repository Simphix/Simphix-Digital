<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownlineBatchUser extends Model
{
    public const STATUS_HELD = 'held';
    public const STATUS_TRANSFERRED = 'transferred';

    protected $fillable = [
        'batch_id',
        'user_id',
        'original_referred_by',
        'current_referred_by',
        'status',
        'transferred_to_user_id',
        'transferred_at',
        'transferred_by',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DownlineBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function originalReferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_referred_by');
    }

    public function currentReferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_referred_by');
    }

    public function transferredToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to_user_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
