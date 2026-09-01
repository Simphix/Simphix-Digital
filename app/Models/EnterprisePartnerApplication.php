<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnterprisePartnerApplication extends Model
{
    protected $fillable = [
        'user_id',
        'total_amount_snapshot',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'total_amount_snapshot' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EnterprisePartnerPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EnterprisePartnerApplicationItem::class, 'application_id');
    }
}
