<?php

use App\Models\SmeDataProviderSetting;
use App\Services\GogozDataApi;
use App\Services\SmeDataProviderFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

function fakeGogozSettings(): SmeDataProviderSetting
{
    return new SmeDataProviderSetting([
        'provider' => 'gogoz',
        'api_key'  => 'test-gogoz-token-12345',
        'base_url' => 'https://www.gongozconcept.com/api/data/',
    ]);
}

it('sends the correct request payload and Token authorization header', function () {
    Http::fake([
        '*' => Http::response([
            'status'  => 'success',
            'ident'   => 'GOGOZ_TXN_999',
            'message' => 'Data purchase successful',
        ], 200),
    ]);

    $result = (new GogozDataApi(fakeGogozSettings()))->purchase(
        mobile: '09095263835',
        network: 'MTN',
        planId: '15',
        requestId: 'REQ_GOGOZ_1',
    );

    Http::assertSent(function ($request) {
        return $request->url() === 'https://www.gongozconcept.com/api/data/'
            && $request['network'] === 1
            && $request['mobile_number'] === '09095263835'
            && $request['plan'] === 15
            && $request['Ported_number'] === true
            && $request['request-id'] === 'REQ_GOGOZ_1'
            && $request->hasHeader('Authorization')
            && $request->header('Authorization')[0] === 'Token test-gogoz-token-12345'
            && $request->hasHeader('Content-Type')
            && str_contains($request->header('Content-Type')[0], 'application/json');
    });

    expect($result['success'])->toBeTrue()
        ->and($result['transaction_ref'])->toBe('GOGOZ_TXN_999')
        ->and($result['message'])->toBe('Data purchase successful');
});

it('maps all network names to standard VTU IDs', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success', 'id' => 'TX1'], 200),
    ]);

    $api = new GogozDataApi(fakeGogozSettings());

    $api->purchase('08011111111', 'MTN', '10', 'REQ_MTN');
    Http::assertSent(fn ($req) => $req['network'] === 1);

    $api->purchase('08022222222', 'GLO', '20', 'REQ_GLO');
    Http::assertSent(fn ($req) => $req['network'] === 2);

    $api->purchase('08033333333', 'AIRTEL', '30', 'REQ_AIRTEL');
    Http::assertSent(fn ($req) => $req['network'] === 3);

    $api->purchase('08044444444', '9MOBILE', '40', 'REQ_9MOB');
    Http::assertSent(fn ($req) => $req['network'] === 4);
});

it('parses response with status successful and transid', function () {
    Http::fake([
        '*' => Http::response(['status' => 'successful', 'transid' => 'TXN_ABC', 'message' => 'Delivered'], 200),
    ]);

    $result = (new GogozDataApi(fakeGogozSettings()))->purchase('08031234567', 'MTN', '5', 'R1');
    expect($result['success'])->toBeTrue()
        ->and($result['transaction_ref'])->toBe('TXN_ABC');
});

it('parses response with success true and reference', function () {
    Http::fake([
        '*' => Http::response(['success' => true, 'reference' => 'REF_XYZ', 'msg' => 'Done'], 200),
    ]);

    $result2 = (new GogozDataApi(fakeGogozSettings()))->purchase('08031234567', 'MTN', '5', 'R2');
    expect($result2['success'])->toBeTrue()
        ->and($result2['transaction_ref'])->toBe('REF_XYZ');
});

it('handles failed responses gracefully', function () {
    Http::fake([
        '*' => Http::response(['status' => 'fail', 'message' => 'Insufficient vendor wallet balance'], 400),
    ]);

    $result = (new GogozDataApi(fakeGogozSettings()))->purchase('08031234567', 'MTN', '5', 'R3');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Insufficient vendor wallet balance');
});

it('recovers on retry after a transient connection failure', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;
        if ($attempts === 1) {
            throw new ConnectionException('Connection timed out');
        }

        return Http::response(['status' => 'success', 'ident' => 'RECOVERED_TXN'], 200);
    });

    $result = (new GogozDataApi(fakeGogozSettings()))->purchase('08031234567', 'MTN', '5', 'R4');

    expect($result['success'])->toBeTrue()
        ->and($result['transaction_ref'])->toBe('RECOVERED_TXN')
        ->and($attempts)->toBe(2);
});

it('is resolved by SmeDataProviderFactory as default and for gogoz provider', function () {
    $apiByProvider = SmeDataProviderFactory::forProvider('gogoz');
    expect($apiByProvider)->toBeInstanceOf(GogozDataApi::class);

    $defaultApi = SmeDataProviderFactory::forProvider('unknown_provider');
    expect($defaultApi)->toBeInstanceOf(GogozDataApi::class);
});
