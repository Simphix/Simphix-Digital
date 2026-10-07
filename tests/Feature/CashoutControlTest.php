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
