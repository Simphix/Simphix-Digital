<?php

namespace App\Services;

use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ServiceChargeService
{
    /**
     * Services that are explicitly excluded from the charges wallet
     * (SIM services follow their own dedicated SIM Activation wallet lifecycle).
     */
    private const EXCLUDED_SERVICES = [
        'simcard',
        'sim',
        'sims',
        'sim_services',
        'sim_plan',
        'simcard_activation',
    ];

    public function __construct(private readonly PlatformWalletLedger $ledger)
    {
    }

    /**
     * Credit a charged fee from an existing or future non-SIM service to the Charges Wallet.
     *
     * @param string $serviceName Service identifier (e.g. 'verification', 'cac', 'smedata', 'airtime', 'travel', etc.)
     * @param float $amount Amount charged to the user
     * @param string $description Ledger entry description
     * @param Model|null $related Related model record (e.g. Verification, CacRegistration, Transaction)
     * @param User|null $user The user who was charged
     * @param array $metadata Additional metadata
     * @return PlatformWalletTransaction|null
     */
    public function creditCharge(
        string $serviceName,
        float $amount,
        string $description,
        ?Model $related = null,
        ?User $user = null,
        array $metadata = []
    ): ?PlatformWalletTransaction {
        $normalized = strtolower(trim($serviceName));

        // SIM services are strictly excluded
        if (in_array($normalized, self::EXCLUDED_SERVICES, true)) {
            return null;
        }

        if ($amount <= 0) {
            return null;
        }

        $metadata = array_merge([
            'service'   => $normalized,
            'user_id'   => $user?->id,
            'user_role' => $user?->role,
        ], $metadata);

        return $this->ledger->credit(
            PlatformWallet::TYPE_CHARGES,
            'Charges Wallet',
            $amount,
            'service_fee_' . $normalized,
            $description,
            $related,
            $metadata
        );
    }

    /**
     * Refund/reverse a previously credited service charge from the Charges Wallet.
     */
    public function refundCharge(
        string $serviceName,
        float $amount,
        string $description,
        ?Model $related = null,
        ?User $user = null,
        array $metadata = []
    ): ?PlatformWalletTransaction {
        $normalized = strtolower(trim($serviceName));

        if (in_array($normalized, self::EXCLUDED_SERVICES, true)) {
            return null;
        }

        if ($amount <= 0) {
            return null;
        }

        $metadata = array_merge([
            'service'   => $normalized,
            'user_id'   => $user?->id,
            'user_role' => $user?->role,
        ], $metadata);

        return $this->ledger->debit(
            PlatformWallet::TYPE_CHARGES,
            $amount,
            'refund',
            'service_fee_refund_' . $normalized,
            $description,
            $related,
            $metadata,
            true // Allow overdraft on refund to ensure customer is not blocked
        );
    }
}
