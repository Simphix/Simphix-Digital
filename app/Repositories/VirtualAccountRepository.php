<?php

namespace App\Repositories;

use App\Exceptions\FundingAccountRejected;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerPayment;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Services\FintavaService;
use App\Support\FintavaCustomerIdentity;
use App\Support\FundingAccountErrors;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VirtualAccountRepository
{
    /**
     * Create a Fintava virtual account for the given user.
     *
     * $identityType is our own app-level choice ('bvn' or 'nin' — which field
     * the user supplied) — kept for signature compatibility with existing
     * callers (WalletController, DownlineDetailController); Fintava's
     * create/customer endpoint takes bvn and nin.
     */
    public function createVirtualAccount(int $loginUserId, string $identityType = 'bvn'): array
    {
        $userDetails = User::where('id', $loginUserId)->first();

        if (!$userDetails) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        try {
            ['response' => $decoded, 'email' => $providerEmail, 'phone' => $providerPhone] = $this->callCreateCustomerApi($userDetails);

            $accountNumber = $decoded['data']['wallet']['accountNumber'];
            $accountName = $decoded['data']['wallet']['accountName'] ?? trim($userDetails->first_name . ' ' . $userDetails->last_name);

            // Fintava's customer and wallet IDs
            $customerId = $decoded['data']['userInfo']['id'] ?? null;
            $walletId = $decoded['data']['wallet']['id'] ?? null;

            if (!$customerId) {
                Log::warning('Fintava created a customer for user ' . $loginUserId . ' but returned no customer id.');
            }

            DB::table('virtual_accounts')->insert([
                'user_id'              => $loginUserId,
                'account_reference'    => $customerId ?? $accountNumber,
                'provider_customer_id' => $customerId,
                'provider_wallet_id'   => $walletId,
                'provider_email'       => $providerEmail,
                'provider_phone'       => $providerPhone,
                'account_number'       => $accountNumber,
                'account_name'         => $accountName,
                'bank_name'            => VirtualAccount::BANK_NAME,
                'bank_code'            => null,
                'provider'             => 'fintava',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            return ['success' => true, 'message' => 'Virtual account created successfully.'];
        } catch (FundingAccountRejected $e) {
            Log::warning('Funding account rejected for user ' . $loginUserId . ': ' . $e->getMessage());

            return ['success' => false, 'message' => FundingAccountErrors::forUser($e->messages) ?? $this->genericFailureMessage()];
        } catch (Exception $e) {
            Log::error('Error creating virtual account for user ' . $loginUserId . ': ' . $e->getMessage());

            return ['success' => false, 'message' => $this->userFacingMessage($e->getMessage())];
        }
    }

    /**
     * Same Fintava virtual account creation, for an Enterprise Partner's
     * eligibility payment — inserts into enterprise_partner_payments (not
     * virtual_accounts) with Loma Bank so PaymentWebhookController /
     * FintavaWebhookController can tell which table a webhook belongs to.
     */
    public function createVirtualAccountForEnterprisePartner(EnterprisePartnerApplication $application, User $user, string $identityType = 'bvn'): array
    {
        try {
            ['response' => $decoded, 'email' => $providerEmail, 'phone' => $providerPhone] = $this->callCreateCustomerApi($user);

            $accountNumber = $decoded['data']['wallet']['accountNumber'];
            $accountName = $decoded['data']['wallet']['accountName'] ?? trim($user->first_name . ' ' . $user->last_name);
            $customerId = $decoded['data']['userInfo']['id'] ?? null;

            $payment = EnterprisePartnerPayment::create([
                'application_id'    => $application->id,
                'user_id'           => $user->id,
                'expected_amount'   => $application->total_amount_snapshot,
                'account_reference' => $customerId ?? $accountNumber,
                'account_number'    => $accountNumber,
                'account_name'      => $accountName,
                'bank_name'         => VirtualAccount::BANK_NAME,
                'status'            => 'pending',
            ]);

            return ['success' => true, 'message' => 'Payment account created successfully.', 'payment' => $payment];
        } catch (FundingAccountRejected $e) {
            Log::warning('EP funding account rejected for user ' . $user->id . ': ' . $e->getMessage());

            return ['success' => false, 'message' => FundingAccountErrors::forUser($e->messages) ?? $this->genericFailureMessage()];
        } catch (Exception $e) {
            Log::error('Error creating EP virtual account for user ' . $user->id . ': ' . $e->getMessage());

            return ['success' => false, 'message' => $this->userFacingMessage($e->getMessage())];
        }
    }

    /**
     * The actual Fintava HTTP call — used by createVirtualAccount(). Returns
     * the decoded successful response plus the random email and phone that
     * were sent, or throws: FundingAccountRejected when Fintava refused the
     * user's own details, a plain Exception for anything else (bad config,
     * open circuit, connection error, unexpected response).
     *
     * @return array{response: array, email: string, phone: string}
     */
    protected function callCreateCustomerApi(User $userDetails): array
    {
        // Fintava rejects an email/phone that already belongs to any other
        // customer, so it gets a fresh random pair every time — see
        // App\Support\FintavaCustomerIdentity.
        $contact = FintavaCustomerIdentity::generate($userDetails);

        $fullName = trim($userDetails->first_name . ' ' . $userDetails->last_name);
        $parts = explode(' ', $fullName, 2);
        $firstName = !empty($userDetails->first_name) ? $userDetails->first_name : ($parts[0] ?? 'User');
        $lastName = !empty($userDetails->last_name) ? $userDetails->last_name : ($parts[1] ?? 'Customer');

        $dob = $userDetails->date_of_birth;
        $dobString = $dob ? ($dob instanceof \Carbon\CarbonInterface ? $dob->format('Y-m-d') : substr((string) $dob, 0, 10)) : '1990-01-01';

        $data = [
            'firstName'     => $firstName,
            'lastName'      => $lastName,
            'phoneNumber'   => $contact['phone'],
            'email'         => $contact['email'],
            // Confirmed with Fintava directly: DYNAMIC_FUND produces a
            // permanent, non-expiring customer + wallet + account number —
            // this is what makes create/customer usable as a persistent
            // per-user deposit account.
            'fundingMethod' => 'DYNAMIC_FUND',
            'address'       => $userDetails->address ?: ($userDetails->state ? $userDetails->state . ', Nigeria' : 'Nigeria'),
            'dateOfBirth'   => $dobString,
            'bvn'           => $userDetails->bvn,
            'nin'           => $userDetails->nin,
        ];

        if (empty($data['bvn']) && empty($data['nin'])) {
            throw new Exception('Missing required BVN or NIN for virtual account creation.');
        }

        $decoded = app(FintavaService::class)->createCustomer($data);

        // 200 or 201 "User created successfully" is what Fintava returns for a new customer.
        if (in_array((int) ($decoded['status'] ?? 0), [200, 201], true) && !empty($decoded['data']['wallet']['accountNumber'])) {
            return ['response' => $decoded, 'email' => $contact['email'], 'phone' => $contact['phone']];
        }

        // Fintava's `message` is sometimes a list of strings, not a string.
        $messages = FundingAccountErrors::normalize($decoded['message'] ?? []) ?: ['Unknown error from Fintava.'];

        // A 4xx validation answer means Fintava disagreed with the user's own details;
        if (in_array((int) ($decoded['status'] ?? 0), [400, 404, 409, 422], true)) {
            throw new FundingAccountRejected($messages);
        }

        throw new Exception('Fintava API error: ' . implode('; ', $messages));
    }

    private function genericFailureMessage(): string
    {
        return 'We could not generate your virtual account right now. Please check your profile details or try again shortly.';
    }

    private function userFacingMessage(string $rawMessage): string
    {
        if (stripos($rawMessage, 'bvn') !== false || stripos($rawMessage, 'nin') !== false || stripos($rawMessage, 'verification') !== false) {
            return 'We could not verify the identity details you provided. Please double-check your BVN/NIN and name, then try again.';
        }

        return 'We could not generate your virtual account right now. Please try again shortly, or contact support if this continues.';
    }
}
