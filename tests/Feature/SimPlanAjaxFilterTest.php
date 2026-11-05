<?php

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimRequest;
use App\Models\SimSwapRequest;
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

test('admin can filter sim inventory by name, email, phone, and ASSIGNED_TO_BSM status', function () {
    $bsmUser = User::factory()->create([
        'first_name' => 'Alexander',
        'last_name'  => 'Hamilton',
        'email'      => 'alex.hamilton@example.com',
        'phone'      => '08099887766',
        'role'       => 'business_sales_manager',
        'status'     => 'active',
    ]);

    $simBsm = Sim::create([
        'number'                    => '08031234567',
        'category'                  => 'POS SIM',
        'provider'                  => 'mtn',
        'status'                    => SimStatus::ASSIGNED_TO_BSM,
        'business_sales_manager_id' => $bsmUser->id,
        'user_id'                   => $bsmUser->id,
    ]);

    $simOther = Sim::create([
        'number'   => '08037654321',
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status'   => SimStatus::UNASSIGNED,
    ]);

    // 1. Filter by Name
    $resName = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', ['name' => 'Alexander']));
    $resName->assertOk();
    expect($resName->json('html'))->toContain('08031234567');
    expect($resName->json('html'))->not->toContain('08037654321');

    // 2. Filter by Email
    $resEmail = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', ['email' => 'alex.hamilton']));
    $resEmail->assertOk();
    expect($resEmail->json('html'))->toContain('08031234567');
    expect($resEmail->json('html'))->not->toContain('08037654321');

    // 3. Filter by Phone
    $resPhone = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', ['phone' => '08099887766']));
    $resPhone->assertOk();
    expect($resPhone->json('html'))->toContain('08031234567');
    expect($resPhone->json('html'))->not->toContain('08037654321');

    // 4. Search by Name/Email/Phone in unified search
    $resSearch = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', ['search' => 'Hamilton']));
    $resSearch->assertOk();
    expect($resSearch->json('html'))->toContain('08031234567');
    expect($resSearch->json('html'))->not->toContain('08037654321');

    // 5. Filter by ASSIGNED_TO_BSM status
    $resStatus = $this->actingAs($this->admin)->getJson(route('admin.sim-plan.index', ['status' => SimStatus::ASSIGNED_TO_BSM]));
    $resStatus->assertOk();
    expect($resStatus->json('html'))->toContain('08031234567');
    expect($resStatus->json('html'))->not->toContain('08037654321');
});

test('admin sim-plan index view contains ASSIGNED TO BSM in status dropdown and name email phone filters', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.sim-plan.index'));

    $response->assertOk();
    $response->assertSee('ASSIGNED TO BSM');
    $response->assertSee('Holder / Customer Name');
    $response->assertSee('Filter by Email...');
    $response->assertSee('Filter by Phone...');
});

test('admin can download bulk upload sample excel file with valid headers', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.sim-plan.download-sample'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $this->assertStringContainsString('sim_bulk_upload_sample.xlsx', (string) $response->headers->get('Content-Disposition'));
});

test('admin sim-plan index view does not crash when request user is null or deleted', function () {
    \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    $orphanedUserId = 999999;

    SimRequest::create([
        'user_id' => $orphanedUserId,
        'request_type' => 'purchase',
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'quantity' => 5,
        'amount' => 5000,
        'status' => 'pending',
    ]);

    SimRequest::create([
        'user_id' => $orphanedUserId,
        'request_type' => 'activation',
        'category' => 'POS SIM',
        'provider' => 'glo',
        'number' => '08051112233',
        'amount' => 1000,
        'status' => 'approved',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.sim-plan.index'));

    \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

    $response->assertOk();
    $response->assertSee('Deleted User (#999999)');
});
