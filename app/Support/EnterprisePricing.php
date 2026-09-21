<?php

namespace App\Support;

use App\Models\BusinessCommissionRate;
use App\Models\User;

/**
 * Single source of truth for Enterprise Partner (business) pricing. Whatever
 * an EP configures in BusinessCommissionRate completely overrides the
 * platform's ServicePrice/base_price for every SIM in their chain: it is what
 * the customer is debited on activation, what the EP and their business
 * agents see on their own pages, and what the payout listener splits.
 */
class EnterprisePricing
{
    /**
     * The EP whose pricing governs $user: the EP themself, a business agent's
     * EP, or a personal customer's agent's EP. Null outside the EP chain.
     */
    public static function businessIdFor(User $user): ?int
    {
        return match ($user->role) {
            'business' => $user->id,
            'business_agent' => self::activeBusinessReferrer($user),
            'personal' => ($agent = $user->referrer) && $agent->role === 'business_agent'
                ? self::activeBusinessReferrer($agent)
                : null,
            default => null,
        };
    }

    private static function activeBusinessReferrer(User $agent): ?int
    {
        $ep = $agent->referrer;

        return $ep && $ep->role === 'business' ? $ep->id : null;
    }

    /**
     * The EP's rate for a SIM category (its own row, else the EP's default
     * row). Null when the EP hasn't priced it, so platform pricing applies.
     */
    public static function rateFor(?int $businessId, string $category): ?BusinessCommissionRate
    {
        if (!$businessId) {
            return null;
        }

        $rate = BusinessCommissionRate::where('business_id', $businessId)
            ->where(function ($query) use ($category) {
                $query->whereHas('serviceField', fn ($q) => $q->where('field_name', $category))
                    ->orWhereNull('service_fields_id');
            })
            ->orderByRaw('service_fields_id IS NULL')
            ->first();

        return ($rate && (float) $rate->price > 0.00) ? $rate : null;
    }

    /** What the customer pays for $category under the EP's pricing, or null. */
    public static function customerPrice(?int $businessId, string $category): ?float
    {
        $rate = self::rateFor($businessId, $category);

        return $rate ? (float) $rate->price : null;
    }

    /**
     * What $viewer earns per activation of $category under their EP's rates:
     * the agent keeps the configured commission, the EP keeps the margin.
     * Null when it doesn't apply (no EP rate, or another role).
     */
    public static function earningFor(User $viewer, string $category): ?float
    {
        $businessId = self::businessIdFor($viewer);
        $rate = self::rateFor($businessId, $category);
        if (!$rate) {
            return null;
        }

        $price = (float) $rate->price;
        $commission = min((float) $rate->commission, $price);

        return match ($viewer->role) {
            'business_agent' => $commission,
            'business' => $price - $commission,
            default => null,
        };
    }
}
