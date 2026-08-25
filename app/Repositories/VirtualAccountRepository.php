<?php

namespace App\Repositories;

use Exception;
use App\Helpers\noncestrHelper;
use App\Helpers\signatureHelper;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerPayment;
use App\Models\User;
use App\Services\CircuitBreaker;
use App\Support\LogRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VirtualAccountRepository
{
    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 20;

    /**
     * Create a PalmPay virtual account for the given user.
     *
     * $identityType is our own app-level choice ('bvn' or 'nin' — which field
     * the user supplied), mapped below to PalmPay's actual identityType
     * values ('personal' / 'personal_nin'). NIN is a fallback path: PalmPay's
     * BVN verification (identityType: personal) rejects some real, valid
     * accounts (confirmed via production logs — respCode AC100007,
     * "LicenseNumber verification failed") and NIN is PalmPay's own
     * documented alternative identity check.
     */
    public function createVirtualAccount(int $loginUserId, string $identityType = 'bvn'): array
    {
        $userDetails = User::where('id', $loginUserId)->first();

        if (!$userDetails) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        try {
            $accountReference = 'F24' . strtoupper(bin2hex(random_bytes(5)));
            $decoded = $this->callCreateAccountApi($userDetails, $identityType, $accountReference);

            DB::table('virtual_accounts')->insert([
                'user_id'           => $loginUserId,
                'account_reference' => $decoded['data']['accountReference'],
                'account_number'    => $decoded['data']['virtualAccountNo'],
                'account_name'      => $decoded['data']['virtualAccountName'],
                'bank_name'         => 'PalmPay',
                'bank_code'         => null,
                'provider'          => 'palmpay',
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return ['success' => true, 'message' => 'Virtual account created successfully.'];
        } catch (Exception $e) {
            Log::error('Error creating virtual account for user ' . $loginUserId . ': ' . $e->getMessage());

            return ['success' => false, 'message' => $this->userFacingMessage($e->getMessage())];
        }
    }

    /**
     * Same PalmPay virtual account creation, for an Enterprise Partner's
     * eligibility payment instead of the general wallet flow — inserts into
     * enterprise_partner_payments (not virtual_accounts) with an
     * EPP-prefixed account_reference (vs. the wallet flow's F24 prefix) so
     * PaymentWebhookController can tell which table a webhook belongs to.
     */
    public function createVirtualAccountForEnterprisePartner(EnterprisePartnerApplication $application, User $user, string $identityType = 'bvn'): array
    {
        try {
            $accountReference = 'EPP' . strtoupper(bin2hex(random_bytes(5)));
            $decoded = $this->callCreateAccountApi($user, $identityType, $accountReference);

            $payment = EnterprisePartnerPayment::create([
                'application_id' => $application->id,
                'user_id'           => $user->id,
                'expected_amount'   => $application->total_amount_snapshot,
                'account_reference' => $decoded['data']['accountReference'],
                'account_number'    => $decoded['data']['virtualAccountNo'],
                'account_name'      => $decoded['data']['virtualAccountName'],
                'bank_name'         => 'PalmPay',
                'status'            => 'pending',
            ]);

            return ['success' => true, 'message' => 'Payment account created successfully.', 'payment' => $payment];
        } catch (Exception $e) {
            Log::error('Error creating EP virtual account for user ' . $user->id . ': ' . $e->getMessage());

            return ['success' => false, 'message' => $this->userFacingMessage($e->getMessage())];
        }
    }

    /**
     * The actual PalmPay HTTP call — signature, headers, circuit breaker,
     * response handling — shared by both createVirtualAccount() and
     * createVirtualAccountForEnterprisePartner(). Returns the decoded
     * successful response array, or throws on any failure (bad config,
     * open circuit, connection error, invalid/error response) — callers
     * decide what to do with their own respective tables.
     */
    protected function callCreateAccountApi(User $userDetails, string $identityType, string $accountReference): array
    {
        $customer_name = trim($userDetails->first_name . ' ' . $userDetails->last_name);

        $requestTime = (int) (microtime(true) * 1000);
        $noncestr    = noncestrHelper::generateNonceStr();

        $palmpayIdentityType = $identityType === 'nin' ? 'personal_nin' : 'personal';
        $licenseNumber = $identityType === 'nin' ? $userDetails->nin : $userDetails->bvn;

        $data = [
            'requestTime'        => $requestTime,
            'identityType'       => $palmpayIdentityType,
            'licenseNumber'      => $licenseNumber,
            'virtualAccountName' => $customer_name,
            'version'            => config('services.palmpay.version', 'V2.0'),
            'customerName'       => $customer_name,
            'email'              => $userDetails->email,
            'accountReference'   => $accountReference,
            'nonceStr'           => $noncestr,
        ];

        Log::info('PalmPay virtual account request', LogRedactor::redact($data));

        $signature = signatureHelper::generate_signature($data, config('keys.private'));

        // Use config() — NOT env() — so this works when config is cached in production
        $baseUrl = config('services.palmpay.base_url');
        $token   = config('services.palmpay.bearer_token');

        if (empty($baseUrl) || empty($token)) {
            throw new Exception('PalmPay API credentials are not configured.');
        }

        $url = rtrim($baseUrl, '/') . '/api/v2/virtual/account/label/create';

        $breaker = new CircuitBreaker('palmpay');

        if ($breaker->isOpen()) {
            throw new Exception('PalmPay is temporarily unavailable. Please try again shortly.');
        }

        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'CountryCode'   => 'NG',
            'Authorization' => "Bearer {$token}",
            'Signature'     => $signature,
            'Content-Type'  => 'application/json',
        ];

        try {
            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::TIMEOUT)
                ->retry(times: 2, sleepMilliseconds: 300, when: fn ($e) => $e instanceof ConnectionException, throw: false)
                ->withHeaders($headers)
                ->post($url, $data);
        } catch (ConnectionException $e) {
            $breaker->recordFailure();
            throw new Exception('Connection error: ' . $e->getMessage());
        }

        $httpCode = $response->status();
        $decoded = $response->json();

        Log::info('PalmPay virtual account response', [
            'http_code' => $httpCode,
            'body'      => LogRedactor::redact((array) $decoded),
        ]);

        if ($decoded === null) {
            $breaker->recordFailure();
            throw new Exception('Invalid JSON response from PalmPay API.');
        }

        if (isset($decoded['respCode']) && $decoded['respCode'] === '00000000') {
            $breaker->recordSuccess();

            return $decoded;
        }

        $breaker->recordFailure();
        $errorMsg = $decoded['respMsg'] ?? $decoded['message'] ?? 'Unknown error from PalmPay.';
        throw new Exception('PalmPay API error: ' . $errorMsg);
    }

    /**
     * Never surface the vendor's raw error text (or that a third-party
     * vendor is even involved) to end users — the real reason is already in
     * the log line above for debugging. Users only get a generic hint,
     * specific enough to act on when we know the ID number itself was the
     * problem.
     */
    private function userFacingMessage(string $rawMessage): string
    {
        if (stripos($rawMessage, 'licensenumber') !== false || stripos($rawMessage, 'verification failed') !== false) {
            return 'We could not verify the ID number you provided. Please double-check it and try again, or try the other verification option (BVN/NIN).';
        }

        return 'We could not generate your virtual account right now. Please try again shortly, or contact support if this continues.';
    }
}
