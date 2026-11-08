<?php

use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    config([
        'services.fintava.api_key' => 'test-key',
        'services.fintava.base_url' => 'https://fake-fintava.test',
    ]);
});

function fakeFintavaCustomerCreation(int $status = 200, string $accountNumber = '0123456789'): void
{
    Http::fake([
        '*/create/customer' => Http::response(
            $status === 200 ? [
                'status' => 200,
                'message' => 'User created successfully',
                'data' => [
                    'userInfo' => [
                        'id' => 'fintava-usr-999',
                        'email' => 'autogen@example.com',
                        'phoneNumber' => '08099998888',
                    ],
                    'wallet' => [
                        'id' => 'fintava-wlt-999',
                        'accountNumber' => $accountNumber,
                        'accountName' => 'John Doe',
                    ],
                ],
            ] : [
                'status' => $status,
                'message' => 'BVN name mismatch with Fintava records',
            ],
            $status
        ),
    ]);
}

test('wallet form requires both BVN and NIN with valid formats', function () {
    $user = User::factory()->create([
        'role' => 'agent',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '08012345678',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'address' => '12 Market Road',
        'bvn' => null,
        'nin' => null,
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT001']);

    $response = $this->actingAs($user)->post(route('virtual.account.create'), [
        'name' => 'John Doe',
        'phone' => '08012345678',
        'bvn' => '123', // invalid
        // missing nin
    ]);

    $response->assertSessionHasErrors(['bvn', 'nin']);
});

test('wallet creation saves profile details and generates virtual account', function () {
    fakeFintavaCustomerCreation(200, '9988776655');

    $user = User::factory()->create([
        'role' => 'agent',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '08012345678',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'address' => '12 Market Road',
        'bvn' => null,
        'nin' => null,
        'date_of_birth' => null,
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT002']);

    $response = $this->actingAs($user)->post(route('virtual.account.create'), [
        'name' => 'John Doe',
        'phone' => '08012345678',
        'bvn' => '12345678901',
        'nin' => '98765432109',
        'date_of_birth' => '1995-05-12',
        'address' => '12 Market Road, Ikeja',
        'state' => 'Lagos',
    ]);

    $response->assertRedirect(route('wallet'));
    $response->assertSessionHas('success');

    $user->refresh();
    expect($user->bvn)->toBe('12345678901');
    expect($user->nin)->toBe('98765432109');
    expect($user->date_of_birth->format('Y-m-d'))->toBe('1995-05-12');
    expect($user->address)->toBe('12 Market Road, Ikeja');

    $va = VirtualAccount::where('user_id', $user->id)->first();
    expect($va)->not->toBeNull();
    expect($va->account_number)->toBe('9988776655');
    expect($va->bank_name)->toBe(VirtualAccount::BANK_NAME);
});

test('wallet creation error redirects with input and actionable message', function () {
    fakeFintavaCustomerCreation(400);

    $user = User::factory()->create([
        'role' => 'agent',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '08012345678',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'address' => '12 Market Road',
        'bvn' => null,
        'nin' => null,
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT003']);

    $response = $this->actingAs($user)->post(route('virtual.account.create'), [
        'name' => 'John Doe',
        'phone' => '08012345678',
        'bvn' => '12345678901',
        'nin' => '98765432109',
        'date_of_birth' => '1995-05-12',
        'address' => '12 Market Road, Ikeja',
    ]);

    $response->assertRedirect(route('wallet'));
    $response->assertSessionHas('error');
    expect(session()->getOldInput('bvn'))->toBe('12345678901');
    expect(session()->getOldInput('nin'))->toBe('98765432109');
});

test('quickCreateWallet creates virtual account for users who already have BVN and NIN', function () {
    fakeFintavaCustomerCreation(200, '5544332211');

    $user = User::factory()->create([
        'role' => 'agent',
        'bvn' => '12345678901',
        'nin' => '98765432109',
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT004']);

    $response = $this->actingAs($user)->post(route('virtual.account.create.quick'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    $va = VirtualAccount::where('user_id', $user->id)->first();
    expect($va)->not->toBeNull();
    expect($va->account_number)->toBe('5544332211');
});

test('quickCreateWallet redirects to wallet page if BVN or NIN is missing', function () {
    $user = User::factory()->create([
        'role' => 'agent',
        'bvn' => '12345678901',
        'nin' => null,
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT005']);

    $response = $this->actingAs($user)->post(route('virtual.account.create.quick'));

    $response->assertRedirect(route('wallet'));
    $response->assertSessionHas('error');
    expect(VirtualAccount::where('user_id', $user->id)->exists())->toBeFalse();
});

test('quickCreateWallet failure redirects to wallet page with actionable error flash', function () {
    fakeFintavaCustomerCreation(400);

    $user = User::factory()->create([
        'role' => 'agent',
        'bvn' => '12345678901',
        'nin' => '98765432109',
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT006']);

    $response = $this->actingAs($user)->post(route('virtual.account.create.quick'));

    $response->assertRedirect(route('wallet'));
    $response->assertSessionHas('error');
});

test('dashboard displays 1-click banner when user has BVN and NIN but no virtual account', function () {
    $user = User::factory()->create([
        'role' => 'agent',
        'bvn' => '12345678901',
        'nin' => '98765432109',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'address' => 'Sample Address',
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $user->id, 'balance' => 0, 'wallet_number' => 'WLT007']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Generate Funding Account');
    $response->assertSee('BVN & NIN Verified', false);
    $response->assertSee(route('virtual.account.create.quick'));
});

test('upline generateVirtualAccount requires both BVN and NIN and creates account', function () {
    fakeFintavaCustomerCreation(200, '7788990011');

    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    $downline = User::factory()->create([
        'role' => 'agent',
        'bvn' => null,
        'nin' => null,
        'email_verified_at' => now(),
    ]);
    Wallet::create(['user_id' => $downline->id, 'balance' => 0, 'wallet_number' => 'WLT008']);

    $response = $this->actingAs($admin)->post(route('network.downline.virtual-account', $downline), [
        'phone' => '08098765432',
        'bvn' => '11223344556',
        'nin' => '66554433221',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $downline->refresh();
    expect($downline->bvn)->toBe('11223344556');
    expect($downline->nin)->toBe('66554433221');
    expect(VirtualAccount::where('user_id', $downline->id)->exists())->toBeTrue();
});
