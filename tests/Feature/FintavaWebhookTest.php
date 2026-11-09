<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
