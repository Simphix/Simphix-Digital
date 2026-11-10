<?php

namespace App\Services;

use App\Support\FundingAccountErrors;
use App\Support\LogRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for Fintava's BaaS API (https://fintava.readme.io) — the
 * platform's banking and payment provider, replacing PalmPay. Auth is a plain bearer
 * token on every request (Fintava, unlike PalmPay, doesn't require signing
 * outbound requests — only its webhooks are signed, see
 * App\Helpers\FintavaSignatureHelper).
 */
class FintavaService
{
    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 20;

    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.fintava.base_url', 'https://dev.fintavapay.com/api/dev'), '/') . '/';
        $this->apiKey = config('services.fintava.api_key');
    }

    /**
     * Create a permanent Fintava customer + wallet (fundingMethod:
     * DYNAMIC_FUND — confirmed non-expiring, unlike the separate
     * virtual-wallet/generate endpoint). This is the virtual-account
     * generation call, used by VirtualAccountRepository.
     */
    public function createCustomer(array $data): array
    {
        return $this->post('create/customer', $data);
    }

    /**
     * List banks, optionally filtered — data: [{code, name}]. Used by
     * SyncFintavaBankListJob to populate the banks table.
     */
    public function getBanks(?string $searchTerm = null): array
    {
        return $this->get('banks', array_filter(['searchTerm' => $searchTerm]));
    }

    /**
     * Resolve a bank account number + sortCode to the account holder's name.
     */
    public function resolveAccountName(string $accountNumber, string $sortCode): array
    {
        return $this->get('name/enquiry', ['accountNumber' => $accountNumber, 'sortCode' => $sortCode]);
    }

    /**
     * The account holder's name out of a name/enquiry response, or null when
     * the lookup did not resolve one. Fintava nests it as
     * data.account.accountName (alongside data.status and
     * data.account.responseCode "00"); the flatter data.accountName /
     * data.account_name shapes are still accepted. A lookup that reports
     * failure (data.status false, or a responseCode other than "00") never
     * yields a name, even if a name field is present.
     */
    public static function accountNameFrom(array $response): ?string
    {
        if (! self::isSuccessStatus($response)) {
            return null;
        }

        $data = $response['data'] ?? null;

        if (! is_array($data) || ($data['status'] ?? true) === false) {
            return null;
        }

        $account = is_array($data['account'] ?? null) ? $data['account'] : $data;

        if (isset($account['responseCode']) && (string) $account['responseCode'] !== '00') {
            return null;
        }

        $name = $account['accountName'] ?? $account['account_name'] ?? null;
        $name = is_string($name) ? trim($name) : '';

        return $name !== '' ? $name : null;
    }

    /**
     * What to tell the user when a name lookup did not resolve. A 200 with no
     * name means Fintava searched and found nothing, and its own "search
     * completed" message would read like a success, so say so plainly.
     */
    public static function accountLookupError(array $response): string
    {
        $fallback = 'Could not verify that account number.';

        if (self::isSuccessStatus($response)) {
            return $fallback;
        }

        $messages = FundingAccountErrors::normalize($response['message'] ?? []);

        return $messages ? implode('; ', $messages) : $fallback;
    }

    /**
     * Transfer to an external bank account — always debits the one
     * centrally-configured sourceId (config('services.fintava.master_source_id')),
     * regardless of which user's cash-out this is for; callers pass it in
     * $params explicitly rather than this method reading config itself, so
     * tests can exercise a specific sourceId without touching config.
     */
    public function bankCredit(array $params): array
    {
        $endpoint = config('services.fintava.payout_endpoint', 'bank/credit/merchant');

        return $this->post($endpoint, $params);
    }

    /**
     * Transfer between Fintava wallets (sweep funds from a customer's virtual account to the merchant wallet).
     * Endpoint: POST transaction/wallet-to-wallet
     * Parameters:
     * - senderAccount: Account number of the customer being debited
     * - receiverAccount: Merchant wallet account number
     * - amount: Amount to transfer
     * - CustomerReference: Unique transaction reference
     * - narration: Optional transfer description
     */
    public function walletTransfer(array $params): array
    {
        return $this->post('transaction/wallet-to-wallet', $params);
    }

    /**
     * Get live wallet balance for a customer wallet.
     * Endpoint: GET customer/wallet/balance/{walletId}
     */
    public function getCustomerWalletBalance(string $walletId): array
    {
        return $this->get('customer/wallet/balance/' . urlencode($walletId));
    }

    /**
     * Fetch customer transactions from Fintava.
     * Endpoint: GET txn
     * Query parameters typically include: customerId, take, page, order
     */
    public function getCustomerTransactions(array $query): array
    {
        return $this->get('txn', $query);
    }

    public function getTransactionByReference(string $reference): array
    {
        return $this->get('transaction/reference/' . urlencode($reference));
    }

    protected function get(string $endpoint, array $query = []): array
    {
        return $this->request('get', $endpoint, $query);
    }

    protected function post(string $endpoint, array $data): array
    {
        return $this->request('post', $endpoint, $data);
    }

    /** Fintava reports 200 for done and 201 for created; both mean it went through. */
    public static function isSuccessStatus(array $response): bool
    {
        return in_array((int) ($response['status'] ?? 0), [200, 201], true);
    }

    /**
     * Some other 2xx: Fintava accepted the request but not in a way we
     * recognise, so the money may have moved. Never treat it as a rejection
     * (that would refund a payout that was actually sent).
     */
    public static function isUnrecognisedSuccessStatus(array $response): bool
    {
        $status = (int) ($response['status'] ?? 0);

        return $status >= 200 && $status < 300 && ! self::isSuccessStatus($response);
    }

    /**
     * Perform the actual HTTP call. Success is HTTP 2xx with the decoded
     * body's `status` field 200 or 201 (201 = created, e.g. a new customer; Fintava's own success indicator).
     */
    protected function request(string $method, string $endpoint, array $data): array
    {
        $url = $this->baseUrl . ltrim($endpoint, '/');

        Log::info("Fintava {$method} Request to $url", ['data' => LogRedactor::redact($data)]);

        $breaker = new CircuitBreaker('fintava');

        if ($breaker->isOpen()) {
            Log::warning("Fintava circuit is open — skipping request to $url");

            return ['status' => 9999, 'message' => 'Fintava is temporarily unavailable. Please try again shortly.'];
        }

        if (empty($this->apiKey)) {
            Log::error('Fintava API key is not configured.');

            return ['status' => 9999, 'message' => 'Fintava is not configured.'];
        }

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ];

        try {
            $request = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::TIMEOUT)
                ->retry(times: 2, sleepMilliseconds: 300, when: fn ($e) => $e instanceof ConnectionException, throw: false)
                ->withHeaders($headers)
                ->when(config('app.env') === 'local', fn ($h) => $h->withoutVerifying());

            $response = $method === 'get'
                ? $request->get($url, $data)
                : $request->post($url, $data);
        } catch (ConnectionException $e) {
            $breaker->recordFailure();
            Log::error('Fintava connection error: ' . $e->getMessage());

            throw $e;
        }

        Log::info("Fintava Response from $url", ['response' => LogRedactor::redact((array) $response->json())]);

        $decoded = $response->json();
        $isSuccess = $response->successful() && is_array($decoded) && in_array((int) ($decoded['status'] ?? 0), [200, 201], true);
        $isClientRejection = is_array($decoded) && in_array((int) ($decoded['status'] ?? $response->status()), [400, 404, 409, 422], true);
        ($isSuccess || $isClientRejection) ? $breaker->recordSuccess() : $breaker->recordFailure();

        return is_array($decoded) ? $decoded : ['status' => $response->status(), 'message' => 'Invalid response from Fintava.'];
    }
}
