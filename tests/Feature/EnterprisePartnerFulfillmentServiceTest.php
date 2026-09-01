<?php

use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerReferralSettings;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\EnterprisePartnerFulfillmentService;
use App\Services\EnterprisePartnerPaymentService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

/**
 * Builds on makePendingEpPayment()/seedUnassignedPosSims() from
 * EnterprisePartnerPaymentServiceTest.php (shared via Pest's global
 * function namespace) by running confirmPayment() first, so the returned
 * application is genuinely 'awaiting_fulfillment' — exactly the state
 * fulfill() expects.
 */
function makeAwaitingFulfillmentEp(int $quantity = 5, ?User $inviter = null, ?array $items = null): array
{
    [$admin, $ep, $application, $payment] = makePendingEpPayment($quantity, $inviter, $items);
    app(EnterprisePartnerPaymentService::class)->confirmPayment($payment);

    return [$admin, $ep, $application->fresh(), $payment->fresh()];
}

test('fulfilling with the exact requested quantity assigns the exact SIMs, flips the EP to active, and marks the order fulfilled', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedPosSims(5);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 5], $admin);

    expect($ep->fresh()->status)->toBe('active');
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($application->fresh()->fulfilled_at)->not->toBeNull();
    expect($application->fresh()->fulfilled_by)->toBe($admin->id);
    expect($item->fresh()->fulfilled_quantity)->toBe(5);
    expect(Sim::where('business_id', $ep->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS)->count())->toBe(5);
});

test('a partial fulfillment assigns only the admin-chosen quantity, and the order still becomes fulfilled (terminal even when partial)', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(10);
    seedUnassignedPosSims(10);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 4], $admin);

    expect($item->fresh()->fulfilled_quantity)->toBe(4);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(4);
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($ep->fresh()->status)->toBe('active');
});

test('an admin-submitted quantity above what was ordered is clamped down to the requested quantity, never over-assigned', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedPosSims(50);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 999], $admin);

    expect($item->fresh()->fulfilled_quantity)->toBe(5);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(5);
});

test('an admin-chosen quantity above live stock assigns only what is available, without throwing', function () {
    // The dev DB this suite runs against may already hold real unassigned
    // POS SIM/mtn stock — order and request comfortably more than any
    // plausible total so a genuine, exact shortfall is guaranteed regardless
    // of that pre-existing count (established pattern in this test suite).
    $availableNow = Sim::where('status', SimStatus::UNASSIGNED)->where('category', 'POS SIM')->where('provider', 'mtn')->count();
    $orderedQuantity = $availableNow + 50;
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp($orderedQuantity);
    seedUnassignedPosSims(3);
    $totalStock = $availableNow + 3;
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $orderedQuantity], $admin);

    expect($item->fresh()->fulfilled_quantity)->toBe($totalStock);
    expect(Sim::where('business_id', $ep->id)->count())->toBe($totalStock);
    expect($application->fresh()->status)->toBe('fulfilled');
});

test('multiple line items can be fulfilled in one call with mixed full/partial/zero quantities', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(items: [
        ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 10, 'unit_price' => 1500],
        ['sim_category' => 'CAMERA SIM', 'provider' => 'glo', 'quantity' => 10, 'unit_price' => 800],
        ['sim_category' => 'POS SIM', 'provider' => 'airtel', 'quantity' => 10, 'unit_price' => 1500],
    ]);
    seedUnassignedPosSims(10); // POS SIM/mtn — full
    Sim::create(['number' => '0806' . fake()->unique()->numerify('#######'), 'category' => 'CAMERA SIM', 'provider' => 'glo', 'status' => SimStatus::UNASSIGNED]);
    Sim::create(['number' => '0806' . fake()->unique()->numerify('#######'), 'category' => 'CAMERA SIM', 'provider' => 'glo', 'status' => SimStatus::UNASSIGNED]);
    // POS SIM/airtel — zero stock, admin also grants 0

    $items = $application->items;
    $mtnItem = $items->firstWhere('provider', 'mtn');
    $gloItem = $items->firstWhere('provider', 'glo');
    $airtelItem = $items->firstWhere('provider', 'airtel');

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [
        $mtnItem->id => 10,
        $gloItem->id => 2,
        $airtelItem->id => 0,
    ], $admin);

    expect($mtnItem->fresh()->fulfilled_quantity)->toBe(10);
    expect($gloItem->fresh()->fulfilled_quantity)->toBe(2);
    expect($airtelItem->fresh()->fulfilled_quantity)->toBe(0);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(12);
});

test('a zero-quantity item is valid — no SIMs assigned for it, no error, and the order still completes', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedPosSims(5);
    $item = $application->items->first();

    $summary = app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 0], $admin);

    expect($summary[0]['fulfilled'])->toBe(0);
    expect($item->fresh()->fulfilled_quantity)->toBe(0);
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($ep->fresh()->status)->toBe('active');
});

test('fulfilling an already-fulfilled application throws and does not double-assign SIMs or double-pay the referral bonus', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    Wallet::create(['user_id' => $rm->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $rm->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['regional_manager_amount' => 5000, 'is_active' => true]);

    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5, $rm);
    seedUnassignedPosSims(5);
    $item = $application->items->first();

    $service = app(EnterprisePartnerFulfillmentService::class);
    $service->fulfill($application, [$item->id => 5], $admin);

    expect(fn () => $service->fulfill($application->fresh(), [$item->id => 5], $admin))
        ->toThrow(\RuntimeException::class);

    expect(Sim::where('business_id', $ep->id)->count())->toBe(5);
    expect((float) $rm->wallet->fresh()->bonus)->toBe(5000.0);
});

test('fulfilling an application that has not had its payment confirmed yet throws', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(5);
    seedUnassignedPosSims(5);
    $item = $application->items->first();

    expect(fn () => app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 5], $admin))
        ->toThrow(\RuntimeException::class);

    expect(Sim::where('business_id', $ep->id)->count())->toBe(0);
});

test('fulfillment credits the inviting regional_manager the configured referral bonus, only at fulfillment time', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    Wallet::create(['user_id' => $rm->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $rm->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['regional_manager_amount' => 5000, 'is_active' => true]);

    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(2, $rm);
    seedUnassignedPosSims(2);
    $item = $application->items->first();

    expect((float) $rm->wallet->fresh()->bonus)->toBe(0.0);

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 2], $admin);

    expect((float) $rm->wallet->fresh()->bonus)->toBe(5000.0);
    expect(\App\Models\Transaction::where('user_id', $rm->id)->where('type', 'credit')->exists())->toBeTrue();
});

test('an EP with no inviter is fulfilled fine with no referral credit', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(1);
    seedUnassignedPosSims(1);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 1], $admin);

    expect($ep->fresh()->status)->toBe('active');
});

test('a referral bonus is not paid when the settings are inactive', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    Wallet::create(['user_id' => $coordinator->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $coordinator->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['coordinator_amount' => 3000, 'is_active' => false]);

    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(1, $coordinator);
    seedUnassignedPosSims(1);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => 1], $admin);

    expect((float) $coordinator->wallet->fresh()->bonus)->toBe(0.0);
});
