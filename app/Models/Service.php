<?php

namespace App\Models;

use App\Services\ServiceChargeService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'image',
    'is_active',
])]
class Service extends Model
{
    /**
     * Determine if a service is currently active and available based on Admin Services Pricing setup.
     */
    public static function isAvailable(string $serviceKey): bool
    {
        $key = strtolower(trim($serviceKey));

        $aliases = [
            'cac'            => ['cac', 'cac_registration', 'cac registration'],
            'verification'   => ['verification', 'identity_verification', 'identity verification', 'bvn', 'nin'],
            'simcard'        => ['simcard', 'sim', 'sims', 'sim_services', 'sim services', 'sim_plan', 'sim plan'],
            'smedata'        => ['smedata', 'data', 'sme_data', 'sme data'],
            'airtime'        => ['airtime', 'vtu'],
            'travel'         => ['travel', 'travel_and_tours', 'travel & tours', 'travel packages'],
            'moniepoint_pos' => ['moniepoint_pos', 'moniepoint pos', 'pos', 'pos_request', 'pos request'],
            'loans'          => ['loans', 'loan', 'business_loans', 'business loans'],
        ];

        $candidates = $aliases[$key] ?? [$key];
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));
        $matchingServices = static::query()
            ->whereRaw("LOWER(name) IN ({$placeholders})", $candidates)
            ->get();

        $matched = $matchingServices->first(function ($service) use ($key) {
            return strtolower(trim($service->name)) === $key;
        }) ?? $matchingServices->first();

        if ($matched) {
            return (bool) $matched->is_active;
        }

        return !array_key_exists($key, $aliases);
    }

    /**
     * Alias for isAvailable.
     */
    public static function isEnabled(string $serviceKey): bool
    {
        return static::isAvailable($serviceKey);
    }

    /**
     * Helper to credit a service charge to the platform charges wallet.
     */
    public function recordCharge(float $amount, string $description, ?Model $related = null, ?User $user = null, array $metadata = []): ?PlatformWalletTransaction
    {
        return app(ServiceChargeService::class)->creditCharge($this->name, $amount, $description, $related, $user, $metadata);
    }

    /**
     * Get the fields (variants) for this service.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ServiceField::class);
    }

    /**
     * Get the prices configured for this service.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class);
    }

    /**
     * Scope a query to only active services.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
