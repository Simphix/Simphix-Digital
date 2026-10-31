<?php

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $field = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-FILTER-TEST', 'base_price' => 1000, 'is_active' => true]
    );

    ServicePrice::firstOrCreate(
        ['service_fields_id' => $field->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 200]
    );

    $this->admin = User::factory()->create([
        'role'              => 'super_admin',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $this->bsm = User::factory()->create([
        'role'              => 'business_sales_manager',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);
});

test('sim plan page returns regular HTML view on standard GET request', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.sim-plan.index'));

    $response->assertOk();
    $response->assertViewIs('admin.sim-plan.index');
    $response->assertSee('SIM Cards');
    $response->assertSee('Updating SIM Inventory');
});

test('sim plan page returns JSON response with rendered partial on AJAX request', function () {
    Sim::create([
        'number'   => '08039998877',
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index'));

    $response->assertOk();
    $response->assertJsonStructure([
        'status',
        'html',
        'total',
        'counts' => ['totalUploaded', 'totalAssigned', 'totalAvailable', 'totalActivated'],
    ]);

    $data = $response->json();
    expect($data['status'])->toBe('success');
    expect($data['html'])->toContain('08039998877');
});

test('sim plan page filters inventory via AJAX by search, category, provider, and status', function () {
    Sim::create([
        'number'   => '08031112222',
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    Sim::create([
        'number'   => '08093334444',
        'category' => 'ROUTER SIM',
        'provider' => '9mobile',
        'status'   => SimStatus::ACTIVATED,
    ]);

    // Test filter by number search
    $searchResponse = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', [
        'search' => '08031112222',
    ]));
    $searchResponse->assertOk();
    $searchHtml = $searchResponse->json('html');
    expect($searchHtml)->toContain('08031112222');
    expect($searchHtml)->not->toContain('08093334444');

    // Test filter by provider
    $providerResponse = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', [
        'provider' => '9mobile',
    ]));
    $providerResponse->assertOk();
    $providerHtml = $providerResponse->json('html');
    expect($providerHtml)->toContain('08093334444');
    expect($providerHtml)->not->toContain('08031112222');

    // Test filter by status
    $statusResponse = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', [
        'status' => 'ACTIVATED',
    ]));
    $statusResponse->assertOk();
    $statusHtml = $statusResponse->json('html');
    expect($statusHtml)->toContain('08093334444');
    expect($statusHtml)->not->toContain('08031112222');
});

test('bsm user is forbidden from admin sim inventory via AJAX', function () {
    Sim::create([
        'number'   => '08055556666',
        'category' => 'POS SIM',
        'provider' => 'glo',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    $response = $this->actingAs($this->bsm)->getJson(route('admin.sim-plan.index', [
        'search' => '08055556666',
    ]));

    $response->assertForbidden();
});
