<?php

use App\Models\SmeDataProviderSetting;
use App\Services\KvdataApi;
use App\Services\SmeDataProviderFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

function fakeKvdataSettings(): SmeDataProviderSetting
{
    return new SmeDataProviderSetting([
        'provider' => 'kvdata',
        'api_key'  => 'test-kvdata-token-12345',
        'base_url' => 'https://kvdataapi.net/api/data/',
    ]);
}

it('sends the correct request payload and Token authorization header', function () {
    Http::fake([
        '*' => Http::response([
            'status'  => 'success',
            'ident'   => 'KVDATA_TXN_999',
            'message' => 'Data purchase successful',
        ], 200),
    ]);

    $result = (new KvdataApi(fakeKvdataSettings()))->purchase(
        mobile: '09095263835',
        network: 'MTN',
        planId: '15',
        requestId: 'REQ_KVDATA_1',
    );

    Http::assertSent(function ($request) {
        return $request->url() === 'https://kvdataapi.net/api/data/'
            && $request['network'] === 1
            && $request['mobile_number'] === '09095263835'
            && $request['plan'] === 15
            && $request['Ported_number'] === true
            && $request['request-id'] === 'REQ_KVDATA_1'
            && $request->hasHeader('Authorization')
            && $request->header('Authorization')[0] === 'Token test-kvdata-token-12345'
            && $request->hasHeader('Content-Type')
            && str_contains($request->header('Content-Type')[0], 'application/json');
    });

    expect($result['success'])->toBeTrue()
        ->and($result['transaction_ref'])->toBe('KVDATA_TXN_999')
        ->and($result['message'])->toBe('Data purchase successful');
});

it('maps all network names to standard VTU IDs', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success', 'id' => 'TX1'], 200),
    ]);

    $api = new KvdataApi(fakeKvdataSettings());

    $api->purchase('08011111111', 'MTN', '10', 'REQ_MTN');
    Http::assertSent(fn ($req) => $req['network'] === 1);

    $api->purchase('08022222222', 'GLO', '20', 'REQ_GLO');
    Http::assertSent(fn ($req) => $req['network'] === 2);

    $api->purchase('08033333333', 'AIRTEL', '30', 'REQ_AIRTEL');
    Http::assertSent(fn ($req) => $req['network'] === 3);

    $api->purchase('08044444444', '9MOBILE', '40', 'REQ_9MOB');
    Http::assertSent(fn ($req) => $req['network'] === 4);
});

it('accepts various success status flags from vendor', function () {
    Http::fakeSequence()
        ->push(['status' => 'successful', 'id' => 101, 'message' => 'Done'], 200)
        ->push(['success' => true, 'id' => 102], 200)
        ->push(['code' => '200', 'id' => 103], 200)
        ->push(['status' => 'fail', 'message' => 'Insufficient vendor balance'], 400);

    $api = new KvdataApi(fakeKvdataSettings());

    $r1 = $api->purchase('08012345678', 'MTN', '1', 'R1');
    expect($r1['success'])->toBeTrue()
        ->and($r1['transaction_ref'])->toBe('101');

    $r2 = $api->purchase('08012345678', 'MTN', '1', 'R2');
    expect($r2['success'])->toBeTrue()
        ->and($r2['transaction_ref'])->toBe('102');

    $r3 = $api->purchase('08012345678', 'MTN', '1', 'R3');
    expect($r3['success'])->toBeTrue()
        ->and($r3['transaction_ref'])->toBe('103');

    $r4 = $api->purchase('08012345678', 'MTN', '1', 'R4');
    expect($r4['success'])->toBeFalse()
        ->and($r4['message'])->toBe('Insufficient vendor balance');
});

it('recovers on retry after one connection failure', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;
        if ($attempts === 1) {
            throw new ConnectionException('Connection timed out');
        }

        return Http::response(['status' => 'success', 'ident' => 'RECOVERED_TXN'], 200);
    });

    $result = (new KvdataApi(fakeKvdataSettings()))->purchase('08031234567', 'MTN', '5', 'R4');

    expect($result['success'])->toBeTrue()
        ->and($result['transaction_ref'])->toBe('RECOVERED_TXN')
        ->and($attempts)->toBe(2);
});

it('is resolved by SmeDataProviderFactory as default and for kvdata provider', function () {
    $apiByProvider = SmeDataProviderFactory::forProvider('kvdata');
    expect($apiByProvider)->toBeInstanceOf(KvdataApi::class);

    $defaultApi = SmeDataProviderFactory::forProvider('unknown_provider');
    expect($defaultApi)->toBeInstanceOf(KvdataApi::class);
});
