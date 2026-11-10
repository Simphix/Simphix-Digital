<?php

use App\Models\FintavaWalletTransfer;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Services\FintavaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config([
        'services.fintava.base_url'         => 'https://dev.fintavapay.com/api/dev',
        'services.fintava.api_key'          => 'test-fintava-key',
        'services.fintava.merchant_account' => '0123456789',
    ]);
});

function makeVirtualAccount(array $attributes = []): VirtualAccount
{
    $user = isset($attributes['user_id']) ? User::find($attributes['user_id']) : User::factory()->create();

    return VirtualAccount::create(array_merge([
        'user_id'              => $user->id,
        'account_reference'    => 'ref-' . uniqid(),
        'provider'             => 'fintava',
        'account_number'       => (string) rand(1000000000, 9999999999),
        'account_name'         => $user->first_name . ' ' . $user->last_name,
        'bank_name'            => 'Loma Bank',
        'provider_customer_id' => 'cust-' . uniqid(),
        'provider_wallet_id'   => 'wall-' . uniqid(),
        'is_active'            => true,
    ], $attributes));
}

test('non-super_admin users cannot access customer fintava account routes', function () {
    $regularUser = User::factory()->create(['role' => 'user']);
    $virtualAccount = makeVirtualAccount();

    $this->actingAs($regularUser)
        ->get(route('admin.fintava-accounts.index'))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('admin.fintava-accounts.show', $virtualAccount))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->post(route('admin.fintava-accounts.transfer', $virtualAccount), [
            'amount' => 1000,
        ])
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('admin.fintava-accounts.export', $virtualAccount))
        ->assertForbidden();
});

test('super_admin can view fintava accounts index and search', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@example.com']);
    $va = makeVirtualAccount([
        'user_id'              => $user->id,
        'provider'             => 'fintava',
        'account_number'       => '1122334455',
        'account_name'         => 'John Doe',
        'provider_customer_id' => 'cust-12345',
        'provider_wallet_id'   => 'wall-67890',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.fintava-accounts.index', ['search' => '1122334455']));

    // When exact match is found, index redirects directly to show
    $response->assertRedirect(route('admin.fintava-accounts.show', $va));
});

test('super_admin can inspect live balance and transactions for a customer fintava account', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    $va = makeVirtualAccount([
        'user_id'              => $user->id,
        'provider'             => 'fintava',
        'account_number'       => '9988776655',
        'account_name'         => 'Jane Smith',
        'provider_customer_id' => 'cust-jane',
        'provider_wallet_id'   => 'wall-jane',
    ]);

    Http::fake([
        'https://dev.fintavapay.com/api/dev/customer/wallet/balance/wall-jane' => Http::response([
            'status'  => 200,
            'message' => 'Wallet balance fetched',
            'data'    => [
                'balance'       => 15500.50,
                'ledgerBalance' => 15500.50,
            ],
        ], 200),
        'https://dev.fintavapay.com/api/dev/txn*' => Http::response([
            'status'  => 200,
            'message' => 'Transactions fetched',
            'data'    => [
                [
                    'reference'      => 'TXN-001',
                    'type'           => 'credit',
                    'amount'         => 15500.50,
                    'status'         => 'successful',
                    'narration'      => 'Inflow transfer',
                    'senderAccount'  => '1234567890',
                    'receiverAccount'=> '9988776655',
                    'createdAt'      => '2026-11-10T12:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.fintava-accounts.show', $va));

    $response->assertOk()
        ->assertSee('₦15,500.50')
        ->assertSee('TXN-001')
        ->assertSee('Inflow transfer')
        ->assertSee('9988776655');
});

test('super_admin can transfer funds from customer fintava account to merchant wallet without debiting platform balance', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create();
    $wallet = Wallet::firstOrCreateForUser($user->id);
    $wallet->update(['balance' => 50000.00]);

    $va = makeVirtualAccount([
        'user_id'              => $user->id,
        'provider'             => 'fintava',
        'account_number'       => '9988776655',
        'account_name'         => 'Test Account',
        'provider_customer_id' => 'cust-test',
        'provider_wallet_id'   => 'wall-test',
    ]);

    Http::fake([
        'https://dev.fintavapay.com/api/dev/transaction/wallet-to-wallet' => Http::response([
            'status'  => 200,
            'message' => 'Transfer completed successfully',
            'data'    => [
                'reference' => 'FINTAVA-TX-999',
                'amount'    => 10000.00,
            ],
        ], 200),
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.fintava-accounts.transfer', $va), [
            'amount'           => 10000.00,
            'receiver_account' => '0123456789',
            'narration'        => 'Manual sweep to merchant',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    // Customer platform balance must NOT be touched
    expect($wallet->fresh()->balance)->toEqual(50000.00);

    // Audit transfer must be recorded
    $transfer = FintavaWalletTransfer::where('virtual_account_id', $va->id)->latest()->first();
    expect($transfer)->not->toBeNull();
    expect((float) $transfer->amount)->toEqual(10000.00);
    expect($transfer->sender_account)->toBe('9988776655');
    expect($transfer->receiver_account)->toBe('0123456789');
    expect($transfer->status)->toBe('completed');
    expect($transfer->provider_reference)->toBe('FINTAVA-TX-999');

    // Verify outbound request payload to Fintava
    Http::assertSent(function ($request) {
        return $request->url() === 'https://dev.fintavapay.com/api/dev/transaction/wallet-to-wallet'
            && $request['senderAccount'] === '9988776655'
            && $request['receiverAccount'] === '0123456789'
            && (float) $request['amount'] === 10000.00;
    });
});

test('super_admin can export customer fintava transactions as CSV', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    $user = User::factory()->create();
    $va = makeVirtualAccount([
        'user_id'              => $user->id,
        'provider'             => 'fintava',
        'account_number'       => '9988776655',
        'provider_customer_id' => 'cust-test',
    ]);

    Http::fake([
        'https://dev.fintavapay.com/api/dev/txn*' => Http::response([
            'status'  => 200,
            'message' => 'Transactions fetched',
            'data'    => [
                [
                    'reference'      => 'TXN-EXPORT-1',
                    'type'           => 'credit',
                    'amount'         => 2500.00,
                    'status'         => 'successful',
                    'narration'      => 'Test CSV deposit',
                    'senderAccount'  => 'Sender-123',
                    'receiverAccount'=> '9988776655',
                    'createdAt'      => '2026-11-10T10:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.fintava-accounts.export', $va));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    
    // Check streamed CSV content
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('TXN-EXPORT-1');
    expect($content)->toContain('2500.00');
    expect($content)->toContain('Test CSV deposit');
    expect($content)->toContain('"Amount (NGN)"');
    expect($content)->toContain('Date,Reference,Type');
});
