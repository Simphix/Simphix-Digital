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

/** Creates $count UNASSIGNED sims for a category/provider and returns their real ids, in creation order. */
function seedUnassignedSimIds(int $count, string $category = 'POS SIM', string $provider = 'mtn'): array
{
    $ids = [];
    for ($i = 0; $i < $count; $i++) {
        $ids[] = Sim::create([
            'number' => '0808' . fake()->unique()->numerify('#######'),
            'category' => $category,
            'provider' => $provider,
            'status' => SimStatus::UNASSIGNED,
        ])->id;
    }

    return $ids;
}

test('fulfilling with specific sim ids assigns exactly those SIMs, flips the EP to active, and marks the order fulfilled', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);

    expect($ep->fresh()->status)->toBe('active');
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($application->fresh()->fulfilled_at)->not->toBeNull();
    expect($application->fresh()->fulfilled_by)->toBe($admin->id);
    expect($item->fresh()->fulfilled_quantity)->toBe(5);
    expect(Sim::whereIn('id', $simIds)->where('business_id', $ep->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS)->count())->toBe(5);
});

test('a partial fulfillment assigns only the admin-selected SIMs, and the order still becomes fulfilled (terminal even when partial)', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(10);
    $simIds = seedUnassignedSimIds(10);
    $item = $application->items->first();
    $selected = array_slice($simIds, 0, 4);

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $selected], $admin);

    expect($item->fresh()->fulfilled_quantity)->toBe(4);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(4);
    expect(Sim::whereIn('id', $selected)->where('business_id', $ep->id)->count())->toBe(4);
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($ep->fresh()->status)->toBe('active');
});

test('submitting more sim ids than were ordered is capped to the first N by submission order, never over-assigned', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(10);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);

    expect($item->fresh()->fulfilled_quantity)->toBe(5);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(5);
    $firstFive = array_slice($simIds, 0, 5);
    $lastFive = array_slice($simIds, 5, 5);
    expect(Sim::whereIn('id', $firstFive)->where('business_id', $ep->id)->count())->toBe(5);
    expect(Sim::whereIn('id', $lastFive)->where('status', SimStatus::UNASSIGNED)->count())->toBe(5);
});

test('a submitted sim id belonging to a different category/provider is skipped, not assigned, and does not count toward fulfilled_quantity', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $validIds = seedUnassignedSimIds(3);
    $wrongCategoryId = seedUnassignedSimIds(1, 'CAMERA SIM', 'glo')[0];
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => [...$validIds, $wrongCategoryId]], $admin);

    expect($item->fresh()->fulfilled_quantity)->toBe(3);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(3);
    expect(Sim::find($wrongCategoryId)->status)->toBe(SimStatus::UNASSIGNED);
});

test('a sim id that raced away to a non-UNASSIGNED status before fulfill() runs is skipped, not fatal', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(3);
    Sim::find($simIds[0])->update(['status' => SimStatus::ASSIGNED_TO_PARTNER]);
    $item = $application->items->first();

    $summary = app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);

    expect($summary[0]['fulfilled'])->toBe(2);
    expect($item->fresh()->fulfilled_quantity)->toBe(2);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(2);
    expect(Sim::find($simIds[0])->business_id)->toBeNull();
});

test('multiple line items can be fulfilled in one call with mixed full/partial/zero selections', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(items: [
        ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 10, 'unit_price' => 1500],
        ['sim_category' => 'CAMERA SIM', 'provider' => 'glo', 'quantity' => 10, 'unit_price' => 800],
        ['sim_category' => 'POS SIM', 'provider' => 'airtel', 'quantity' => 10, 'unit_price' => 1500],
    ]);
    $mtnIds = seedUnassignedSimIds(10, 'POS SIM', 'mtn'); // full
    $gloIds = seedUnassignedSimIds(2, 'CAMERA SIM', 'glo'); // partial
    // POS SIM/airtel — zero stock, admin also grants 0

    $items = $application->items;
    $mtnItem = $items->firstWhere('provider', 'mtn');
    $gloItem = $items->firstWhere('provider', 'glo');
    $airtelItem = $items->firstWhere('provider', 'airtel');

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [
        $mtnItem->id => $mtnIds,
        $gloItem->id => $gloIds,
        $airtelItem->id => [],
    ], $admin);

    expect($mtnItem->fresh()->fulfilled_quantity)->toBe(10);
    expect($gloItem->fresh()->fulfilled_quantity)->toBe(2);
    expect($airtelItem->fresh()->fulfilled_quantity)->toBe(0);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(12);
});

test('an item with no selected sim ids is valid — no SIMs assigned for it, no error, and the order still completes', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedSimIds(5);
    $item = $application->items->first();

    $summary = app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => []], $admin);

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
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();

    $service = app(EnterprisePartnerFulfillmentService::class);
    $service->fulfill($application, [$item->id => $simIds], $admin);

    expect(fn () => $service->fulfill($application->fresh(), [$item->id => $simIds], $admin))
        ->toThrow(\RuntimeException::class);

    expect(Sim::where('business_id', $ep->id)->count())->toBe(5);
    expect((float) $rm->wallet->fresh()->bonus)->toBe(5000.0);
});

test('fulfilling an application that has not had its payment confirmed yet throws', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(5);
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();

    expect(fn () => app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin))
        ->toThrow(\RuntimeException::class);

    expect(Sim::where('business_id', $ep->id)->count())->toBe(0);
});

test('fulfillment credits the inviting regional_manager the configured referral bonus, only at fulfillment time', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    Wallet::create(['user_id' => $rm->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $rm->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['regional_manager_amount' => 5000, 'is_active' => true]);

    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(2, $rm);
    $simIds = seedUnassignedSimIds(2);
    $item = $application->items->first();

    expect((float) $rm->wallet->fresh()->bonus)->toBe(0.0);

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);

    expect((float) $rm->wallet->fresh()->bonus)->toBe(5000.0);
    expect(\App\Models\Transaction::where('user_id', $rm->id)->where('type', 'credit')->exists())->toBeTrue();
});

test('an EP with no inviter is fulfilled fine with no referral credit', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(1);
    $simIds = seedUnassignedSimIds(1);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);

    expect($ep->fresh()->status)->toBe('active');
});

test('a referral bonus is not paid when the settings are inactive', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    Wallet::create(['user_id' => $coordinator->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $coordinator->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['coordinator_amount' => 3000, 'is_active' => false]);

    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(1, $coordinator);
    $simIds = seedUnassignedSimIds(1);
    $item = $application->items->first();

    app(EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);

    expect((float) $coordinator->wallet->fresh()->bonus)->toBe(0.0);
});
