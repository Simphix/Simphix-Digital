<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformWelfareSettings extends Model
{
    protected $fillable = [
        'amount_per_activation',
        'welfare_type',
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
     * Deliberately not `firstOrCreate(['id' => 1], ...)` — `id` isn't
     * mass-assignable, so once the table's auto-increment counter drifts
     * past 1 (any prior insert, even one later rolled back), that lookup
     * stops matching and silently creates a fresh default row on every call.
     */
    public static function current(): self
    {
        return static::first() ?? static::create([
            'amount_per_activation' => 0.00,
            'welfare_type' => 'fixed',
            'description' => 'Platform Welfare Contribution',
            'is_active' => true,
            'deduct_from_sim_operations' => true,
        ]);
    }

    /**
     * Calculate welfare allocation amount for a given SIM or base price.
     */
    public function calculateWelfareAmount(?Sim $sim = null, ?float $activationPrice = null): float
    {
        if (!$this->is_active || (float) $this->amount_per_activation <= 0.00) {
            return 0.00;
        }

        if ($this->welfare_type === 'percent') {
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
