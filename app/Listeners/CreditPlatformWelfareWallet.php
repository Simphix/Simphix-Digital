<?php

namespace App\Listeners;

use App\Events\SimActivated;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use App\Services\PlatformWalletLedger;

/**
 * Credits the Platform Welfare Wallet a fixed, admin-configurable amount
 * every time a SIM activates. Auto-discovered exactly like AwardCommissions
 * and GrantActivationBonus (no EventServiceProvider in this app — Laravel
 * finds this via the type-hinted SimActivated parameter on handle()).
 *
 * Runs synchronously, not queued, so the credit lands the moment activation
 * is confirmed — same reasoning as AwardCommissions.
 */
class CreditPlatformWelfareWallet
{
    public function handle(SimActivated $event): void
    {
        $settings = PlatformWelfareSettings::current();

        if (!$settings->is_active || $settings->amount_per_activation <= 0) {
            return;
        }

        app(PlatformWalletLedger::class)->credit(
            PlatformWallet::TYPE_WELFARE,
            'Platform Welfare Wallet',
            (float) $settings->amount_per_activation,
            'sim_activation',
            "Welfare wallet credit on activation of SIM {$event->sim->number}",
            $event->sim
        );
    }
}
