<?php

namespace App\Services;

use App\Contracts\SmeDataProviderInterface;
use App\Models\SmeDataProviderSetting;
use App\Support\LogRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The Gogoz Concept (gongozconcept.com) data-purchase vendor call, implementing
 * SmeDataProviderInterface as the #1 primary SME Data provider.
 *
 * API Documentation:
 * Postman: https://documenter.getpostman.com/view/25060066/2sA35G4htY
 * Token Auth: "Authorization: Token <token>"
 * Endpoint: POST https://www.gongozconcept.com/api/data/
 */
class GogozDataApi implements SmeDataProviderInterface
{
    /**
     * Network IDs mapped per standard Nigerian VTU API numbering:
     * 1: MTN, 2: GLO, 3: AIRTEL, 4: 9MOBILE
     */
    private const NETWORK_ID_MAP = [
        'MTN'     => 1,
        'GLO'     => 2,
        'AIRTEL'  => 3,
        '9MOBILE' => 4,
    ];

    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 20;

    public function __construct(private ?SmeDataProviderSetting $settings = null)
    {
    }

    public function baseUrl(): string
    {
        return $this->settings?->base_url ?: config('services.gogoz.base_url', 'https://www.gongozconcept.com/api/data/');
    }

    public function token(): ?string
    {
        return $this->settings?->api_key ?: config('services.gogoz.api_key');
    }

    /**
     * Calls the Gogoz Concept data-purchase endpoint for a mobile number.
     *
     * @return array{success: bool, data: array, api_data: array, transaction_ref: string, message: ?string}
     */
    public function purchase(string $mobile, string $network, string $planId, string $requestId, ?string $vendorAmount = null): array
    {
        $networkId = self::NETWORK_ID_MAP[strtoupper($network)] ?? 1;

        $payload = [
            'network'       => $networkId,
            'mobile_number' => $mobile,
            'plan'          => is_numeric($planId) ? (int) $planId : $planId,
            'Ported_number' => true,
            'request-id'    => $requestId,
        ];

        $breaker = new CircuitBreaker('gogoz');

        if ($breaker->isOpen()) {
            Log::warning('Gogoz Concept vendor circuit is open — skipping call', ['request_id' => $requestId]);

            return [
                'success'         => false,
                'data'            => [],
                'api_data'        => [],
                'transaction_ref' => $requestId,
                'message'         => 'Data vendor is temporarily unavailable. Please try again shortly.',
            ];
        }

        Log::info('Gogoz Concept API Request Payload', [
            'url'     => $this->baseUrl(),
            'payload' => LogRedactor::redact($payload),
        ]);

        try {
            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::TIMEOUT)
                ->retry(times: 2, sleepMilliseconds: 300, when: fn ($e) => $e instanceof ConnectionException, throw: false)
                ->withHeaders([
                    'Authorization' => 'Token ' . $this->token(),
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->withoutVerifying()
                ->post($this->baseUrl(), $payload);
        } catch (ConnectionException $e) {
            $breaker->recordFailure();
            throw $e;
        }

        $data = $response->json();
        Log::info('Gogoz Concept API Response', [
            'status'   => $response->status(),
            'response' => LogRedactor::redact((array) $data),
            'raw_body' => $response->body(),
        ]);

        $isSuccess = $response->successful() && (
            (isset($data['status']) && in_array(strtolower((string) $data['status']), ['success', 'successful', 'true', '1'], true)) ||
            (isset($data['success']) && $data['success'] === true) ||
            (isset($data['code']) && in_array((string) $data['code'], ['200', '201', '0', '00', '000'], true))
        );

        $isSuccess ? $breaker->recordSuccess() : $breaker->recordFailure();

        $apiData = $data['data'] ?? [];
        $transactionRef = $data['ident']
            ?? $data['id']
            ?? $data['transid']
            ?? $data['reference']
            ?? $data['transaction_id']
            ?? $apiData['transaction_ref']
            ?? $apiData['reference']
            ?? $apiData['id']
            ?? $requestId;

        return [
            'success'         => $isSuccess,
            'data'            => $data ?? [],
            'api_data'        => $apiData,
            'transaction_ref' => (string) $transactionRef,
            'message'         => $data['message'] ?? $data['msg'] ?? ($isSuccess ? 'Transaction successful' : 'Purchase failed'),
        ];
    }
}
