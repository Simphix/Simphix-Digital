<?php

namespace Tests\Feature;

use App\Events\SimActivated;
use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\State;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PlatformWalletLedger;
use App\Services\Reconciliation\PlatformWalletReconciler;
use App\Services\SimActivationWalletService;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SimActivationWalletLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure simcard service and test field exist
        $service = Service::firstOrCreate(
            ['name' => 'simcard'],
            ['display_name' => 'SIM Card Service', 'status' => 'active']
        );

        $field = ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'test_personal'],
            ['display_name' => 'Test Personal', 'field_code' => 'test_personal', 'base_price' => 1000.00]
        );

        // Configure commissions for partner, coordinator, regional_manager
        ServicePrice::updateOrCreate(
            ['service_fields_id' => $field->id, 'user_type' => 'personal', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 1000.00, 'commission' => 0.00]
        );

        ServicePrice::updateOrCreate(
            ['service_fields_id' => $field->id, 'user_type' => 'partner', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 1000.00, 'commission' => 150.00]
        );

        ServicePrice::updateOrCreate(
            ['service_fields_id' => $field->id, 'user_type' => 'coordinator', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 1000.00, 'commission' => 100.00]
        );

        ServicePrice::updateOrCreate(
            ['service_fields_id' => $field->id, 'user_type' => 'regional_manager', 'user_id' => null],
            ['service_id' => $service->id, 'price' => 1000.00, 'commission' => 50.00]
        );
    }

    public function test_sim_activation_fee_is_credited_to_platform_wallet(): void
    {
        $walletService = app(SimActivationWalletService::class);
        $initialBalance = (float) $walletService->getWallet()->balance;

        $user = User::factory()->create(['role' => 'partner']);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'test_personal',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'partner_id' => $user->id,
        ]);

        $tx = $walletService->creditActivationFee($sim, 1000.00, $user);

        $this->assertNotNull($tx);
        $this->assertEquals(1000.00, (float) $tx->amount);
        $this->assertEquals('credit', $tx->type);
        $this->assertEquals('sim_activation_fee', $tx->source);

        $updatedWallet = $walletService->getWallet();
        $this->assertEquals($initialBalance + 1000.00, (float) $updatedWallet->balance);
    }

    public function test_commissions_are_deducted_from_sim_activation_wallet(): void
    {
        $walletService = app(SimActivationWalletService::class);

        // Pre-credit 1000.00
        $partner = User::factory()->create(['role' => 'partner']);
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $rm = User::factory()->create(['role' => 'regional_manager']);

        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'test_personal',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'partner_id' => $partner->id,
            'coordinator_id' => $coordinator->id,
            'regional_manager_id' => $rm->id,
        ]);

        $walletService->creditActivationFee($sim, 1000.00, $partner);
        $balanceAfterFee = (float) $walletService->getWallet()->balance;

        // Deduct commission for partner (150.00), coordinator (100.00), RM (50.00)
        $walletService->deductCommission($sim, 150.00, $partner);
        $walletService->deductCommission($sim, 100.00, $coordinator);
        $walletService->deductCommission($sim, 50.00, $rm);

        $finalWallet = $walletService->getWallet();
        $expectedBalance = $balanceAfterFee - (150.00 + 100.00 + 50.00);

        $this->assertEquals($expectedBalance, (float) $finalWallet->balance);
        $this->assertEquals(300.00, (float) $finalWallet->total_debited);
    }

    public function test_full_lifecycle_on_activation_event_runs_on_sim_activation_wallet(): void
    {
        $walletService = app(SimActivationWalletService::class);
        $initialBalance = (float) $walletService->getWallet()->balance;

        $partner = User::factory()->create(['role' => 'partner']);
        Wallet::firstOrCreateForUser($partner->id);

        $coordinator = User::factory()->create(['role' => 'coordinator']);
        Wallet::firstOrCreateForUser($coordinator->id);

        $rm = User::factory()->create(['role' => 'regional_manager']);
        Wallet::firstOrCreateForUser($rm->id);

        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'test_personal',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'partner_id' => $partner->id,
            'coordinator_id' => $coordinator->id,
            'regional_manager_id' => $rm->id,
        ]);

        // 1. Credit activation fee into the wallet
        $walletService->creditActivationFee($sim, 1000.00, $partner);

        // 2. Dispatch SimActivated event (which triggers AwardCommissions listener)
        event(new SimActivated($sim, $partner, true));

        // Total commissions configured: Partner 150 + Coordinator 100 + RM 50 = 300
        $finalWallet = $walletService->getWallet();
        $expectedNetGain = 1000.00 - 300.00; // 700.00

        $this->assertEquals($initialBalance + $expectedNetGain, (float) $finalWallet->balance);

        // Verify ledger transactions exist
        $this->assertDatabaseHas('platform_wallet_transactions', [
            'platform_wallet_id' => $finalWallet->id,
            'source' => 'sim_activation_fee',
            'amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('platform_wallet_transactions', [
            'platform_wallet_id' => $finalWallet->id,
            'source' => 'sim_activation_commission',
            'amount' => 150.00,
        ]);

        $this->assertDatabaseHas('platform_wallet_transactions', [
            'platform_wallet_id' => $finalWallet->id,
            'source' => 'sim_activation_commission',
            'amount' => 100.00,
        ]);

        $this->assertDatabaseHas('platform_wallet_transactions', [
            'platform_wallet_id' => $finalWallet->id,
            'source' => 'sim_activation_commission',
            'amount' => 50.00,
        ]);
    }

    public function test_business_and_business_agents_are_excluded_from_sim_activation_wallet(): void
    {
        $walletService = app(SimActivationWalletService::class);
        $initialBalance = (float) $walletService->getWallet()->balance;

        $business = User::factory()->create(['role' => 'business']);
        $businessAgent = User::factory()->create([
            'role' => 'business_agent',
            'referred_by' => $business->id,
        ]);

        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'test_personal',
            'status' => SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
            'business_id' => $business->id,
            'business_agent_id' => $businessAgent->id,
        ]);

        // Attempting to credit or deduct for a business SIM should do nothing
        $creditTx = $walletService->creditActivationFee($sim, 1000.00, $businessAgent);
        $this->assertNull($creditTx);

        $debitTx = $walletService->deductCommission($sim, 200.00, $businessAgent);
        $this->assertNull($debitTx);

        // Balance remains unchanged
        $this->assertEquals($initialBalance, (float) $walletService->getWallet()->balance);
    }

    public function test_admin_wallet_dashboard_displays_sim_activation_wallet(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $walletService = app(SimActivationWalletService::class);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'test_personal',
            'status' => SimStatus::ACTIVATED,
        ]);

        $walletService->creditActivationFee($sim, 500.00);

        $response = $this->actingAs($admin)->get(route('admin.manage.adminwallet'));

        $response->assertOk();
        $response->assertSee('SIM Activation Wallet');
    }

    public function test_platform_wallet_reconciler_validates_sim_activation_wallet_integrity(): void
    {
        $reconciler = new PlatformWalletReconciler();
        $findings = $reconciler->findings(Carbon::now()->subDay(), Carbon::now()->addDay());

        $simActivationFindings = $findings->filter(function ($f) {
            return ($f->details['type'] ?? '') === PlatformWallet::TYPE_SIM_ACTIVATION;
        });

        // Drift should be zero
        $this->assertCount(0, $simActivationFindings);
    }
}
