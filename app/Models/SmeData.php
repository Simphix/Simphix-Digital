<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmeData extends Model
{
    use HasFactory;

    protected $table = 'sme_data';

    protected $fillable = [
        'data_id',
        'provider',
        'network',
        'plan_type',
        'personal_price',
        'agent_price',
        'partner_price',
        'business_price',
        'vendor_amount',
        'size',
        'validity',
        'status',
    ];

    protected $casts = [
        'personal_price' => 'decimal:2',
        'agent_price' => 'decimal:2',
        'partner_price' => 'decimal:2',
        'business_price' => 'decimal:2',
        'vendor_amount' => 'decimal:2',
    ];

    /**
     * Calculate price based on the user role/upgrade tier
     *
     * @param string $role
     * @return float
     */
    public function calculatePriceForRole(string $role): float
    {
        return match ($role) {
            'agent' => (float) $this->agent_price,
            'partner' => (float) $this->partner_price,
            'business', 'business_agent' => (float) $this->business_price,
            'business_sales_manager', 'support', 'super_admin' => (float) $this->business_price,
            default => (float) $this->personal_price,
        };
    }
}
