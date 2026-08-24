<?php

use App\Events\SimActivated;
use App\Listeners\CreditPlatformWelfareWallet;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use App\Models\Sim;
use App\Models\User;
use App\Services\PlatformWalletLedger;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function makeActivatedSim(): Sim
{
    return Sim::create([
        'number' => '080' . fake()->numerify('########'),
        'category' => 'standard',
        'provider' => 'mtn',
        'status' => SimStatus::ACTIVATED,
    ]);
}

test('platform wallet ledger credits and debits correctly', function () {
    $tx = app(PlatformWalletLedger::class)->credit(
        PlatformWallet::TYPE_WELFARE,
        'Platform Welfare Wallet',
        150.00,
        'test_source',
        'Test credit'
    );

    $wallet = PlatformWallet::where('type', PlatformWallet::TYPE_WELFARE)->first();
    expect($wallet->balance)->toEqual('150.00');
    expect($wallet->total_credited)->toEqual('150.00');
    expect($tx->type)->toBe('credit');

    app(PlatformWalletLedger::class)->debit(
        PlatformWallet::TYPE_WELFARE,
        50.00,
        'cashout_debit',
        'cashout',
        'Test debit'
    );

    $wallet->refresh();
    expect($wallet->balance)->toEqual('100.00');
    expect($wallet->total_debited)->toEqual('50.00');
});

test('platform wallet ledger debit throws on insufficient balance', function () {
    PlatformWallet::forType(PlatformWallet::TYPE_WELFARE, 'Platform Welfare Wallet');

    expect(fn () => app(PlatformWalletLedger::class)->debit(
        PlatformWallet::TYPE_WELFARE, 1000.00, 'cashout_debit', 'cashout', 'Too much'
    ))->toThrow(RuntimeException::class);
});

test('welfare wallet is credited a fixed amount on SIM activation when active', function () {
    PlatformWelfareSettings::current()->update(['amount_per_activation' => 75.00, 'is_active' => true]);

    $sim = makeActivatedSim();
    $actor = User::factory()->create();

    (new CreditPlatformWelfareWallet())->handle(new SimActivated($sim, $actor));

    $wallet = PlatformWallet::where('type', PlatformWallet::TYPE_WELFARE)->first();
    expect($wallet)->not->toBeNull();
    expect($wallet->balance)->toEqual('75.00');
});

test('welfare wallet is not credited when settings are inactive', function () {
    PlatformWelfareSettings::current()->update(['amount_per_activation' => 75.00, 'is_active' => false]);

    $sim = makeActivatedSim();
    $actor = User::factory()->create();

    (new CreditPlatformWelfareWallet())->handle(new SimActivated($sim, $actor));

    expect(PlatformWallet::where('type', PlatformWallet::TYPE_WELFARE)->exists())->toBeFalse();
});

test('welfare wallet is not credited when the configured amount is zero', function () {
    PlatformWelfareSettings::current()->update(['amount_per_activation' => 0.00, 'is_active' => true]);

    $sim = makeActivatedSim();
    $actor = User::factory()->create();

    (new CreditPlatformWelfareWallet())->handle(new SimActivated($sim, $actor));

    expect(PlatformWallet::where('type', PlatformWallet::TYPE_WELFARE)->exists())->toBeFalse();
});

test('admin wallet dashboard no longer exposes total manual credit/debit but exposes platform wallets', function () {
    $data = (new \App\Http\Controllers\Admin\AdminWalletController())->index()->getData();

    expect($data)->not->toHaveKey('totalManualCredit');
    expect($data)->not->toHaveKey('totalManualDebit');
    expect($data)->toHaveKey('monthlyManualCredit');
    expect($data)->toHaveKey('monthlyManualDebit');
    expect($data['welfareWallet'])->toBeInstanceOf(PlatformWallet::class);
    expect($data['utilitiesWallet'])->toBeInstanceOf(PlatformWallet::class);
    expect($data['welfareWallet']->type)->toBe(PlatformWallet::TYPE_WELFARE);
    expect($data['utilitiesWallet']->type)->toBe(PlatformWallet::TYPE_UTILITIES_REVENUE);
});
