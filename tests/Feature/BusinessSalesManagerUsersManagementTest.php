<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BusinessSalesManagerUsersManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $bsm;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bsm = User::factory()->create([
            'role'              => 'business_sales_manager',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $this->superAdmin = User::factory()->create([
            'role'              => 'super_admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_bsm_dashboard_renders_users_management_action_and_desk(): void
    {
        $response = $this->actingAs($this->bsm)->get(route('bsm.dashboard'));

        $response->assertOk();
        $response->assertSee('Users Management');
        $response->assertSee(route('admin.manage.users'));
    }

    public function test_bsm_can_access_users_management_index_and_details(): void
    {
        $user = User::factory()->create([
            'first_name' => 'FieldTarget',
            'role'       => 'agent',
            'status'     => 'active',
        ]);

        // Access users index
        $indexResponse = $this->actingAs($this->bsm)->get(route('admin.manage.users'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Manage Users');
        $indexResponse->assertSee('FieldTarget');

        // Access user show page
        $showResponse = $this->actingAs($this->bsm)->get(route('admin.manage.users.show', $user));
        $showResponse->assertOk();
        $showResponse->assertSee('FieldTarget');

        // Access user team page
        $teamResponse = $this->actingAs($this->bsm)->get(route('admin.manage.users.team', $user));
        $teamResponse->assertOk();
    }

    public function test_bsm_can_edit_and_update_regular_user_to_allowed_roles(): void
    {
        $target = User::factory()->create([
            'first_name' => 'AgentJohn',
            'last_name'  => 'Doe',
            'email'      => 'agent.john@example.com',
            'phone'      => '08033445566',
            'role'       => 'agent',
            'status'     => 'active',
        ]);

        // BSM can view edit form
        $editResponse = $this->actingAs($this->bsm)->get(route('admin.manage.users.edit', $target));
        $editResponse->assertOk();
        // Option for business_sales_manager must NOT be present in dropdown
        $editResponse->assertDontSee('<option value="business_sales_manager"', false);

        // BSM can update target to partner
        $updateResponse = $this->actingAs($this->bsm)->put(route('admin.manage.users.update', $target), [
            'first_name' => 'AgentJohn',
            'last_name'  => 'Doe',
            'email'      => $target->email,
            'phone'      => '08033445566',
            'status'     => 'active',
            'role'       => 'partner',
        ]);

        $updateResponse->assertRedirect(route('admin.manage.users'));
        $updateResponse->assertSessionHas('success');
        $this->assertEquals('partner', $target->fresh()->role);
    }

    public function test_bsm_cannot_assign_or_upgrade_user_to_bsm_role_via_user_edit(): void
    {
        $target = User::factory()->create([
            'first_name' => 'TestCandidate',
            'last_name'  => 'User',
            'email'      => 'test.cand@example.com',
            'phone'      => '08099112233',
            'role'       => 'personal',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($this->bsm)->put(route('admin.manage.users.update', $target), [
            'first_name' => 'TestCandidate',
            'last_name'  => 'User',
            'email'      => $target->email,
            'phone'      => '08099112233',
            'status'     => 'active',
            'role'       => 'business_sales_manager',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertEquals('personal', $target->fresh()->role);
    }

    public function test_bsm_cannot_assign_or_upgrade_user_to_bsm_role_via_access_update(): void
    {
        $target = User::factory()->create([
            'role'   => 'coordinator',
            'status' => 'active',
        ]);

        // Attempting to set role to business_sales_manager
        $response = $this->actingAs($this->bsm)->put(route('admin.manage.access.update', $target), [
            'role' => 'business_sales_manager',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertEquals('coordinator', $target->fresh()->role);
    }

    public function test_bsm_cannot_approve_upgrade_request_to_bsm_role(): void
    {
        $candidate = User::factory()->create([
            'role'                 => 'partner',
            'upgrade_status'       => 'pending',
            'pending_role'         => 'business_sales_manager',
            'upgrade_requested_at' => now(),
            'status'               => 'active',
        ]);

        $response = $this->actingAs($this->bsm)
            ->post(route('admin.manage.upgrades.approve', $candidate));

        $response->assertSessionHas('error');
        $this->assertEquals('pending', $candidate->fresh()->upgrade_status);
        $this->assertEquals('partner', $candidate->fresh()->role);

        // Meanwhile, super_admin can approve it
        $adminResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.manage.upgrades.approve', $candidate));

        $adminResponse->assertSessionHas('success');
        $this->assertEquals('approved', $candidate->fresh()->upgrade_status);
        $this->assertEquals('business_sales_manager', $candidate->fresh()->role);
    }

    public function test_bsm_can_approve_regular_upgrade_requests(): void
    {
        $candidate = User::factory()->create([
            'role'                 => 'agent',
            'upgrade_status'       => 'pending',
            'pending_role'         => 'partner',
            'upgrade_requested_at' => now(),
            'status'               => 'active',
        ]);

        $response = $this->actingAs($this->bsm)
            ->post(route('admin.manage.upgrades.approve', $candidate));

        $response->assertSessionHas('success');
        $this->assertEquals('approved', $candidate->fresh()->upgrade_status);
        $this->assertEquals('partner', $candidate->fresh()->role);
    }

    public function test_bsm_cannot_modify_or_delete_super_admin_or_another_bsm(): void
    {
        $otherBsm = User::factory()->create([
            'role'   => 'business_sales_manager',
            'status' => 'active',
        ]);

        // 1. Edit page forbidden for other BSM
        $this->actingAs($this->bsm)
            ->get(route('admin.manage.users.edit', $otherBsm))
            ->assertForbidden();

        // 2. Edit page forbidden for Super Admin
        $this->actingAs($this->bsm)
            ->get(route('admin.manage.users.edit', $this->superAdmin))
            ->assertForbidden();

        // 3. Update forbidden for other BSM
        $this->actingAs($this->bsm)
            ->put(route('admin.manage.users.update', $otherBsm), [
                'first_name' => 'Tampered',
                'last_name'  => 'BSM',
                'email'      => $otherBsm->email,
                'phone'      => '08099887711',
                'status'     => 'active',
                'role'       => 'agent',
            ])
            ->assertForbidden();

        // 4. Access update rejected for other BSM
        $accessResponse = $this->actingAs($this->bsm)
            ->put(route('admin.manage.access.update', $otherBsm), [
                'role' => 'agent',
            ]);
        $accessResponse->assertSessionHas('error');
        $this->assertEquals('business_sales_manager', $otherBsm->fresh()->role);

        // 5. Delete rejected for other BSM
        $deleteResponse = $this->actingAs($this->bsm)
            ->delete(route('admin.manage.users.destroy', $otherBsm));
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $otherBsm->id]);

        // 6. Delete rejected for Super Admin
        $deleteAdminResponse = $this->actingAs($this->bsm)
            ->delete(route('admin.manage.users.destroy', $this->superAdmin));
        $deleteAdminResponse->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_non_staff_users_cannot_access_users_management(): void
    {
        $partner = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $this->actingAs($partner)
            ->get(route('admin.manage.users'))
            ->assertForbidden();
    }
}
