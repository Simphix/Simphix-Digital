<?php

namespace App\Listeners;

use App\Events\SimActivated;
use App\Models\PlatformBsmCommissionSettings;
use App\Models\PlatformWallet;
use App\Services\PlatformWalletLedger;
use App\Services\SimActivationWalletService;

/**
 * Credits the BSM Central Commission Wallet a configurable amount
 * (and optionally deducts it from the Platform SIM Operations Wallet)
 * every time a retail SIM activates.
 *
 * Runs synchronously, not queued, so the deduction and credit land the moment activation
 * is confirmed.
 */
class CreditBsmCentralCommissionWallet
{
    public function handle(SimActivated $event): void
    {
        $settings = PlatformBsmCommissionSettings::current();

        if (!$settings->is_active) {
            return;
        }

        $sim = $event->sim;

        // Skip enterprise partner flows (business and business agents)
        $simActivationService = app(SimActivationWalletService::class);
        if ($simActivationService->isExcludedBusinessFlow($sim)) {
            return;
        }

        $amount = $settings->calculateCommissionAmount($sim);

        if ($amount <= 0.00) {
            return;
        }

        // Deduct from the Platform SIM Operations Wallet if configured
        if ($settings->deduct_from_sim_operations) {
            $simActivationService->deductBsmCommission($sim, $amount, $settings->description);
        }

        // Credit to the BSM Central Commission Wallet
        $description = $settings->description
            ? "{$settings->description} on activation of SIM {$sim->number}"
            : "BSM Central Commission credit on activation of SIM {$sim->number}";

        app(PlatformWalletLedger::class)->credit(
            PlatformWallet::TYPE_BSM_COMMISSION,
            'BSM Central Commission Wallet',
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
