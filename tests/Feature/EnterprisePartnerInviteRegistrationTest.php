<?php

use App\Models\EnterprisePartnerApplication;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\User;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

/** Seeds >= 100 UNASSIGNED sims for a category/provider so it clears the eligibility stock gate. */
function seedEligibleStock(string $category, string $provider = 'mtn', int $count = 100): void
{
    for ($i = 0; $i < $count; $i++) {
        Sim::create([
            'number' => '080' . fake()->unique()->numerify('########'),
            'category' => $category,
            'provider' => $provider,
            'status' => SimStatus::UNASSIGNED,
        ]);
    }
}

beforeEach(function () {
    // 'POS SIM' already exists as a real seeded category in the dev DB this
    // suite runs against, so firstOrCreate() matches that existing row
    // rather than creating a fresh one — read back whatever its real
    // base_price is instead of assuming a value, since the controller under
    // test resolves eligibility pricing off this exact category/row.
    $this->service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $this->field = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-' . fake()->unique()->numerify('####'), 'base_price' => 1500, 'is_active' => true]
    );
    $this->unitPrice = $this->field->getPriceForUserType('business');
    seedEligibleStock('POS SIM', 'mtn');
});

test('the Invite Enterprise Partner navbar button is visible to eligible roles and hidden from others', function () {
    $partner = User::factory()->create(['role' => 'partner']);
    $agent = User::factory()->create(['role' => 'agent']);

    $this->actingAs($partner)->get(route('dashboard'))->assertOk()
        ->assertSee(route('enterprise-partner.invite'), false)
        ->assertSee('Invite Enterprise Partner');
    $this->actingAs($agent)->get(route('dashboard'))->assertOk()->assertDontSee(route('enterprise-partner.invite'), false);
});

test('the Upline Manager sidebar panel is hidden for the business role but still shown for other roles with a referrer', function () {
    $rm = User::factory()->create(['role' => 'regional_manager']);
    $ep = User::factory()->create(['role' => 'business', 'status' => 'active', 'referred_by' => $rm->id]);
    $coordinator = User::factory()->create(['role' => 'coordinator', 'referred_by' => $rm->id]);

    $this->actingAs($ep)->get(route('dashboard'))->assertOk()->assertDontSee('Upline Manager');
    $this->actingAs($coordinator)->get(route('dashboard'))->assertOk()->assertSee('Upline Manager');
});

test('only inviter-eligible roles can view the invite page', function () {
    $partner = User::factory()->create(['role' => 'partner', 'referral_code' => strtoupper(fake()->bothify('????????'))]);
    $agent = User::factory()->create(['role' => 'agent']);

    $this->actingAs($partner)->get(route('enterprise-partner.invite'))->assertOk()->assertSee($partner->referral_code);
    $this->actingAs($agent)->get(route('enterprise-partner.invite'))->assertForbidden();
});

test('the registration page 404s without a valid inviter ref', function () {
    $this->get(route('enterprise-partner.register'))->assertNotFound();
    $this->get(route('enterprise-partner.register', ['ref' => 'NOT-REAL']))->assertNotFound();

    $agent = User::factory()->create(['role' => 'agent', 'referral_code' => 'AGENTREF']);
    $this->get(route('enterprise-partner.register', ['ref' => 'AGENTREF']))->assertNotFound();
});

test('a suspended inviter cannot be used to register', function () {
    $partner = User::factory()->create(['role' => 'partner', 'referral_code' => 'SUSPREF', 'status' => 'suspended']);

    $this->get(route('enterprise-partner.register', ['ref' => 'SUSPREF']))->assertNotFound();
});

test('submitting the registration form creates a business account, wallet, and an empty order header ready for the cart, then logs the EP in', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF', 'status' => 'active']);

    $response = $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF',
        'first_name' => 'Ada',
        'last_name' => 'Business',
        'email' => 'ada.business@example.com',
        'phone' => '08099999999',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Ada Ventures',
        'business_type' => 'llc',
        'cac_number' => 'RC-123456',
    ]);

    $response->assertRedirect(route('enterprise-partner.info'));

    $ep = User::where('email', 'ada.business@example.com')->first();
    expect($ep)->not->toBeNull();
    expect($ep->role)->toBe('business');
    expect($ep->status)->toBe('pending_eligibility');
    expect($ep->referred_by)->toBe($rm->id);
    expect($ep->email_verified_at)->not->toBeNull();
    expect($ep->wallet)->not->toBeNull();
    expect((float) $ep->wallet->balance)->toBe(0.0);

    $application = EnterprisePartnerApplication::where('user_id', $ep->id)->first();
    expect($application)->not->toBeNull();
    expect((float) $application->total_amount_snapshot)->toBe(0.0);
    expect($application->status)->toBe('pending_payment');
    expect($application->items)->toHaveCount(0);

    $this->assertAuthenticatedAs($ep);
});

test('the cart page loads for a freshly-registered EP with an empty order', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF2', 'status' => 'active']);

    $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF2',
        'first_name' => 'Bola',
        'last_name' => 'Biz',
        'email' => 'bola.biz@example.com',
        'phone' => '08088888888',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Bola Traders',
        'business_type' => 'sole_proprietor',
        'cac_number' => 'RC-654321',
    ]);

    $response = $this->get(route('enterprise-partner.info'));

    $response->assertOk();
    $response->assertSee('Your order is empty');
    $response->assertSee('0 / 100 minimum');
});

test('an unpaid EP is redirected to the cart (not generate-account) by the payment gate, and the cart page stays reachable', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF3', 'status' => 'active']);

    $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF3',
        'first_name' => 'Chidi',
        'last_name' => 'Co',
        'email' => 'chidi.co@example.com',
        'phone' => '08077777777',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Chidi Co',
        'business_type' => 'partnership',
        'cac_number' => 'RC-999999',
    ]);

    $this->get(route('enterprise-partner.info'))->assertOk();
    $this->get(route('dashboard'))->assertRedirect('/enterprise-partner/info');
});
