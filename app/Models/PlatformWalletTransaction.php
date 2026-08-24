<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlatformWalletTransaction extends Model
{
    protected $fillable = [
        'platform_wallet_id',
        'transaction_ref',
        'amount',
        'type',
        'source',
        'related_type',
        'related_id',
        'description',
        'old_balance',
        'new_balance',
        'performed_by',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'old_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PlatformWallet::class, 'platform_wallet_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
