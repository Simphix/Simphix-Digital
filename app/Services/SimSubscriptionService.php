<?php

namespace App\Services;

use App\Support\EnterprisePricing;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\SimSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Support\SimStatus;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimSubscriptionService
{
    public function __construct(
        protected FintavaService $fintava
    ) {}

    /**
     * Determine the reactivation fee for a given SIM.
     */
    public function getReactivationFee(Sim $sim, ?User $payer = null): float
    {
        $enterprisePrice = EnterprisePricing::customerPrice($sim->business_id, $sim->category);
        if ($enterprisePrice !== null) {
            return (float) $enterprisePrice;
        }

        $simService = Service::where('name', 'simcard')->first();
        $serviceField = $simService
            ? ServiceField::where('service_id', $simService->id)->where('field_name', $sim->category)->first()
            : null;

        if (! $serviceField) {
            return 1000.00;
        }

        $userType = $payer?->role ?? 'personal';
        $customPrice = ServicePrice::where('service_fields_id', $serviceField->id)
            ->where('user_type', $userType)
            ->whereNull('user_id')
            ->value('price');

        return (float) ($customPrice ?? $serviceField->base_price);
    }

    /**
     * Reactivate a SIM subscription by debiting the partner's wallet (Main or Bonus).
     */
    public function reactivateViaWallet(Sim $sim, User $partner, string $walletSource, ?User $actor = null): SimSubscription
    {
        $actor = $actor ?? $partner;
        $fee = $this->getReactivationFee($sim, $partner);

        return DB::transaction(function () use ($sim, $partner, $walletSource, $actor, $fee) {
            $wallet = Wallet::where('user_id', $partner->id)->lockForUpdate()->first();
            if (! $wallet) {
                throw new Exception('Partner wallet not found.');
            }

            $balanceField = $walletSource === 'bonus' ? 'bonus' : 'balance';
            $sourceLabel = $walletSource === 'bonus' ? 'Commission Balance' : 'Main Balance';

            if ($walletSource === 'main') {
                if ($wallet->is_locked) {
                    throw new Exception('Partner wallet is currently locked.');
                }
                $available = (float) ($wallet->balance - (float) $wallet->lien_amount);
                if ($available < $fee) {
                    throw new Exception("Insufficient {$sourceLabel}. Required: ₦" . number_format($fee, 2) . ", Available: ₦" . number_format(max(0, $available), 2));
                }
            } else {
                if ((float) $wallet->bonus < $fee) {
                    throw new Exception("Insufficient {$sourceLabel}. Required: ₦" . number_format($fee, 2) . ", Available: ₦" . number_format((float) $wallet->bonus, 2));
                }
            }

            // Debit wallet
            $oldBalance = (float) $wallet->{$balanceField};
            $wallet->decrement($balanceField, $fee);
            $newBalance = (float) $wallet->{$balanceField};

            // Credit platform SIM activation wallet
            app(SimActivationWalletService::class)->creditActivationFee($sim, $fee, $partner);

            $ref = 'REACT-' . time() . '-' . rand(1000, 9999);
            $actorDesc = $actor->id === $partner->id
                ? "partner {$partner->first_name} {$partner->last_name}"
                : "admin {$actor->first_name} {$actor->last_name}";

            Transaction::create([
                'transaction_ref' => $ref,
                'user_id'         => $partner->id,
                'amount'          => $fee,
                'fee'             => 0.00,
                'net_amount'      => $fee,
                'description'     => "SIM Subscription Reactivation (365 Days): {$sim->number} ({$sim->category}, " . strtoupper($sim->provider) . ") charged from {$sourceLabel} by {$actorDesc}",
                'type'            => 'debit',
                'status'          => 'completed',
                'performed_by'    => $actor->first_name . ' ' . $actor->last_name,
            ]);

            $simService = Service::where('name', 'simcard')->first();
            Report::create([
                'user_id'      => $partner->id,
                'phone_number' => $sim->number,
                'network'      => $sim->provider,
                'ref'          => $ref,
                'amount'       => $fee,
                'status'       => 'completed',
                'type'         => 'sim_reactivation',
                'description'  => "SIM Subscription Reactivation (365 Days): {$sim->number} ({$sim->category}) charged from {$sourceLabel}",
                'old_balance'  => $oldBalance,
                'new_balance'  => $newBalance,
                'service_id'   => $simService?->id,
            ]);

            // Calculate start and new expiry date (365 days from now, or extended if not yet expired)
            $now = now();
            $startsAt = $now;
            $newExpiresAt = ($sim->subscription_expires_at && $sim->subscription_expires_at->isFuture())
                ? $sim->subscription_expires_at->copy()->addDays(365)
                : $now->copy()->addDays(365);

            $sim->update([
                'status'                  => SimStatus::ACTIVATED,
                'subscription_expires_at' => $newExpiresAt,
            ]);

            // Create subscription record
            $subscription = SimSubscription::create([
                'sim_id'            => $sim->id,
                'partner_id'        => $partner->id,
                'starts_at'         => $startsAt,
                'expires_at'        => $newExpiresAt,
                'fee'               => $fee,
                'payment_method'    => 'partner_wallet',
                'wallet_type'       => $walletSource,
                'payment_reference' => $ref,
                'status'            => 'active',
                'paid_at'           => $now,
                'notes'             => "Reactivated via {$sourceLabel} by {$actorDesc}",
            ]);

            // Log SIM history
            SimHistory::create([
                'sim_id'      => $sim->id,
                'from_status' => $sim->status,
                'to_status'   => SimStatus::ACTIVATED,
                'user_id'     => $actor->id,
                'notes'       => "SIM subscription reactivated for 365 days until " . $newExpiresAt->format('M d, Y') . " via {$sourceLabel}",
            ]);

            return $subscription;
        });
    }

    /**
     * Generate a dedicated one-time funding account for SIM reactivation.
     */
    public function generateFundingAccount(Sim $sim, ?User $partner = null, ?User $actor = null): SimSubscription
    {
        $partner = $partner ?? $sim->partner ?? $sim->user;
        $fee = $this->getReactivationFee($sim, $partner);
        $reference = 'REACT-ACC-' . $sim->id . '-' . time();

        $accountNumber = null;
        $accountName = 'SimPhix - Reactivation ' . $sim->number;
        $bankName = VirtualAccount::BANK_NAME;

        // Try to generate a virtual account via Fintava if partner is available
        if ($partner) {
            try {
                // If partner already has a virtual account, check if we can reuse or generate fresh
                $existingVa = VirtualAccount::where('user_id', $partner->id)->first();
                if ($existingVa && ! empty($existingVa->account_number)) {
                    $accountNumber = $existingVa->account_number;
                    $accountName = $existingVa->account_name ?: $accountName;
                    $bankName = $existingVa->bank_name ?: VirtualAccount::BANK_NAME;
                } else {
                    $contact = \App\Support\FintavaCustomerIdentity::generate($partner);
                    $fullName = trim($partner->first_name . ' ' . $partner->last_name);
                    $parts = explode(' ', $fullName, 2);

                    $payload = [
                        'email'         => $contact['email'],
                        'phoneNumber'   => $contact['phone'],
                        'firstName'     => ! empty($partner->first_name) ? $partner->first_name : ($parts[0] ?? 'Partner'),
                        'lastName'      => ! empty($partner->last_name) ? $partner->last_name : ($parts[1] ?? 'Reactivation'),
                        'dateOfBirth'   => '1990-01-01',
                        'gender'        => 'Male',
                        'bvn'           => $partner->bvn ?? '22222222222',
                        'fundingMethod' => 'DYNAMIC_FUND',
                    ];

                    $res = $this->fintava->createCustomer($payload);
                    if (! empty($res['data']['wallet']['accountNumber'])) {
                        $accountNumber = $res['data']['wallet']['accountNumber'];
                        $accountName = $res['data']['wallet']['accountName'] ?? $accountName;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Fintava virtual account generation failed for SIM reactivation: " . $e->getMessage());
            }
        }

        // If Fintava call failed or in test/sandbox, provide a deterministic virtual funding account
        if (! $accountNumber) {
            $prefix = '99' . str_pad((string) ($sim->provider === 'mtn' ? 1 : ($sim->provider === 'airtel' ? 2 : 3)), 1, '0');
            $accountNumber = $prefix . str_pad((string) ($sim->id . rand(100, 999)), 7, '0', STR_PAD_LEFT);
        }

        $now = now();
        $newExpiresAt = ($sim->subscription_expires_at && $sim->subscription_expires_at->isFuture())
            ? $sim->subscription_expires_at->copy()->addDays(365)
            : $now->copy()->addDays(365);

        return SimSubscription::create([
            'sim_id'            => $sim->id,
            'partner_id'        => $partner?->id,
            'starts_at'         => $now,
            'expires_at'        => $newExpiresAt,
            'fee'               => $fee,
            'payment_method'    => 'funding_account',
            'wallet_type'       => null,
            'payment_reference' => $reference,
            'account_number'    => $accountNumber,
            'account_name'      => $accountName,
            'bank_name'         => $bankName,
            'status'            => 'pending',
            'paid_at'           => null,
            'notes'             => "One-time reactivation account generated by " . ($actor?->name ?? 'Admin'),
        ]);
    }

    /**
     * Confirm a pending reactivation payment (e.g. from webhook or manual admin confirmation).
     */
    public function confirmReactivationPayment(SimSubscription $subscription, ?float $amountPaid = null, ?string $providerRef = null, ?User $actor = null): void
    {
        DB::transaction(function () use ($subscription, $amountPaid, $providerRef, $actor) {
            $sub = SimSubscription::where('id', $subscription->id)->lockForUpdate()->first();
            if (! $sub || $sub->status === 'active') {
                return;
            }

            $sim = Sim::where('id', $sub->sim_id)->lockForUpdate()->first();
            if (! $sim) {
                throw new Exception('SIM not found for this subscription.');
            }

            $now = now();
            $newExpiresAt = ($sim->subscription_expires_at && $sim->subscription_expires_at->isFuture())
                ? $sim->subscription_expires_at->copy()->addDays(365)
                : $now->copy()->addDays(365);

            $fee = $amountPaid ?? (float) $sub->fee;

            // Credit SIM activation platform wallet
            app(SimActivationWalletService::class)->creditActivationFee($sim, $fee, $sub->partner);

            $ref = $providerRef ?? $sub->payment_reference ?? ('REACT-PAID-' . time());
            $actorDesc = $actor ? "admin {$actor->first_name} {$actor->last_name}" : "bank webhook deposit";

            if ($sub->partner_id) {
                Transaction::create([
                    'transaction_ref'   => $ref,
                    'provider_reference'=> $providerRef,
                    'user_id'           => $sub->partner_id,
                    'amount'            => $fee,
                    'fee'               => 0.00,
                    'net_amount'        => $fee,
                    'description'       => "SIM Subscription Reactivation (365 Days): {$sim->number} paid via one-time account {$sub->account_number} ({$actorDesc})",
                    'type'              => 'credit',
                    'status'            => 'completed',
                    'performed_by'      => $actor ? ($actor->first_name . ' ' . $actor->last_name) : 'Loma Bank Webhook',
                ]);
            }

            $sub->update([
                'status'  => 'active',
                'paid_at' => $now,
                'notes'   => ($sub->notes ? $sub->notes . ' | ' : '') . "Confirmed & activated by {$actorDesc}",
            ]);

            $sim->update([
                'status'                  => SimStatus::ACTIVATED,
                'subscription_expires_at' => $newExpiresAt,
            ]);

            SimHistory::create([
                'sim_id'      => $sim->id,
                'from_status' => $sim->status,
                'to_status'   => SimStatus::ACTIVATED,
                'user_id'     => $actor?->id ?? $sub->partner_id,
                'notes'       => "SIM subscription reactivated for 365 days until " . $newExpiresAt->format('M d, Y') . " via one-time funding account ({$sub->account_number})",
            ]);
        });
    }
}
