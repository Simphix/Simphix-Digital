<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBsmCommissionSettings extends Model
{
    protected $fillable = [
        'amount_per_activation',
        'commission_type',
        'description',
        'is_active',
        'deduct_from_sim_operations',
    ];

    protected $casts = [
        'amount_per_activation' => 'decimal:2',
        'is_active' => 'boolean',
        'deduct_from_sim_operations' => 'boolean',
    ];

    /**
     * Single global settings row, created with safe defaults on first access.
     */
    public static function current(): self
    {
        return static::first() ?? static::create([
            'amount_per_activation' => 0.00,
            'commission_type' => 'fixed',
            'description' => 'BSM Central Commission',
            'is_active' => true,
            'deduct_from_sim_operations' => true,
        ]);
    }

    /**
     * Calculate BSM central commission amount for a given SIM or base price.
     */
    public function calculateCommissionAmount(?Sim $sim = null, ?float $activationPrice = null): float
    {
        if (!$this->is_active || (float) $this->amount_per_activation <= 0.00) {
            return 0.00;
        }

        if ($this->commission_type === 'percent') {
            $price = $activationPrice;
            if ($price === null && $sim) {
                $service = Service::where('name', 'simcard')->first();
                $field = $service
                    ? ServiceField::where('service_id', $service->id)->where('field_name', $sim->category)->first()
                    : null;
                $price = $field ? (float) $field->base_price : 1000.00;
            }
            $price = $price ?? 1000.00;

            return round(($price * (float) $this->amount_per_activation) / 100, 2);
        }

        return (float) $this->amount_per_activation;
    }
}
