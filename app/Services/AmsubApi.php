<?php

namespace App\Services;

use App\Contracts\SmeDataProviderInterface;
use App\Models\SmeDataProviderSetting;
use App\Support\LogRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The AMSUB (amsubapi.com) data-purchase vendor call — a fourth vendor
 * behind SmeDataProviderInterface, alongside GogozDataApi, SmeDataPurchaseApi,
 * and SmePlugApi.
 */
class AmsubApi implements SmeDataProviderInterface
{
    // AMSUB's own network numbering, confirmed via their published docs —
    // deliberately not shared with any other vendor's map.
    private const NETWORK_ID_MAP = [
        'MTN' => 1,
        'AIRTEL' => 2,
        'GLO' => 3,
        '9MOBILE' => 4,
    ];

    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 20;

    public function __construct(private ?SmeDataProviderSetting $settings = null)
    {
    }

    public function baseUrl(): string
    {
        return $this->settings?->base_url ?: config('services.amsub.base_url');
    }

    public function token(): ?string
    {
        return $this->settings?->api_key ?: config('services.amsub.api_key');
    }

    /**
     * @return array{success: bool, data: array, api_data: array, transaction_ref: string, message: ?string}
     */
    public function purchase(string $mobile, string $network, string $planId, string $requestId, ?string $vendorAmount = null): array
    {
        $networkId = self::NETWORK_ID_MAP[strtoupper($network)] ?? 1;
        $payload = [
            'network'    => $networkId,
            'phone'      => $mobile,
            'data_plan'  => $planId,
            'bypass'     => false,
            'request-id' => $requestId,
        ];

        $breaker = new CircuitBreaker('amsub');

        if ($breaker->isOpen()) {
            Log::warning('AMSUB vendor circuit is open — skipping call', ['request_id' => $requestId]);

            return [
                'success'         => false,
                'data'            => [],
                'api_data'        => [],
                'transaction_ref' => $requestId,
                'message'         => 'Data vendor is temporarily unavailable. Please try again shortly.',
            ];
        }

        $url = rtrim($this->baseUrl(), '/') . '/data';

        Log::info('AMSUB API Request Payload', [
            'url'     => $url,
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
                ])->post($url, $payload);
        } catch (ConnectionException $e) {
            $breaker->recordFailure();
            throw $e;
        }

        $data = $response->json();
        Log::info('AMSUB API Response', [
            'status'   => $response->status(),
            'response' => LogRedactor::redact((array) $data),
            'raw_body' => $response->body(),
        ]);

        $isSuccess = $response->successful() && is_array($data) && ($data['status'] ?? null) === 'success';
        $isSuccess ? $breaker->recordSuccess() : $breaker->recordFailure();

        return [
            'success'         => $isSuccess,
            'data'            => $data,
            'api_data'        => $data ?? [],
            'transaction_ref' => $data['request-id'] ?? $requestId,
            'message'         => $data['message'] ?? null,
        ];
    }
}
