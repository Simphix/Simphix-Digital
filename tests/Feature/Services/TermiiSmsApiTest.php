<?php

use App\Services\TermiiSmsApi;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('sendSms formats a local-format number to Termii format before sending', function () {
    Http::fake(['*' => Http::response(['message_id' => 'abc', 'message' => 'ok'], 200)]);

    $result = app(TermiiSmsApi::class)->sendSms('08130586193', 'test message');

    expect($result['success'])->toBeTrue();
    Http::assertSent(fn (HttpRequest $request) => $request['to'] === '2348130586193');
});

test('sendSms formats a +234-prefixed number to Termii format before sending', function () {
    Http::fake(['*' => Http::response(['message_id' => 'abc', 'message' => 'ok'], 200)]);

    app(TermiiSmsApi::class)->sendSms('+2348130586193', 'test message');

    Http::assertSent(fn (HttpRequest $request) => $request['to'] === '2348130586193');
});

test('sendSms rejects an unparseable phone number without calling the vendor', function () {
    Http::fake();

    $result = app(TermiiSmsApi::class)->sendSms('not-a-phone', 'test message');

    expect($result)->toBe(['success' => false, 'message_id' => null, 'message' => 'Invalid recipient phone number.']);
    Http::assertNothingSent();
});
