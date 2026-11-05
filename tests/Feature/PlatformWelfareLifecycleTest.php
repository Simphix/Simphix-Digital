<?php

namespace Tests\Feature;

use App\Events\SimActivated;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SimActivationWalletService;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlatformWelfareLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure simcard service and test field exist
        $service = Service::firstOrCreate(
            ['name' => 'simcard'],
            ['description' => 'SIM Card Service', 'is_active' => true]
        );

        ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'welfare_test_category'],
            ['field_code' => 'welfare_test_cat', 'description' => 'Welfare Test Category', 'base_price' => 1200.00, 'is_active' => true]
        );
    }

    public function test_super_admin_can_configure_platform_welfare_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.platform-wallets.welfare-settings.update'), [
            'amount_per_activation' => 65.50,
            'welfare_type' => 'fixed',
            'description' => 'Healthcare & Emergency Welfare',
            'deduct_from_sim_operations' => '1',
            'is_active' => '1',
        ]);

        $response->assertSessionHas('success');

        $settings = PlatformWelfareSettings::current()->fresh();
        $this->assertEquals(65.50, (float) $settings->amount_per_activation);
        $this->assertEquals('fixed', $settings->welfare_type);
        $this->assertEquals('Healthcare & Emergency Welfare', $settings->description);
        $this->assertTrue($settings->deduct_from_sim_operations);
        $this->assertTrue($settings->is_active);
    }

    public function test_welfare_calculation_fixed_and_percent(): void
    {
        $settings = PlatformWelfareSettings::current();

        // Fixed type
        $settings->update([
            'amount_per_activation' => 80.00,
            'welfare_type' => 'fixed',
            'is_active' => true,
        ]);
        $this->assertEquals(80.00, $settings->calculateWelfareAmount());

        // Percent type (5% of 1200 = 60)
        $settings->update([
            'amount_per_activation' => 5.00,
            'welfare_type' => 'percent',
            'is_active' => true,
        ]);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'welfare_test_category',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
        ]);
        $this->assertEquals(60.00, $settings->calculateWelfareAmount($sim, 1200.00));

        // Inactive returns 0.00
        $settings->update(['is_active' => false]);
        $this->assertEquals(0.00, $settings->calculateWelfareAmount($sim));
    }

    public function test_sim_activation_event_deducts_welfare_from_sim_operations_wallet_and_credits_welfare_wallet(): void
    {
        $settings = PlatformWelfareSettings::current();
        $settings->update([
            'amount_per_activation' => 75.00,
            'welfare_type' => 'fixed',
            'description' => 'Platform Welfare Fund',
            'deduct_from_sim_operations' => true,
            'is_active' => true,
        ]);

        $walletService = app(SimActivationWalletService::class);
        $simOpsWallet = $walletService->getWallet();

        // Pre-credit SIM Operations Wallet with 2000.00
        $partner = User::factory()->create(['role' => 'partner']);
        Wallet::firstOrCreateForUser($partner->id);

        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'welfare_test_category',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'partner_id' => $partner->id,
        ]);

        $walletService->creditActivationFee($sim, 2000.00, $partner);

        $simOpsBalanceBefore = (float) $simOpsWallet->fresh()->balance;
        $welfareWallet = PlatformWallet::forType(PlatformWallet::TYPE_WELFARE, 'Platform Welfare Wallet');
        $welfareBalanceBefore = (float) $welfareWallet->fresh()->balance;

        // Trigger SimActivated event (without commissions to isolate welfare)
        event(new SimActivated($sim, $partner, false));

        $simOpsBalanceAfter = (float) $simOpsWallet->fresh()->balance;
        $welfareBalanceAfter = (float) $welfareWallet->fresh()->balance;

        // 1. Sim operations wallet should have debited 75.00
        $this->assertEquals($simOpsBalanceBefore - 75.00, $simOpsBalanceAfter);

        // 2. Welfare wallet should have credited 75.00
        $this->assertEquals($welfareBalanceBefore + 75.00, $welfareBalanceAfter);

        // 3. Verify ledger transactions
        $this->assertDatabaseHas('platform_wallet_transactions', [
            'platform_wallet_id' => $simOpsWallet->id,
            'source' => 'sim_activation_welfare',
            'amount' => 75.00,
            'type' => 'debit',
        ]);

        $this->assertDatabaseHas('platform_wallet_transactions', [
            'platform_wallet_id' => $welfareWallet->id,
            'source' => 'sim_activation',
            'amount' => 75.00,
            'type' => 'credit',
        ]);
    }

    public function test_welfare_deduction_is_skipped_for_business_and_business_agents(): void
    {
        $settings = PlatformWelfareSettings::current();
        $settings->update([
            'amount_per_activation' => 50.00,
            'welfare_type' => 'fixed',
            'deduct_from_sim_operations' => true,
            'is_active' => true,
        ]);

        $walletService = app(SimActivationWalletService::class);
        $simOpsWallet = $walletService->getWallet();
        $initialSimOpsBalance = (float) $simOpsWallet->balance;

        $business = User::factory()->create(['role' => 'business']);
        $businessAgent = User::factory()->create([
            'role' => 'business_agent',
            'referred_by' => $business->id,
        ]);

        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'welfare_test_category',
            'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
            'business_id' => $business->id,
            'business_agent_id' => $businessAgent->id,
        ]);

        // Trigger activation for business SIM
        event(new SimActivated($sim, $businessAgent, false));

        // SIM Operations Wallet balance must remain completely untouched
        $this->assertEquals($initialSimOpsBalance, (float) $simOpsWallet->fresh()->balance);

        // No sim_activation_welfare transaction on sim operations wallet
        $this->assertDatabaseMissing('platform_wallet_transactions', [
            'platform_wallet_id' => $simOpsWallet->id,
            'source' => 'sim_activation_welfare',
            'related_id' => $sim->id,
        ]);
    }

    public function test_welfare_does_not_fire_when_inactive(): void
    {
        $settings = PlatformWelfareSettings::current();
        $settings->update([
            'amount_per_activation' => 50.00,
            'is_active' => false,
        ]);

        $walletService = app(SimActivationWalletService::class);
        $simOpsWallet = $walletService->getWallet();
        $initialBalance = (float) $simOpsWallet->balance;

        $user = User::factory()->create(['role' => 'partner']);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'welfare_test_category',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'partner_id' => $user->id,
        ]);

        event(new SimActivated($sim, $user, false));

        $this->assertEquals($initialBalance, (float) $simOpsWallet->fresh()->balance);
    }
}
