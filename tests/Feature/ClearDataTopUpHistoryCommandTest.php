<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\SimActivationBonusGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClearDataTopUpHistoryCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_warns_when_no_matching_topups_found(): void
    {
        // Delete any existing top-up reports first within transaction
        Report::whereIn('type', ['admin_data_topup', 'data', 'activation_bonus_data'])->delete();

        $this->artisan('topups:clear-history --force')
            ->expectsOutputToContain('No matching data top-up history records found to clear.')
            ->assertSuccessful();
    }

    public function test_prompts_for_confirmation_and_aborts_when_declined(): void
    {
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08012345678',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-TEST-CONFIRM',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'Admin data top-up test',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $this->artisan('topups:clear-history')
            ->expectsConfirmation('Are you sure you want to permanently clear 1 data top-up history record(s)? This action cannot be undone.', 'no')
            ->expectsOutputToContain('Operation cancelled by user')
            ->assertSuccessful();

        $this->assertDatabaseHas('reports', ['ref' => 'TOPUP-TEST-CONFIRM']);
    }

    public function test_dry_run_previews_without_deleting(): void
    {
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08012345678',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-TEST-DRYRUN',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'Admin data top-up test',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $this->artisan('topups:clear-history --dry-run')
            ->expectsOutputToContain('[DRY-RUN] Preview complete. No records were deleted.')
            ->assertSuccessful();

        $this->assertDatabaseHas('reports', ['ref' => 'TOPUP-TEST-DRYRUN']);
    }

    public function test_force_clears_data_topups_while_preserving_other_reports(): void
    {
        // 1. Data top-up reports (should be cleared)
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08011111111',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-ADMIN-1',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'Admin topup',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08022222222',
            'network'      => 'GLO',
            'ref'          => 'TOPUP-PAID-1',
            'amount'       => 500.00,
            'status'       => 'completed',
            'type'         => 'data',
            'description'  => 'Paid SME data',
            'old_balance'  => 1000.00,
            'new_balance'  => 500.00,
        ]);

        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08033333333',
            'network'      => 'AIRTEL',
            'ref'          => 'TOPUP-BONUS-1',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Activation bonus data',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        // 2. Non-topup reports (must NEVER be touched)
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08044444444',
            'network'      => 'MTN',
            'ref'          => 'KEEP-COMMISSION-1',
            'amount'       => 200.00,
            'status'       => 'completed',
            'type'         => 'commission',
            'description'  => 'Partner commission',
            'old_balance'  => 1000.00,
            'new_balance'  => 1200.00,
        ]);

        $this->artisan('topups:clear-history --force')
            ->expectsOutputToContain('Successfully cleared')
            ->assertSuccessful();

        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-ADMIN-1']);
        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-PAID-1']);
        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-BONUS-1']);
        $this->assertDatabaseHas('reports', ['ref' => 'KEEP-COMMISSION-1']);
    }

    public function test_admin_only_filter_clears_only_admin_data_topups(): void
    {
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08011111111',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-ADMIN-ONLY',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'Admin topup',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08022222222',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-PAID-KEEP',
            'amount'       => 500.00,
            'status'       => 'completed',
            'type'         => 'data',
            'description'  => 'Paid SME data',
            'old_balance'  => 1000.00,
            'new_balance'  => 500.00,
        ]);

        $this->artisan('topups:clear-history --admin-only --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-ADMIN-ONLY']);
        $this->assertDatabaseHas('reports', ['ref' => 'TOPUP-PAID-KEEP']);
    }

    public function test_failed_only_filter_clears_only_failed_records(): void
    {
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08011111111',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-FAILED-1',
            'amount'       => 0.00,
            'status'       => 'failed',
            'type'         => 'admin_data_topup',
            'description'  => 'Failed topup',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08022222222',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-SUCCESS-KEEP',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'Completed topup',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $this->artisan('topups:clear-history --failed-only --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-FAILED-1']);
        $this->assertDatabaseHas('reports', ['ref' => 'TOPUP-SUCCESS-KEEP']);
    }

    public function test_network_and_phone_filter_work_correctly(): void
    {
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08099998888',
            'network'      => 'GLO',
            'ref'          => 'TOPUP-GLO-MATCH',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'GLO topup',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08011112222',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-MTN-OTHER',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'MTN topup',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $this->artisan('topups:clear-history --network=GLO --phone=08099998888 --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-GLO-MATCH']);
        $this->assertDatabaseHas('reports', ['ref' => 'TOPUP-MTN-OTHER']);
    }

    public function test_aliases_work_identically(): void
    {
        Report::create([
            'user_id'      => $this->user->id,
            'phone_number' => '08011119999',
            'network'      => 'MTN',
            'ref'          => 'TOPUP-ALIAS-TEST',
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'admin_data_topup',
            'description'  => 'Alias test',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $this->artisan('topups:clear --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('reports', ['ref' => 'TOPUP-ALIAS-TEST']);
    }
}
