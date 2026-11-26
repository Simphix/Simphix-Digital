<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Report;
use App\Models\SenatorialDistrict;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\SimCustomer;
use App\Models\SimHistory;
use App\Models\State;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $bsm;
    protected User $partner;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->bsm = User::factory()->create(['role' => 'business_sales_manager']);
        $this->partner = User::factory()->create(['role' => 'partner', 'first_name' => 'John', 'last_name' => 'Partner']);
        $this->agent = User::factory()->create(['role' => 'agent', 'first_name' => 'Alice', 'last_name' => 'Agent']);

        foreach ([$this->admin, $this->bsm, $this->partner, $this->agent] as $u) {
            Wallet::firstOrCreate(
                ['user_id' => $u->id],
                ['balance' => 50000.00, 'bonus' => 0.00, 'wallet_number' => 'WLT' . str_pad((string) $u->id, 7, '0', STR_PAD_LEFT)]
            );
        }

        $service = Service::firstOrCreate(['name' => 'simcard'], ['description' => 'SIM Service', 'status' => 1]);
        ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'POS SIM'],
            ['field_code' => 'POS-SIM', 'field_type' => 'text', 'base_price' => 1000.00, 'is_active' => true]
        );
        ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'CAMERA SIM'],
            ['field_code' => 'CAMERA-SIM', 'field_type' => 'text', 'base_price' => 1500.00, 'is_active' => true]
        );
    }

    public function test_admin_dashboard_filters_out_transactions_outside_date_range(): void
    {
        // Transaction today
        $txToday = Transaction::create([
            'transaction_ref' => 'TX-TODAY-' . rand(1000, 9999),
            'user_id'         => $this->partner->id,
            'amount'          => 1500.00,
            'fee'             => 0.00,
            'net_amount'      => 1500.00,
            'description'     => 'Today unique deposit payment',
            'type'            => 'credit',
            'status'          => 'completed',
        ]);
        $txToday->forceFill(['created_at' => now()])->save();

        // Transaction 10 days ago
        $txOld = Transaction::create([
            'transaction_ref' => 'TX-OLD-' . rand(1000, 9999),
            'user_id'         => $this->partner->id,
            'amount'          => 3000.00,
            'fee'             => 0.00,
            'net_amount'      => 3000.00,
            'description'     => 'Old month unique payment',
            'type'            => 'credit',
            'status'          => 'completed',
        ]);
        $txOld->forceFill(['created_at' => now()->subDays(10)])->save();

        $response = $this->actingAs($this->admin)->get(route('dashboard', ['range' => 'today']));

        $response->assertOk();
        $this->assertTrue($response->viewData('recentTransactions')->contains('id', $txToday->id));
        $this->assertFalse($response->viewData('recentTransactions')->contains('id', $txOld->id));
        $response->assertSee('Today unique deposit payment');
    }

    public function test_admin_dashboard_filters_by_transaction_type_and_status(): void
    {
        $creditTx = Transaction::create([
            'transaction_ref' => 'TX-CREDIT-' . rand(1000, 9999),
            'user_id'         => $this->partner->id,
            'amount'          => 2000.00,
            'type'            => 'credit',
            'status'          => 'completed',
            'description'     => 'Unique Credit Description',
            'created_at'      => now(),
        ]);

        $debitTx = Transaction::create([
            'transaction_ref' => 'TX-DEBIT-' . rand(1000, 9999),
            'user_id'         => $this->partner->id,
            'amount'          => 500.00,
            'type'            => 'debit',
            'status'          => 'completed',
            'description'     => 'Unique Debit Description',
            'created_at'      => now(),
        ]);

        // Request credits only
        $resCredit = $this->actingAs($this->admin)->get(route('dashboard', [
            'range'   => 'today',
            'tx_type' => 'credit',
        ]));
        $resCredit->assertOk();
        $this->assertTrue($resCredit->viewData('recentTransactions')->contains('id', $creditTx->id));
        $this->assertFalse($resCredit->viewData('recentTransactions')->contains('id', $debitTx->id));
        $resCredit->assertSee($creditTx->description);

        // Request debits only
        $resDebit = $this->actingAs($this->admin)->get(route('dashboard', [
            'range'   => 'today',
            'tx_type' => 'debit',
        ]));
        $resDebit->assertOk();
        $this->assertTrue($resDebit->viewData('recentTransactions')->contains('id', $debitTx->id));
        $this->assertFalse($resDebit->viewData('recentTransactions')->contains('id', $creditTx->id));
        $resDebit->assertSee($debitTx->description);
    }

    public function test_admin_dashboard_filters_by_search_query(): void
    {
        $specialTx = Transaction::create([
            'transaction_ref' => 'TX-SPEC-12345',
            'user_id'         => $this->agent->id,
            'amount'          => 750.00,
            'type'            => 'debit',
            'status'          => 'completed',
            'description'     => 'Special Commission Payout',
            'created_at'      => now(),
        ]);

        $otherTx = Transaction::create([
            'transaction_ref' => 'TX-NORM-67890',
            'user_id'         => $this->partner->id,
            'amount'          => 1200.00,
            'type'            => 'debit',
            'status'          => 'completed',
            'description'     => 'Regular Airtime Purchase',
            'created_at'      => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'range'  => 'today',
            'search' => 'TX-SPEC-12345',
        ]));

        $response->assertOk();
        $this->assertTrue($response->viewData('recentTransactions')->contains('id', $specialTx->id));
        $this->assertFalse($response->viewData('recentTransactions')->contains('id', $otherTx->id));
        $response->assertSee($specialTx->description);
    }

    public function test_sims_operations_dashboard_filters_by_provider_and_category(): void
    {
        $mtnSim = Sim::create([
            'number'     => '08031112233',
            'provider'   => 'mtn',
            'category'   => 'POS SIM',
            'status'     => SimStatus::ACTIVATED,
            'user_id'    => $this->partner->id,
        ]);
        SimHistory::create([
            'sim_id'      => $mtnSim->id,
            'to_status'   => SimStatus::ACTIVATED,
            'created_at'  => now(),
        ]);

        $airtelSim = Sim::create([
            'number'     => '08029998877',
            'provider'   => 'airtel',
            'category'   => 'CAMERA SIM',
            'status'     => SimStatus::ACTIVATED,
            'user_id'    => $this->partner->id,
        ]);
        SimHistory::create([
            'sim_id'      => $airtelSim->id,
            'to_status'   => SimStatus::ACTIVATED,
            'created_at'  => now(),
        ]);

        // Filter by MTN
        $response = $this->actingAs($this->admin)->get(route('admin.sims-operations.index', [
            'range'    => 'today',
            'provider' => 'mtn',
        ]));

        $response->assertOk();
        $response->assertSee($mtnSim->number);
        $response->assertDontSee($airtelSim->number);

        // Filter by CAMERA SIM
        $responseCat = $this->actingAs($this->admin)->get(route('admin.sims-operations.index', [
            'range'    => 'today',
            'category' => 'CAMERA SIM',
        ]));

        $responseCat->assertOk();
        $responseCat->assertSee($airtelSim->number);
        $responseCat->assertDontSee($mtnSim->number);
    }

    public function test_bsm_dashboard_filters_out_by_provider_and_search(): void
    {
        $gloSim = Sim::create([
            'number'     => '08050001122',
            'provider'   => 'glo',
            'category'   => 'POS SIM',
            'status'     => SimStatus::ACTIVATED,
            'partner_id' => $this->partner->id,
        ]);

        $airtelSim = Sim::create([
            'number'     => '08023334455',
            'provider'   => 'airtel',
            'category'   => 'POS SIM',
            'status'     => SimStatus::ACTIVATED,
            'partner_id' => $this->partner->id,
        ]);

        $customer = SimCustomer::create([
            'sim_id'       => $gloSim->id,
            'full_name'    => 'Target Glo Customer',
            'phone_number' => '08050001122',
        ]);

        // Filter by GLO provider
        $response = $this->actingAs($this->bsm)->get(route('bsm.dashboard', [
            'provider' => 'glo',
        ]));

        $response->assertOk();
        $response->assertSee('Provider: GLO');

        // Search specific number
        $resSearch = $this->actingAs($this->bsm)->get(route('bsm.dashboard', [
            'search' => '08050001122',
        ]));

        $resSearch->assertOk();
        $resSearch->assertSee('Search:');
        $resSearch->assertSee('08050001122');
    }
}
