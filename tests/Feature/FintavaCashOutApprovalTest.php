<?php

use App\Models\CashOutRequest;
use App\Models\PlatformCashOutRequest;
use App\Models\PlatformWallet;
use App\Models\Report;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(DatabaseTransactions::class);

function pendingCashOut(User $user, float $amount = 5000, float $totalCharge = 5000): CashOutRequest
{
    $ref = 'WDL' . strtoupper(uniqid());

    Transaction::create([
        'transaction_ref' => $ref,
        'user_id' => $user->id,
        'amount' => $totalCharge,
        'fee' => 0,
        'net_amount' => $totalCharge,
        'description' => 'Cash out',
        'type' => 'debit',
        'status' => 'pending',
        'performed_by' => $user->first_name,
    ]);

    Report::create([
        'user_id' => $user->id,
        'phone_number' => $user->phone_number ?? '08012345678',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
        'bank_code' => '000013',
        'bank_name' => 'GTBANK',
        'network' => 'Withdrawal',
        'ref' => $ref,
        'amount' => $totalCharge,
        'status' => 'pending',
        'type' => 'withdrawal',
        'description' => 'Cash out',
        'old_balance' => 10000,
        'new_balance' => 10000 - $totalCharge,
    ]);

    return CashOutRequest::create([
        'user_id' => $user->id,
        'transaction_ref' => $ref,
        'bank_code' => '000013',
        'bank_name' => 'GTBANK',
        'account_no' => '0123456789',
        'account_name' => 'Jane Doe',
        'amount' => $amount,
        'fee' => 0,
        'tax' => 0,
        'total_charge' => $totalCharge,
        'status' => 'pending',
    ]);
}

beforeEach(function () {
    Cache::flush();
    config(['services.fintava.api_key' => 'test-key', 'services.fintava.master_source_id' => 'master-src-1']);
});

test('a successful bank/credit approves the request, completes the transaction, and stores provider_reference', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $cashOutRequest = pendingCashOut($user);

    Http::fake(['*/bank/credit*' => Http::response([
        'status' => 200,
        'message' => 'successful',
        'data' => ['reference' => 'FTV-REF-001'],
    ], 200)]);

    $response = $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest));

    $response->assertRedirect()->assertSessionHas('success');
    expect($cashOutRequest->fresh()->status)->toBe('approved');

    $transaction = Transaction::where('transaction_ref', $cashOutRequest->transaction_ref)->firstOrFail();
    expect($transaction->status)->toBe('completed');
    expect($transaction->provider_reference)->toBe('FTV-REF-001');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'bank/credit')
        && $request['sourceId'] === 'master-src-1'
        && $request['accountNumber'] === '0123456789');
});

test('an explicit Fintava rejection refunds the wallet and marks the request rejected', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 400, 'message' => 'Insufficient funds in source account'], 400)]);

    $response = $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest));

    $response->assertRedirect()->assertSessionHas('error');
    expect($cashOutRequest->fresh()->status)->toBe('rejected');
    expect(Transaction::where('transaction_ref', $cashOutRequest->transaction_ref)->first()->status)->toBe('failed');
    expect($wallet->fresh()->balance)->toEqualWithDelta(6000, 0.01); // 1000 + 5000 refunded
});

test('a system/auth error leaves the request pending and does not refund', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 401, 'message' => 'Invalid authorization token'], 401)]);

    $response = $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest));

    $response->assertRedirect()->assertSessionHas('error');
    expect($cashOutRequest->fresh()->status)->toBe('pending');
    expect($wallet->fresh()->balance)->toEqualWithDelta(1000, 0.01); // untouched
});

test('a connection exception leaves the request pending and does not refund (ambiguous outcome)', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Timed out');
    });

    $response = $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest));

    $response->assertRedirect()->assertSessionHas('success'); // "awaiting final confirmation" message
    expect($cashOutRequest->fresh()->status)->toBe('pending');
    expect($wallet->fresh()->balance)->toEqualWithDelta(1000, 0.01); // untouched
});

test('rejecting a pending cash out request refunds the wallet', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    $response = $this->actingAs($admin)->post(route('admin.cash-out.reject', $cashOutRequest), ['admin_notes' => 'Bad account.']);

    $response->assertRedirect()->assertSessionHas('success');
    expect($cashOutRequest->fresh()->status)->toBe('rejected');
    expect($wallet->fresh()->balance)->toEqualWithDelta(6000, 0.01);
});

// -----------------------------------------------------------------------
// Platform cash-out approvals — identical master sourceId, PlatformWalletLedger refund path
// -----------------------------------------------------------------------

test('platform cash-out approval debits the same configured master sourceId', function () {
    $requester = User::factory()->create(['role' => 'super_admin']);
    $approver = User::factory()->create(['role' => 'super_admin']);
    $wallet = PlatformWallet::create(['type' => PlatformWallet::TYPE_WELFARE, 'name' => 'Welfare Wallet', 'balance' => 20000]);

    $platformCashOutRequest = PlatformCashOutRequest::create([
        'platform_wallet_id' => $wallet->id,
        'bank_code' => '000013',
        'bank_name' => 'GTBANK',
        'account_no' => '0123456789',
        'account_name' => 'Company Ltd',
        'amount' => 5000,
        'status' => 'pending',
        'requested_by' => $requester->id,
    ]);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 200, 'message' => 'successful', 'data' => ['reference' => 'FTV-PLAT-001']], 200)]);

    $response = $this->actingAs($approver)->post(route('admin.platform-wallets.approvals.approve', $platformCashOutRequest));

    $response->assertRedirect()->assertSessionHas('success');
    expect($platformCashOutRequest->fresh()->status)->toBe('approved');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'bank/credit') && $request['sourceId'] === 'master-src-1');
});

test('platform cash-out rejection refunds the platform wallet via the ledger', function () {
    $requester = User::factory()->create(['role' => 'super_admin']);
    $approver = User::factory()->create(['role' => 'super_admin']);
    $wallet = PlatformWallet::create(['type' => PlatformWallet::TYPE_WELFARE, 'name' => 'Welfare Wallet', 'balance' => 20000]);

    $platformCashOutRequest = PlatformCashOutRequest::create([
        'platform_wallet_id' => $wallet->id,
        'bank_code' => '000013',
        'bank_name' => 'GTBANK',
        'account_no' => '0123456789',
        'account_name' => 'Company Ltd',
        'amount' => 5000,
        'status' => 'pending',
        'requested_by' => $requester->id,
    ]);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 400, 'message' => 'Rejected'], 400)]);

    $response = $this->actingAs($approver)->post(route('admin.platform-wallets.approvals.approve', $platformCashOutRequest));

    $response->assertRedirect()->assertSessionHas('error');
    expect($platformCashOutRequest->fresh()->status)->toBe('rejected');
    expect($wallet->fresh()->balance)->toEqualWithDelta(25000, 0.01);
});

function platformCashOut(): array
{
    $requester = User::factory()->create(['role' => 'super_admin']);
    $approver = User::factory()->create(['role' => 'super_admin']);
    $wallet = PlatformWallet::firstOrCreate(['type' => PlatformWallet::TYPE_WELFARE], ['name' => 'Welfare Wallet', 'balance' => 20000]);

    $request = PlatformCashOutRequest::create([
        'platform_wallet_id' => $wallet->id,
        'bank_code' => '000013',
        'bank_name' => 'GTBANK',
        'account_no' => '0123456789',
        'account_name' => 'Company Ltd',
        'amount' => 5000,
        'status' => 'pending',
        'requested_by' => $requester->id,
    ]);

    return [$approver, $request, $wallet];
}

test('a 201 bank/credit is a success, not a rejection with a refund', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 201, 'message' => 'created', 'data' => ['reference' => 'FTV-201']], 201)]);

    $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest))->assertSessionHas('success');

    expect($cashOutRequest->fresh()->status)->toBe('approved');
    expect(Transaction::where('transaction_ref', $cashOutRequest->transaction_ref)->first()->provider_reference)->toBe('FTV-201');
    expect($wallet->fresh()->balance)->toEqualWithDelta(1000, 0.01); // no refund
});

test('an unrecognised 2xx status leaves the cash-out pending and does not refund', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 202, 'message' => 'accepted'], 202)]);

    $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest))->assertSessionHas('error');

    expect($cashOutRequest->fresh()->status)->toBe('pending');
    expect($wallet->fresh()->balance)->toEqualWithDelta(1000, 0.01);
});

test('a list-shaped rejection message no longer crashes the approval and still refunds', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['role' => 'coordinator']);
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 1000]);
    $cashOutRequest = pendingCashOut($user, 5000, 5000);

    Http::fake(['*/bank/credit*' => Http::response(['status' => 400, 'message' => ['Insufficient funds in source account']], 400)]);

    $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutRequest))->assertSessionHas('error');

    expect($cashOutRequest->fresh()->status)->toBe('rejected');
    expect($cashOutRequest->fresh()->admin_notes)->toBe('Insufficient funds in source account');
    expect($wallet->fresh()->balance)->toEqualWithDelta(6000, 0.01);
});

test('platform cash-out: a 201 approves', function () {
    [$approver, $request] = platformCashOut();
    Http::fake(['*/bank/credit*' => Http::response(['status' => 201, 'message' => 'created'], 201)]);

    $this->actingAs($approver)->post(route('admin.platform-wallets.approvals.approve', $request))->assertSessionHas('success');

    expect($request->fresh()->status)->toBe('approved');
});

test('platform cash-out: an unrecognised 2xx stays pending without refund', function () {
    [$approver, $request, $wallet] = platformCashOut();
    Http::fake(['*/bank/credit*' => Http::response(['status' => 202, 'message' => 'accepted'], 202)]);

    $this->actingAs($approver)->post(route('admin.platform-wallets.approvals.approve', $request))->assertSessionHas('error');

    expect($request->fresh()->status)->toBe('pending');
    expect($wallet->fresh()->balance)->toEqualWithDelta(20000, 0.01);
});
