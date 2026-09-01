<?php

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use App\Support\LogRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Termii (termii.com) SMS gateway — the SMS vendor for this app, following
 * the same shape as the SME data vendor adapters (SmePlugApi et al.):
 * retrying HTTP client, a circuit breaker so a down vendor fails fast
 * instead of every caller waiting out a full timeout, and redacted logging.
 */
class TermiiSmsApi implements SmsProviderInterface
{
    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 20;

    public function baseUrl(): string
    {
        return config('services.termii.base_url');
    }

    public function apiKey(): ?string
    {
        return config('services.termii.api_key');
    }

    public function senderId(): string
    {
        return config('services.termii.sender_id');
    }

    /**
     * @return array{success: bool, message_id: ?string, message: ?string}
     */
    public function sendSms(string $to, string $message): array
    {
        $breaker = new CircuitBreaker('termii');

        if ($breaker->isOpen()) {
            Log::warning('Termii SMS circuit is open — skipping send.');

            return ['success' => false, 'message_id' => null, 'message' => 'SMS gateway is temporarily unavailable. Please try another channel.'];
        }

        $payload = [
            'to' => $to,
            'from' => $this->senderId(),
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'generic',
            'api_key' => $this->apiKey(),
        ];

        $url = rtrim($this->baseUrl(), '/') . '/api/sms/send';

        Log::info('Termii SMS Request', ['url' => $url, 'payload' => LogRedactor::redact($payload)]);

        try {
            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::TIMEOUT)
                ->retry(times: 2, sleepMilliseconds: 300, when: fn ($e) => $e instanceof ConnectionException, throw: false)
                ->asJson()
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            $breaker->recordFailure();
            throw $e;
        }

        $data = $response->json();
        Log::info('Termii SMS Response', [
            'status' => $response->status(),
            'response' => LogRedactor::redact((array) $data),
        ]);

        $isSuccess = $response->successful() && is_array($data) && !empty($data['message_id']);
        $isSuccess ? $breaker->recordSuccess() : $breaker->recordFailure();

        return [
            'success' => $isSuccess,
            'message_id' => $data['message_id'] ?? null,
            'message' => $data['message'] ?? ($isSuccess ? null : 'Failed to send SMS.'),
        ];
    }
}
