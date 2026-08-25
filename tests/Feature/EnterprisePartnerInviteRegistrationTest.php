<?php

use App\Models\EnterprisePartnerApplication;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

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

test('the registration page renders eligibility pricing for a valid inviter', function () {
    $coordinator = User::factory()->create(['role' => 'coordinator', 'referral_code' => 'COORDREF', 'status' => 'active']);

    $response = $this->get(route('enterprise-partner.register', ['ref' => 'COORDREF']));

    $response->assertOk();
    $response->assertViewHas('unitPrice', $this->unitPrice);
    $response->assertViewHas('quantity', 100);
    $response->assertViewHas('totalAmount', $this->unitPrice * 100);
});

test('a suspended inviter cannot be used to register', function () {
    $partner = User::factory()->create(['role' => 'partner', 'referral_code' => 'SUSPREF', 'status' => 'suspended']);

    $this->get(route('enterprise-partner.register', ['ref' => 'SUSPREF']))->assertNotFound();
});

test('submitting the registration form creates a business account, wallet, and application snapshot, then logs the EP in', function () {
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
    expect($application->quantity)->toBe(100);
    expect((float) $application->unit_price_snapshot)->toBe($this->unitPrice);
    expect((float) $application->total_amount_snapshot)->toBe($this->unitPrice * 100);
    expect($application->status)->toBe('pending_payment');

    $this->assertAuthenticatedAs($ep);
});

test('the info page shows the freshly-registered EP their eligibility snapshot', function () {
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
    $response->assertSee(number_format($this->unitPrice * 100, 2), false);
});

test('an unpaid EP is redirected away from the rest of the app by the payment gate, but the info page stays reachable', function () {
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
    $this->get(route('dashboard'))->assertRedirect('/enterprise-partner/generate-account');
});
