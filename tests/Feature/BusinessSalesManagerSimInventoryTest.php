<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\User;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BusinessSalesManagerSimInventoryTest extends TestCase
{
    use DatabaseTransactions;

    private User $bsm;
    private User $admin;
    private User $rm;
    private User $coordinator;
    private User $partner;
    private User $personalUser;
    private ServiceField $field;

    protected function setUp(): void
    {
        parent::setUp();

        $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
        $this->field = ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'POS SIM TEST BSM'],
            ['field_code' => 'POS-BSM-' . uniqid(), 'base_price' => 1000, 'is_active' => true]
        );

        ServicePrice::firstOrCreate(
            ['service_fields_id' => $this->field->id, 'user_type' => 'personal', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 1000, 'commission' => 200]
        );

        $this->bsm = User::factory()->create([
            'role'              => 'business_sales_manager',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $this->admin = User::factory()->create([
            'role'              => 'super_admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $this->rm = User::factory()->create([
            'role'   => 'regional_manager',
            'status' => 'active',
        ]);

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

        $this->personalUser = User::factory()->create([
            'role'   => 'personal',
            'status' => 'active',
        ]);
    }

    public function test_bsm_is_completely_forbidden_from_admin_level_sim_management_routes(): void
    {
        $this->actingAs($this->bsm)
            ->get(route('admin.sim-plan.index'))
            ->assertForbidden();

        $this->actingAs($this->bsm)
            ->post(route('admin.sim-plan.store'), [
                'provider' => 'mtn',
                'numbers'  => '08012345678',
                'category' => 'POS SIM',
            ])
            ->assertForbidden();

        $this->actingAs($this->bsm)
            ->post(route('admin.sim-plan.direct-assign'), [])
            ->assertForbidden();

        $this->actingAs($this->bsm)
            ->post(route('admin.sim-plan.import'))
            ->assertForbidden();

        $this->actingAs($this->bsm)
            ->get(route('admin.sim-plan.bulk-assign'))
            ->assertForbidden();

        $this->actingAs($this->bsm)
            ->get(route('admin.sim-plan.bulk-collect'))
            ->assertForbidden();
    }

    public function test_admin_can_assign_unassigned_sim_to_bsm(): void
    {
        $sim = Sim::create([
            'number'   => '080' . random_int(10000000, 99999999),
            'category' => 'POS SIM TEST BSM',
            'provider' => 'mtn',
            'status'   => SimStatus::UNASSIGNED,
        ]);

        $assignmentService = app(SimAssignmentService::class);
        $assignmentService->adminAssign($sim, $this->bsm, $this->admin);

        $sim->refresh();
        $this->assertEquals(SimStatus::ASSIGNED_TO_BSM, $sim->status);
        $this->assertEquals($this->bsm->id, $sim->business_sales_manager_id);
        $this->assertEquals($this->bsm->id, $sim->user_id);
    }

    public function test_bsm_has_access_to_all_sim_inventory_and_only_sees_sims_assigned_to_him(): void
    {
        // 1. Sim assigned to this BSM
        $bsmSim = Sim::create([
            'number'                    => '08011112233',
            'category'                  => 'POS SIM TEST BSM',
            'provider'                  => 'mtn',
            'business_sales_manager_id' => $this->bsm->id,
            'user_id'                   => $this->bsm->id,
            'status'                    => SimStatus::ASSIGNED_TO_BSM,
        ]);

        // 2. Sim assigned to another manager
        $otherSim = Sim::create([
            'number'              => '08099998877',
            'category'            => 'POS SIM TEST BSM',
            'provider'            => 'glo',
            'regional_manager_id' => $this->rm->id,
            'status'              => SimStatus::ASSIGNED_TO_RM,
        ]);

        // 3. Unassigned SIM in admin pool
        $unassignedSim = Sim::create([
            'number'   => '08055554433',
            'category' => 'POS SIM TEST BSM',
            'provider' => 'airtel',
            'status'   => SimStatus::UNASSIGNED,
        ]);

        $response = $this->actingAs($this->bsm)->get(route('sims.inventory'));

        $response->assertOk();
        $response->assertSee('All SIMs');
        $response->assertSee($bsmSim->number);
        $response->assertDontSee($otherSim->number);
        $response->assertDontSee($unassignedSim->number);
    }

    public function test_bsm_can_assign_held_sim_to_any_user_on_the_platform(): void
    {
        $sim1 = Sim::create([
            'number'                    => '080' . random_int(10000000, 99999999),
            'category'                  => 'POS SIM TEST BSM',
            'provider'                  => 'mtn',
            'business_sales_manager_id' => $this->bsm->id,
            'user_id'                   => $this->bsm->id,
            'status'                    => SimStatus::ASSIGNED_TO_BSM,
        ]);

        $sim2 = Sim::create([
            'number'                    => '080' . random_int(10000000, 99999999),
            'category'                  => 'POS SIM TEST BSM',
            'provider'                  => 'mtn',
            'business_sales_manager_id' => $this->bsm->id,
            'user_id'                   => $this->bsm->id,
            'status'                    => SimStatus::ASSIGNED_TO_BSM,
        ]);

        $sim3 = Sim::create([
            'number'                    => '080' . random_int(10000000, 99999999),
            'category'                  => 'POS SIM TEST BSM',
            'provider'                  => 'mtn',
            'business_sales_manager_id' => $this->bsm->id,
            'user_id'                   => $this->bsm->id,
            'status'                    => SimStatus::ASSIGNED_TO_BSM,
        ]);

        // Assign sim1 to Regional Manager
        $responseRm = $this->actingAs($this->bsm)->post(route('sims.assign'), [
            'sim_id'  => $sim1->id,
            'user_id' => $this->rm->id,
        ]);
        $responseRm->assertSessionHas('success');
        $sim1->refresh();
        $this->assertEquals(SimStatus::ASSIGNED_TO_RM, $sim1->status);
        $this->assertEquals($this->rm->id, $sim1->regional_manager_id);
        $this->assertEquals($this->bsm->id, $sim1->business_sales_manager_id);

        // Assign sim2 to Partner
        $responsePartner = $this->actingAs($this->bsm)->post(route('sims.assign'), [
            'sim_id'  => $sim2->id,
            'user_id' => $this->partner->id,
        ]);
        $responsePartner->assertSessionHas('success');
        $sim2->refresh();
        $this->assertEquals(SimStatus::ASSIGNED_TO_PARTNER, $sim2->status);
        $this->assertEquals($this->partner->id, $sim2->partner_id);
        $this->assertEquals($this->bsm->id, $sim2->business_sales_manager_id);

        // Bulk assign sim3 to Personal user
        $responsePersonal = $this->actingAs($this->bsm)->post(route('sims.bulk-assign'), [
            'sim_ids' => [$sim3->id],
            'user_id' => $this->personalUser->id,
        ]);
        $responsePersonal->assertSessionHas('success');
        $sim3->refresh();
        $this->assertEquals(SimStatus::ASSIGNED_TO_PARTNER, $sim3->status);
        $this->assertEquals($this->personalUser->id, $sim3->user_id);
        $this->assertEquals($this->bsm->id, $sim3->business_sales_manager_id);
    }

    public function test_bsm_cannot_assign_sims_they_do_not_hold(): void
    {
        $otherSim = Sim::create([
            'number'              => '080' . random_int(10000000, 99999999),
            'category'            => 'POS SIM TEST BSM',
            'provider'            => 'mtn',
            'regional_manager_id' => $this->rm->id,
            'status'              => SimStatus::ASSIGNED_TO_RM,
        ]);

        $response = $this->actingAs($this->bsm)->post(route('sims.assign'), [
            'sim_id'  => $otherSim->id,
            'user_id' => $this->partner->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertNotEquals($this->partner->id, $otherSim->fresh()->partner_id);
    }

    public function test_bsm_can_collect_back_sim_assigned_to_field(): void
    {
        $sim = Sim::create([
            'number'                    => '080' . random_int(10000000, 99999999),
            'category'                  => 'POS SIM TEST BSM',
            'provider'                  => 'mtn',
            'business_sales_manager_id' => $this->bsm->id,
            'regional_manager_id'       => $this->rm->id,
            'status'                    => SimStatus::ASSIGNED_TO_RM,
        ]);

        $response = $this->actingAs($this->bsm)->post(route('sims.bulk-collect'), [
            'sim_ids' => [$sim->id],
            'user_id' => $this->rm->id,
        ]);

        $response->assertSessionHas('success');
        $sim->refresh();
        $this->assertEquals(SimStatus::ASSIGNED_TO_BSM, $sim->status);
        $this->assertNull($sim->regional_manager_id);
        $this->assertEquals($this->bsm->id, $sim->business_sales_manager_id);
    }

    public function test_bsm_dashboard_and_sidebar_links_to_all_sims_inventory_and_not_admin_sim_plan(): void
    {
        $response = $this->actingAs($this->bsm)->get(route('bsm.dashboard'));

        $response->assertOk();
        $response->assertSee(route('sims.inventory'));
        $response->assertSee('All SIMs Inventory');
        $response->assertDontSee(route('admin.sim-plan.index'));
    }
}
