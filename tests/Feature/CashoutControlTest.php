<?php

use App\Models\CashoutSettings;
use App\Models\User;
use App\Models\Wallet;
use App\Http\Middleware\EnsureCashoutEnabled;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function cashoutUser(string $role): User
{
    $user = User::factory()->create(['role' => $role]);
    Wallet::create([
        'user_id' => $user->id,
        'balance' => 100000,
        'bonus' => 0,
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);

    return $user->fresh();
}

test('cash out defaults to enabled', function () {
    CashoutSettings::query()->delete();

    expect(CashoutSettings::enabled())->toBeTrue();
});

test('only a super admin can open or change the cash out control', function () {
    $user = cashoutUser('regional_manager');

    $this->actingAs($user)->get(route('admin.cash-out.settings.edit'))->assertForbidden();
    $this->actingAs($user)->put(route('admin.cash-out.settings.update'), ['is_enabled' => 0])->assertForbidden();

    expect(CashoutSettings::enabled())->toBeTrue();
});

test('a super admin can switch cash out off and on', function () {
    $admin = cashoutUser('super_admin');

    $this->actingAs($admin)->get(route('admin.cash-out.settings.edit'))->assertOk();

    $this->actingAs($admin)->put(route('admin.cash-out.settings.update'), ['is_enabled' => 0])
        ->assertSessionHas('success');
    expect(CashoutSettings::enabled())->toBeFalse();

    $this->actingAs($admin)->put(route('admin.cash-out.settings.update'), ['is_enabled' => 1]);
    expect(CashoutSettings::enabled())->toBeTrue();
});

test('every cash out entry point is blocked on the backend while off', function () {
    CashoutSettings::current()->update(['is_enabled' => false]);
    $admin = cashoutUser('super_admin');
    $user = cashoutUser('regional_manager');

    $this->actingAs($user)->get(route('withdraw'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', EnsureCashoutEnabled::MESSAGE);

    $this->actingAs($user)->post(route('withdraw.process'), ['amount' => 1000, 'pin' => '1234'])
        ->assertSessionHas('error', EnsureCashoutEnabled::MESSAGE);

    $this->actingAs($admin)->get(route('admin.platform-wallets.cashout.show', 'welfare'))
        ->assertSessionHas('error', EnsureCashoutEnabled::MESSAGE);
    $this->actingAs($admin)->post(route('admin.platform-wallets.cashout.store', 'welfare'), ['amount' => 1000])
        ->assertSessionHas('error', EnsureCashoutEnabled::MESSAGE);

    // Approval payouts are blocked too (the guard runs before route binding,
    // so no real request record is needed).
    $this->actingAs($admin)->postJson(route('admin.cash-out.approve', 999999))
        ->assertForbidden();
    $this->actingAs($admin)->postJson(route('admin.platform-wallets.approvals.approve', 999999))
        ->assertForbidden();
});

test('cash out routes are reachable again once switched back on', function () {
    CashoutSettings::current()->update(['is_enabled' => true]);
    $user = cashoutUser('regional_manager');

    $this->actingAs($user)->get(route('withdraw'))->assertOk();
});

test('cash out buttons are hidden from the ui while off', function () {
    $user = cashoutUser('regional_manager');

    CashoutSettings::current()->update(['is_enabled' => true]);
    $this->actingAs($user)->get(route('wallet'))->assertSee(route('withdraw'), false);

    CashoutSettings::current()->update(['is_enabled' => false]);
    $this->actingAs($user)->get(route('wallet'))->assertDontSee(route('withdraw'), false);
});

test('admin can view role cash out limits including enterprise business and business agent', function () {
    $admin = cashoutUser('super_admin');

    $response = $this->actingAs($admin)->get(route('admin.cash-out.settings.edit'));

    $response->assertOk();
    $response->assertSee('Role-Based Cash Out Limits');
    $response->assertSee('Enterprise Business');
    $response->assertSee('Business Agent');
    $response->assertSee('role_limits[business][min]', false);
    $response->assertSee('role_limits[business][max]', false);
    $response->assertSee('role_limits[business_agent][min]', false);
    $response->assertSee('role_limits[business_agent][max]', false);
});

test('admin can update role-based cashout minimum and maximum limits', function () {
    $admin = cashoutUser('super_admin');

    $payload = [
        'role_limits' => [
            'business' => ['min' => 10000, 'max' => 2000000],
            'business_agent' => ['min' => 2000, 'max' => 300000],
            'partner' => ['min' => 1500, 'max' => 500000],
            'coordinator' => ['min' => 1500, 'max' => 500000],
            'regional_manager' => ['min' => 2500, 'max' => 1000000],
            'agent' => ['min' => 500, 'max' => 50000],
            'personal' => ['min' => 500, 'max' => 50000],
        ],
    ];

    $this->actingAs($admin)->put(route('admin.cash-out.settings.update'), $payload)
        ->assertRedirect()
        ->assertSessionHas('success', 'Role cash out limits updated successfully.');

    $limits = CashoutSettings::current()->fresh()->getLimitsForRole('business');
    expect($limits['min'])->toEqual(10000);
    expect($limits['max'])->toEqual(2000000);

    $agentLimits = CashoutSettings::current()->fresh()->getLimitsForRole('business_agent');
    expect($agentLimits['min'])->toEqual(2000);
    expect($agentLimits['max'])->toEqual(300000);
});

test('validation prevents invalid role limits when max is less than min', function () {
    $admin = cashoutUser('super_admin');

    $payload = [
        'role_limits' => [
            'business' => ['min' => 50000, 'max' => 10000], // invalid: max < min
        ],
    ];

    $response = $this->actingAs($admin)->put(route('admin.cash-out.settings.update'), $payload);
    $response->assertSessionHasErrors('role_limits.business.max');
});

test('role minimum and maximum cashout limits are enforced during withdrawal', function () {
    CashoutSettings::current()->update([
        'is_enabled' => true,
        'role_limits' => [
            'business' => ['min' => 5000, 'max' => 100000],
            'business_agent' => ['min' => 1000, 'max' => 50000],
        ],
    ]);

    $businessUser = cashoutUser('business');
    $businessUser->update([
        'transaction_pin' => \Illuminate\Support\Facades\Hash::make('1234'),
    ]);

    \App\Models\WithdrawalAccount::create([
        'user_id' => $businessUser->id,
        'bank_code' => '058',
        'bank_name' => 'GTBank',
        'account_no' => '0123456789',
        'account_name' => 'Enterprise User Co',
    ]);

    // Zero out eligibility requirement for test
    $service = \App\Models\Service::firstOrCreate(
        ['name' => 'Withdrawal'],
        ['description' => 'Wallet Withdrawal Service', 'is_active' => true]
    );
    \App\Models\ServiceField::updateOrCreate(
        ['service_id' => $service->id, 'field_name' => 'withdrawal eligibility'],
        ['field_code' => 'WDL_002', 'base_price' => 0, 'is_active' => true]
    );

    // 1. Below role minimum (4000 < 5000)
    $response = $this->actingAs($businessUser)->post(route('withdraw.process'), [
        'amount' => 4000,
        'pin' => '1234',
    ]);
    $response->assertSessionHasErrors('amount');

    // 2. Above role maximum (150000 > 100000)
    $response = $this->actingAs($businessUser)->post(route('withdraw.process'), [
        'amount' => 150000,
        'pin' => '1234',
    ]);
    $response->assertSessionHasErrors('amount');

    // 3. Within role range (10000) succeeds and creates pending CashOutRequest
    $response = $this->actingAs($businessUser)->post(route('withdraw.process'), [
        'amount' => 10000,
        'pin' => '1234',
    ]);
    $response->assertRedirect();
    expect(\App\Models\CashOutRequest::where('user_id', $businessUser->id)->where('amount', 10000)->exists())->toBeTrue();
});
