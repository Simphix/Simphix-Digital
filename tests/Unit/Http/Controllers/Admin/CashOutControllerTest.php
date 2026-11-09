<?php

use App\Http\Controllers\Admin\CashOutController;

/**
 * Exercises the private isSystemError() classifier directly via reflection —
 * consistent with the rest of the Unit suite (no DB, no RefreshDatabase
 * configured for this project). A full HTTP round-trip through approve()
 * would need real User/Wallet/CashOutRequest rows and admin auth, which this
 * suite doesn't set up; this pins the actual behavioral change instead.
 */
function callIsSystemError(string $respMsg): bool
{
    $controller = (new ReflectionClass(CashOutController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(CashOutController::class, 'isSystemError');
    $method->setAccessible(true);

    return $method->invoke($controller, $respMsg);
}

it('classifies a Fintava sign error as a system error, not a business rejection', function () {
    expect(callIsSystemError('sign error'))->toBeTrue()
        ->and(callIsSystemError('Invalid signature'))->toBeTrue()
        ->and(callIsSystemError('Authorization does not start with Bearer'))->toBeTrue()
        ->and(callIsSystemError('token expired'))->toBeTrue()
        ->and(callIsSystemError('certificate error'))->toBeTrue();
});

it('is case-insensitive', function () {
    expect(callIsSystemError('SIGN ERROR'))->toBeTrue();
});

it('does not classify a genuine business decline as a system error', function () {
    expect(callIsSystemError('Insufficient balance in merchant account'))->toBeFalse()
        ->and(callIsSystemError('Invalid bank account number'))->toBeFalse()
        ->and(callIsSystemError('Recipient account is frozen'))->toBeFalse();
});
