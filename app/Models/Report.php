<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number',
        'account_number',
        'account_name',
        'bank_code',
        'bank_name',
        'network',
        'ref',
        'amount',
        'status',
        'type',
        'description',
        'old_balance',
        'new_balance',
        'service_id',
        'resolved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'old_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
