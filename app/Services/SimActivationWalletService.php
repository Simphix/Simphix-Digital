<?php

namespace App\Services;

use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use App\Models\Sim;
use App\Models\User;

class SimActivationWalletService
{
    public function __construct(
        protected PlatformWalletLedger $ledger
    ) {}

    /**
     * Credit the SIM Activation Wallet when a SIM activation fee is collected from a user or partner.
     * Does NOT apply to business and business agents.
     */
    public function creditActivationFee(Sim $sim, float $amount, ?User $payer = null): ?PlatformWalletTransaction
    {
        if ($this->isExcludedBusinessFlow($sim, $payer)) {
            return null;
        }

        if ($amount <= 0.00) {
            return null;
        }

        return $this->ledger->credit(
            PlatformWallet::TYPE_SIM_ACTIVATION,
            'SIM Activation Wallet',
            $amount,
            'sim_activation_fee',
            "SIM Activation Fee collected for {$sim->number} ({$sim->category})",
            $sim,
            [
                'sim_id'     => $sim->id,
                'sim_number' => $sim->number,
                'category'   => $sim->category,
                'provider'   => $sim->provider,
                'payer_id'   => $payer?->id,
                'payer_role' => $payer?->role,
                'amount'     => $amount,
            ]
        );
    }

    /**
     * Deduct commission payout from the SIM Activation Wallet.
     * Does NOT apply to business and business agents.
     */
    public function deductCommission(Sim $sim, float $amount, ?User $beneficiary = null): ?PlatformWalletTransaction
    {
        if ($this->isExcludedBusinessFlow($sim, $beneficiary)) {
            return null;
        }

        if ($amount <= 0.00) {
            return null;
        }

        $beneficiaryDesc = $beneficiary
            ? " to {$beneficiary->first_name} {$beneficiary->last_name} (" . str_replace('_', ' ', $beneficiary->role) . ")"
            : '';

        return $this->ledger->debit(
            PlatformWallet::TYPE_SIM_ACTIVATION,
            $amount,
            'debit',
            'sim_activation_commission',
            "Commission deducted for SIM {$sim->number}{$beneficiaryDesc}",
            $sim,
            [
                'sim_id'           => $sim->id,
                'sim_number'       => $sim->number,
                'category'         => $sim->category,
                'beneficiary_id'   => $beneficiary?->id,
                'beneficiary_role' => $beneficiary?->role,
                'amount'           => $amount,
            ],
            allowOverdraft: true
        );
    }

    /**
     * Deduct platform welfare contribution from the SIM Activation / Operations Wallet.
     * Does NOT apply to business and business agents.
     */
    public function deductWelfare(Sim $sim, float $amount, ?string $description = null): ?PlatformWalletTransaction
    {
        if ($this->isExcludedBusinessFlow($sim)) {
            return null;
        }

        if ($amount <= 0.00) {
            return null;
        }

        $desc = $description
            ? "{$description} deducted for SIM {$sim->number}"
            : "Platform welfare contribution deducted for SIM {$sim->number}";

        return $this->ledger->debit(
            PlatformWallet::TYPE_SIM_ACTIVATION,
            $amount,
            'debit',
            'sim_activation_welfare',
            $desc,
            $sim,
            [
                'sim_id'        => $sim->id,
                'sim_number'    => $sim->number,
                'category'      => $sim->category,
                'amount'        => $amount,
                'target_wallet' => PlatformWallet::TYPE_WELFARE,
            ],
            allowOverdraft: true
        );
    }

    /**
     * Check whether this SIM or user is part of the Enterprise Partner (business / business_agent) flow.
     */
    public function isExcludedBusinessFlow(Sim $sim, ?User $user = null): bool
    {
        if (!empty($sim->business_id)) {
            return true;
        }

        if ($user && in_array($user->role, ['business', 'business_agent'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve the SIM Activation Wallet instance.
     */
    public function getWallet(): PlatformWallet
    {
        return PlatformWallet::forType(PlatformWallet::TYPE_SIM_ACTIVATION, 'SIM Activation Wallet');
    }
}
