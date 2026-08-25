<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterprisePartnerPayment extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'expected_amount',
        'account_reference',
        'account_number',
        'account_name',
        'bank_name',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnterprisePartnerApplication::class, 'application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
