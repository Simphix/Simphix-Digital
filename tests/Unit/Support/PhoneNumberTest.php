<?php

use App\Support\PhoneNumber;

it('leaves an already-local number unchanged', function () {
    expect(PhoneNumber::sanitizeLocal('08130586193'))->toBe('08130586193');
});

it('normalizes a +234-prefixed number to local format', function () {
    expect(PhoneNumber::sanitizeLocal('+2348130586193'))->toBe('08130586193');
});

it('normalizes a bare 234-prefixed number to local format', function () {
    expect(PhoneNumber::sanitizeLocal('2348130586193'))->toBe('08130586193');
});

it('strips spaces and dashes before validating', function () {
    expect(PhoneNumber::sanitizeLocal('0813-058-6193'))->toBe('08130586193')
        ->and(PhoneNumber::sanitizeLocal('0813 058 6193'))->toBe('08130586193');
});

it('rejects a number that is too short', function () {
    expect(PhoneNumber::sanitizeLocal('081305861'))->toBeNull();
});

it('rejects a number that is too long', function () {
    expect(PhoneNumber::sanitizeLocal('081305861930'))->toBeNull();
});

it('rejects a non-Nigerian-shaped number', function () {
    expect(PhoneNumber::sanitizeLocal('01234567890'))->toBeNull(); // second digit not 7/8/9
});

it('rejects a non-numeric string', function () {
    expect(PhoneNumber::sanitizeLocal('not-a-phone'))->toBeNull();
});

it('converts the reported failing example to Termii format', function () {
    expect(PhoneNumber::toTermiiFormat('08130586193'))->toBe('2348130586193');
});

it('converts a +234-prefixed number to Termii format', function () {
    expect(PhoneNumber::toTermiiFormat('+2348130586193'))->toBe('2348130586193');
});

it('returns null from toTermiiFormat for invalid input', function () {
    expect(PhoneNumber::toTermiiFormat('not-a-phone'))->toBeNull();
});

it('reports valid and invalid numbers correctly via isValid', function () {
    expect(PhoneNumber::isValid('08130586193'))->toBeTrue()
        ->and(PhoneNumber::isValid('not-a-phone'))->toBeFalse();
});

it('validationRule fails a request field that is not a valid phone number', function () {
    $rule = PhoneNumber::validationRule();
    $failed = null;

    $rule('phone', 'not-a-phone', function ($message) use (&$failed) {
        $failed = $message;
    });

    expect($failed)->not->toBeNull();
});

it('validationRule passes a valid phone number without calling fail', function () {
    $rule = PhoneNumber::validationRule();
    $failed = null;

    $rule('phone', '08130586193', function ($message) use (&$failed) {
        $failed = $message;
    });

    expect($failed)->toBeNull();
});

it('normalizeRequestInput merges a normalized value into the request', function () {
    $request = \Illuminate\Http\Request::create('/', 'POST', ['phone' => '+2348130586193']);

    PhoneNumber::normalizeRequestInput($request);

    expect($request->input('phone'))->toBe('08130586193');
});

it('normalizeRequestInput leaves unparseable input untouched', function () {
    $request = \Illuminate\Http\Request::create('/', 'POST', ['phone' => 'not-a-phone']);

    PhoneNumber::normalizeRequestInput($request);

    expect($request->input('phone'))->toBe('not-a-phone');
});

it('normalizeRequestInput no-ops when the field is absent', function () {
    $request = \Illuminate\Http\Request::create('/', 'POST', []);

    PhoneNumber::normalizeRequestInput($request);

    expect($request->has('phone'))->toBeFalse();
});
