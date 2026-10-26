<?php

use App\Models\Sim;
use App\Models\SimHistory;
use App\Models\User;
use App\Support\DownlineMetrics;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);
});

test('DownlineMetrics calculates catalog metrics for enterprise business role', function () {
    $business = User::factory()->create([
        'role' => 'business',
        'status' => 'active',
    ]);

    // 2 Business Agents under this Enterprise Business
    $agent1 = User::factory()->create([
        'role' => 'business_agent',
        'referred_by' => $business->id,
        'status' => 'active',
    ]);
    $agent2 = User::factory()->create([
        'role' => 'business_agent',
        'referred_by' => $business->id,
        'status' => 'active',
    ]);

    // SIM 1: Direct at business HQ, held
    Sim::create([
        'number' => '08000000001',
        'category' => 'pos',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_BUSINESS,
        'business_id' => $business->id,
        'business_agent_id' => null,
    ]);

    // SIM 2: Direct at business HQ, activated
    $sim2 = Sim::create([
        'number' => '08000000002',
        'category' => 'pos',
        'provider' => 'mtn',
        'status' => SimStatus::ACTIVATED,
        'business_id' => $business->id,
        'business_agent_id' => null,
    ]);
    SimHistory::create([
        'sim_id' => $sim2->id,
        'from_status' => SimStatus::ASSIGNED_TO_BUSINESS,
        'to_status' => SimStatus::ACTIVATED,
    ]);

    // SIM 3: Delegated to agent 1, activated
    $sim3 = Sim::create([
        'number' => '08000000003',
        'category' => 'pos',
        'provider' => 'mtn',
        'status' => SimStatus::ACTIVATED,
        'business_id' => $business->id,
        'business_agent_id' => $agent1->id,
    ]);
    SimHistory::create([
        'sim_id' => $sim3->id,
        'from_status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
        'to_status' => SimStatus::ACTIVATED,
    ]);

    // SIM 4: Delegated to agent 2, held
    Sim::create([
        'number' => '08000000004',
        'category' => 'pos',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
        'business_id' => $business->id,
        'business_agent_id' => $agent2->id,
    ]);

    $metrics = DownlineMetrics::forUser($business);

    expect($metrics['isCatalogRole'])->toBeTrue();
    expect($metrics['nextRole'])->toBe('business_agent');
    expect($metrics['totalDownline'])->toBe(2);
    expect($metrics['roleBreakdown']->get('business_agent'))->toBe(2);

    // Personal SIMs (at business HQ): 2 total (1 held, 1 activated) -> 50% conversion
    expect($metrics['personalTotal'])->toBe(2);
    expect($metrics['personalActivated'])->toBe(1);
    expect($metrics['personalConversion'])->toBe(50.0);

    // Team SIMs (all 4 in cascade): 4 total (2 activated) -> 50% conversion
    expect($metrics['teamTotal'])->toBe(4);
    expect($metrics['teamActivated'])->toBe(2);
    expect($metrics['teamConversion'])->toBe(50.0);

    // Activations
    expect($metrics['totalActivations'])->toBeGreaterThanOrEqual(2);
});

test('admin can view enterprise business profile with metrics and drill down to team', function () {
    $business = User::factory()->create([
        'role' => 'business',
        'status' => 'active',
        'first_name' => 'Acme',
        'last_name' => 'Enterprise',
    ]);

    $agent = User::factory()->create([
        'role' => 'business_agent',
        'referred_by' => $business->id,
        'status' => 'active',
        'first_name' => 'Agent',
        'middle_name' => null,
        'last_name' => 'Smith',
    ]);

    Sim::create([
        'number' => '08099990001',
        'category' => 'pos',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_BUSINESS,
        'business_id' => $business->id,
    ]);

    // 1. View User page
    $response = $this->actingAs($this->admin)->get(route('admin.manage.users.show', $business));

    $response->assertOk();
    $response->assertSee('Enterprise Business');
    $response->assertSee('Downline Onboarding');
    $response->assertSee('Personal SIM Inventory');
    $response->assertSee('Team SIM Inventory');
    $response->assertSee('Total Onboarded');
    $response->assertSee(route('admin.manage.users.team', $business), false);

    // 2. Click through to team page
    $teamResponse = $this->actingAs($this->admin)->get(route('admin.manage.users.team', $business));
    $teamResponse->assertOk();
    $teamResponse->assertSee("Acme's Team", false);
    $teamResponse->assertSee($agent->first_name);
    $teamResponse->assertSee($agent->last_name);
    $teamResponse->assertSee('Business Agent');
    $teamResponse->assertSee(route('admin.manage.users.show', $agent), false);

    // 3. Filter by role
    $filteredResponse = $this->actingAs($this->admin)->get(route('admin.manage.users.team', [
        'user' => $business,
        'role' => 'business_agent',
    ]));
    $filteredResponse->assertOk();
    $filteredResponse->assertSee($agent->first_name);
    $filteredResponse->assertSee('Showing downline members with role');
});
