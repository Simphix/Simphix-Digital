<?php

use App\Services\FintavaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config(['services.fintava.api_key' => 'test-key']);
});

it('parses a successful vendor response', function () {
    Http::fake(['*' => Http::response(['status' => 200, 'message' => 'successful', 'data' => []], 200)]);

    $response = (new FintavaService())->getBanks();

    expect($response['status'])->toBe(200);
    Http::assertSentCount(1);
});

it('does not retry on a 500 response', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    $response = (new FintavaService())->resolveAccountName('0123456789', '000013');

    expect($response['status'] ?? null)->not->toBe(200);
    Http::assertSentCount(1);
});

it('recovers on the retry after one connection failure', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;
        if ($attempts === 1) {
            throw new ConnectionException('Timed out');
        }

        return Http::response(['status' => 200, 'message' => 'successful', 'data' => []], 200);
    });

    $response = (new FintavaService())->getBanks();

    expect($response['status'])->toBe(200)
        ->and($attempts)->toBe(2);
});

it('re-throws (does not swallow) a connection failure that survives the retry, so the payout stays pending instead of being auto-refunded', function () {
    Http::fake(function () {
        throw new ConnectionException('Timed out');
    });

    expect(fn () => (new FintavaService())->bankCredit(['sourceId' => 'src1', 'accountNumber' => '0123456789', 'accountName' => 'Test', 'sortCode' => '000013', 'amount' => 1000]))
        ->toThrow(ConnectionException::class);
});
