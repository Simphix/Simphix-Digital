<?php

use App\Models\CashoutSettings;
use App\Models\FintavaWalletTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(DatabaseTransactions::class);

function fintavaWebhookRequest(array $payload, string $secret = 'test-webhook-secret')
{
    $body = json_encode($payload);
    $signature = hash_hmac('sha512', $body, $secret);

    return [$body, $signature];
}

beforeEach(function () {
    config(['services.fintava.webhook_secret' => 'test-webhook-secret']);
});

// -----------------------------------------------------------------------
// Signature verification
// -----------------------------------------------------------------------

test('a webhook with a valid signature is accepted', function () {
    $payload = ['event' => 'account_funded', 'data' => ['accountNumber' => 'NONEXISTENT', 'reference' => 'REF1', 'amount' => '100.00', 'status' => 'success']];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body);

    $response->assertOk();
    expect(WebhookLog::where('provider', 'fintava')->latest()->first()->status)->not->toBe('invalid_signature');
});

test('a webhook with an invalid signature is rejected with 401', function () {
    $payload = ['event' => 'account_funded', 'data' => ['accountNumber' => 'X', 'reference' => 'REF1', 'amount' => '100.00', 'status' => 'success']];
    [$body] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => 'clearly-wrong-signature',
    ], $body);

    $response->assertStatus(401);
    expect(WebhookLog::where('provider', 'fintava')->latest()->first()->status)->toBe('invalid_signature');
});

// -----------------------------------------------------------------------
// account_funded — deposit
// -----------------------------------------------------------------------

test('account_funded credits the wallet and creates a completed transaction', function () {
    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 500]);
    VirtualAccount::create([
        'user_id' => $user->id,
        'account_reference' => 'cust-1',
        'account_number' => '1234567890',
        'account_name' => $user->first_name . ' ' . $user->last_name,
        'bank_name' => 'Loma Bank',
        'provider' => 'fintava',
        'is_active' => true,
    ]);

    Mail::fake();

    $payload = ['event' => 'account_funded', 'data' => [
        'accountNumber' => '1234567890',
        'reference' => 'DEP-REF-001',
        'amount' => '1500.00',
        'status' => 'success',
        'accountName' => 'Sender Name',
    ]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body);

    $response->assertOk();
    expect($wallet->fresh()->balance)->toEqualWithDelta(2000, 0.01);

    $transaction = Transaction::where('provider_reference', 'DEP-REF-001')->firstOrFail();
    expect($transaction->status)->toBe('completed');
    expect($transaction->type)->toBe('credit');
    expect((float) $transaction->amount)->toEqualWithDelta(1500, 0.01);
});

test('account_funded is idempotent — a duplicate delivery does not double-credit the wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 0]);
    VirtualAccount::create([
        'user_id' => $user->id,
        'account_reference' => 'cust-2',
        'account_number' => '2222222222',
        'account_name' => $user->first_name . ' ' . $user->last_name,
        'bank_name' => 'Loma Bank',
        'provider' => 'fintava',
        'is_active' => true,
    ]);

    Mail::fake();

    $payload = ['event' => 'account_funded', 'data' => [
        'accountNumber' => '2222222222',
        'reference' => 'DEP-REF-DUP',
        'amount' => '1000.00',
        'status' => 'success',
        'accountName' => 'Sender Name',
    ]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    // Deliver twice.
    $this->call('POST', route('fintava.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_x-fintava-signature' => $signature], $body);
    $this->call('POST', route('fintava.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_x-fintava-signature' => $signature], $body);

    expect($wallet->fresh()->balance)->toEqualWithDelta(1000, 0.01);
    expect(Transaction::where('provider_reference', 'DEP-REF-DUP')->count())->toBe(1);
});

test('account_funded for an unknown virtual account is logged as unmatched and returns 200', function () {
    $payload = ['event' => 'account_funded', 'data' => [
        'accountNumber' => 'NOT-REGISTERED',
        'reference' => 'DEP-REF-ORPHAN',
        'amount' => '500.00',
        'status' => 'success',
    ]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body);

    $response->assertOk();
    expect(Transaction::where('provider_reference', 'DEP-REF-ORPHAN')->exists())->toBeFalse();
    expect(WebhookLog::where('provider', 'fintava')->latest()->first()->status)->toBe('unmatched');
});

// -----------------------------------------------------------------------
// customer_bank_transfer — payout status
// -----------------------------------------------------------------------

test('customer_bank_transfer marks a matching transaction completed on SUCCESS', function () {
    $user = User::factory()->create();
    Transaction::create([
        'transaction_ref' => 'WDLTEST001',
        'provider_reference' => 'PAYOUT-REF-001',
        'user_id' => $user->id,
        'amount' => 3000,
        'fee' => 0,
        'net_amount' => 3000,
        'type' => 'debit',
        'status' => 'pending',
        'performed_by' => 'System',
    ]);

    $payload = ['event' => 'customer_bank_transfer', 'data' => ['reference' => 'PAYOUT-REF-001', 'status' => 'SUCCESS', 'amount' => 3000]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $this->call('POST', route('fintava.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_x-fintava-signature' => $signature], $body)
        ->assertOk();

    expect(Transaction::where('provider_reference', 'PAYOUT-REF-001')->first()->status)->toBe('completed');
});

test('customer_bank_transfer marks a matching transaction failed on a non-success status', function () {
    $user = User::factory()->create();
    Transaction::create([
        'transaction_ref' => 'WDLTEST002',
        'provider_reference' => 'PAYOUT-REF-002',
        'user_id' => $user->id,
        'amount' => 3000,
        'fee' => 0,
        'net_amount' => 3000,
        'type' => 'debit',
        'status' => 'pending',
        'performed_by' => 'System',
    ]);

    $payload = ['event' => 'customer_bank_transfer', 'data' => ['reference' => 'PAYOUT-REF-002', 'status' => 'FAILED', 'amount' => 3000]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $this->call('POST', route('fintava.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_x-fintava-signature' => $signature], $body)
        ->assertOk();

    expect(Transaction::where('provider_reference', 'PAYOUT-REF-002')->first()->status)->toBe('failed');
});

// -----------------------------------------------------------------------
// debit_transfer_reversal — reversal refund
// -----------------------------------------------------------------------

test('debit_transfer_reversal flips a completed transaction to reversed and refunds the wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);

    Transaction::create([
        'transaction_ref' => 'WDLTEST003',
        'provider_reference' => 'PAYOUT-REF-003',
        'user_id' => $user->id,
        'amount' => 3000,
        'fee' => 0,
        'net_amount' => 3000,
        'type' => 'debit',
        'status' => 'completed',
        'performed_by' => 'System',
    ]);

    $payload = ['event' => 'debit_transfer_reversal', 'data' => ['transactionReference' => 'PAYOUT-REF-003', 'amount' => 3000, 'status' => 'success']];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $this->call('POST', route('fintava.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_x-fintava-signature' => $signature], $body)
        ->assertOk();

    expect(Transaction::where('provider_reference', 'PAYOUT-REF-003')->first()->status)->toBe('reversed');
    expect($wallet->fresh()->balance)->toEqualWithDelta(4000, 0.01);
});

test('debit_transfer_reversal is a no-op if the transaction is already reversed', function () {
    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);

    Transaction::create([
        'transaction_ref' => 'WDLTEST004',
        'provider_reference' => 'PAYOUT-REF-004',
        'user_id' => $user->id,
        'amount' => 3000,
        'fee' => 0,
        'net_amount' => 3000,
        'type' => 'debit',
        'status' => 'reversed',
        'performed_by' => 'System',
    ]);

    $payload = ['event' => 'debit_transfer_reversal', 'data' => ['transactionReference' => 'PAYOUT-REF-004', 'amount' => 3000, 'status' => 'success']];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $this->call('POST', route('fintava.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_x-fintava-signature' => $signature], $body)
        ->assertOk();

    // Balance untouched — not refunded a second time.
    expect($wallet->fresh()->balance)->toEqualWithDelta(1000, 0.01);
});

// -----------------------------------------------------------------------
// Unhandled event types — acknowledged, not processed
// -----------------------------------------------------------------------

test('an unhandled event type is acknowledged with 200 but not processed as a deposit', function () {
    $payload = ['event' => 'card_payment', 'data' => ['amount' => 500]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body);

    $response->assertOk();
    expect(WebhookLog::where('provider', 'fintava')->latest()->first()->status)->toBe('ignored');
});

// -----------------------------------------------------------------------
// Inflow charges & Wallet-to-Wallet sweep audits
// -----------------------------------------------------------------------

test('account_funded applies configurable inflow fee (0.3% capped at 300) without charging customer for wallet-to-wallet transfer', function () {
    config([
        'services.fintava.merchant_account' => '0099887766',
        'services.fintava.api_key' => 'test-api-key',
    ]);

    $settings = CashoutSettings::current();
    $settings->update([
        'inflow_fee' => 0.30,
        'inflow_fee_type' => 'percent',
        'inflow_fee_cap' => 300.00,
        'inflow_fee_description' => 'Wallet Funding Fee',
        'inflow_threshold_amount' => 999999.00, // keep threshold inactive for this test
        'inflow_threshold_fee' => 0.00,
    ]);

    Http::fake([
        '*transaction/wallet-to-wallet*' => Http::response([
            'status' => 200,
            'message' => 'Wallet transfer successful',
            'data' => [
                'reference' => 'FNT-W2W-12345',
                'transactionReference' => 'FNT-W2W-12345',
            ],
        ], 200),
    ]);

    Mail::fake();

    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 0.00]);

    $va = VirtualAccount::create([
        'user_id' => $user->id,
        'account_reference' => 'cust-w2w-1',
        'account_number' => '1122334455',
        'account_name' => $user->first_name . ' ' . $user->last_name,
        'bank_name' => 'Loma Bank',
        'provider' => 'fintava',
        'is_active' => true,
    ]);

    // Deposit ₦10,000. 0.3% of 10,000 = ₦30 fee.
    $payload = ['event' => 'account_funded', 'data' => [
        'accountNumber' => '1122334455',
        'reference' => 'DEP-SWEEP-001',
        'amount' => '10000.00',
        'status' => 'success',
        'accountName' => 'Alice Sender',
    ]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body);

    $response->assertOk();

    // 1. Verify customer platform level balance: full 10,000 credited (NOT charged on platform)
    expect((float) $wallet->fresh()->balance)->toBe(10000.00);

    // 2. Verify no fee was debited from customer's platform account
    $feeTx = Transaction::where('user_id', $user->id)->where('type', 'debit')->first();
    expect($feeTx)->toBeNull();

    // 3. Verify Fintava wallet-to-wallet transfer API was called with net sweep amount (10,000 - 30 fee = 9,970)
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'transaction/wallet-to-wallet')
            && $request['senderAccount'] === '1122334455'
            && $request['receiverAccount'] === '0099887766'
            && (float) $request['amount'] === 9970.00
            && $request['CustomerReference'] === 'W2W-DEP-SWEEP-001';
    });

    // 4. Verify FintavaWalletTransfer audit record
    $transfer = FintavaWalletTransfer::where('deposit_reference', 'DEP-SWEEP-001')->first();
    expect($transfer)->not->toBeNull();
    expect($transfer->status)->toBe('completed');
    expect($transfer->sender_account)->toBe('1122334455');
    expect($transfer->receiver_account)->toBe('0099887766');
    expect((float) $transfer->amount)->toBe(9970.00); // ₦9,970 swept to merchant
    expect((float) $transfer->fee_charged)->toBe(30.00); // ₦30 Fintava fee accounted for
    expect((float) $transfer->net_amount)->toBe(10000.00); // ₦10,000 total deposit
    expect($transfer->provider_reference)->toBe('FNT-W2W-12345');

    // 5. Verify deposit transaction metadata contains sweep audit
    $depositTx = Transaction::where('provider_reference', 'DEP-SWEEP-001')->first();
    expect($depositTx->metadata['fintava_sweep']['status'])->toBe('completed');
    expect((float) $depositTx->metadata['fintava_sweep']['amount_swept'])->toBe(9970.00);
    expect((float) $depositTx->metadata['fintava_sweep']['fintava_fee'])->toBe(30.00);
});

test('inflow charge percentage is capped at ₦300 on large deposits', function () {
    config([
        'services.fintava.merchant_account' => '0099887766',
        'services.fintava.api_key' => 'test-api-key',
    ]);

    $settings = CashoutSettings::current();
    $settings->update([
        'inflow_fee' => 0.30,
        'inflow_fee_type' => 'percent',
        'inflow_fee_cap' => 300.00,
        'inflow_fee_description' => 'Wallet Funding Fee',
        'inflow_threshold_amount' => null,
        'inflow_threshold_fee' => 0.00,
    ]);

    Http::fake([
        '*transaction/wallet-to-wallet*' => Http::response(['status' => 200, 'message' => 'OK', 'data' => ['reference' => 'W2W-CAP-1']], 200),
    ]);

    Mail::fake();

    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 0.00]);

    VirtualAccount::create([
        'user_id' => $user->id,
        'account_reference' => 'cust-w2w-cap',
        'account_number' => '5544332211',
        'account_name' => $user->first_name . ' ' . $user->last_name,
        'bank_name' => 'Loma Bank',
        'provider' => 'fintava',
        'is_active' => true,
    ]);

    // Deposit ₦200,000. 0.3% of 200,000 = ₦600. Capped at ₦300.
    $payload = ['event' => 'account_funded', 'data' => [
        'accountNumber' => '5544332211',
        'reference' => 'DEP-CAP-001',
        'amount' => '200000.00',
        'status' => 'success',
        'accountName' => 'Bob Sender',
    ]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body)->assertOk();

    // Customer platform balance gets full 200,000
    expect((float) $wallet->fresh()->balance)->toBe(200000.00);

    // Sweep amount transferred to merchant wallet is 200,000 - 300 (cap) = 199,700
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'transaction/wallet-to-wallet')
            && (float) $request['amount'] === 199700.00;
    });

    $transfer = FintavaWalletTransfer::where('deposit_reference', 'DEP-CAP-001')->first();
    expect((float) $transfer->amount)->toBe(199700.00);
    expect((float) $transfer->fee_charged)->toBe(300.00);
});

test('fintava wallet-to-wallet failure is audited as failed without breaking user credit or webhook acknowledgement', function () {
    config([
        'services.fintava.merchant_account' => '0099887766',
        'services.fintava.api_key' => 'test-api-key',
    ]);

    $settings = CashoutSettings::current();
    $settings->update([
        'inflow_fee' => 0.00,
        'inflow_threshold_amount' => null,
    ]);

    // Simulate Fintava BaaS rejecting the wallet-to-wallet transfer
    Http::fake([
        '*transaction/wallet-to-wallet*' => Http::response(['status' => 400, 'message' => 'Insufficient funds in sender account'], 400),
    ]);

    Mail::fake();

    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 0.00]);

    VirtualAccount::create([
        'user_id' => $user->id,
        'account_reference' => 'cust-w2w-fail',
        'account_number' => '7788990011',
        'account_name' => $user->first_name . ' ' . $user->last_name,
        'bank_name' => 'Loma Bank',
        'provider' => 'fintava',
        'is_active' => true,
    ]);

    $payload = ['event' => 'account_funded', 'data' => [
        'accountNumber' => '7788990011',
        'reference' => 'DEP-FAIL-001',
        'amount' => '5000.00',
        'status' => 'success',
        'accountName' => 'Charlie Sender',
    ]];
    [$body, $signature] = fintavaWebhookRequest($payload);

    $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_x-fintava-signature' => $signature,
    ], $body);

    $response->assertOk();

    // User is still credited their money (the money arrived at Fintava)
    expect((float) $wallet->fresh()->balance)->toBe(5000.00);

    // Audit transfer record tracks the failure and exact error message
    $transfer = FintavaWalletTransfer::where('deposit_reference', 'DEP-FAIL-001')->first();
    expect($transfer)->not->toBeNull();
    expect($transfer->status)->toBe('failed');
    expect($transfer->error_message)->toContain('Insufficient funds');
});

test('super admin can view fintava wallet sweeps and retry a failed sweep', function () {
    config(['services.fintava.api_key' => 'test-api-key']);
    $admin = User::factory()->create(['role' => 'super_admin']);

    $sweep = FintavaWalletTransfer::create([
        'deposit_reference' => 'DEP-RETRY-01',
        'customer_reference' => 'W2W-DEP-RETRY-01',
        'sender_account' => '1234567890',
        'receiver_account' => '9876543210',
        'amount' => 5000.00,
        'fee_charged' => 0.00,
        'net_amount' => 5000.00,
        'status' => 'failed',
        'error_message' => 'Network timeout',
    ]);

    // Admin views sweeps index
    $this->actingAs($admin)
        ->get(route('admin.fintava-sweeps.index'))
        ->assertOk()
        ->assertSee('W2W-DEP-RETRY-01')
        ->assertSee('Failed');

    // Admin retries sweep with success
    Http::fake([
        '*transaction/wallet-to-wallet*' => Http::response([
            'status' => 200,
            'message' => 'Success',
            'data' => ['reference' => 'FNT-RETRY-SUCCESS'],
        ], 200),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.fintava-sweeps.retry', $sweep->id))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($sweep->fresh()->status)->toBe('completed');
    expect($sweep->fresh()->provider_reference)->toBe('FNT-RETRY-SUCCESS');
});
