<?php

use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimCustomer;
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
        ['field_code' => 'POS-PERF', 'base_price' => 1000, 'is_active' => true]
    );

    ServicePrice::firstOrCreate(
        ['service_fields_id' => $field->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 200]
    );

    // Setup a complete hierarchy: RM -> Coordinator -> Partner
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
        'name'                => 'South West ' . uniqid(),
        'regional_manager_id' => $this->rm->id,
    ]);
    $this->state = State::create([
        'name'      => 'Lagos ' . uniqid(),
        'region_id' => $this->region->id,
    ]);
    $this->district = SenatorialDistrict::create([
        'name'           => 'Lagos Central ' . uniqid(),
        'state_id'       => $this->state->id,
        'coordinator_id' => $this->coordinator->id,
    ]);
});

test('non-catalog users cannot access sales performance', function () {
    $personal = User::factory()->create(['role' => 'personal']);
    $agent = User::factory()->create(['role' => 'agent']);

    $this->actingAs($personal)->get(route('sales-performance.index'))->assertForbidden();
    $this->actingAs($agent)->get(route('sales-performance.index'))->assertForbidden();
    $this->actingAs($personal)->get(route('sales-performance.export'))->assertForbidden();
});

test('regional manager sees overview and breakdown of sales by downline jurisdictions', function () {
    // Create an activated SIM in the coordinator's district sold by partner
    $sim1 = Sim::create([
        'number'              => '0803' . fake()->unique()->numerify('#######'),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'status'              => SimStatus::ACTIVATED,
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'user_id'             => $this->partner->id,
    ]);

    SimCustomer::create([
        'sim_id'       => $sim1->id,
        'partner_id'   => $this->partner->id,
        'full_name'    => 'Adebayo Johnson',
        'phone_number' => '08031112222',
        'email'        => 'adebayo@example.com',
    ]);

    // Create an unactivated SIM still pending with the coordinator
    Sim::create([
        'number'              => '0803' . fake()->unique()->numerify('#######'),
        'category'            => 'POS SIM',
        'provider'            => 'airtel',
        'status'              => SimStatus::ASSIGNED_TO_COORDINATOR,
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'user_id'             => $this->coordinator->id,
    ]);

    $response = $this->actingAs($this->rm)->get(route('sales-performance.index', ['range' => 'all']));

    $response->assertOk();
    $response->assertSee('Sales Performance');
    $response->assertSee('Executive Overview');
    $response->assertSee('Sales Breakdown by Territory & Downline', false);
    $response->assertSee($this->district->name);
    $response->assertSee($this->state->name);
    $response->assertSee('Adebayo Johnson');
    $response->assertSee('Export CSV Report');
});

test('coordinator sees their senatorial district partner breakdown', function () {
    $sim = Sim::create([
        'number'              => '0803' . fake()->unique()->numerify('#######'),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'status'              => SimStatus::ACTIVATED,
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'user_id'             => $this->partner->id,
    ]);

    $response = $this->actingAs($this->coordinator)->get(route('sales-performance.index', ['range' => 'all']));

    $response->assertOk();
    $response->assertSee('Executive Overview');
    $response->assertSee('Sales Breakdown by Territory');
    $response->assertSee($this->partner->first_name);
    $response->assertSee($sim->number);
});

test('catalog users can export sales performance report to CSV', function () {
    $sim = Sim::create([
        'number'              => '0803' . fake()->unique()->numerify('#######'),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'status'              => SimStatus::ACTIVATED,
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'user_id'             => $this->partner->id,
    ]);

    SimCustomer::create([
        'sim_id'       => $sim->id,
        'partner_id'   => $this->partner->id,
        'full_name'    => 'Kudirat Alabi',
        'phone_number' => '08055556666',
    ]);

    $response = $this->actingAs($this->rm)->get(route('sales-performance.export', ['range' => 'all']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->getContent();

    expect($content)->toContain('SIMPHIX SALES PERFORMANCE & TERRITORY REPORT');
    expect($content)->toContain('OVERVIEW SUMMARY');
    expect($content)->toContain('TERRITORY & DOWNLINE BREAKDOWN');
    expect($content)->toContain('ITEMIZED ACTIVATED SIMS (SALES RECORDS)');
    expect($content)->toContain($sim->number);
    expect($content)->toContain('Kudirat Alabi');
    expect($content)->toContain($this->district->name);
});

test('catalog users can export sales performance report to PDF', function () {
    $sim = Sim::create([
        'number'              => '0803' . fake()->unique()->numerify('#######'),
        'category'            => 'POS SIM',
        'provider'            => 'mtn',
        'status'              => SimStatus::ACTIVATED,
        'regional_manager_id' => $this->rm->id,
        'coordinator_id'      => $this->coordinator->id,
        'partner_id'          => $this->partner->id,
        'user_id'             => $this->partner->id,
    ]);

    SimCustomer::create([
        'sim_id'       => $sim->id,
        'partner_id'   => $this->partner->id,
        'full_name'    => 'Emeka Okafor',
        'phone_number' => '08077778888',
    ]);

    $response = $this->actingAs($this->rm)->get(route('sales-performance.export', [
        'format' => 'pdf',
        'range'  => '7days',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $content = $response->getContent();

    // Verify it is a valid PDF
    expect(str_starts_with($content, '%PDF'))->toBeTrue();
    expect(strlen($content))->toBeGreaterThan(1000);
});

test('sales performance page contains interactive export settings modal', function () {
    $response = $this->actingAs($this->rm)->get(route('sales-performance.index'));

    $response->assertOk();
    $response->assertSee('Export Report');
    $response->assertSee('Export Sales Performance');
    $response->assertSee('Export PDF Report');
    $response->assertSee('Export CSV Report');
    $response->assertSee('Document Type');
    $response->assertSee('Reporting Period');
    $response->assertSee('Custom Date Range');
});
