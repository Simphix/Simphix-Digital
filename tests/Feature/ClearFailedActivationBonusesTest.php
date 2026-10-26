<?php

namespace Tests\Feature;

use App\Jobs\ClearFailedActivationBonuses;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClearFailedActivationBonusesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_job_deletes_failed_and_retrying_activation_bonus_reports(): void
    {
        $user = User::factory()->create();

        // Failed activation bonus report
        $failedReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'mtn',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'failed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Activation bonus data top-up failed: vendor outage',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        // Retrying activation bonus report
        $retryingReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'mtn',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'retrying',
            'type'         => 'activation_bonus_data',
            'description'  => 'Activation bonus retry in flight',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        // Successful activation bonus report (must NOT be deleted)
        $completedReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'mtn',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'completed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Activation bonus delivered successfully',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        // Other failed report (e.g. airtime) - must NOT be deleted
        $airtimeReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'mtn',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 500.00,
            'status'       => 'failed',
            'type'         => 'airtime',
            'description'  => 'Airtime recharge failed',
            'old_balance'  => 500.00,
            'new_balance'  => 500.00,
        ]);

        $job = new ClearFailedActivationBonuses();
        $cleared = $job->handle();

        $this->assertGreaterThanOrEqual(2, $cleared);
        $this->assertDatabaseMissing('reports', ['id' => $failedReport->id]);
        $this->assertDatabaseMissing('reports', ['id' => $retryingReport->id]);
        $this->assertDatabaseHas('reports', ['id' => $completedReport->id]);
        $this->assertDatabaseHas('reports', ['id' => $airtimeReport->id]);
    }

    public function test_job_can_mark_records_as_resolved_instead_of_deleting(): void
    {
        $user = User::factory()->create();

        $failedReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'airtel',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'failed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Activation bonus data top-up failed',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $job = new ClearFailedActivationBonuses(asResolved: true);
        $cleared = $job->handle();

        $this->assertGreaterThanOrEqual(1, $cleared);
        $failedReport->refresh();
        $this->assertEquals('resolved', $failedReport->status);
        $this->assertNotNull($failedReport->resolved_at);
        $this->assertStringContainsString('[Cleared by Admin]', $failedReport->description);
    }

    public function test_job_filters_by_days(): void
    {
        $user = User::factory()->create();

        // Old report (created 10 days ago)
        $oldReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'mtn',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'failed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Old failed bonus',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);
        $oldReport->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

        // Recent report (created 1 hour ago)
        $recentReport = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'mtn',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'failed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Recent failed bonus',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
            'created_at'   => now()->subHour(),
        ]);

        $job = new ClearFailedActivationBonuses(olderThanDays: 5);
        $job->handle();

        $this->assertDatabaseMissing('reports', ['id' => $oldReport->id]);
        $this->assertDatabaseHas('reports', ['id' => $recentReport->id]);
    }

    public function test_artisan_command_clears_failed_activation_bonuses(): void
    {
        $user = User::factory()->create();

        $report = Report::create([
            'user_id'      => $user->id,
            'phone_number' => '080' . rand(10000000, 99999999),
            'network'      => 'glo',
            'ref'          => 'REF_' . Str::random(10),
            'amount'       => 0.00,
            'status'       => 'failed',
            'type'         => 'activation_bonus_data',
            'description'  => 'Failed via Glo',
            'old_balance'  => 0.00,
            'new_balance'  => 0.00,
        ]);

        $this->artisan('sim:clear-failed-activation-bonuses', ['--network' => 'glo'])
            ->expectsOutputToContain('Successfully deleted')
            ->assertSuccessful();

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_artisan_command_can_dispatch_to_queue(): void
    {
        Queue::fake();

        $this->artisan('activation-bonus:clear-failed', ['--queue' => true])
            ->expectsOutputToContain('dispatched to the background queue')
            ->assertSuccessful();

        Queue::assertPushed(ClearFailedActivationBonuses::class);
    }
}
