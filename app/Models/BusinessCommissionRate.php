<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessCommissionRate extends Model
{
    protected $fillable = [
        'business_id',
        'service_fields_id',
        'price',
        'commission',
    ];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'commission' => 'decimal:2',
        ];
    }

    /**
     * The Enterprise Partner this rate belongs to.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * The SIM category this rate applies to, or null for the EP's default
     * rate covering any category without its own override.
     */
    public function serviceField(): BelongsTo
    {
        return $this->belongsTo(ServiceField::class, 'service_fields_id');
    }
}
