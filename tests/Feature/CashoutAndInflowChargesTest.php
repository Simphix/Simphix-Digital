<?php

use App\Http\Controllers\PaymentWebhookController;
use App\Models\CashoutSettings;
use App\Models\CashOutRequest;
use App\Models\Report;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WithdrawalAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

function createTestUser(string $role = 'coordinator', float $initialBalance = 100000.00): User
{
    $user = User::factory()->create([
        'role' => $role,
        'transaction_pin' => Hash::make('1234'),
    ]);

    Wallet::create([
        'user_id' => $user->id,
        'balance' => $initialBalance,
        'bonus' => 0,
        'status' => 'active',
        'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
    ]);

    WithdrawalAccount::create([
        'user_id' => $user->id,
        'bank_code' => '999991',
        'bank_name' => 'Loma Bank',
        'account_no' => '0123456789',
        'account_name' => $user->first_name . ' ' . $user->last_name,
    ]);

    // Give user transaction history so withdrawal eligibility threshold passes
    Transaction::create([
        'transaction_ref' => 'ELIG-' . Str::random(10),
        'user_id' => $user->id,
        'amount' => 3000000.00,
        'fee' => 0,
        'net_amount' => 3000000.00,
        'description' => 'Eligibility transaction',
        'type' => 'debit',
        'status' => 'completed',
        'performed_by' => 'System',
    ]);

    return $user->fresh();
}

test('CashoutSettings calculates cashout charges and threshold breakdown correctly', function () {
    $settings = CashoutSettings::current();
    $settings->update([
        'cashout_fee' => 25.00,
        'cashout_fee_type' => 'fixed',
        'cashout_fee_description' => 'Bank Transfer Processing Fee',
        'cashout_threshold_amount' => 9999.99,
        'cashout_threshold_fee' => 50.00,
        'cashout_threshold_description' => 'Stamp Duty Levy',
    ]);

    // Case 1: Below threshold (5,000)
    $below = $settings->calculateCashoutCharges(5000.00);
    expect($below['amount'])->toBe(5000.00);
    expect($below['base_fee'])->toBe(25.00);
    expect($below['base_fee_description'])->toBe('Bank Transfer Processing Fee');
    expect($below['threshold_met'])->toBeFalse();
    expect($below['threshold_fee'])->toBe(0.00);
    expect($below['total_charges'])->toBe(25.00);
    expect($below['total_debit'])->toBe(5025.00);
    expect($below['net_payout'])->toBe(5000.00);

    // Case 2: Above threshold (15,000)
    $above = $settings->calculateCashoutCharges(15000.00);
    expect($above['amount'])->toBe(15000.00);
    expect($above['base_fee'])->toBe(25.00);
    expect($above['threshold_met'])->toBeTrue();
    expect($above['threshold_fee'])->toBe(50.00);
    expect($above['threshold_description'])->toBe('Stamp Duty Levy');
    expect($above['total_charges'])->toBe(75.00);
    expect($above['total_debit'])->toBe(15075.00);
    expect($above['net_payout'])->toBe(15000.00);

    // Case 3: Percentage base fee (2%)
    $settings->update([
        'cashout_fee' => 2.00,
        'cashout_fee_type' => 'percent',
    ]);
    $percent = $settings->calculateCashoutCharges(20000.00);
    // 2% of 20,000 = 400 + 50 levy = 450 total charges
    expect($percent['base_fee'])->toBe(400.00);
    expect($percent['threshold_fee'])->toBe(50.00);
    expect($percent['total_charges'])->toBe(450.00);
    expect($percent['total_debit'])->toBe(20450.00);
});

test('CashoutSettings calculates inflow charges and threshold deductions correctly', function () {
    $settings = CashoutSettings::current();
    $settings->update([
        'inflow_fee' => 10.00,
        'inflow_fee_type' => 'fixed',
        'inflow_fee_description' => 'Wallet Funding Fee',
        'inflow_threshold_amount' => 9999.99,
        'inflow_threshold_fee' => 50.00,
        'inflow_threshold_description' => 'Electronic Money Transfer Levy',
    ]);

    // Case 1: Below threshold (5,000)
    $below = $settings->calculateInflowCharges(5000.00);
    expect($below['amount'])->toBe(5000.00);
    expect($below['base_fee'])->toBe(10.00);
    expect($below['threshold_met'])->toBeFalse();
    expect($below['threshold_fee'])->toBe(0.00);
    expect($below['total_deductions'])->toBe(10.00);
    expect($below['net_credited'])->toBe(4990.00);

    // Case 2: Above threshold (15,000)
    $above = $settings->calculateInflowCharges(15000.00);
    expect($above['amount'])->toBe(15000.00);
    expect($above['base_fee'])->toBe(10.00);
    expect($above['threshold_met'])->toBeTrue();
    expect($above['threshold_fee'])->toBe(50.00);
    expect($above['threshold_description'])->toBe('Electronic Money Transfer Levy');
    expect($above['total_deductions'])->toBe(60.00);
    expect($above['net_credited'])->toBe(14940.00);

    // Case 3: Percentage fee with cap (0.3% capped at 300)
    $settings->update([
        'inflow_fee' => 0.30,
        'inflow_fee_type' => 'percent',
        'inflow_fee_cap' => 300.00,
        'inflow_threshold_amount' => null,
    ]);

    // 10,000 -> 30 fee
    $tenK = $settings->calculateInflowCharges(10000.00);
    expect($tenK['base_fee'])->toBe(30.00);
    expect($tenK['total_deductions'])->toBe(30.00);
    expect($tenK['net_credited'])->toBe(9970.00);

    // 200,000 -> 600 capped at 300
    $twoHundredK = $settings->calculateInflowCharges(200000.00);
    expect($twoHundredK['base_fee'])->toBe(300.00);
    expect($twoHundredK['total_deductions'])->toBe(300.00);
    expect($twoHundredK['net_credited'])->toBe(199700.00);
});

test('inflow webhook automatically applies threshold deduction when exceeding threshold', function () {
    $settings = CashoutSettings::current();
    $settings->update([
        'inflow_fee' => 0.00,
        'inflow_threshold_amount' => 9999.99,
        'inflow_threshold_fee' => 50.00,
        'inflow_threshold_description' => 'Electronic Money Transfer Levy',
    ]);

    $user = createTestUser('coordinator', 1000.00);
    $va = VirtualAccount::create([
        'user_id' => $user->id,
        'account_number' => 'VA' . fake()->unique()->numerify('########'),
        'account_reference' => 'REF-' . Str::random(10),
        'bank_name' => 'PalmPay',
        'account_name' => 'Test Reserved Account',
    ]);

    $orderNo = 'ORD' . strtoupper(Str::random(10));
    $payload = [
        'transType' => 20, // Payin
        'virtualAccountNo' => $va->account_number,
        'merchantOrderNo' => $orderNo,
        'orderAmount' => 1500000, // ₦15,000 in kobo
        'payerBankName' => 'Access Bank',
        'payerAccountName' => 'John Doe',
        'orderStatus' => 1,
    ];

    $controller = app(PaymentWebhookController::class);
    $method = new \ReflectionMethod(PaymentWebhookController::class, 'processReservedAccountTransaction');
    $method->setAccessible(true);
    $method->invoke($controller, $payload);

    // Initial 1000 + 15000 funded - 50 levy - 15 sync VAT charge = 15935
    $userWallet = $user->wallet->fresh();
    expect((float) $userWallet->balance)->toBe(15935.00);

    // Verify the levy deduction transaction was recorded with configured description
    $levyTx = Transaction::where('user_id', $user->id)
        ->where('metadata->type', 'levy_charge')
        ->first();

    expect($levyTx)->not->toBeNull();
    expect((float) $levyTx->amount)->toBe(50.00);
    expect($levyTx->description)->toBe('Electronic Money Transfer Levy');
});

test('user cashout validates total required balance and debits total charge', function () {
    $settings = CashoutSettings::current();
    $settings->update([
        'is_enabled' => true,
        'cashout_fee' => 20.00,
        'cashout_fee_type' => 'fixed',
        'cashout_fee_description' => 'Withdrawal Processing Fee',
        'cashout_threshold_amount' => 9999.99,
        'cashout_threshold_fee' => 50.00,
        'cashout_threshold_description' => 'Transaction Levy / Stamp Duty',
    ]);

    $user = createTestUser('coordinator', 20000.00);

    // Cash out 12,000. Total required = 12000 + 20 + 50 = 12070.
    // Wallet deducted upfront = 12,020 (amount + base fee). Tax (50) is charged on admin approval.
    $response = $this->actingAs($user)->post(route('withdraw.process'), [
        'amount' => 12000.00,
        'pin' => '1234',
    ]);

    $response->assertRedirect();

    $wallet = $user->wallet->fresh();
    expect((float) $wallet->balance)->toBe(20000.00 - 12020.00); // 7,980.00

    $cashOutReq = CashOutRequest::where('user_id', $user->id)->latest()->first();
    expect($cashOutReq)->not->toBeNull();
    expect((float) $cashOutReq->amount)->toBe(12000.00);
    expect((float) $cashOutReq->fee)->toBe(20.00);
    expect((float) $cashOutReq->tax)->toBe(50.00);
    expect((float) $cashOutReq->total_charge)->toBe(12020.00);

    $transaction = Transaction::where('transaction_ref', $cashOutReq->transaction_ref)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->metadata['charges']['has_threshold'])->toBeTrue();
    expect((float) $transaction->metadata['charges']['threshold_fee'])->toBe(50.00);
    expect((float) $transaction->metadata['charges']['total_debit'])->toBe(12070.00);
});

test('super admin can update cashout charges and money inflow charges settings', function () {
    $admin = createTestUser('super_admin');

    // 1. Update Cashout Charges
    $res1 = $this->actingAs($admin)->put(route('admin.cash-out.settings.update'), [
        'cashout_charges_form' => '1',
        'cashout_fee' => '35.50',
        'cashout_fee_type' => 'fixed',
        'cashout_fee_description' => 'Custom Cashout Fee',
        'cashout_threshold_amount' => '15000.00',
        'cashout_threshold_fee' => '75.00',
        'cashout_threshold_description' => 'Custom Threshold Levy',
    ]);

    $res1->assertSessionHas('success');

    $settings = CashoutSettings::current()->fresh();
    expect((float) $settings->cashout_fee)->toBe(35.50);
    expect($settings->cashout_fee_description)->toBe('Custom Cashout Fee');
    expect((float) $settings->cashout_threshold_amount)->toBe(15000.00);
    expect((float) $settings->cashout_threshold_fee)->toBe(75.00);
    expect($settings->cashout_threshold_description)->toBe('Custom Threshold Levy');

    // 2. Update Inflow Charges
    $res2 = $this->actingAs($admin)->put(route('admin.cash-out.settings.update'), [
        'inflow_charges_form' => '1',
        'inflow_fee' => '1.50',
        'inflow_fee_type' => 'percent',
        'inflow_fee_description' => 'Deposit Processing Fee',
        'inflow_threshold_amount' => '25000.00',
        'inflow_threshold_fee' => '100.00',
        'inflow_threshold_description' => 'Government Stamp Levy',
    ]);

    $res2->assertSessionHas('success');

    $settings = CashoutSettings::current()->fresh();
    expect((float) $settings->inflow_fee)->toBe(1.50);
    expect($settings->inflow_fee_type)->toBe('percent');
    expect($settings->inflow_fee_description)->toBe('Deposit Processing Fee');
    expect((float) $settings->inflow_threshold_amount)->toBe(25000.00);
    expect((float) $settings->inflow_threshold_fee)->toBe(100.00);
    expect($settings->inflow_threshold_description)->toBe('Government Stamp Levy');
});

test('cashout approval credits base fee and threshold tax to admin cashout charges wallet', function () {
    $settings = CashoutSettings::current();
    $settings->update([
        'is_enabled' => true,
        'cashout_fee' => 25.00,
        'cashout_fee_type' => 'fixed',
        'cashout_fee_description' => 'Transfer Processing Fee',
        'cashout_threshold_amount' => 5000.00,
        'cashout_threshold_fee' => 50.00,
        'cashout_threshold_description' => 'Stamp Duty Levy',
    ]);

    $user = createTestUser('coordinator', 50000.00);
    $admin = createTestUser('super_admin');

    // 1. Submit cashout for 10,000 (total required: 10,000 + 25 fee + 50 tax = 10,075)
    $this->actingAs($user)->post(route('withdraw.process'), [
        'amount' => 10000.00,
        'pin' => '1234',
    ])->assertRedirect();

    $cashOutReq = CashOutRequest::where('user_id', $user->id)->latest()->first();
    expect($cashOutReq)->not->toBeNull();
    expect((float) $cashOutReq->fee)->toBe(25.00);
    expect((float) $cashOutReq->tax)->toBe(50.00);

    // Initial platform charges wallet should be 0 before approval
    $chargesWalletBefore = \App\Models\PlatformWallet::where('type', \App\Models\PlatformWallet::TYPE_CASHOUT_CHARGES)->first();
    $initialBalance = $chargesWalletBefore ? (float) $chargesWalletBefore->balance : 0.00;

    // 2. Mock Fintava payout success and approve cashout
    $this->mock(\App\Services\FintavaService::class, function ($mock) {
        $mock->shouldReceive('bankCredit')->andReturn([
            'status' => 200,
            'message' => 'Transfer success',
            'data' => [
                'reference' => 'FTV-REF-12345',
            ],
        ]);
    });

    $res = $this->actingAs($admin)->post(route('admin.cash-out.approve', $cashOutReq));
    $res->assertSessionHas('success');

    // 3. Verify Admin Cashout Charges Wallet was credited with both fee (25) and tax (50)
    $chargesWallet = \App\Models\PlatformWallet::where('type', \App\Models\PlatformWallet::TYPE_CASHOUT_CHARGES)->first();
    expect($chargesWallet)->not->toBeNull();
    expect((float) $chargesWallet->balance)->toBe($initialBalance + 75.00);

    // 4. Verify PlatformWalletTransaction entries exist
    $feeTx = \App\Models\PlatformWalletTransaction::where('platform_wallet_id', $chargesWallet->id)
        ->where('source', 'cashout_fee')
        ->latest('id')
        ->first();
    expect($feeTx)->not->toBeNull();
    expect((float) $feeTx->amount)->toBe(25.00);

    $taxTx = \App\Models\PlatformWalletTransaction::where('platform_wallet_id', $chargesWallet->id)
        ->where('source', 'cashout_tax')
        ->latest('id')
        ->first();
    expect($taxTx)->not->toBeNull();
    expect((float) $taxTx->amount)->toBe(50.00);

    // 5. Verify the Admin Wallet dashboard and Settings views display the charges wallet
    $this->actingAs($admin)->get(route('admin.manage.adminwallet'))
        ->assertOk()
        ->assertSee('Cashout Charges Wallet');

    $this->actingAs($admin)->get(route('admin.cash-out.settings.edit'))
        ->assertOk()
        ->assertSee('Cashout Charges Wallet Balance');
});
