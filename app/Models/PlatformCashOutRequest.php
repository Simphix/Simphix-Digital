<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCashOutRequest extends Model
{
    protected $fillable = [
        'platform_wallet_id',
        'platform_wallet_transaction_id',
        'bank_code',
        'bank_name',
        'account_no',
        'account_name',
        'amount',
        'status',
        'admin_notes',
        'requested_by',
        'approved_by',
        'resolved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(PlatformWallet::class, 'platform_wallet_id');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(PlatformWalletTransaction::class, 'platform_wallet_transaction_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
