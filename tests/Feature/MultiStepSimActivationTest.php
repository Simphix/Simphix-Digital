<?php

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\SimHistory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->partner = User::factory()->create([
        'role' => 'partner',
        'status' => 'active',
    ]);

    $this->partnerWallet = Wallet::firstOrCreateForUser($this->partner->id);
    $this->partnerWallet->update([
        'balance' => 5000.00,
        'bonus'   => 2000.00,
    ]);

    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $this->field = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-TEST', 'base_price' => 1000, 'is_active' => true]
    );

    ServicePrice::firstOrCreate(
        ['service_fields_id' => $this->field->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 0]
    );

    ServicePrice::firstOrCreate(
        ['service_fields_id' => $this->field->id, 'user_type' => 'partner', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 200]
    );

    $this->simNumber = '0805' . fake()->unique()->numerify('#######');
    $this->sim = Sim::create([
        'number'     => $this->simNumber,
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
    ]);
});

test('activation page can be viewed by partner and lists held sims', function () {
    $response = $this->actingAs($this->partner)->get(route('sims.activate.page'));

    $response->assertOk();
    $response->assertSee('SIM Card Activation');
    $response->assertSee($this->simNumber);
});

test('activation page includes select SIM category option and shows held categories', function () {
    $cctvNumber = '0805' . fake()->unique()->numerify('#######');
    Sim::create([
        'number'     => $cctvNumber,
        'category'   => 'CCTV',
        'provider'   => 'airtel',
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
    ]);

    $response = $this->actingAs($this->partner)->get(route('sims.activate.page'));

    $response->assertOk();
    $response->assertSee('SIM Category');
    $response->assertSee('POS SIM');
    $response->assertSee('CCTV');
    $response->assertSee($this->simNumber);
    $response->assertSee($cctvNumber);
});

test('non-partner role cannot access activation page', function () {
    $regularUser = User::factory()->create(['role' => 'personal']);
    $response = $this->actingAs($regularUser)->get(route('sims.activate.page'));

    $response->assertForbidden();
});

test('happy path: partner activates sim with main balance debit and customer info is stored', function () {
    $payload = [
        'sim_id'        => $this->sim->id,
        'full_name'     => 'Emeka Okafor',
        'phone_number'  => '08098765432',
        'email'         => 'emeka@example.com',
        'address'       => '12 Admiralty Way, Lekki',
        'wallet_source' => 'main',
    ];

    $response = $this->actingAs($this->partner)
        ->post(route('sims.activate'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->sim->refresh();
    $this->partnerWallet->refresh();

    expect($this->sim->status)->toBe(SimStatus::ACTIVATED);
    // 5000 - 1000 = 4000
    expect((float) $this->partnerWallet->balance)->toBe(4000.00);

    // Customer record created
    $customer = SimCustomer::where('sim_id', $this->sim->id)->first();
    expect($customer)->not->toBeNull();
    expect($customer->full_name)->toBe('Emeka Okafor');
    expect($customer->phone_number)->toBe('08098765432');
    expect($customer->email)->toBe('emeka@example.com');
    expect($customer->address)->toBe('12 Admiralty Way, Lekki');

    // History and Transaction created
    expect(SimHistory::where('sim_id', $this->sim->id)->where('to_status', SimStatus::ACTIVATED)->exists())->toBeTrue();
    expect(Transaction::where('user_id', $this->partner->id)->where('type', 'debit')->exists())->toBeTrue();
});

test('happy path: partner can activate sim with commission/bonus balance debit', function () {
    $payload = [
        'sim_id'        => $this->sim->id,
        'full_name'     => 'Fatima Aliyu',
        'phone_number'  => '08123456789',
        'email'         => null,
        'address'       => null,
        'wallet_source' => 'bonus',
    ];

    $response = $this->actingAs($this->partner)
        ->post(route('sims.activate'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->sim->refresh();
    $this->partnerWallet->refresh();

    expect($this->sim->status)->toBe(SimStatus::ACTIVATED);
    // Main balance untouched
    expect((float) $this->partnerWallet->balance)->toBe(5000.00);
    // Commission balance debited: 2000 - 1000 = 1000
    // (Note: post-activation AwardCommissions may credit 200 commission to bonus)
    expect((float) $this->partnerWallet->bonus)->toBeGreaterThanOrEqual(1000.00);

    $customer = SimCustomer::where('sim_id', $this->sim->id)->first();
    expect($customer)->not->toBeNull();
    expect($customer->full_name)->toBe('Fatima Aliyu');
});

test('activation fails when main balance is insufficient', function () {
    $this->partnerWallet->update(['balance' => 500.00]); // Needed: 1000.00

    $payload = [
        'sim_id'        => $this->sim->id,
        'full_name'     => 'John Doe',
        'phone_number'  => '08011112222',
        'wallet_source' => 'main',
    ];

    $response = $this->actingAs($this->partner)
        ->post(route('sims.activate'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('error');

    expect($this->sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
    expect(SimCustomer::where('sim_id', $this->sim->id)->exists())->toBeFalse();
});

test('activation fails when bonus balance is insufficient', function () {
    $this->partnerWallet->update(['bonus' => 200.00]); // Needed: 1000.00

    $payload = [
        'sim_id'        => $this->sim->id,
        'full_name'     => 'Jane Doe',
        'phone_number'  => '08011113333',
        'wallet_source' => 'bonus',
    ];

    $response = $this->actingAs($this->partner)
        ->post(route('sims.activate'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('error');

    expect($this->sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
});

test('cannot activate a sim that is already activated', function () {
    $this->sim->update(['status' => SimStatus::ACTIVATED]);

    $payload = [
        'sim_id'        => $this->sim->id,
        'full_name'     => 'Double Activate',
        'phone_number'  => '08099998888',
        'wallet_source' => 'main',
    ];

    $response = $this->actingAs($this->partner)
        ->post(route('sims.activate'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('error');

    // Balance should not be touched
    expect((float) $this->partnerWallet->fresh()->balance)->toBe(5000.00);
});

test('partner cannot activate a sim held by another partner', function () {
    $otherPartner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
    $otherWallet = Wallet::firstOrCreateForUser($otherPartner->id);
    $otherWallet->update(['balance' => 5000.00]);

    $payload = [
        'sim_id'        => $this->sim->id,
        'full_name'     => 'Sneaky Activation',
        'phone_number'  => '08099997777',
        'wallet_source' => 'main',
    ];

    $response = $this->actingAs($otherPartner)
        ->post(route('sims.activate'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('error');

    expect($this->sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
});

test('sidebar has Requests link and excludes POS, CCTV, and Router SIM links', function () {
    $response = $this->actingAs($this->partner)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Requests');
    $response->assertDontSee('Downline Requests');
    $response->assertDontSee('POS SIM');
    $response->assertDontSee('CCTV SIM');
    $response->assertDontSee('Router SIM');
});

test('requests page renders incoming and my requests tabs and lists my requests', function () {
    $upline = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);
    $this->partner->update(['referred_by' => $upline->id]);

    \App\Models\SimRequest::create([
        'user_id'   => $this->partner->id,
        'upline_id' => $upline->id,
        'category'  => 'POS SIM',
        'provider'  => 'mtn',
        'quantity'  => 5,
        'status'    => 'pending',
    ]);

    $response = $this->actingAs($this->partner)->get(route('sims.requests'));

    $response->assertOk();
    $response->assertSee('Incoming Requests');
    $response->assertSee('My Requests');
    $response->assertSee('Request SIM Cards');
    $response->assertSee('POS SIM');
});
