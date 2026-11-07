<?php

namespace Tests\Feature;

use App\Events\SimActivated;
use App\Models\Business;
use App\Models\PlatformBsmCommissionSettings;
use App\Models\PlatformWallet;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\User;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BsmCentralCommissionWalletTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $service = Service::firstOrCreate(
            ['name' => 'simcard'],
            ['description' => 'SIM Card Service', 'is_active' => true]
        );

        ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'bsm_test_category'],
            ['field_code' => 'bsm_test_cat', 'description' => 'BSM Test Category', 'base_price' => 1500.00, 'is_active' => true]
        );
    }

    public function test_super_admin_can_view_and_configure_bsm_commission_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.platform-wallets.bsm-commission-settings.edit'))
            ->assertStatus(200)
            ->assertSee('BSM Central Commission Settings');

        $response = $this->actingAs($admin)->put(route('admin.platform-wallets.bsm-commission-settings.update'), [
            'amount_per_activation' => 50.00,
            'commission_type' => 'fixed',
            'description' => 'BSM Operations Commission',
            'deduct_from_sim_operations' => '1',
            'is_active' => '1',
        ]);

        $response->assertSessionHas('success');

        $settings = PlatformBsmCommissionSettings::current()->fresh();
        $this->assertEquals(50.00, (float) $settings->amount_per_activation);
        $this->assertEquals('fixed', $settings->commission_type);
        $this->assertEquals('BSM Operations Commission', $settings->description);
        $this->assertTrue($settings->deduct_from_sim_operations);
        $this->assertTrue($settings->is_active);
    }

    public function test_bsm_commission_calculation_fixed_and_percent(): void
    {
        $settings = PlatformBsmCommissionSettings::current();

        // Fixed
        $settings->update([
            'amount_per_activation' => 120.00,
            'commission_type' => 'fixed',
            'is_active' => true,
        ]);
        $this->assertEquals(120.00, $settings->calculateCommissionAmount());

        // Percent (10% of 1500 = 150)
        $settings->update([
            'amount_per_activation' => 10.00,
            'commission_type' => 'percent',
            'is_active' => true,
        ]);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'bsm_test_category',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
        ]);
        $this->assertEquals(150.00, $settings->calculateCommissionAmount($sim, 1500.00));

        // Inactive returns 0.00
        $settings->update(['is_active' => false]);
        $this->assertEquals(0.00, $settings->calculateCommissionAmount($sim));
    }

    public function test_sim_activation_event_credits_bsm_wallet_and_debits_sim_operations(): void
    {
        $settings = PlatformBsmCommissionSettings::current();
        $settings->update([
            'amount_per_activation' => 75.00,
            'commission_type' => 'fixed',
            'description' => 'BSM Central Activation Cut',
            'deduct_from_sim_operations' => true,
            'is_active' => true,
        ]);

        $bsmWallet = PlatformWallet::forType(PlatformWallet::TYPE_BSM_COMMISSION, 'BSM Central Commission Wallet');
        $simOperationsWallet = PlatformWallet::forType(PlatformWallet::TYPE_SIM_ACTIVATION, 'SIM Activation Wallet');

        $initialBsmBalance = (float) $bsmWallet->balance;
        $initialSimOpsBalance = (float) $simOperationsWallet->balance;

        $agent = User::factory()->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'airtel',
            'category' => 'bsm_test_category',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'agent_id' => $agent->id,
        ]);

        event(new SimActivated($sim, $agent));

        $bsmWallet->refresh();
        $simOperationsWallet->refresh();

        $this->assertEquals($initialBsmBalance + 75.00, (float) $bsmWallet->balance);
        $this->assertEquals($initialSimOpsBalance - 75.00, (float) $simOperationsWallet->balance);

        $bsmTx = $bsmWallet->transactions()->latest()->first();
        $this->assertNotNull($bsmTx);
        $this->assertEquals(75.00, (float) $bsmTx->amount);
        $this->assertEquals('credit', $bsmTx->type);
        $this->assertEquals('sim_activation', $bsmTx->source);
    }

    public function test_sim_activation_does_not_debit_sim_operations_when_toggle_is_disabled(): void
    {
        $settings = PlatformBsmCommissionSettings::current();
        $settings->update([
            'amount_per_activation' => 40.00,
            'commission_type' => 'fixed',
            'deduct_from_sim_operations' => false,
            'is_active' => true,
        ]);

        $bsmWallet = PlatformWallet::forType(PlatformWallet::TYPE_BSM_COMMISSION, 'BSM Central Commission Wallet');
        $simOperationsWallet = PlatformWallet::forType(PlatformWallet::TYPE_SIM_ACTIVATION, 'SIM Activation Wallet');

        $initialBsmBalance = (float) $bsmWallet->balance;
        $initialSimOpsBalance = (float) $simOperationsWallet->balance;

        $agent = User::factory()->create(['role' => 'agent', 'status' => 'active']);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'glo',
            'category' => 'bsm_test_category',
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
            'agent_id' => $agent->id,
        ]);

        event(new SimActivated($sim, $agent));

        $bsmWallet->refresh();
        $simOperationsWallet->refresh();

        $this->assertEquals($initialBsmBalance + 40.00, (float) $bsmWallet->balance);
        $this->assertEquals($initialSimOpsBalance, (float) $simOperationsWallet->balance);
    }

    public function test_sim_activation_skips_bsm_commission_for_enterprise_partner(): void
    {
        $settings = PlatformBsmCommissionSettings::current();
        $settings->update([
            'amount_per_activation' => 50.00,
            'commission_type' => 'fixed',
            'is_active' => true,
        ]);

        $bsmWallet = PlatformWallet::forType(PlatformWallet::TYPE_BSM_COMMISSION, 'BSM Central Commission Wallet');
        $initialBsmBalance = (float) $bsmWallet->balance;

        $businessUser = User::factory()->create(['role' => 'business', 'status' => 'active']);
        $sim = Sim::create([
            'number' => '080' . rand(10000000, 99999999),
            'provider' => 'mtn',
            'category' => 'bsm_test_category',
            'business_id' => $businessUser->id,
            'status' => SimStatus::ASSIGNED_TO_PARTNER,
        ]);

        event(new SimActivated($sim, $businessUser));

        $bsmWallet->refresh();
        $this->assertEquals($initialBsmBalance, (float) $bsmWallet->balance);
    }

    public function test_bsm_user_has_view_only_access_to_commission_wallet(): void
    {
        $bsmUser = User::factory()->create([
            'role' => 'business_sales_manager',
            'status' => 'active',
        ]);

        $this->actingAs($bsmUser)
            ->get(route('bsm.commission-wallet'))
            ->assertStatus(200)
            ->assertSee('BSM Central Commission Wallet')
            ->assertSee('View-Only')
            ->assertSee('Read-Only Commission Monitor');
    }

    public function test_non_bsm_user_cannot_access_bsm_commission_wallet(): void
    {
        $agent = User::factory()->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $this->actingAs($agent)
            ->get(route('bsm.commission-wallet'))
            ->assertStatus(403);
    }

    public function test_admin_wallet_dashboard_displays_bsm_wallet_card(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.manage.adminwallet'))
            ->assertStatus(200)
            ->assertSee('BSM Commission Wallet')
            ->assertSee(route('admin.platform-wallets.bsm-commission-settings.edit'));
    }
}
