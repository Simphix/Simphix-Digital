<?php

use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerPayment;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function makeEpWithEmptyCart(): array
{
    $ep = User::factory()->create(['role' => 'business', 'status' => 'pending_eligibility']);
    Wallet::create(['user_id' => $ep->id, 'balance' => 0, 'wallet_number' => 'WLT' . str_pad((string) $ep->id, 7, '0', STR_PAD_LEFT)]);
    $application = EnterprisePartnerApplication::create([
        'user_id' => $ep->id,
        'total_amount_snapshot' => 0.00,
        'status' => 'pending_payment',
    ]);

    return [$ep, $application];
}

beforeEach(function () {
    $this->service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $this->posSim = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-' . fake()->unique()->numerify('####'), 'base_price' => 1500, 'is_active' => true]
    );
    $this->camera = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'CAMERA SIM'],
        ['field_code' => 'CAM-' . fake()->unique()->numerify('####'), 'base_price' => 800, 'is_active' => true]
    );
    $this->posPrice = $this->posSim->getPriceForUserType('business');
    $this->cameraPrice = $this->camera->getPriceForUserType('business');
    seedEligibleStock('POS SIM', 'mtn', 100);
    seedEligibleStock('CAMERA SIM', 'glo', 100);
});

test('a real freshly-registered EP (no state/lga/address, as the actual registration form leaves them) can add a cart item without being bounced by the global KYC modal', function () {
    // Regression guard: EnsureKycCompleted is a SEPARATE global middleware
    // from EnsureEnterprisePartnerPaid, with its own whitelist — it's not
    // enough to whitelist the new cart routes in one and forget the other.
    // Goes through the real register() flow (not the makeEpWithEmptyCart()
    // factory helper, which populates state/lga/address and would never
    // have caught this) so state/lga/address are genuinely null, exactly
    // as a real EP's registration leaves them.
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF-KYC', 'status' => 'active']);
    $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF-KYC',
        'first_name' => 'Kyc', 'last_name' => 'Guard',
        'email' => 'kyc.guard@example.test', 'phone' => '08033332222',
        'password' => 'Passw0rd!123', 'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Kyc Guard Co', 'business_type' => 'llc', 'cac_number' => 'RC-000003',
    ]);
    $ep = User::where('email', 'kyc.guard@example.test')->first();
    expect($ep->state)->toBeNull();

    $response = $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), [
        'sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60,
    ]);

    $response->assertRedirect(route('enterprise-partner.info'));
    $application = EnterprisePartnerApplication::where('user_id', $ep->id)->first();
    expect($application->items()->where('sim_category', 'POS SIM')->exists())->toBeTrue();
});

test('adding an item creates a correctly-priced line and updates the header total', function () {
    [$ep, $application] = makeEpWithEmptyCart();

    $response = $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), [
        'sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60,
    ]);

    $response->assertRedirect(route('enterprise-partner.info'));

    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();
    expect($item->quantity)->toBe(60);
    expect((float) $item->unit_price_snapshot)->toBe($this->posPrice);
    expect((float) $item->line_total_snapshot)->toBe($this->posPrice * 60);
    expect((float) $application->fresh()->total_amount_snapshot)->toBe($this->posPrice * 60);
});

test('adding the same category/network combo twice increments the existing line instead of duplicating it', function () {
    [$ep, $application] = makeEpWithEmptyCart();

    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 30]);
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 20]);

    expect(EnterprisePartnerApplicationItem::where('application_id', $application->id)->count())->toBe(1);
    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();
    expect($item->quantity)->toBe(50);
    expect((float) $application->fresh()->total_amount_snapshot)->toBe($this->posPrice * 50);
});

test('adding multiple distinct category/network combos accumulates the running total', function () {
    [$ep, $application] = makeEpWithEmptyCart();

    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60]);
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'CAMERA SIM', 'provider' => 'glo', 'quantity' => 40]);

    expect(EnterprisePartnerApplicationItem::where('application_id', $application->id)->count())->toBe(2);
    $expectedTotal = ($this->posPrice * 60) + ($this->cameraPrice * 40);
    expect((float) $application->fresh()->total_amount_snapshot)->toBe($expectedTotal);
});

test('updating a line items quantity recalculates the total', function () {
    [$ep, $application] = makeEpWithEmptyCart();
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60]);
    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();

    $this->actingAs($ep)->patch(route('enterprise-partner.cart.items.update', $item), ['quantity' => 80]);

    expect($item->fresh()->quantity)->toBe(80);
    expect((float) $application->fresh()->total_amount_snapshot)->toBe($this->posPrice * 80);
});

test('removing a line item recalculates the total down to zero when it was the last item', function () {
    [$ep, $application] = makeEpWithEmptyCart();
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60]);
    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();

    $this->actingAs($ep)->delete(route('enterprise-partner.cart.items.destroy', $item));

    expect(EnterprisePartnerApplicationItem::where('application_id', $application->id)->count())->toBe(0);
    expect((float) $application->fresh()->total_amount_snapshot)->toBe(0.0);
});

test('adding more than the live stock is rejected server-side', function () {
    [$ep, $application] = makeEpWithEmptyCart();

    $response = $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), [
        'sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 1000,
    ]);

    $response->assertSessionHasErrors('quantity');
    expect(EnterprisePartnerApplicationItem::where('application_id', $application->id)->count())->toBe(0);
});

test('updating a quantity beyond live stock is rejected server-side', function () {
    [$ep, $application] = makeEpWithEmptyCart();
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60]);
    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();

    $response = $this->actingAs($ep)->patch(route('enterprise-partner.cart.items.update', $item), ['quantity' => 5000]);

    $response->assertSessionHasErrors('quantity');
    expect($item->fresh()->quantity)->toBe(60);
});

test('add/update/remove are all rejected once a payment account exists for the application', function () {
    [$ep, $application] = makeEpWithEmptyCart();
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 100]);
    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();

    EnterprisePartnerPayment::create([
        'application_id' => $application->id,
        'user_id' => $ep->id,
        'expected_amount' => $application->fresh()->total_amount_snapshot,
        'account_reference' => 'EPP' . strtoupper(fake()->unique()->bothify('??????????')),
        'account_number' => fake()->unique()->numerify('##########'),
        'account_name' => 'Test EP Account',
        'status' => 'pending',
    ]);

    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'CAMERA SIM', 'provider' => 'glo', 'quantity' => 10])->assertForbidden();
    $this->actingAs($ep)->patch(route('enterprise-partner.cart.items.update', $item), ['quantity' => 50])->assertForbidden();
    $this->actingAs($ep)->delete(route('enterprise-partner.cart.items.destroy', $item))->assertForbidden();

    expect($item->fresh()->quantity)->toBe(100);
});

test('generateAccount is blocked below the 100-total minimum and succeeds once reached across two combined items', function () {
    config(['services.palmpay.bearer_token' => 'test-token', 'services.palmpay.base_url' => 'https://fake-palmpay.test']);
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

    [$ep, $application] = makeEpWithEmptyCart();
    $ep->update(['bvn' => '12345678901']);
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60]);

    $blocked = $this->actingAs($ep)->post(route('enterprise-partner.generate-account.create'), ['identity_type' => 'bvn', 'bvn' => '12345678901']);
    $blocked->assertRedirect(route('enterprise-partner.info'));
    expect(EnterprisePartnerPayment::where('application_id', $application->id)->exists())->toBeFalse();

    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'CAMERA SIM', 'provider' => 'glo', 'quantity' => 40]);

    $allowed = $this->actingAs($ep)->post(route('enterprise-partner.generate-account.create'), ['identity_type' => 'bvn', 'bvn' => '12345678901']);
    $allowed->assertRedirect(route('enterprise-partner.generate-account'));
    expect(EnterprisePartnerPayment::where('application_id', $application->id)->exists())->toBeTrue();
});

test('a user cannot mutate another EPs cart item', function () {
    [$ep, $application] = makeEpWithEmptyCart();
    [$intruder] = makeEpWithEmptyCart();
    $this->actingAs($ep)->post(route('enterprise-partner.cart.items.store'), ['sim_category' => 'POS SIM', 'provider' => 'mtn', 'quantity' => 60]);
    $item = EnterprisePartnerApplicationItem::where('application_id', $application->id)->first();

    $this->actingAs($intruder)->patch(route('enterprise-partner.cart.items.update', $item), ['quantity' => 90])->assertForbidden();
    $this->actingAs($intruder)->delete(route('enterprise-partner.cart.items.destroy', $item))->assertForbidden();

    expect($item->fresh()->quantity)->toBe(60);
});
