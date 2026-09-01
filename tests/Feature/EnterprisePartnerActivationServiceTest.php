<?php

use App\Http\Controllers\PaymentWebhookController;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerPayment;
use App\Models\EnterprisePartnerReferralSettings;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WebhookLog;
use App\Services\EnterprisePartnerActivationService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

/**
 * $items defaults to a single POS SIM/mtn line for $quantity, matching the
 * pre-cart single-item shape most of these tests still exercise — pass an
 * explicit $items array (each ['sim_category', 'provider', 'quantity',
 * 'unit_price' => 1500]) to build a multi-item order instead.
 */
function makePendingEpPayment(int $quantity = 5, ?User $inviter = null, ?array $items = null): array
{
    $admin = User::where('role', 'super_admin')->first() ?? User::factory()->create(['role' => 'super_admin']);
    $ep = User::factory()->create(['role' => 'business', 'status' => 'pending_eligibility', 'referred_by' => $inviter?->id]);
    Wallet::create(['user_id' => $ep->id, 'balance' => 0, 'wallet_number' => 'WLT' . str_pad((string) $ep->id, 7, '0', STR_PAD_LEFT)]);

    $items ??= [['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => $quantity, 'unit_price' => 1500]];

    $application = EnterprisePartnerApplication::create([
        'user_id' => $ep->id,
        'total_amount_snapshot' => 0.00,
        'status' => 'pending_payment',
    ]);

    $total = 0.0;
    foreach ($items as $item) {
        $lineTotal = $item['quantity'] * $item['unit_price'];
        $total += $lineTotal;
        EnterprisePartnerApplicationItem::create([
            'application_id' => $application->id,
            'sim_category' => $item['sim_category'],
            'provider' => $item['provider'],
            'quantity' => $item['quantity'],
            'unit_price_snapshot' => $item['unit_price'],
            'line_total_snapshot' => $lineTotal,
        ]);
    }
    $application->update(['total_amount_snapshot' => $total]);

    $payment = EnterprisePartnerPayment::create([
        'application_id' => $application->id,
        'user_id' => $ep->id,
        'expected_amount' => $application->total_amount_snapshot,
        'account_reference' => 'EPP' . strtoupper(fake()->unique()->bothify('??????????')),
        'account_number' => fake()->unique()->numerify('##########'),
        'account_name' => 'Test EP Account',
        'status' => 'pending',
    ]);

    return [$admin, $ep, $application, $payment];
}

function seedUnassignedPosSims(int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        Sim::create([
            'number' => '0805' . fake()->unique()->numerify('#######'),
            'category' => 'POS SIM',
            'provider' => 'mtn',
            'status' => SimStatus::UNASSIGNED,
        ]);
    }
}

test('activation assigns the exact quantity of real SIMs, flips the EP to active, and marks payment/application paid', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(5);
    seedUnassignedPosSims(5);

    app(EnterprisePartnerActivationService::class)->activate($payment);

    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($application->fresh()->status)->toBe('paid');
    expect($ep->fresh()->status)->toBe('active');

    $assignedSims = Sim::where('business_id', $ep->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS)->count();
    expect($assignedSims)->toBe(5);
});

test('activation credits the inviting regional_manager the configured referral bonus', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    Wallet::create(['user_id' => $rm->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $rm->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['regional_manager_amount' => 5000, 'is_active' => true]);

    [$admin, $ep, $application, $payment] = makePendingEpPayment(2, $rm);
    seedUnassignedPosSims(2);

    app(EnterprisePartnerActivationService::class)->activate($payment);

    expect((float) $rm->wallet->fresh()->bonus)->toBe(5000.0);
    expect(\App\Models\Transaction::where('user_id', $rm->id)->where('type', 'credit')->exists())->toBeTrue();
});

test('an EP with no inviter activates fine with no referral credit', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(1);
    seedUnassignedPosSims(1);

    app(EnterprisePartnerActivationService::class)->activate($payment);

    expect($ep->fresh()->status)->toBe('active');
});

test('a referral bonus is not paid when the settings are inactive', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator']);
    Wallet::create(['user_id' => $coordinator->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $coordinator->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['coordinator_amount' => 3000, 'is_active' => false]);

    [$admin, $ep, $application, $payment] = makePendingEpPayment(1, $coordinator);
    seedUnassignedPosSims(1);

    app(EnterprisePartnerActivationService::class)->activate($payment);

    expect((float) $coordinator->wallet->fresh()->bonus)->toBe(0.0);
});

test('activation is idempotent — calling it twice does not double-assign SIMs or double-pay the referral bonus', function () {
    $partner = User::factory()->create(['role' => 'partner']);
    Wallet::create(['user_id' => $partner->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $partner->id, 7, '0', STR_PAD_LEFT)]);
    EnterprisePartnerReferralSettings::current()->update(['partner_amount' => 2000, 'is_active' => true]);

    [$admin, $ep, $application, $payment] = makePendingEpPayment(3, $partner);
    seedUnassignedPosSims(3);

    $service = app(EnterprisePartnerActivationService::class);
    $service->activate($payment);
    $service->activate($payment->fresh());

    expect(Sim::where('business_id', $ep->id)->count())->toBe(3);
    expect((float) $partner->wallet->fresh()->bonus)->toBe(2000.0);
});

test('a stock shortfall still activates the EP with whatever SIMs were available', function () {
    // The dev DB this suite runs against may already hold real unassigned
    // POS SIM/mtn stock, so the "available" count is whatever exists right
    // now — request comfortably more than that to guarantee a real
    // shortfall regardless of that pre-existing count.
    $availableNow = Sim::where('status', SimStatus::UNASSIGNED)->where('category', 'POS SIM')->where('provider', 'mtn')->count();
    [$admin, $ep, $application, $payment] = makePendingEpPayment($availableNow + 3);

    app(EnterprisePartnerActivationService::class)->activate($payment);

    expect($ep->fresh()->status)->toBe('active');
    expect($payment->fresh()->status)->toBe('paid');
    expect(Sim::where('business_id', $ep->id)->count())->toBe($availableNow);
    expect(Sim::where('business_id', $ep->id)->count())->toBeLessThan($application->items->sum('quantity'));
});

test('activation assigns stock correctly across two distinct category/network items, with a shortfall logged only for the item that runs short', function () {
    seedUnassignedPosSims(5); // POS SIM/mtn — plenty

    // The dev DB this suite runs against may already hold real unassigned
    // CAMERA SIM/glo stock — same reasoning as the shortfall test above:
    // request exactly 2 more than whatever already exists, guaranteeing a
    // real, exact shortfall of 2 regardless of that pre-existing count.
    $cameraAvailableNow = Sim::where('status', SimStatus::UNASSIGNED)->where('category', 'CAMERA SIM')->where('provider', 'glo')->count();
    $cameraRequested = $cameraAvailableNow + 2;

    [$admin, $ep, $application, $payment] = makePendingEpPayment(items: [
        ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 5, 'unit_price' => 1500],
        ['sim_category' => 'CAMERA SIM', 'provider' => 'glo', 'quantity' => $cameraRequested, 'unit_price' => 800],
    ]);

    app(EnterprisePartnerActivationService::class)->activate($payment);

    expect($ep->fresh()->status)->toBe('active');
    expect(Sim::where('business_id', $ep->id)->where('category', 'POS SIM')->count())->toBe(5);
    expect(Sim::where('business_id', $ep->id)->where('category', 'CAMERA SIM')->count())->toBe($cameraAvailableNow);
});

test('the webhook fallback activates a matching EP payment on a sufficient, successful payin', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(1);
    seedUnassignedPosSims(1);

    $controller = app(PaymentWebhookController::class);
    $log = WebhookLog::create(['provider' => 'palmpay', 'event_type' => 'payin', 'payload' => [], 'status' => 'received']);

    $payload = [
        'virtualAccountNo' => $payment->account_number,
        'merchantOrderNo' => 'EP-ORDER-' . uniqid(),
        'orderAmount' => (int) ($payment->expected_amount * 100),
        'orderStatus' => 1,
        'payerBankName' => 'Test Bank',
        'payerAccountName' => 'Test Payer',
    ];

    $controller->processAndLog($payload, $log);

    expect($log->fresh()->status)->toBe('success');
    expect($payment->fresh()->status)->toBe('paid');
    expect($ep->fresh()->status)->toBe('active');
});

test('the webhook fallback does not activate on an insufficient payment amount', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(1);
    seedUnassignedPosSims(1);

    $controller = app(PaymentWebhookController::class);
    $log = WebhookLog::create(['provider' => 'palmpay', 'event_type' => 'payin', 'payload' => [], 'status' => 'received']);

    $payload = [
        'virtualAccountNo' => $payment->account_number,
        'merchantOrderNo' => 'EP-ORDER-' . uniqid(),
        'orderAmount' => 100, // ₦1.00 — far short of the expected amount
        'orderStatus' => 1,
        'payerBankName' => 'Test Bank',
        'payerAccountName' => 'Test Payer',
    ];

    $controller->processAndLog($payload, $log);

    expect($payment->fresh()->status)->toBe('pending');
    expect($ep->fresh()->status)->toBe('pending_eligibility');
});
