<?php

namespace Tests\Feature;

use App\Models\DownlineBatch;
use App\Models\DownlineBatchUser;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManagerReplacementDownlineBatchTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    public function test_candidate_search_finds_users_by_phone_email_and_name_across_any_role(): void
    {
        $outgoing = User::factory()->create([
            'role' => 'regional_manager',
            'status' => 'active',
        ]);

        $candidate1 = User::factory()->create([
            'first_name' => 'JohnUniqueX',
            'last_name' => 'DoeUniqueX',
            'email' => 'john.uniquex@example.com',
            'phone' => '08099887766',
            'role' => 'personal',
            'status' => 'active',
        ]);

        $candidate2 = User::factory()->create([
            'first_name' => 'AgentMary',
            'email' => 'mary.special@example.com',
            'phone' => '08122334455',
            'role' => 'agent',
            'status' => 'active',
        ]);

        // Search by phone
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.manage.users.replace.candidates', [
                'user' => $outgoing,
                'q' => '08099887766',
            ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals($candidate1->id, $data[0]['id']);

        // Search by email
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.manage.users.replace.candidates', [
                'user' => $outgoing,
                'q' => 'mary.special@example.com',
            ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals($candidate2->id, $data[0]['id']);

        // Search by name
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.manage.users.replace.candidates', [
                'user' => $outgoing,
                'q' => 'JohnUniqueX',
            ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals($candidate1->id, $data[0]['id']);
    }

    public function test_replace_manager_upgrades_candidate_role_and_batches_both_parties_downlines_to_master_manager(): void
    {
        $masterManager = User::platformMasterManager();

        // Outgoing Regional Manager
        $outgoing = User::factory()->create([
            'role' => 'regional_manager',
            'status' => 'active',
        ]);

        $region = Region::create([
            'name' => 'Test Region ' . uniqid(),
            'regional_manager_id' => $outgoing->id,
        ]);

        // Outgoing manager's downlines (e.g. 3 coordinators)
        $outgoingDownlines = User::factory()->count(3)->create([
            'role' => 'coordinator',
            'referred_by' => $outgoing->id,
            'status' => 'active',
        ]);

        // Incoming replacement user (currently an agent with 2 downlines)
        $incoming = User::factory()->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $incomingDownlines = User::factory()->count(2)->create([
            'role' => 'personal',
            'referred_by' => $incoming->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.manage.users.replace.store', $outgoing), [
                'incoming_user_id' => $incoming->id,
                'confirm' => 1,
            ]);

        $response->assertRedirect(route('admin.manage.users.show', $incoming));
        $response->assertSessionHas('success');

        // 1. Incoming user must be upgraded to outgoing manager's exact role
        $incoming->refresh();
        $this->assertEquals('regional_manager', $incoming->role);

        // 2. Outgoing manager jurisdiction was transferred
        $region->refresh();
        $this->assertEquals($incoming->id, $region->regional_manager_id);

        // 3. Outgoing downlines must now have referred_by = Master Manager
        foreach ($outgoingDownlines as $d) {
            $d->refresh();
            $this->assertEquals($masterManager->id, $d->referred_by);
        }

        // 4. Outgoing downlines batch was created
        $outgoingBatch = DownlineBatch::where('source_user_id', $outgoing->id)->first();
        $this->assertNotNull($outgoingBatch);
        $this->assertEquals('outgoing_manager', $outgoingBatch->source_type);
        $this->assertEquals(3, $outgoingBatch->initial_count);
        $this->assertEquals(3, $outgoingBatch->current_count);
        $this->assertEquals(0, $outgoingBatch->transferred_count);
        $this->assertEquals(DownlineBatch::STATUS_HELD, $outgoingBatch->status);
        $this->assertEquals($masterManager->id, $outgoingBatch->master_manager_id);
        $this->assertCount(3, $outgoingBatch->batchUsers);

        // 5. Incoming downlines must now also have referred_by = Master Manager
        foreach ($incomingDownlines as $d) {
            $d->refresh();
            $this->assertEquals($masterManager->id, $d->referred_by);
        }

        // 6. Incoming user downlines batch was created
        $incomingBatch = DownlineBatch::where('source_user_id', $incoming->id)->first();
        $this->assertNotNull($incomingBatch);
        $this->assertEquals('incoming_manager', $incomingBatch->source_type);
        $this->assertEquals(2, $incomingBatch->initial_count);
        $this->assertEquals(2, $incomingBatch->current_count);
        $this->assertEquals(0, $incomingBatch->transferred_count);
        $this->assertEquals(DownlineBatch::STATUS_HELD, $incomingBatch->status);
        $this->assertCount(2, $incomingBatch->batchUsers);
    }

    public function test_bulk_transfer_all_held_downlines_to_destination_manager(): void
    {
        $masterManager = User::platformMasterManager();

        $sourceManager = User::factory()->create([
            'role' => 'coordinator',
            'status' => 'active',
        ]);

        $downlines = User::factory()->count(4)->create([
            'role' => 'partner',
            'referred_by' => $masterManager->id,
            'status' => 'active',
        ]);

        $batch = DownlineBatch::create([
            'batch_reference' => 'BATCH-TEST-ALL-001',
            'source_user_id' => $sourceManager->id,
            'source_role' => $sourceManager->role,
            'source_type' => 'outgoing_manager',
            'master_manager_id' => $masterManager->id,
            'title' => 'Test Batch All',
            'initial_count' => 4,
            'current_count' => 4,
            'transferred_count' => 0,
            'status' => DownlineBatch::STATUS_HELD,
            'created_by' => $this->admin->id,
        ]);

        foreach ($downlines as $user) {
            DownlineBatchUser::create([
                'batch_id' => $batch->id,
                'user_id' => $user->id,
                'original_referred_by' => $sourceManager->id,
                'current_referred_by' => $masterManager->id,
                'status' => DownlineBatchUser::STATUS_HELD,
            ]);
        }

        $destinationManager = User::factory()->create([
            'role' => 'coordinator',
            'status' => 'active',
        ]);

        // Transfer all (no user_ids passed)
        $response = $this->actingAs($this->admin)
            ->post(route('admin.manage.downline-batches.transfer', $batch), [
                'destination_user_id' => $destinationManager->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // All 4 downlines should now have referred_by = destinationManager
        foreach ($downlines as $user) {
            $user->refresh();
            $this->assertEquals($destinationManager->id, $user->referred_by);
        }

        $batch->refresh();
        $this->assertEquals(0, $batch->current_count);
        $this->assertEquals(4, $batch->transferred_count);
        $this->assertEquals(DownlineBatch::STATUS_TRANSFERRED, $batch->status);

        foreach ($batch->batchUsers as $bu) {
            $this->assertEquals(DownlineBatchUser::STATUS_TRANSFERRED, $bu->status);
            $this->assertEquals($destinationManager->id, $bu->current_referred_by);
            $this->assertEquals($destinationManager->id, $bu->transferred_to_user_id);
            $this->assertEquals($this->admin->id, $bu->transferred_by);
            $this->assertNotNull($bu->transferred_at);
        }
    }

    public function test_partial_bulk_transfer_of_selected_users_updates_status_to_partially_transferred(): void
    {
        $masterManager = User::platformMasterManager();

        $sourceManager = User::factory()->create([
            'role' => 'regional_manager',
            'status' => 'active',
        ]);

        $downlines = User::factory()->count(5)->create([
            'role' => 'coordinator',
            'referred_by' => $masterManager->id,
            'status' => 'active',
        ]);

        $batch = DownlineBatch::create([
            'batch_reference' => 'BATCH-TEST-PARTIAL-001',
            'source_user_id' => $sourceManager->id,
            'source_role' => $sourceManager->role,
            'source_type' => 'outgoing_manager',
            'master_manager_id' => $masterManager->id,
            'title' => 'Test Batch Partial',
            'initial_count' => 5,
            'current_count' => 5,
            'transferred_count' => 0,
            'status' => DownlineBatch::STATUS_HELD,
            'created_by' => $this->admin->id,
        ]);

        foreach ($downlines as $user) {
            DownlineBatchUser::create([
                'batch_id' => $batch->id,
                'user_id' => $user->id,
                'original_referred_by' => $sourceManager->id,
                'current_referred_by' => $masterManager->id,
                'status' => DownlineBatchUser::STATUS_HELD,
            ]);
        }

        $destinationManager = User::factory()->create([
            'role' => 'regional_manager',
            'status' => 'active',
        ]);

        // Transfer only first 2 users
        $transferIds = [$downlines[0]->id, $downlines[1]->id];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.manage.downline-batches.transfer', $batch), [
                'destination_user_id' => $destinationManager->id,
                'user_ids' => $transferIds,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check the 2 transferred users
        $this->assertEquals($destinationManager->id, $downlines[0]->fresh()->referred_by);
        $this->assertEquals($destinationManager->id, $downlines[1]->fresh()->referred_by);

        // Check the 3 remaining users still on masterManager
        $this->assertEquals($masterManager->id, $downlines[2]->fresh()->referred_by);
        $this->assertEquals($masterManager->id, $downlines[3]->fresh()->referred_by);
        $this->assertEquals($masterManager->id, $downlines[4]->fresh()->referred_by);

        $batch->refresh();
        $this->assertEquals(3, $batch->current_count);
        $this->assertEquals(2, $batch->transferred_count);
        $this->assertEquals(DownlineBatch::STATUS_PARTIALLY_TRANSFERRED, $batch->status);
    }

    public function test_downline_batches_index_and_show_pages_render_for_admin(): void
    {
        $batch = DownlineBatch::create([
            'batch_reference' => 'BATCH-TEST-VIEW-001',
            'source_user_id' => $this->admin->id,
            'source_role' => 'regional_manager',
            'source_type' => 'outgoing_manager',
            'master_manager_id' => User::platformMasterManager()->id,
            'title' => 'Test Batch View',
            'initial_count' => 0,
            'current_count' => 0,
            'transferred_count' => 0,
            'status' => DownlineBatch::STATUS_HELD,
            'created_by' => $this->admin->id,
        ]);

        $indexResponse = $this->actingAs($this->admin)
            ->get(route('admin.manage.downline-batches.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Downline Batches');
        $indexResponse->assertSee($batch->batch_reference);

        $showResponse = $this->actingAs($this->admin)
            ->get(route('admin.manage.downline-batches.show', $batch));
        $showResponse->assertOk();
        $showResponse->assertSee($batch->batch_reference);
    }

    public function test_cannot_transfer_downline_batch_to_super_admin_or_inactive_user(): void
    {
        $masterManager = User::platformMasterManager();

        $batch = DownlineBatch::create([
            'batch_reference' => 'BATCH-FAIL-001',
            'source_user_id' => $this->admin->id,
            'source_role' => 'regional_manager',
            'source_type' => 'outgoing_manager',
            'master_manager_id' => $masterManager->id,
            'title' => 'Test Batch Fail',
            'initial_count' => 1,
            'current_count' => 1,
            'transferred_count' => 0,
            'status' => DownlineBatch::STATUS_HELD,
            'created_by' => $this->admin->id,
        ]);

        $downline = User::factory()->create([
            'role' => 'coordinator',
            'referred_by' => $masterManager->id,
            'status' => 'active',
        ]);

        DownlineBatchUser::create([
            'batch_id' => $batch->id,
            'user_id' => $downline->id,
            'original_referred_by' => $this->admin->id,
            'current_referred_by' => $masterManager->id,
            'status' => DownlineBatchUser::STATUS_HELD,
        ]);

        // 1. Attempt transfer to super_admin
        $response = $this->actingAs($this->admin)
            ->post(route('admin.manage.downline-batches.transfer', $batch), [
                'destination_user_id' => $this->admin->id,
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals($masterManager->id, $downline->fresh()->referred_by);

        // 2. Attempt transfer to inactive user
        $inactiveUser = User::factory()->create([
            'role' => 'regional_manager',
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.manage.downline-batches.transfer', $batch), [
                'destination_user_id' => $inactiveUser->id,
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals($masterManager->id, $downline->fresh()->referred_by);
    }

    public function test_candidate_search_excludes_outgoing_manager_and_super_admin(): void
    {
        $outgoing = User::factory()->create([
            'first_name' => 'SelfTarget',
            'role' => 'regional_manager',
            'status' => 'active',
        ]);

        $superAdmin = User::factory()->create([
            'first_name' => 'AdminCandidate',
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.manage.users.replace.candidates', [
                'user' => $outgoing,
                'q' => 'SelfTarget',
            ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertEmpty($data);

        $responseAdmin = $this->actingAs($this->admin)
            ->getJson(route('admin.manage.users.replace.candidates', [
                'user' => $outgoing,
                'q' => 'AdminCandidate',
            ]));

        $responseAdmin->assertOk();
        $dataAdmin = $responseAdmin->json();
        $this->assertEmpty($dataAdmin);
    }
}
