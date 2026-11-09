<?php

use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerPayment;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\VirtualAccountRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function fakeFintavaSuccess(): void
{
    config([
        'services.fintava.api_key' => 'test-key',
        'services.fintava.base_url' => 'https://fake-fintava.test',
    ]);
    Http::fake([
        '*/create/customer' => Http::response([
            'status' => 200,
            'message' => 'User created successfully',
            'data' => [
                'userInfo' => [
                    'id' => 'fintava-user-123',
                    'email' => 'test@example.com',
                    'phoneNumber' => '08012345678',
                ],
                'wallet' => [
                    'id' => 'fintava-wallet-123',
                    'accountNumber' => '9999888877',
                    'accountName' => 'Test Account',
                ],
            ],
        ], 200),
    ]);
}

function makeEpWithApplication(float $unitPrice = 1500.0, int $quantity = 100): array
{
    $ep = User::factory()->create(['role' => 'business', 'status' => 'pending_eligibility', 'bvn' => null, 'nin' => null]);
    Wallet::create(['user_id' => $ep->id, 'balance' => 0, 'wallet_number' => 'WLT' . str_pad((string) $ep->id, 7, '0', STR_PAD_LEFT)]);
    $application = EnterprisePartnerApplication::create([
        'user_id' => $ep->id,
        'total_amount_snapshot' => $unitPrice * $quantity,
        'status' => 'pending_payment',
    ]);
    EnterprisePartnerApplicationItem::create([
        'application_id' => $application->id,
        'sim_category' => 'POS SIM',
        'provider' => 'mtn',
        'quantity' => $quantity,
        'unit_price_snapshot' => $unitPrice,
        'line_total_snapshot' => $unitPrice * $quantity,
    ]);

    return [$ep, $application];
}

test('createVirtualAccount (existing wallet flow) still behaves identically after the repository refactor', function () {
    fakeFintavaSuccess();
    $user = User::factory()->create(['bvn' => '12345678901', 'nin' => '12345678902']);

    $result = (new VirtualAccountRepository())->createVirtualAccount($user->id);

    expect($result['success'])->toBeTrue();
    expect(DB::table('virtual_accounts')->where('user_id', $user->id)->exists())->toBeTrue();
    $row = DB::table('virtual_accounts')->where('user_id', $user->id)->first();
    expect($row->account_number)->toBe('9999888877');
    expect($row->bank_name)->toBe(\App\Models\VirtualAccount::BANK_NAME);
});

test('createVirtualAccountForEnterprisePartner inserts into enterprise_partner_payments with Fintava customer reference', function () {
    fakeFintavaSuccess();
    [$ep, $application] = makeEpWithApplication();
    $ep->update(['bvn' => '12345678901', 'nin' => '12345678902']);

    $result = (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $ep);

    expect($result['success'])->toBeTrue();
    $payment = EnterprisePartnerPayment::where('application_id', $application->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->account_number)->toBe('9999888877');
    expect($payment->bank_name)->toBe(\App\Models\VirtualAccount::BANK_NAME);
    expect((float) $payment->expected_amount)->toBe(150000.0);
    expect($payment->status)->toBe('pending');
    expect(DB::table('virtual_accounts')->where('user_id', $ep->id)->exists())->toBeFalse();
});

test('a failed Fintava call is reported without inserting a payment row', function () {
    config(['services.fintava.api_key' => 'test-key', 'services.fintava.base_url' => 'https://fake-fintava.test']);
    Http::fake(['*/create/customer' => Http::response(['status' => 400, 'message' => 'Verification failed'], 400)]);
    [$ep, $application] = makeEpWithApplication();
    $ep->update(['bvn' => '12345678901', 'nin' => '12345678902']);

    $result = (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $ep);

    expect($result['success'])->toBeFalse();
    expect(EnterprisePartnerPayment::where('application_id', $application->id)->exists())->toBeFalse();
});

test('non-business roles are refused the generate-account page', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    $this->actingAs($partner)->get(route('enterprise-partner.generate-account'))->assertForbidden();
});

test('an EP without a payment yet sees the identity form; submitting it generates one', function () {
    fakeFintavaSuccess();
    [$ep, $application] = makeEpWithApplication();

    $this->actingAs($ep)->get(route('enterprise-partner.generate-account'))
        ->assertOk()
        ->assertViewHas('payment', null);

    $response = $this->actingAs($ep)->post(route('enterprise-partner.generate-account.create'), [
        'bvn' => '12345678901',
        'nin' => '12345678902',
    ]);

    $response->assertRedirect(route('enterprise-partner.generate-account'));
    expect($ep->fresh()->bvn)->toBe('12345678901');
    expect($ep->fresh()->nin)->toBe('12345678902');

    $payment = EnterprisePartnerPayment::where('application_id', $application->id)->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->expected_amount)->toBe((float) $application->total_amount_snapshot);
});

test('once a payment exists, the page shows its details instead of the identity form, and resubmitting is refused', function () {
    fakeFintavaSuccess();
    [$ep, $application] = makeEpWithApplication();
    $ep->update(['bvn' => '12345678901', 'nin' => '12345678902']);
    (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $ep);

    $response = $this->actingAs($ep)->get(route('enterprise-partner.generate-account'));
    $response->assertOk();
    $response->assertSee('9999888877');

    $again = $this->actingAs($ep)->post(route('enterprise-partner.generate-account.create'), [
        'bvn' => '10987654321',
        'nin' => '10987654322',
    ]);
    $again->assertRedirect(route('enterprise-partner.generate-account'));
    expect(EnterprisePartnerPayment::where('application_id', $application->id)->count())->toBe(1);
});

test('a pending EP with no state/lga/address (as the real registration form leaves them) is not blocked by the global KYC modal on the generate-account page', function () {
    [$ep, $application] = makeEpWithApplication();
    $ep->forceFill(['state' => null, 'lga' => null, 'address' => null, 'email_verified_at' => now()])->save();

    $response = $this->actingAs($ep)->get(route('enterprise-partner.generate-account'));

    $response->assertOk();
    $response->assertDontSee('Complete Profile Setup');
});

test('a non-EP user with incomplete KYC still gets the blocking modal (the exception is scoped to pending EPs only)', function () {
    $user = User::factory()->create(['state' => null, 'email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Complete Profile Setup');
});
