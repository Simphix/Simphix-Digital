<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Report;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimRequest;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BsmAccelerationReportExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $bsm;
    private User $superAdmin;
    private User $rm;
    private User $coordinator;
    private User $partner;
    private User $agent;
    private Service $service;
    private ServiceField $field;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup SIM service & pricing
        $this->service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
        $this->field = ServiceField::firstOrCreate(
            ['service_id' => $this->service->id, 'field_name' => 'POS SIM TEST'],
            ['field_code' => 'POS-TEST-' . uniqid(), 'base_price' => 1000, 'is_active' => true]
        );

        ServicePrice::firstOrCreate(
            ['service_fields_id' => $this->field->id, 'user_type' => 'personal', 'user_id' => null],
            ['service_id' => $this->service->id, 'price' => 1000, 'commission' => 200]
        );

        // BSM User
        $this->bsm = User::factory()->create([
            'role'              => 'business_sales_manager',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        // Super Admin User
        $this->superAdmin = User::factory()->create([
            'role'              => 'super_admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        // Hierarchy: RM -> Coordinator -> Partner -> Agent
        $this->rm = User::factory()->create([
            'role'       => 'regional_manager',
            'status'     => 'active',
            'first_name' => 'Kano',
            'last_name'  => 'RM',
            'email'      => 'kano.rm.' . uniqid() . '@example.com',
        ]);

        $this->coordinator = User::factory()->create([
            'role'        => 'coordinator',
            'status'      => 'active',
            'first_name'  => 'Central',
            'last_name'   => 'Coord',
            'email'       => 'central.coord.' . uniqid() . '@example.com',
            'referred_by' => $this->rm->id,
        ]);

        $this->partner = User::factory()->create([
            'role'        => 'partner',
            'status'      => 'active',
            'first_name'  => 'Fagge',
            'last_name'   => 'Partner',
            'email'       => 'fagge.partner.' . uniqid() . '@example.com',
            'referred_by' => $this->coordinator->id,
        ]);

        $this->agent = User::factory()->create([
            'role'        => 'agent',
            'status'      => 'active',
            'first_name'  => 'Aminu',
            'last_name'   => 'FieldAgent',
            'email'       => 'aminu.agent.' . uniqid() . '@example.com',
            'referred_by' => $this->partner->id,
        ]);

        // Region for RM
        Region::create([
            'name'                => 'Northern Region ' . uniqid(),
            'regional_manager_id' => $this->rm->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_exporting_or_searching_managers(): void
    {
        $exportResponse = $this->get(route('bsm.reports.acceleration.export'));
        $exportResponse->assertRedirect(route('login'));

        $searchResponse = $this->get(route('bsm.reports.acceleration.managers'));
        $searchResponse->assertRedirect(route('login'));
    }

    public function test_non_bsm_cannot_export_acceleration_report_or_search_managers(): void
    {
        $response = $this->actingAs($this->agent)->get(route('bsm.reports.acceleration.export'));
        $response->assertForbidden();

        $searchResponse = $this->actingAs($this->agent)->get(route('bsm.reports.acceleration.managers'));
        $searchResponse->assertForbidden();
    }

    public function test_bsm_can_search_managers_via_json_endpoint(): void
    {
        $response = $this->actingAs($this->bsm)->getJson(route('bsm.reports.acceleration.managers', [
            'q' => $this->rm->first_name,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'email', 'phone', 'role', 'role_label'],
        ]);

        $json = $response->json();
        $this->assertNotEmpty($json);
        $this->assertEquals($this->rm->id, $json[0]['id']);
        $this->assertEquals('Regional Manager', $json[0]['role_label']);

        // Search with role filter
        $roleFiltered = $this->actingAs($this->bsm)->getJson(route('bsm.reports.acceleration.managers', [
            'role' => 'coordinator',
        ]));
        $roleFiltered->assertOk();
        $results = $roleFiltered->json();
        foreach ($results as $item) {
            $this->assertEquals('coordinator', $item['role']);
        }
    }

    public function test_bsm_can_export_acceleration_report_as_csv(): void
    {
        // Create sample SIMs with various statuses
        Sim::create([
            'number'              => '080' . random_int(10000000, 99999999),
            'category'            => 'POS SIM',
            'provider'            => 'mtn',
            'regional_manager_id' => $this->rm->id,
            'coordinator_id'      => $this->coordinator->id,
            'partner_id'          => $this->partner->id,
            'user_id'             => $this->agent->id,
            'status'              => SimStatus::ASSIGNED_TO_PARTNER,
            'created_at'          => now()->subDays(12),
        ]);

        Sim::create([
            'number'              => '081' . random_int(10000000, 99999999),
            'category'            => 'POS SIM',
            'provider'            => 'mtn',
            'regional_manager_id' => $this->rm->id,
            'coordinator_id'      => $this->coordinator->id,
            'partner_id'          => $this->partner->id,
            'user_id'             => $this->agent->id,
            'status'              => SimStatus::ACTIVATED,
            'created_at'          => now()->subDays(3),
            'activated_at'        => now()->subDays(2),
        ]);

        // Create sample inventory request
        SimRequest::create([
            'user_id'    => $this->partner->id,
            'category'   => 'POS SIM',
            'provider'   => 'mtn',
            'quantity'   => 15,
            'status'     => 'pending',
            'created_at' => now()->subDays(2),
        ]);

        // Create sample failed bonus report
        Report::create([
            'user_id'      => $this->partner->id,
            'service_id'   => $this->service->id,
            'phone_number' => '08099887766',
            'network'      => 'MTN',
            'ref'          => 'FAIL_BSM_' . uniqid(),
            'amount'       => 0.00,
            'type'         => 'activation_bonus_data',
            'status'       => 'failed',
            'description'  => 'Vendor API network timeout',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
            'created_at'   => now()->subDay(),
        ]);

        $response = $this->actingAs($this->bsm)->get(route('bsm.reports.acceleration.export', [
            'format' => 'csv',
            'range'  => '30days',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('filename="bsm-sales-acceleration-report-', (string) $response->headers->get('content-disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('SALES ACCELERATION & INTERVENTIONS REPORT', $content);
        $this->assertStringContainsString('EXECUTIVE ACCELERATION & INTERVENTIONS OVERVIEW', $content);
        $this->assertStringContainsString('MANAGER ACCELERATION & FIELD VELOCITY SUMMARY', $content);
        $this->assertStringContainsString('STALLED FIELD STOCK LINE ITEMS', $content);
        $this->assertStringContainsString('PENDING INVENTORY DISPATCH REQUESTS', $content);
        $this->assertStringContainsString('STALLED ACTIVATION BONUS RETRIES', $content);
        $this->assertStringContainsString($this->rm->first_name, $content);
    }

    public function test_bsm_can_export_acceleration_report_as_pdf(): void
    {
        // Create sample dormant SIM
        Sim::create([
            'number'              => '070' . random_int(10000000, 99999999),
            'category'            => 'POS SIM',
            'provider'            => 'mtn',
            'regional_manager_id' => $this->rm->id,
            'coordinator_id'      => $this->coordinator->id,
            'partner_id'          => $this->partner->id,
            'user_id'             => $this->agent->id,
            'status'              => SimStatus::ASSIGNED_TO_PARTNER,
            'created_at'          => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->bsm)->get(route('bsm.reports.acceleration.export', [
            'format' => 'pdf',
            'range'  => '30days',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('filename="bsm-sales-acceleration-report-', (string) $response->headers->get('content-disposition'));

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    public function test_bsm_can_export_with_custom_date_range_and_specific_manager(): void
    {
        $startDate = now()->subDays(15)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->bsm)->get(route('bsm.reports.acceleration.export', [
            'format'     => 'csv',
            'range'      => 'custom',
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'manager_id' => $this->rm->id,
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('Custom Range', $content);
        $this->assertStringContainsString($this->rm->email, $content);
    }

    public function test_bsm_can_filter_report_by_manager_search_query_and_role(): void
    {
        $response = $this->actingAs($this->bsm)->get(route('bsm.reports.acceleration.export', [
            'format'         => 'csv',
            'range'          => '7days',
            'manager_search' => $this->rm->first_name,
            'manager_role'   => 'regional_manager',
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('Last 7 Days', $content);
        $this->assertStringContainsString($this->rm->first_name, $content);
    }

    public function test_super_admin_can_also_export_acceleration_report(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('bsm.reports.acceleration.export', [
            'format' => 'csv',
        ]));

        $response->assertOk();
    }
}
