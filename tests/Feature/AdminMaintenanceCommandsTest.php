<?php

use App\Models\BusinessCommissionRate;
use App\Models\CacRegistration;
use App\Models\CashOutRequest;
use App\Models\CommissionEarning;
use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
use App\Models\EnterprisePartnerPayment;
use App\Models\LoanRequest;
use App\Models\MoniepointPosRequest;
use App\Models\PlatformCashOutRequest;
use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use App\Models\Sim;
use App\Models\SimActivationBonusGrant;
use App\Models\SimCustomer;
use App\Models\SimHistory;
use App\Models\SimRequest;
use App\Models\SimSwapRequest;
use App\Models\Transaction;
use App\Models\TravelPackage;
use App\Models\TravelRequest;
use App\Models\User;
use App\Models\WebhookLog;
use App\Support\SimStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sims:remove-activated respects cancellation and removes activated sims upon confirmation', function () {
    $user = User::factory()->create();

    $activatedSim = Sim::create([
        'number' => '08011112222',
        'category' => 'corporate',
        'provider' => 'mtn',
        'status' => SimStatus::ACTIVATED,
        'user_id' => $user->id,
    ]);

    SimCustomer::create([
        'sim_id' => $activatedSim->id,
        'full_name' => 'John Doe',
        'phone_number' => '08011112222',
    ]);

    SimHistory::create([
        'sim_id' => $activatedSim->id,
        'from_status' => SimStatus::ASSIGNED_TO_PARTNER,
        'to_status' => SimStatus::ACTIVATED,
        'note' => 'Activated for testing',
    ]);

    $unactivatedSim = Sim::create([
        'number' => '08033334444',
        'category' => 'corporate',
        'provider' => 'mtn',
        'status' => SimStatus::ASSIGNED_TO_PARTNER,
        'partner_id' => $user->id,
    ]);

    // 1. Cancellation test
    $this->artisan('sims:remove-activated')
        ->expectsConfirmation('Are you sure you want to permanently delete 1 activated SIM(s)? This will also remove their linked customer details, history logs, and bonus grant ledgers.', 'no')
        ->expectsOutputToContain('Operation cancelled by user')
        ->assertSuccessful();

    expect(Sim::where('id', $activatedSim->id)->exists())->toBeTrue()
        ->and(Sim::where('id', $unactivatedSim->id)->exists())->toBeTrue();

    // 2. Force deletion test
    $this->artisan('sims:remove-activated --force')
        ->expectsOutputToContain('Successfully removed 1 activated SIM(s)')
        ->assertSuccessful();

    expect(Sim::where('id', $activatedSim->id)->exists())->toBeFalse()
        ->and(SimCustomer::where('sim_id', $activatedSim->id)->exists())->toBeFalse()
        ->and(SimHistory::where('sim_id', $activatedSim->id)->exists())->toBeFalse()
        ->and(Sim::where('id', $unactivatedSim->id)->exists())->toBeTrue();
});

test('enterprise-partners:purge removes enterprise partners and their activated and unactivated SIMs', function () {
    $epUser = User::factory()->create([
        'role' => 'business',
        'business_name' => 'Acme EP LTD',
    ]);

    $businessAgent = User::factory()->create([
        'role' => 'business_agent',
        'referred_by' => $epUser->id,
    ]);

    $regularPartner = User::factory()->create([
        'role' => 'partner',
    ]);

    $epSimActivated = Sim::create([
        'number' => '08099990001',
        'category' => 'business',
        'provider' => 'glo',
        'status' => SimStatus::ACTIVATED,
        'business_id' => $epUser->id,
    ]);

    $epSimUnactivated = Sim::create([
        'number' => '08099990002',
        'category' => 'business',
        'provider' => 'glo',
        'status' => SimStatus::ASSIGNED_TO_BUSINESS,
        'business_id' => $epUser->id,
    ]);

    $regularSim = Sim::create([
        'number' => '08088880003',
        'category' => 'corporate',
        'provider' => 'airtel',
        'status' => SimStatus::ASSIGNED_TO_PARTNER,
        'partner_id' => $regularPartner->id,
    ]);

    $app = EnterprisePartnerApplication::create([
        'user_id' => $epUser->id,
        'status' => 'pending',
        'total_amount_snapshot' => 15000,
    ]);

    EnterprisePartnerApplicationItem::create([
        'application_id' => $app->id,
        'sim_category' => 'business',
        'provider' => 'glo',
        'quantity' => 10,
        'unit_price_snapshot' => 1500,
        'line_total_snapshot' => 15000,
    ]);

    EnterprisePartnerPayment::create([
        'user_id' => $epUser->id,
        'application_id' => $app->id,
        'expected_amount' => 15000,
        'account_reference' => 'EP-ACC-REF-1001',
        'status' => 'completed',
    ]);

    // 1. Cancellation test
    $this->artisan('enterprise-partners:purge')
        ->expectsConfirmation('Are you sure you want to permanently delete 1 Enterprise Partner(s), 1 Business Agent(s), and 2 associated SIM(s)?', 'no')
        ->expectsOutputToContain('Operation cancelled by user')
        ->assertSuccessful();

    expect(User::where('id', $epUser->id)->exists())->toBeTrue()
        ->and(User::where('id', $businessAgent->id)->exists())->toBeTrue()
        ->and(Sim::where('id', $epSimActivated->id)->exists())->toBeTrue();

    // 2. Execution test with --force
    $this->artisan('enterprise-partners:purge --force')
        ->expectsOutputToContain('Successfully removed 1 Enterprise Partner(s), 1 Business Agent(s), and 2 associated SIM(s)')
        ->assertSuccessful();

    expect(User::where('id', $epUser->id)->exists())->toBeFalse()
        ->and(User::where('id', $businessAgent->id)->exists())->toBeFalse()
        ->and(Sim::where('id', $epSimActivated->id)->exists())->toBeFalse()
        ->and(Sim::where('id', $epSimUnactivated->id)->exists())->toBeFalse()
        ->and(EnterprisePartnerApplication::where('id', $app->id)->exists())->toBeFalse()
        ->and(User::where('id', $regularPartner->id)->exists())->toBeTrue()
        ->and(Sim::where('id', $regularSim->id)->exists())->toBeTrue();
});

test('transactions:clear-history clears transaction history and handles foreign key constraints like verifications', function () {
    $user = User::factory()->create();

    $tx = Transaction::create([
        'transaction_ref' => 'TX-1001',
        'user_id' => $user->id,
        'amount' => 5000,
        'fee' => 0,
        'net_amount' => 5000,
        'type' => 'credit',
        'status' => 'successful',
        'description' => 'Test Transaction',
    ]);

    $service = \App\Models\Service::create([
        'name' => 'NIN Verification Service',
        'description' => 'NIN Verification Service',
    ]);

    $field = \App\Models\ServiceField::create([
        'service_id' => $service->id,
        'field_name' => 'Standard NIN',
        'field_code' => 'NIN_STD',
    ]);

    \App\Models\Verification::create([
        'reference' => 'VER-1001',
        'user_id' => $user->id,
        'service_id' => $service->id,
        'service_field_id' => $field->id,
        'transaction_id' => $tx->id,
        'amount' => 100,
        'status' => 'successful',
    ]);

    // 1. Cancellation
    $this->artisan('transactions:clear-history')
        ->expectsConfirmation('Are you sure you want to permanently clear all 2 transaction record(s)? This action cannot be undone.', 'no')
        ->expectsOutputToContain('Operation cancelled by user')
        ->assertSuccessful();

    expect(Transaction::count())->toBe(1)
        ->and(\App\Models\Verification::count())->toBe(1);

    // 2. Force clear
    $this->artisan('transactions:clear-history --force')
        ->expectsOutputToContain('Successfully cleared 2 transaction record(s)')
        ->assertSuccessful();

    expect(Transaction::count())->toBe(0)
        ->and(\App\Models\Verification::count())->toBe(0);
});

test('activation-events:clear clears activation events and bonus grants', function () {
    $user = User::factory()->create();

    $sim = Sim::create([
        'number' => '08055556666',
        'category' => 'corporate',
        'provider' => 'mtn',
        'status' => SimStatus::ACTIVATED,
    ]);

    SimHistory::create([
        'sim_id' => $sim->id,
        'from_status' => SimStatus::ASSIGNED_TO_PARTNER,
        'to_status' => SimStatus::ACTIVATED,
        'note' => 'SIM activated.',
    ]);

    SimHistory::create([
        'sim_id' => $sim->id,
        'from_status' => SimStatus::UNASSIGNED,
        'to_status' => SimStatus::ASSIGNED_TO_BSM,
        'note' => 'Dispatched to BSM',
    ]);

    SimActivationBonusGrant::create([
        'sim_id' => $sim->id,
        'period_index' => 1,
        'period_start' => now(),
        'period_end' => now()->addMonth(),
        'granted_at' => now(),
        'status' => 'completed',
    ]);

    CommissionEarning::create([
        'user_id' => $user->id,
        'role' => 'partner',
        'sim_id' => $sim->id,
        'amount' => 200,
        'period_week' => now()->startOfWeek(),
    ]);

    // 1. Cancellation
    $this->artisan('activation-events:clear')
        ->expectsConfirmation('Are you sure you want to permanently delete 3 all activation events and bonus grant ledgers? This action cannot be undone.', 'no')
        ->expectsOutputToContain('Operation cancelled by user')
        ->assertSuccessful();

    expect(SimHistory::count())->toBe(2)
        ->and(SimActivationBonusGrant::count())->toBe(1);

    // 2. Force clear
    $this->artisan('activation-events:clear --force')
        ->expectsOutputToContain('Successfully cleared 3 activation event record(s)')
        ->assertSuccessful();

    expect(SimActivationBonusGrant::count())->toBe(0)
        ->and(CommissionEarning::count())->toBe(0)
        // Non-activation history should remain
        ->and(SimHistory::where('to_status', SimStatus::ASSIGNED_TO_BSM)->count())->toBe(1);
});

test('requests:clear-pending clears pending requests across all models while preserving resolved ones', function () {
    $user = User::factory()->create();

    // 1. SimRequest
    SimRequest::create([
        'user_id' => $user->id,
        'number' => '08012345678',
        'category' => 'corporate',
        'provider' => 'mtn',
        'status' => 'pending',
    ]);
    SimRequest::create([
        'user_id' => $user->id,
        'number' => '08087654321',
        'category' => 'corporate',
        'provider' => 'mtn',
        'status' => 'approved',
    ]);

    // 2. LoanRequest
    LoanRequest::create([
        'reference' => LoanRequest::generateReference(),
        'user_id' => $user->id,
        'name' => 'Loan Applicant',
        'phone' => '08011223344',
        'moniepoint_account_number' => '8011223344',
        'amount_requested' => 100000,
        'status' => LoanRequest::STATUS_PENDING,
    ]);

    // 3. POS Request
    MoniepointPosRequest::create([
        'reference' => MoniepointPosRequest::generateReference(),
        'user_id' => $user->id,
        'name' => 'POS Applicant',
        'phone' => '08099887766',
        'email' => 'pos@example.com',
        'pos_type' => 'smart_pos',
        'status' => MoniepointPosRequest::STATUS_PENDING,
    ]);

    // 4. Travel Request
    $pkg = TravelPackage::create([
        'category' => 'umrah',
        'name' => 'Ramadan Umrah',
        'slug' => 'ramadan-umrah',
        'base_price' => 2500000,
        'is_active' => true,
    ]);

    TravelRequest::create([
        'reference' => TravelRequest::generateReference(),
        'user_id' => $user->id,
        'travel_package_id' => $pkg->id,
        'package_name' => $pkg->name,
        'name' => 'Traveler 1',
        'phone' => '08055443322',
        'status' => TravelRequest::STATUS_PENDING,
    ]);

    // Cancellation test
    $this->artisan('requests:clear-pending')
        ->expectsConfirmation('Are you sure you want to permanently clear all 4 pending request(s)? This action cannot be undone.', 'no')
        ->expectsOutputToContain('Operation cancelled by user')
        ->assertSuccessful();

    expect(SimRequest::where('status', 'pending')->count())->toBe(1);

    // Force clear test
    $this->artisan('requests:clear-pending --force')
        ->expectsOutputToContain('Successfully cleared 4 pending request(s)')
        ->assertSuccessful();

    // Pending should be gone
    expect(SimRequest::where('status', 'pending')->count())->toBe(0)
        ->and(LoanRequest::where('status', LoanRequest::STATUS_PENDING)->count())->toBe(0)
        ->and(MoniepointPosRequest::where('status', MoniepointPosRequest::STATUS_PENDING)->count())->toBe(0)
        ->and(TravelRequest::where('status', TravelRequest::STATUS_PENDING)->count())->toBe(0)
        // Approved should remain
        ->and(SimRequest::where('status', 'approved')->count())->toBe(1);
});

test('webhooks:clear-logs clears webhook logs matching criteria', function () {
    WebhookLog::create([
        'provider' => 'palmpay',
        'event_type' => 'payment.success',
        'payload' => ['sample' => 'data'],
        'status' => 'processed',
    ]);

    WebhookLog::create([
        'provider' => 'paystack',
        'event_type' => 'charge.success',
        'payload' => ['sample' => 'data'],
        'status' => 'failed',
    ]);

    // 1. Cancellation test
    $this->artisan('webhooks:clear-logs')
        ->expectsConfirmation('Are you sure you want to permanently clear 2 webhook log(s)? This action cannot be undone.', 'no')
        ->expectsOutputToContain('Operation cancelled by user')
        ->assertSuccessful();

    expect(WebhookLog::count())->toBe(2);

    // 2. Filter test
    $this->artisan('webhooks:clear-logs --provider=paystack --force')
        ->expectsOutputToContain('Successfully cleared 1 webhook log(s)')
        ->assertSuccessful();

    expect(WebhookLog::count())->toBe(1)
        ->and(WebhookLog::where('provider', 'palmpay')->exists())->toBeTrue();

    // 3. Force all
    $this->artisan('webhooks:clear-logs --force')
        ->expectsOutputToContain('Successfully cleared 1 webhook log(s)')
        ->assertSuccessful();

    expect(WebhookLog::count())->toBe(0);
});
