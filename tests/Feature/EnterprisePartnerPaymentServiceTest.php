<?php

use App\Http\Controllers\PaymentWebhookController;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerPayment;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WebhookLog;
use App\Services\EnterprisePartnerPaymentService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

/**
 * Shared across this file and EnterprisePartnerFulfillmentServiceTest.php
 * (Pest loads every test file's top-level functions into one global
 * namespace, so this is declared exactly once here — do not redeclare it).
 * $items defaults to a single POS SIM/mtn line for $quantity — pass an
 * explicit $items array (each ['sim_category', 'provider', 'quantity',
 * 'unit_price' => 1500]) to build a multi-item order instead. The returned
 * $admin is the fulfillment tests' acting super_admin — unused by the
 * payment-confirmation tests in this file, which no longer look one up
 * internally (that lookup moved to the fulfillment controller/service).
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

test('confirming payment flips payment to paid, application to awaiting_fulfillment, and the EP to pending_fulfillment — without assigning any SIMs or paying a referral bonus', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    Wallet::create(['user_id' => $rm->id, 'balance' => 0, 'bonus' => 0, 'wallet_number' => 'WLT' . str_pad((string) $rm->id, 7, '0', STR_PAD_LEFT)]);

    [$admin, $ep, $application, $payment] = makePendingEpPayment(5, $rm);
    seedUnassignedPosSims(5);

    app(EnterprisePartnerPaymentService::class)->confirmPayment($payment);

    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($application->fresh()->status)->toBe('awaiting_fulfillment');
    expect($application->fresh()->paid_at)->not->toBeNull();
    expect($ep->fresh()->status)->toBe('pending_fulfillment');

    expect(Sim::where('business_id', $ep->id)->count())->toBe(0);
    expect($application->fresh()->items->sum('fulfilled_quantity'))->toBe(0);
    expect((float) $rm->wallet->fresh()->bonus)->toBe(0.0);
});

test('confirming payment is idempotent — calling it twice does not double-flip or re-run side effects', function () {
    [$admin, $ep, $application, $payment] = makePendingEpPayment(3);
    seedUnassignedPosSims(3);

    $service = app(EnterprisePartnerPaymentService::class);
    $service->confirmPayment($payment);
    $paidAt = $payment->fresh()->paid_at;

    $service->confirmPayment($payment->fresh());

    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
    expect($application->fresh()->status)->toBe('awaiting_fulfillment');
    expect(Sim::where('business_id', $ep->id)->count())->toBe(0);
});

test('the webhook fallback confirms a matching EP payment on a sufficient, successful payin', function () {
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
    expect($application->fresh()->status)->toBe('awaiting_fulfillment');
    expect($ep->fresh()->status)->toBe('pending_fulfillment');
    expect(Sim::where('business_id', $ep->id)->count())->toBe(0);
});

test('the webhook fallback does not confirm on an insufficient payment amount', function () {
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
