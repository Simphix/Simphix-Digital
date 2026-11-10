<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FintavaWalletTransfer extends Model
{
    protected $fillable = [
        'user_id',
        'virtual_account_id',
        'deposit_reference',
        'customer_reference',
        'sender_account',
        'receiver_account',
        'amount',
        'fee_charged',
        'net_amount',
        'status',
        'provider_reference',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'fee_charged'      => 'decimal:2',
        'net_amount'       => 'decimal:2',
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function virtualAccount(): BelongsTo
    {
        return $this->belongsTo(VirtualAccount::class);
    }
}
