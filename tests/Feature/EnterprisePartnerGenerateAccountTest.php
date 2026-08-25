<?php

use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerPayment;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\VirtualAccountRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function fakePalmPaySuccess(): void
{
    config(['services.palmpay.bearer_token' => 'test-token', 'services.palmpay.base_url' => 'https://fake-palmpay.test']);
    // Echo back whatever accountReference we sent, same as the real PalmPay
    // API does — needed to verify the F24/EPP prefixing end to end.
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        return Http::response([
            'respCode' => '00000000',
            'data' => [
                'accountReference' => $request->data()['accountReference'] ?? 'UNKNOWN',
                'virtualAccountNo' => '9999888877',
                'virtualAccountName' => 'Test Account',
            ],
        ], 200);
    });
}

function makeEpWithApplication(float $unitPrice = 1500.0, int $quantity = 100): array
{
    $ep = User::factory()->create(['role' => 'business', 'status' => 'pending_eligibility', 'bvn' => null, 'nin' => null]);
    Wallet::create(['user_id' => $ep->id, 'balance' => 0, 'wallet_number' => 'WLT' . str_pad((string) $ep->id, 7, '0', STR_PAD_LEFT)]);
    $application = EnterprisePartnerApplication::create([
        'user_id' => $ep->id,
        'sim_category' => 'POS SIM',
        'provider' => 'mtn',
        'quantity' => $quantity,
        'unit_price_snapshot' => $unitPrice,
        'total_amount_snapshot' => $unitPrice * $quantity,
        'status' => 'pending_payment',
    ]);

    return [$ep, $application];
}

test('createVirtualAccount (existing wallet flow) still behaves identically after the repository refactor', function () {
    fakePalmPaySuccess();
    $user = User::factory()->create(['bvn' => '12345678901']);

    $result = (new VirtualAccountRepository())->createVirtualAccount($user->id, 'bvn');

    expect($result['success'])->toBeTrue();
    expect(DB::table('virtual_accounts')->where('user_id', $user->id)->exists())->toBeTrue();
    $row = DB::table('virtual_accounts')->where('user_id', $user->id)->first();
    expect($row->account_number)->toBe('9999888877');
    expect($row->account_reference)->toStartWith('F24');
});

test('createVirtualAccountForEnterprisePartner inserts into enterprise_partner_payments with an EPP-prefixed reference', function () {
    fakePalmPaySuccess();
    [$ep, $application] = makeEpWithApplication();
    $ep->update(['bvn' => '12345678901']);

    $result = (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $ep, 'bvn');

    expect($result['success'])->toBeTrue();
    $payment = EnterprisePartnerPayment::where('application_id', $application->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->account_reference)->toStartWith('EPP');
    expect($payment->account_number)->toBe('9999888877');
    expect((float) $payment->expected_amount)->toBe(150000.0);
    expect($payment->status)->toBe('pending');
    expect(DB::table('virtual_accounts')->where('user_id', $ep->id)->exists())->toBeFalse();
});

test('a failed PalmPay call is reported without inserting a payment row', function () {
    config(['services.palmpay.bearer_token' => 'test-token', 'services.palmpay.base_url' => 'https://fake-palmpay.test']);
    Http::fake(['fake-palmpay.test/*' => Http::response(['respCode' => '99999999', 'respMsg' => 'Something went wrong'], 200)]);
    [$ep, $application] = makeEpWithApplication();
    $ep->update(['bvn' => '12345678901']);

    $result = (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $ep, 'bvn');

    expect($result['success'])->toBeFalse();
    expect(EnterprisePartnerPayment::where('application_id', $application->id)->exists())->toBeFalse();
});

test('non-business roles are refused the generate-account page', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    $this->actingAs($partner)->get(route('enterprise-partner.generate-account'))->assertForbidden();
});

test('an EP without a payment yet sees the identity form; submitting it generates one', function () {
    fakePalmPaySuccess();
    [$ep, $application] = makeEpWithApplication();

    $this->actingAs($ep)->get(route('enterprise-partner.generate-account'))
        ->assertOk()
        ->assertViewHas('payment', null);

    $response = $this->actingAs($ep)->post(route('enterprise-partner.generate-account.create'), [
        'identity_type' => 'bvn',
        'bvn' => '12345678901',
    ]);

    $response->assertRedirect(route('enterprise-partner.generate-account'));
    expect($ep->fresh()->bvn)->toBe('12345678901');

    $payment = EnterprisePartnerPayment::where('application_id', $application->id)->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->expected_amount)->toBe((float) $application->total_amount_snapshot);
});

test('once a payment exists, the page shows its details instead of the identity form, and resubmitting is refused', function () {
    fakePalmPaySuccess();
    [$ep, $application] = makeEpWithApplication();
    $ep->update(['bvn' => '12345678901']);
    (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $ep, 'bvn');

    $response = $this->actingAs($ep)->get(route('enterprise-partner.generate-account'));
    $response->assertOk();
    $response->assertSee('9999888877');

    $again = $this->actingAs($ep)->post(route('enterprise-partner.generate-account.create'), [
        'identity_type' => 'bvn',
        'bvn' => '10987654321',
    ]);
    $again->assertRedirect(route('enterprise-partner.generate-account'));
    expect(EnterprisePartnerPayment::where('application_id', $application->id)->count())->toBe(1);
});
