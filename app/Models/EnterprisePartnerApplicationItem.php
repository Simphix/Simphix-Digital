<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterprisePartnerApplicationItem extends Model
{
    protected $fillable = [
        'application_id',
        'sim_category',
        'provider',
        'quantity',
        'unit_price_snapshot',
        'line_total_snapshot',
    ];

    protected $casts = [
        'unit_price_snapshot' => 'decimal:2',
        'line_total_snapshot' => 'decimal:2',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnterprisePartnerApplication::class, 'application_id');
    }
}
