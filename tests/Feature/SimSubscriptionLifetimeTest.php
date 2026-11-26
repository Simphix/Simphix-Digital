<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\SimSubscription;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SimAssignmentService;
use App\Services\SimSubscriptionService;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimSubscriptionLifetimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $partner;
    protected Service $simService;
    protected ServiceField $posCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role'   => 'super_admin',
            'status' => 'active',
        ]);
        Wallet::firstOrCreateForUser($this->superAdmin->id, 50000.00);

        $this->partner = User::factory()->create([
            'role'   => 'partner',
            'status' => 'active',
        ]);
        $partnerWallet = Wallet::firstOrCreateForUser($this->partner->id);
        $partnerWallet->update([
            'balance' => 10000.00,
            'bonus'   => 5000.00,
        ]);

        // Ensure simcard service and POS SIM category exist
        $this->simService = Service::firstOrCreate(
            ['name' => 'simcard'],
            ['description' => 'SIM Cards Service', 'is_active' => true]
        );

        $this->posCategory = ServiceField::firstOrCreate(
            ['service_id' => $this->simService->id, 'field_name' => 'POS SIM'],
            ['field_code' => 'POS-SIM', 'field_type' => 'text', 'base_price' => 1000.00, 'is_active' => true]
        );

        ServicePrice::firstOrCreate(
            ['service_fields_id' => $this->posCategory->id, 'user_type' => 'partner', 'user_id' => null],
            ['service_id' => $this->simService->id, 'price' => 1000.00, 'commission' => 200]
        );
    }

    public function test_activated_sim_sets_360_days_subscription_lifetime(): void
    {
        $sim = Sim::create([
            'number'     => '09012345678',
            'category'   => 'POS SIM',
            'provider'   => 'mtn',
            'partner_id' => $this->partner->id,
            'user_id'    => $this->partner->id,
            'status'     => SimStatus::ASSIGNED_TO_PARTNER,
        ]);

        app(SimAssignmentService::class)->activate($sim, $this->partner);

        $sim->refresh();

        $this->assertEquals(SimStatus::ACTIVATED, $sim->status);
        $this->assertNotNull($sim->activated_at);
        $this->assertNotNull($sim->subscription_expires_at);
        $this->assertGreaterThanOrEqual(359, $sim->daysRemaining());
        $this->assertFalse($sim->isSubscriptionExpired());
        $this->assertEquals('active', $sim->subscriptionStatus());

        // Check SimSubscription record
        $sub = $sim->subscriptions()->first();
        $this->assertNotNull($sub);
        $this->assertEquals('active', $sub->status);
        $this->assertEquals('initial_activation', $sub->payment_method);
    }

    public function test_days_remaining_and_expired_status_calculation(): void
    {
        // 1. Fresh activated SIM
        $activeSim = Sim::create([
            'number'                  => '09011111111',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(10),
            'subscription_expires_at' => now()->addDays(355),
        ]);

        $this->assertEquals(355, $activeSim->daysRemaining());
        $this->assertFalse($activeSim->isSubscriptionExpired());
        $this->assertEquals('active', $activeSim->subscriptionStatus());

        // 2. Expiring soon SIM (e.g. 15 days left)
        $expiringSim = Sim::create([
            'number'                  => '09022222222',
            'category'                => 'POS SIM',
            'provider'                => 'airtel',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(350),
            'subscription_expires_at' => now()->addDays(15),
        ]);

        $this->assertEquals(15, $expiringSim->daysRemaining());
        $this->assertFalse($expiringSim->isSubscriptionExpired());
        $this->assertEquals('expiring_soon', $expiringSim->subscriptionStatus());

        // 3. Expired SIM (360 days completed)
        $expiredSim = Sim::create([
            'number'                  => '09033333333',
            'category'                => 'POS SIM',
            'provider'                => 'glo',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        $this->assertEquals(0, $expiredSim->daysRemaining());
        $this->assertTrue($expiredSim->isSubscriptionExpired());
        $this->assertEquals('expired', $expiredSim->subscriptionStatus());
    }

    public function test_partner_can_view_days_remaining_in_inventory(): void
    {
        Sim::create([
            'number'                  => '09044444444',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(5),
            'subscription_expires_at' => now()->addDays(360),
        ]);

        $response = $this->actingAs($this->partner)->get(route('sims.inventory'));

        $response->assertOk();
        $response->assertSee('Days Left');
        $response->assertSee('360 days left');
    }

    public function test_partner_can_reactivate_expired_sim_using_main_wallet(): void
    {
        $expiredSim = Sim::create([
            'number'                  => '09055555555',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        $partnerWallet = Wallet::where('user_id', $this->partner->id)->first();
        $balanceBefore = (float) $partnerWallet->balance;

        $response = $this->actingAs($this->partner)
            ->post(route('sims.activated.reactivate', $expiredSim), [
                'wallet_source' => 'main',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $expiredSim->refresh();
        $partnerWallet->refresh();

        $this->assertEquals($balanceBefore - 1000.00, (float) $partnerWallet->balance);
        $this->assertFalse($expiredSim->isSubscriptionExpired());
        $this->assertGreaterThanOrEqual(359, $expiredSim->daysRemaining());
        $this->assertEquals('active', $expiredSim->subscriptionStatus());

        $this->assertDatabaseHas('sim_subscriptions', [
            'sim_id'         => $expiredSim->id,
            'partner_id'     => $this->partner->id,
            'payment_method' => 'partner_wallet',
            'wallet_type'    => 'main',
            'status'         => 'active',
            'fee'            => 1000.00,
        ]);
    }

    public function test_partner_can_reactivate_expired_sim_using_commission_bonus_wallet(): void
    {
        $expiredSim = Sim::create([
            'number'                  => '09066666666',
            'category'                => 'POS SIM',
            'provider'                => 'airtel',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        $partnerWallet = Wallet::where('user_id', $this->partner->id)->first();
        $bonusBefore = (float) $partnerWallet->bonus;

        $response = $this->actingAs($this->partner)
            ->post(route('sims.activated.reactivate', $expiredSim), [
                'wallet_source' => 'bonus',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $partnerWallet->refresh();
        $this->assertEquals($bonusBefore - 1000.00, (float) $partnerWallet->bonus);

        $expiredSim->refresh();
        $this->assertFalse($expiredSim->isSubscriptionExpired());
    }

    public function test_admin_can_access_sim_subscriptions_page(): void
    {
        Sim::create([
            'number'                  => '09077777777',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(20),
            'subscription_expires_at' => now()->addDays(345),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.sim-subscriptions.index'));

        $response->assertOk();
        $response->assertSee('SIM Subscriptions');
        $response->assertSee('09077777777');
        $response->assertSee('345 days left');
    }

    public function test_admin_can_reactivate_sim_by_charging_partner(): void
    {
        $expiredSim = Sim::create([
            'number'                  => '09088888888',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(380),
            'subscription_expires_at' => now()->subDays(15),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.sim-subscriptions.reactivate', $expiredSim), [
                'payment_option' => 'partner_wallet',
                'wallet_source'  => 'main',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $expiredSim->refresh();
        $this->assertFalse($expiredSim->isSubscriptionExpired());
        $this->assertGreaterThanOrEqual(359, $expiredSim->daysRemaining());
    }

    public function test_admin_can_generate_one_time_funding_account_and_confirm_payment(): void
    {
        $expiredSim = Sim::create([
            'number'                  => '09099999999',
            'category'                => 'POS SIM',
            'provider'                => 'glo',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(375),
            'subscription_expires_at' => now()->subDays(10),
        ]);

        // Generate funding account (JSON request)
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.sim-subscriptions.generate-funding-account', $expiredSim));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $json = $response->json();
        $this->assertNotEmpty($json['account_number']);
        $this->assertEquals(1000.00, $json['amount']);

        $subscription = SimSubscription::find($json['subscription_id']);
        $this->assertNotNull($subscription);
        $this->assertEquals('pending', $subscription->status);
        $this->assertEquals('funding_account', $subscription->payment_method);

        // Confirm funding payment
        $confirmResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.sim-subscriptions.confirm-funding', $subscription));

        $confirmResponse->assertRedirect();
        $confirmResponse->assertSessionHas('success');

        $subscription->refresh();
        $expiredSim->refresh();

        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->paid_at);
        $this->assertFalse($expiredSim->isSubscriptionExpired());
        $this->assertEquals('active', $expiredSim->subscriptionStatus());
    }

    public function test_fintava_webhook_automatically_confirms_reactivation_deposit(): void
    {
        config(['services.fintava.webhook_secret' => 'test-webhook-secret']);

        $expiredSim = Sim::create([
            'number'                  => '09010101010',
            'category'                => 'POS SIM',
            'provider'                => 'airtel',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        $subscription = SimSubscription::create([
            'sim_id'            => $expiredSim->id,
            'partner_id'        => $this->partner->id,
            'starts_at'         => now(),
            'expires_at'        => now()->addDays(360),
            'fee'               => 1000.00,
            'payment_method'    => 'funding_account',
            'account_number'    => '9910001234',
            'account_name'      => 'SimPhix - Reactivation 09010101010',
            'bank_name'         => 'Loma Bank',
            'payment_reference' => 'REACT-WH-TEST',
            'status'            => 'pending',
        ]);

        $payload = [
            'event' => 'account_funded',
            'data'  => [
                'reference'               => 'DEP-REF-' . time(),
                'amount'                  => 1000.00,
                'accountNumber'           => '9910001234',
                'beneficiaryAccountNumber'=> '9910001234',
                'status'                  => 'success',
                'accountName'             => 'John Customer',
            ],
        ];

        $body = json_encode($payload);
        $signature = hash_hmac('sha512', $body, 'test-webhook-secret');

        $response = $this->call('POST', route('fintava.webhook'), [], [], [], [
            'CONTENT_TYPE'             => 'application/json',
            'HTTP_x-fintava-signature' => $signature,
        ], $body);

        $response->assertOk();

        $subscription->refresh();
        $expiredSim->refresh();

        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->paid_at);
        $this->assertFalse($expiredSim->isSubscriptionExpired());
    }

    public function test_bsm_can_view_sim_subscriptions_on_dashboard_and_subscriptions_page(): void
    {
        $bsm = User::factory()->create([
            'role'   => 'business_sales_manager',
            'status' => 'active',
        ]);

        $activeSim = Sim::create([
            'number'                  => '09022223333',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(10),
            'subscription_expires_at' => now()->addDays(355),
        ]);

        $expiredSim = Sim::create([
            'number'                  => '09033334444',
            'category'                => 'POS SIM',
            'provider'                => 'airtel',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        // Check BSM dashboard renders subscription metrics and alert
        $dashResponse = $this->actingAs($bsm)->get(route('bsm.dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertViewHas('totalSubscriptions');
        $dashResponse->assertViewHas('subscriptionExpiredCount', 1);
        $dashResponse->assertSee('SIM Subscriptions Desk');
        $dashResponse->assertSee('SIM Subscriptions Requiring Action');

        // Check BSM subscriptions desk page
        $subsResponse = $this->actingAs($bsm)->get(route('bsm.subscriptions'));
        $subsResponse->assertOk();
        $subsResponse->assertSeeText('SIM Subscriptions & Lifetimes');
        $subsResponse->assertSee('09022223333');
        $subsResponse->assertSee('09033334444');
        $subsResponse->assertSee('Expired (0d)');
    }

    public function test_bsm_can_reactivate_sim_subscription_via_partner_wallet(): void
    {
        $bsm = User::factory()->create([
            'role'   => 'business_sales_manager',
            'status' => 'active',
        ]);

        $expiredSim = Sim::create([
            'number'                  => '09044445555',
            'category'                => 'POS SIM',
            'provider'                => 'glo',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        $partnerWallet = $this->partner->wallet;
        $initialBalance = (float) $partnerWallet->balance;

        $response = $this->actingAs($bsm)->post(route('bsm.subscriptions.reactivate', $expiredSim), [
            'payment_option' => 'partner_wallet',
            'wallet_source'  => 'main',
            'notes'          => 'BSM reactivation test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $expiredSim->refresh();
        $partnerWallet->refresh();

        $this->assertFalse($expiredSim->isSubscriptionExpired());
        $this->assertEquals($initialBalance - 1000.00, (float) $partnerWallet->balance);
        $this->assertDatabaseHas('sim_subscriptions', [
            'sim_id'         => $expiredSim->id,
            'partner_id'     => $this->partner->id,
            'payment_method' => 'partner_wallet',
            'wallet_type'    => 'main',
            'status'         => 'active',
        ]);
    }

    public function test_bsm_can_generate_funding_account_for_reactivation(): void
    {
        $bsm = User::factory()->create([
            'role'   => 'business_sales_manager',
            'status' => 'active',
        ]);

        $expiredSim = Sim::create([
            'number'                  => '09055556666',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now()->subDays(370),
            'subscription_expires_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($bsm)->postJson(route('bsm.subscriptions.generate-funding-account', $expiredSim));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['account_number']);
        $this->assertNotEmpty($data['bank_name']);
        $this->assertEquals(1000.00, $data['amount']);

        $this->assertDatabaseHas('sim_subscriptions', [
            'sim_id'         => $expiredSim->id,
            'payment_method' => 'funding_account',
            'status'         => 'pending',
            'account_number' => $data['account_number'],
        ]);
    }

    public function test_sim_lifecycle_countdown_decrements_at_midnight_every_day(): void
    {
        $tz = 'Africa/Lagos';

        // 1. Activate at 11:30:00 AM on Nov 17, 2026
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-17 11:30:00', $tz));

        $sim = Sim::create([
            'number'                  => '09088889999',
            'category'                => 'POS SIM',
            'provider'                => 'mtn',
            'partner_id'              => $this->partner->id,
            'status'                  => SimStatus::ACTIVATED,
            'activated_at'            => now(),
            'subscription_expires_at' => now()->copy()->addDays(360),
        ]);

        // Day of activation: 360 days
        $this->assertEquals(360, $sim->daysRemaining());

        // Later that day (6:00 PM): remains 360 days
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-17 18:00:00', $tz));
        $this->assertEquals(360, $sim->daysRemaining());

        // 1 second before midnight: remains 360 days
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-17 23:59:59', $tz));
        $this->assertEquals(360, $sim->daysRemaining());

        // Exactly at 12:00:00 AM midnight (start of Nov 18): decrements by 1 day to 359 days
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-18 00:00:00', $tz));
        $this->assertEquals(359, $sim->daysRemaining());

        // Noon on Nov 18: remains 359 days
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-18 12:00:00', $tz));
        $this->assertEquals(359, $sim->daysRemaining());

        // Exactly at next 12:00:00 AM midnight (start of Nov 19): decrements by 1 day to 358 days
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-19 00:00:00', $tz));
        $this->assertEquals(358, $sim->daysRemaining());

        // Clean up test clock
        \Carbon\Carbon::setTestNow();
    }
}
