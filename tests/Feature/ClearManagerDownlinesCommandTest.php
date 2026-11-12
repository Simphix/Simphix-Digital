<?php

namespace Tests\Feature;

use App\Console\Commands\ClearManagerDownlinesCommand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearManagerDownlinesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_role_downlines_are_moved_to_platform_master_manager(): void
    {
        $masterManager = User::platformMasterManager();

        $regionalManager = User::factory()->create([
            'role'   => 'regional_manager',
            'status' => 'active',
        ]);

        $coordinator = User::factory()->create([
            'role'        => 'coordinator',
            'referred_by' => $regionalManager->id,
            'status'      => 'active',
        ]);

        $partner = User::factory()->create([
            'role'        => 'partner',
            'referred_by' => $coordinator->id,
            'status'      => 'active',
        ]);

        $this->artisan('downlines:clear', ['--force' => true])
            ->assertSuccessful();

        // Catalog roles should now point to Master Manager
        $this->assertEquals($masterManager->id, $coordinator->fresh()->referred_by);
        $this->assertEquals($masterManager->id, $partner->fresh()->referred_by);
    }

    public function test_catalog_role_already_under_master_manager_remains_under_master_manager(): void
    {
        $masterManager = User::platformMasterManager();

        $coordinator = User::factory()->create([
            'role'        => 'coordinator',
            'referred_by' => $masterManager->id,
            'status'      => 'active',
        ]);

        $this->artisan('downlines:clear', ['--force' => true])
            ->assertSuccessful();

        $this->assertEquals($masterManager->id, $coordinator->fresh()->referred_by);
    }

    public function test_admin_and_staff_accounts_have_upline_removed_completely(): void
    {
        $masterManager = User::platformMasterManager();

        $someManager = User::factory()->create([
            'role'   => 'regional_manager',
            'status' => 'active',
        ]);

        $superAdmin = User::factory()->create([
            'role'        => 'super_admin',
            'referred_by' => $someManager->id,
            'status'      => 'active',
        ]);

        $bsm = User::factory()->create([
            'role'        => 'business_sales_manager',
            'referred_by' => $masterManager->id,
            'status'      => 'active',
        ]);

        $support = User::factory()->create([
            'role'        => 'support',
            'referred_by' => $someManager->id,
            'status'      => 'active',
        ]);

        $this->artisan('downlines:clear', ['--force' => true])
            ->assertSuccessful();

        // All admin/staff accounts must have upline removed completely (null)
        $this->assertNull($superAdmin->fresh()->referred_by);
        $this->assertNull($bsm->fresh()->referred_by);
        $this->assertNull($support->fresh()->referred_by);
    }

    public function test_customer_downlines_have_upline_removed_by_default(): void
    {
        $partner = User::factory()->create([
            'role'   => 'partner',
            'status' => 'active',
        ]);

        $personal = User::factory()->create([
            'role'        => 'personal',
            'referred_by' => $partner->id,
            'status'      => 'active',
        ]);

        $agent = User::factory()->create([
            'role'        => 'agent',
            'referred_by' => $partner->id,
            'status'      => 'active',
        ]);

        $this->artisan('downlines:clear', ['--force' => true])
            ->assertSuccessful();

        // Personal and Agent customer downlines have uplines removed
        $this->assertNull($personal->fresh()->referred_by);
        $this->assertNull($agent->fresh()->referred_by);
    }

    public function test_customer_downlines_can_be_assigned_to_master_manager_with_flag(): void
    {
        $masterManager = User::platformMasterManager();

        $partner = User::factory()->create([
            'role'   => 'partner',
            'status' => 'active',
        ]);

        $personal = User::factory()->create([
            'role'        => 'personal',
            'referred_by' => $partner->id,
            'status'      => 'active',
        ]);

        $this->artisan('downlines:clear', [
            '--force'     => true,
            '--customers' => 'master',
        ])->assertSuccessful();

        $this->assertEquals($masterManager->id, $personal->fresh()->referred_by);
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $regionalManager = User::factory()->create([
            'role'   => 'regional_manager',
            'status' => 'active',
        ]);

        $coordinator = User::factory()->create([
            'role'        => 'coordinator',
            'referred_by' => $regionalManager->id,
            'status'      => 'active',
        ]);

        $superAdmin = User::factory()->create([
            'role'        => 'super_admin',
            'referred_by' => $regionalManager->id,
            'status'      => 'active',
        ]);

        $this->artisan('downlines:clear', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY-RUN MODE ACTIVE]')
            ->assertSuccessful();

        // No database changes in dry-run
        $this->assertEquals($regionalManager->id, $coordinator->fresh()->referred_by);
        $this->assertEquals($regionalManager->id, $superAdmin->fresh()->referred_by);
    }

    public function test_manager_id_option_scopes_to_specific_manager(): void
    {
        $masterManager = User::platformMasterManager();

        $mgr1 = User::factory()->create([
            'role'   => 'regional_manager',
            'status' => 'active',
        ]);

        $mgr2 = User::factory()->create([
            'role'   => 'regional_manager',
            'status' => 'active',
        ]);

        $coord1 = User::factory()->create([
            'role'        => 'coordinator',
            'referred_by' => $mgr1->id,
            'status'      => 'active',
        ]);

        $coord2 = User::factory()->create([
            'role'        => 'coordinator',
            'referred_by' => $mgr2->id,
            'status'      => 'active',
        ]);

        // Target only mgr1
        $this->artisan('downlines:clear', [
            '--manager-id' => $mgr1->id,
            '--force'      => true,
        ])->assertSuccessful();

        // coord1 should be moved to Master Manager
        $this->assertEquals($masterManager->id, $coord1->fresh()->referred_by);

        // coord2 should remain with mgr2
        $this->assertEquals($mgr2->id, $coord2->fresh()->referred_by);
    }

    public function test_cannot_clear_downlines_from_platform_master_manager(): void
    {
        $masterManager = User::platformMasterManager();

        $this->artisan('downlines:clear', [
            '--manager-id' => $masterManager->id,
            '--force'      => true,
        ])
            ->expectsOutputToContain('The Platform Level Master Manager is the platform holding seat and cannot have its downlines cleared.')
            ->assertFailed();
    }
}
