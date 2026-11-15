<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ServiceAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->customer = User::factory()->create([
            'role' => 'personal',
            'status' => 'active',
            'email_verified_at' => now(),
            'first_name' => 'Service',
            'last_name' => 'Customer',
            'phone' => '080' . random_int(10000000, 99999999),
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'address' => '1 Example Street',
        ]);

        foreach (['simcard', 'verification', 'airtime', 'smedata', 'cac', 'travel', 'moniepoint_pos', 'loans'] as $serviceKey) {
            $this->service($serviceKey, true);
        }
    }

    public function test_admin_services_pricing_can_toggle_service_availability(): void
    {
        $service = $this->service('travel', true);

        $this->actingAs($this->admin)
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee('Travel & Tours Packages');

        $this->actingAs($this->admin)
            ->put(route('admin.services.update', $service), [
                'name' => $service->name,
                'description' => $service->description,
            ])
            ->assertRedirect();

        $this->assertFalse(Service::isAvailable('travel'));
        $this->actingAs($this->admin)
            ->put(route('admin.services.update', $service->fresh()), [
                'name' => $service->name,
                'description' => $service->description,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue(Service::isAvailable('travel'));
    }

    public function test_all_customer_service_controls_are_available_in_admin_pricing(): void
    {
        $adminServicesPage = $this->actingAs($this->admin)->get(route('admin.services.index'));
        $adminServicesPage->assertOk();

        foreach (['simcard', 'verification', 'airtime', 'smedata', 'cac', 'travel', 'moniepoint_pos', 'loans'] as $key) {
            $this->assertTrue(Service::isAvailable($key), "Expected {$key} to have an active service control.");
        }

        foreach ([
            ['simcard', 'sim', 'sims', 'sim services', 'sim_plan'],
            ['verification', 'identity_verification', 'identity verification'],
            ['airtime', 'vtu'],
            ['smedata', 'sme_data', 'sme data'],
            ['cac', 'cac_registration', 'cac registration'],
            ['travel', 'travel_and_tours', 'travel & tours'],
            ['moniepoint_pos', 'moniepoint pos', 'pos'],
            ['loans', 'business_loans', 'business loans'],
        ] as $aliases) {
            $placeholders = implode(',', array_fill(0, count($aliases), '?'));
            $this->assertTrue(
                Service::query()->whereRaw("LOWER(name) IN ({$placeholders})", $aliases)->exists(),
                'Expected a service control for: ' . $aliases[0]
            );
        }
    }

    public function test_disabling_services_blocks_their_customer_pages_and_requests(): void
    {
        $routesByService = [
            'simcard' => '/sims',
            'verification' => '/verification/nin',
            'cac' => '/cac',
            'travel' => '/travel',
            'moniepoint_pos' => '/moniepoint/pos',
            'loans' => '/loans',
        ];

        foreach ($routesByService as $serviceKey => $path) {
            $this->service($serviceKey, false);
            $this->actingAs($this->customer)
                ->get($path)
                ->assertRedirect(route('dashboard'));
        }
    }

    public function test_bvn_and_nin_pages_show_admin_configured_role_prices(): void
    {
        $service = $this->service('verification', true);
        $bvnField = ServiceField::updateOrCreate(
            ['field_code' => '600'],
            ['service_id' => $service->id, 'field_name' => 'BVN Verification', 'base_price' => 100, 'is_active' => true]
        );
        $ninField = ServiceField::updateOrCreate(
            ['field_code' => '610'],
            ['service_id' => $service->id, 'field_name' => 'NIN Verification', 'base_price' => 200, 'is_active' => true]
        );

        ServicePrice::updateOrCreate(
            ['service_fields_id' => $bvnField->id, 'user_type' => 'personal', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 731, 'commission' => 0]
        );
        ServicePrice::updateOrCreate(
            ['service_fields_id' => $ninField->id, 'user_type' => 'personal', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 842, 'commission' => 0]
        );

        $this->actingAs($this->customer)
            ->get(route('bvn.verification.index'))
            ->assertOk()
            ->assertSee('731.00');

        $this->get(route('nin.verification.index'))
            ->assertOk()
            ->assertSee('842.00');
    }

    public function test_disabled_services_are_removed_from_dashboard_shortcuts_banners_and_sidebar(): void
    {
        foreach (['simcard', 'verification', 'cac', 'travel', 'moniepoint_pos', 'loans'] as $key) {
            $this->service($key, false);
        }

        $response = $this->actingAs($this->customer)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee(route('sims.index'), false)
            ->assertDontSee(route('nin.verification.index'), false)
            ->assertDontSee(route('bvn.verification.index'), false)
            ->assertDontSee(route('cac.overview'), false)
            ->assertDontSee(route('travel.index'), false)
            ->assertDontSee(route('moniepoint.pos.index'), false)
            ->assertDontSee(route('loans.index'), false)
            ->assertDontSee('SIM Services')
            ->assertDontSee('Verification')
            ->assertDontSee('CAC Registration')
            ->assertDontSee('Travel &amp; Tours')
            ->assertDontSee('POS Request')
            ->assertDontSee('Request Your POS Terminal Today')
            ->assertDontSee('Instant Working Capital &amp; Business Loans');
    }

    public function test_quick_services_expand_when_more_than_eight_items_are_available(): void
    {
        $response = $this->actingAs($this->customer)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('View all')
            ->assertSee('x-show="showAllQuickServices"', false)
            ->assertSee(route('loans.index'), false);

        $this->service('travel', false);
        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('View all')
            ->assertDontSee('x-show="showAllQuickServices"', false);
    }

    private function service(string $name, bool $isActive): Service
    {
        $service = Service::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if (! $service) {
            $service = Service::create([
                'name' => $name,
                'description' => ucfirst($name) . ' service',
                'is_active' => $isActive,
            ]);
        } else {
            $service->update(['is_active' => $isActive]);
        }

        return $service;
    }
}
