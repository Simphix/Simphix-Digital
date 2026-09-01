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

test('the registration page renders an availability matrix reflecting real stock', function () {
    $glo = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'CAMERA SIM'],
        ['field_code' => 'CAM-' . fake()->unique()->numerify('####'), 'base_price' => 800, 'is_active' => true]
    );
    seedEligibleStock('CAMERA SIM', 'glo', 100);
    seedEligibleStock('CAMERA SIM', '9mobile', 40); // short of the 100 minimum — must render unavailable

    $coordinator = User::factory()->create(['role' => 'coordinator', 'referral_code' => 'COORDREF', 'status' => 'active']);

    $response = $this->get(route('enterprise-partner.register', ['ref' => 'COORDREF']));

    $response->assertOk();
    $response->assertViewHas('quantity', 100);
    $response->assertViewHas('matrix', function ($matrix) {
        return $matrix['POS SIM']['providers']['mtn']['available'] === true
            && $matrix['CAMERA SIM']['providers']['glo']['available'] === true
            && $matrix['CAMERA SIM']['providers']['9mobile']['available'] === false;
    });
});

test('a suspended inviter cannot be used to register', function () {
    $partner = User::factory()->create(['role' => 'partner', 'referral_code' => 'SUSPREF', 'status' => 'suspended']);

    $this->get(route('enterprise-partner.register', ['ref' => 'SUSPREF']))->assertNotFound();
});

test('submitting the registration form creates a business account, wallet, and application snapshot for the chosen category/network, then logs the EP in', function () {
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
        'sim_category' => 'POS SIM',
        'provider' => 'mtn',
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
    expect($application->sim_category)->toBe('POS SIM');
    expect($application->provider)->toBe('mtn');
    expect($application->quantity)->toBe(100);
    expect((float) $application->unit_price_snapshot)->toBe($this->unitPrice);
    expect((float) $application->total_amount_snapshot)->toBe($this->unitPrice * 100);
    expect($application->status)->toBe('pending_payment');

    $this->assertAuthenticatedAs($ep);
});

test('registering with an available non-default category and network stores exactly that selection and its own price', function () {
    $camera = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'CAMERA SIM'],
        ['field_code' => 'CAM2-' . fake()->unique()->numerify('####'), 'base_price' => 800, 'is_active' => true]
    );
    seedEligibleStock('CAMERA SIM', 'glo', 100);
    $cameraPrice = $camera->getPriceForUserType('business');
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF-CAM', 'status' => 'active']);

    $response = $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF-CAM',
        'first_name' => 'Cam', 'last_name' => 'Era',
        'email' => 'cam.era@example.com', 'phone' => '08066665555',
        'password' => 'Passw0rd!123', 'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Cam Era Ventures', 'business_type' => 'llc', 'cac_number' => 'RC-777777',
        'sim_category' => 'CAMERA SIM', 'provider' => 'glo',
    ]);

    $response->assertRedirect(route('enterprise-partner.info'));

    $application = EnterprisePartnerApplication::whereHas('user', fn ($q) => $q->where('email', 'cam.era@example.com'))->first();
    expect($application->sim_category)->toBe('CAMERA SIM');
    expect($application->provider)->toBe('glo');
    expect((float) $application->unit_price_snapshot)->toBe($cameraPrice);
});

test('registration is rejected server-side for a category/network without enough stock, even if the client bypassed the disabled option', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF-SHORT', 'status' => 'active']);

    $response = $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF-SHORT',
        'first_name' => 'Short', 'last_name' => 'Stock',
        'email' => 'short.stock@example.com', 'phone' => '08055554444',
        'password' => 'Passw0rd!123', 'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Short Co', 'business_type' => 'llc', 'cac_number' => 'RC-000001',
        'sim_category' => 'POS SIM', 'provider' => 'glo', // seeded stock is on 'mtn' only
    ]);

    $response->assertSessionHasErrors('sim_category');
    expect(User::where('email', 'short.stock@example.com')->exists())->toBeFalse();
});

test('registration is rejected for an unknown SIM category', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'RMREF-BAD', 'status' => 'active']);

    $response = $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'RMREF-BAD',
        'first_name' => 'Bad', 'last_name' => 'Cat',
        'email' => 'bad.cat@example.com', 'phone' => '08044443333',
        'password' => 'Passw0rd!123', 'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Bad Co', 'business_type' => 'llc', 'cac_number' => 'RC-000002',
        'sim_category' => 'NOT A REAL CATEGORY', 'provider' => 'mtn',
    ]);

    $response->assertSessionHasErrors('sim_category');
    expect(User::where('email', 'bad.cat@example.com')->exists())->toBeFalse();
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
        'sim_category' => 'POS SIM',
        'provider' => 'mtn',
    ]);

    $response = $this->get(route('enterprise-partner.info'));

    $response->assertOk();
    $response->assertSee(number_format($this->unitPrice * 100, 2), false);
    $response->assertSee('POS SIM');
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
        'sim_category' => 'POS SIM',
        'provider' => 'mtn',
    ]);

    $this->get(route('enterprise-partner.info'))->assertOk();
    $this->get(route('dashboard'))->assertRedirect('/enterprise-partner/generate-account');
});
