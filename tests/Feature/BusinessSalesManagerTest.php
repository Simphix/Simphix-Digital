<?php

use App\Models\Region;
use App\Models\Report;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\SimRequest;
use App\Models\State;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Setup SIM service & pricing
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $field = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-BSM', 'base_price' => 1000, 'is_active' => true]
    );

    ServicePrice::firstOrCreate(
        ['service_fields_id' => $field->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 200]
    );

    // Setup BSM user
    $this->bsm = User::factory()->create([
        'role'              => 'business_sales_manager',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    // Setup hierarchy: RM -> Coordinator -> Partner
    $this->rm = User::factory()->create(['role' => 'regional_manager', 'status' => 'active']);
    $this->coordinator = User::factory()->create([
        'role'        => 'coordinator',
        'status'      => 'active',
        'referred_by' => $this->rm->id,
    ]);
    $this->partner = User::factory()->create([
        'role'        => 'partner',
        'status'      => 'active',
        'referred_by' => $this->coordinator->id,
    ]);

    // Setup Region, State, District
    $this->region = Region::create([
        'name'                => 'North West ' . uniqid(),
        'regional_manager_id' => $this->rm->id,
    ]);
    $this->state = State::create([
        'name'      => 'Kano ' . uniqid(),
        'region_id' => $this->region->id,
    ]);
    $this->district = SenatorialDistrict::create([
        'name'           => 'Kano Central ' . uniqid(),
        'state_id'       => $this->state->id,
        'coordinator_id' => $this->coordinator->id,
    ]);
});

test('unauthenticated user cannot access BSM dashboard', function () {
    $this->get(route('bsm.dashboard'))
        ->assertRedirect(route('login'));
});

test('unauthorized customer cannot access BSM console', function () {
    $personal = User::factory()->create(['role' => 'personal', 'email_verified_at' => now()]);

    $this->actingAs($personal)
        ->get(route('bsm.dashboard'))
        ->assertStatus(403);
});

test('BSM user is redirected to BSM dashboard upon hitting /dashboard', function () {
    $this->actingAs($this->bsm)
        ->get(route('dashboard'))
        ->assertRedirect(route('bsm.dashboard'));
});

test('BSM user with missing KYC fields can access BSM dashboard without redirect loop', function () {
    $bsmIncomplete = User::factory()->create([
        'role'              => 'business_sales_manager',
        'status'            => 'active',
        'email_verified_at' => now(),
        'state'             => null,
        'lga'               => null,
        'address'           => null,
    ]);

    // Hitting /dashboard redirects to /bsm/dashboard
    $response = $this->actingAs($bsmIncomplete)->get(route('dashboard'));
    $response->assertRedirect(route('bsm.dashboard'));

    // Following redirect lands cleanly on /bsm/dashboard with 200 OK (no redirect loop back to /dashboard)
    $follow = $this->actingAs($bsmIncomplete)->get(route('bsm.dashboard'));
    $follow->assertOk();
});

test('Business Sales Manager can access BSM dashboard and sees executive KPIs', function () {
    // Create some SIMs in various statuses
    Sim::create([
        'number'   => '080' . random_int(10000000, 99999999),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $simActive = Sim::create([
        'number'              => '080' . random_int(10000000, 99999999),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'user_id'             => $this->partner->id,
        'status'              => SimStatus::ACTIVATED,
    ]);

    SimCustomer::create([
        'sim_id'       => $simActive->id,
        'partner_id'   => $this->partner->id,
        'full_name'    => 'Aliyu Bello',
        'phone_number' => '08099887766',
        'email'        => 'aliyu@test.com',
    ]);

    $response = $this->actingAs($this->bsm)->get(route('bsm.dashboard'));

    $response->assertOk()
        ->assertSee('Business Sales Manager Console')
        ->assertSee('SIM Lifecycle Desk')
        ->assertSee('Top Performing Territories Snapshot')
        ->assertSee($this->region->name);
});

test('BSM can access all decoupled dedicated desks and tabs redirect smoothly', function () {
    // 1. Territories desk
    $this->actingAs($this->bsm)
        ->get(route('bsm.territories'))
        ->assertOk()
        ->assertSee('Regional Sales Performance')
        ->assertSee('Senatorial Districts Breakdown');

    // 2. SIM Lifecycle desk
    $this->actingAs($this->bsm)
        ->get(route('bsm.lifecycle'))
        ->assertOk()
        ->assertSee('SIM Lifecycle Governance')
        ->assertSee('Lifecycle Status')
        ->assertSee('Current Holder');

    // 3. Customer Handling desk
    $this->actingAs($this->bsm)
        ->get(route('bsm.customers'))
        ->assertOk()
        ->assertSee('Customer Handling Desk')
        ->assertSee('Quick Onboard & Activate', false);

    // 4. Sales Acceleration desk
    $this->actingAs($this->bsm)
        ->get(route('bsm.acceleration'))
        ->assertOk()
        ->assertSee('Sales Acceleration &amp; Interventions Desk', false)
        ->assertSee('Stalled Field Stock (≥ 7 Days)', false);

    // 5. Backward compatibility query param redirects
    $this->actingAs($this->bsm)
        ->get(route('bsm.dashboard', ['tab' => 'territories']))
        ->assertRedirect(route('bsm.territories'));

    $this->actingAs($this->bsm)
        ->get(route('bsm.dashboard', ['tab' => 'lifecycle']))
        ->assertRedirect(route('bsm.lifecycle'));

    $this->actingAs($this->bsm)
        ->get(route('bsm.dashboard', ['tab' => 'customers']))
        ->assertRedirect(route('bsm.customers'));

    $this->actingAs($this->bsm)
        ->get(route('bsm.dashboard', ['tab' => 'acceleration']))
        ->assertRedirect(route('bsm.acceleration'));
});

test('Super Admin can also access BSM console', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('bsm.dashboard'))
        ->assertOk()
        ->assertSee('Business Sales Manager Console');
});

test('BSM can export jurisdiction report as CSV', function () {
    $response = $this->actingAs($this->bsm)
        ->get(route('bsm.reports.jurisdictions.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->getContent();

    expect($content)->toContain('SIMPHIX BUSINESS SALES MANAGER - TERRITORY & SALES REPORT');
    expect($content)->toContain('EXECUTIVE OVERVIEW');
    expect($content)->toContain('REGIONAL SALES BREAKDOWN');
    expect($content)->toContain('SENATORIAL DISTRICTS BREAKDOWN');
});

test('BSM can assign an unassigned SIM to a Regional Manager or Coordinator', function () {
    $sim = Sim::create([
        'number'   => '080' . random_int(10000000, 99999999),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.assign'), [
            'sim_id'    => $sim->id,
            'target_id' => $this->rm->id,
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_RM);
    expect($sim->fresh()->regional_manager_id)->toBe($this->rm->id);
});

test('BSM can execute lateral swap for a held SIM to unblock distribution', function () {
    $newCoordinator = User::factory()->create(['role' => 'coordinator', 'status' => 'active']);

    $sim = Sim::create([
        'number'         => '080' . random_int(10000000, 99999999),
        'category'       => 'POS SIM',
        'provider'       => 'mtn',
        'coordinator_id' => $this->coordinator->id,
        'status'         => SimStatus::ASSIGNED_TO_COORDINATOR,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.reassign'), [
            'sim_id'        => $sim->id,
            'new_holder_id' => $newCoordinator->id,
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->coordinator_id)->toBe($newCoordinator->id);
});

test('BSM can revert delegated unactivated SIM back to partner', function () {
    $downlineUser = User::factory()->create(['role' => 'personal', 'status' => 'active']);

    $sim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $downlineUser->id,
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.revert'), [
            'sim_id' => $sim->id,
            'note'   => 'BSM unblocked stuck handoff',
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->user_id)->toBe($this->partner->id);
});

test('BSM can suspend and reactivate a SIM', function () {
    $sim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
        'status'     => SimStatus::ACTIVATED,
    ]);

    // Suspend
    $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.status', $sim), ['action' => 'suspend'])
        ->assertSessionHas('success');

    expect($sim->fresh()->status)->toBe(SimStatus::SUSPENDED);

    // Reactivate
    $this->actingAs($this->bsm)
        ->post(route('bsm.lifecycle.status', $sim), ['action' => 'activate'])
        ->assertSessionHas('success');

    expect($sim->fresh()->status)->toBe(SimStatus::ACTIVATED);
});

test('BSM Customer Desk: quick onboard and activate customer', function () {
    $sim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.customers.quick-activate'), [
            'sim_id'       => $sim->id,
            'full_name'    => 'Fatima Aliyu',
            'phone_number' => '08033221100',
            'email'        => 'fatima@test.com',
            'address'      => '12 Marina Road, Lagos',
        ]);

    $response->assertSessionHas('success');
    expect($sim->fresh()->status)->toBe(SimStatus::ACTIVATED);

    $customer = SimCustomer::where('sim_id', $sim->id)->first();
    expect($customer)->not->toBeNull();
    expect($customer->full_name)->toBe('Fatima Aliyu');
    expect($customer->phone_number)->toBe('08033221100');
});

test('BSM can approve a pending partner SIM purchase request', function () {
    $sim = Sim::create([
        'number'   => '080' . random_int(10000000, 99999999),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $request = SimRequest::create([
        'user_id'      => $this->partner->id,
        'sim_id'       => $sim->id,
        'category'     => 'POS SIM',
        'provider'     => 'mtn',
        'quantity'     => 1,
        'request_type' => 'purchase',
        'status'       => 'pending',
    ]);

    $response = $this->actingAs($this->bsm)
        ->post(route('bsm.requests.approve', $request));

    $response->assertSessionHas('success');
    expect($request->fresh()->status)->toBe('approved');
    expect($sim->fresh()->status)->toBe(SimStatus::ASSIGNED_TO_PARTNER);
});

test('BSM desks render days held and idle days as rounded whole numbers without decimals', function () {
    $stuckSim = Sim::create([
        'number'     => '080' . random_int(10000000, 99999999),
        'category'   => 'POS SIM',
        'provider'   => 'mtn',
        'partner_id' => $this->partner->id,
        'user_id'    => $this->partner->id,
        'status'     => SimStatus::ASSIGNED_TO_PARTNER,
    ]);
    $stuckSim->forceFill([
        'created_at' => now()->subDays(10)->subHours(5)->subMinutes(30),
        'updated_at' => now()->subDays(10)->subHours(5)->subMinutes(30),
    ])->save();

    expect($stuckSim->daysHeld())->toBe(10);

    // Check lifecycle desk for rounded idle badge
    $this->actingAs($this->bsm)
        ->get(route('bsm.lifecycle'))
        ->assertOk()
        ->assertSee('10d idle');

    // Check acceleration desk for rounded days held
    $this->actingAs($this->bsm)
        ->get(route('bsm.acceleration'))
        ->assertOk()
        ->assertSee('10 days');
});

test('BSM can access SIM Cards & Plans Management but uploading SIMs is disabled', function () {
    // 1. BSM can view the SIM Cards & Plans management index
    $response = $this->actingAs($this->bsm)
        ->get(route('admin.sim-plan.index'));

    $response->assertOk();
    $response->assertSee('SIM Cards & Plans Management', false);
    $response->assertSee('SIM Uploading Disabled');
    $response->assertSee('Uploading new inventory batches is restricted to Super Admins');
    $response->assertDontSee('Upload Numbers');

    // 2. Direct single SIM store endpoint is forbidden for BSM
    $this->actingAs($this->bsm)
        ->post(route('admin.sim-plan.store'), [
            'provider'   => 'mtn',
            'sim_number' => '08012345678',
            'category'   => 'POS SIM',
            'cost_price' => 500,
        ])
        ->assertForbidden();

    // 3. Excel import endpoint is forbidden for BSM
    $this->actingAs($this->bsm)
        ->post(route('admin.sim-plan.import'))
        ->assertForbidden();

    // 4. Super Admin can view index with upload forms active
    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $adminResponse = $this->actingAs($admin)
        ->get(route('admin.sim-plan.index'));

    $adminResponse->assertOk();
    $adminResponse->assertSee('SIM Cards & Plans Management', false);
    $adminResponse->assertDontSee('SIM Uploading Disabled');
    $adminResponse->assertSee('Upload Numbers');
});

test('BSM can perform deep territory drilldown down the hierarchy: Region -> District -> Partner -> Agent -> SIMs', function () {
    // Set up a full downline hierarchy:
    // Region ($this->region) -> District ($this->district) -> Coordinator ($this->coordinator)
    // -> Partner ($this->partner) -> Agent ($agent) -> Sim with Customer
    $agent = User::factory()->create([
        'role'        => 'business_agent',
        'referred_by' => $this->partner->id,
        'status'      => 'active',
    ]);

    $sim = Sim::create([
        'number'              => '080' . random_int(10000000, 99999999),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'business_agent_id'   => $agent->id,
        'user_id'             => $agent->id,
        'status'              => SimStatus::ACTIVATED,
    ]);

    $customer = SimCustomer::create([
        'sim_id'       => $sim->id,
        'partner_id'   => $this->partner->id,
        'full_name'    => 'Hauwa Ibrahim',
        'phone_number' => '08011223344',
        'email'        => 'hauwa@example.com',
    ]);

    // 1. Level: Region -> Senatorial Districts
    $resRegion = $this->actingAs($this->bsm)->getJson(route('bsm.territories.drilldown', [
        'level' => 'region',
        'id'    => $this->region->id,
    ]));

    $resRegion->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('level', 'region')
        ->assertJsonPath('entity.name', $this->region->name);

    $districts = $resRegion->json('items');
    expect($districts)->toBeArray()->not->toBeEmpty();
    expect($districts[0]['district'])->toBe($this->district->name);
    expect($districts[0]['coordinator_name'])->toBe($this->coordinator->name);
    expect($districts[0]['activated'])->toBeGreaterThanOrEqual(1);

    // 2. Level: Coordinator -> Partners
    $resCoord = $this->actingAs($this->bsm)->getJson(route('bsm.territories.drilldown', [
        'level' => 'coordinator',
        'id'    => $this->coordinator->id,
    ]));

    $resCoord->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('level', 'coordinator')
        ->assertJsonPath('entity.name', $this->coordinator->name);

    $partners = $resCoord->json('items');
    expect($partners)->toBeArray()->not->toBeEmpty();
    expect($partners[0]['name'])->toBe($this->partner->name);
    expect($partners[0]['activated'])->toBeGreaterThanOrEqual(1);

    // 3. Level: Partner -> Field Agents
    $resPartner = $this->actingAs($this->bsm)->getJson(route('bsm.territories.drilldown', [
        'level' => 'partner',
        'id'    => $this->partner->id,
    ]));

    $resPartner->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('level', 'partner')
        ->assertJsonPath('entity.name', $this->partner->name);

    $agents = $resPartner->json('items');
    expect($agents)->toBeArray()->not->toBeEmpty();
    expect($agents[0]['name'])->toBe($agent->name);
    expect($agents[0]['customer_count'])->toBe(1);

    // 4. Level: Agent -> SIMs & Customers
    $resAgent = $this->actingAs($this->bsm)->getJson(route('bsm.territories.drilldown', [
        'level' => 'agent',
        'id'    => $agent->id,
    ]));

    $resAgent->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('level', 'agent')
        ->assertJsonPath('entity.name', $agent->name);

    $sims = $resAgent->json('items');
    expect($sims)->toBeArray()->not->toBeEmpty();
    expect($sims[0]['number'])->toBe($sim->number);
    expect($sims[0]['customer_name'])->toBe('Hauwa Ibrahim');
    expect($sims[0]['customer_phone'])->toBe('08011223344');

    // 5. Unauthorized access check
    $personal = User::factory()->create(['role' => 'personal', 'email_verified_at' => now()]);
    $this->actingAs($personal)
        ->getJson(route('bsm.territories.drilldown', ['level' => 'region', 'id' => $this->region->id]))
        ->assertForbidden();
});
