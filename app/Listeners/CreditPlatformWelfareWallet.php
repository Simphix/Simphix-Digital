<?php

namespace App\Listeners;

use App\Events\SimActivated;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use App\Services\PlatformWalletLedger;
use App\Services\SimActivationWalletService;

/**
 * Credits the Platform Welfare Wallet a configurable amount
 * and deducts it from the Platform SIM Operations Wallet every time a SIM activates.
 *
 * Runs synchronously, not queued, so the deduction and credit land the moment activation
 * is confirmed.
 */
class CreditPlatformWelfareWallet
{
    public function handle(SimActivated $event): void
    {
        $settings = PlatformWelfareSettings::current();

        if (!$settings->is_active) {
            return;
        }

        $sim = $event->sim;
        $amount = $settings->calculateWelfareAmount($sim);

        if ($amount <= 0.00) {
            return;
        }

        // Deduct from the Platform SIM Operations Wallet (retail lifecycle only)
        if ($settings->deduct_from_sim_operations) {
            app(SimActivationWalletService::class)->deductWelfare($sim, $amount, $settings->description);
        }

        // Credit to the Platform Welfare Wallet
        $description = $settings->description
            ? "{$settings->description} on activation of SIM {$sim->number}"
            : "Welfare wallet credit on activation of SIM {$sim->number}";

        app(PlatformWalletLedger::class)->credit(
            PlatformWallet::TYPE_WELFARE,
            'Platform Welfare Wallet',
            $amount,
            'sim_activation',
            $description,
            $sim,
            [
                'sim_id'                       => $sim->id,
                'sim_number'                   => $sim->number,
                'category'                     => $sim->category,
                'amount'                       => $amount,
                'deducted_from_sim_operations' => (bool) $settings->deduct_from_sim_operations,
            ]
        );
    }
}
