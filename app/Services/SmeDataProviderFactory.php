<?php

namespace App\Services;

use App\Contracts\SmeDataProviderInterface;
use App\Models\SmeDataProviderSetting;

class SmeDataProviderFactory
{
    /**
     * The vendor currently active for the storefront.
     */
    public static function make(): SmeDataProviderInterface
    {
        return self::forProvider(SmeDataProviderSetting::activeProvider());
    }

    /**
     * A specific vendor, regardless of which one is currently active —
     * used by callers (e.g. the activation-bonus job) that must route by
     * a plan's own provider rather than the global active flag.
     */
    public static function forProvider(string $provider): SmeDataProviderInterface
    {
        $settings = SmeDataProviderSetting::forProvider($provider);

        return match ($provider) {
            'gogoz'   => new GogozDataApi($settings),
            'smeplug' => new SmePlugApi($settings),
            'amsub'   => new AmsubApi($settings),
            'legacy'  => new SmeDataPurchaseApi($settings),
            default   => new GogozDataApi($settings),
        };
    }
}
