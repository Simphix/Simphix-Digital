<?php

use App\Models\BusinessCommissionRate;
use App\Models\CommissionEarning;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    $this->svc = app(SimAssignmentService::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->ep = User::factory()->create(['role' => 'business']);
    $this->agent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id]);
    $this->customer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->agent->id]);

    foreach ([$this->ep, $this->agent, $this->customer] as $user) {
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'bonus' => 0,
            'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
        ]);
    }

    $this->service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $this->field = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-' . fake()->unique()->numerify('####'), 'base_price' => 1000, 'is_active' => true]
    );
});

function activateEpChainSim(Tests\TestCase $test): Sim
{
    $sim = Sim::create([
        'number' => '0803' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::UNASSIGNED,
    ]);
    $test->svc->adminAssign($sim, $test->ep, $test->admin);
    $test->svc->assignDown($sim, $test->ep, $test->agent);
    $test->svc->assignDown($sim, $test->agent, $test->customer);
    $test->svc->activate($sim, $test->agent);

    return $sim->fresh();
}

test('no commission is awarded when the EP has not configured a rate', function () {
    activateEpChainSim($this);

    expect(CommissionEarning::where('role', 'business')->count())->toBe(0);
    expect(CommissionEarning::where('role', 'business_agent')->count())->toBe(0);
});

test('the default rate splits price between the business_agent commission and the EP margin', function () {
    BusinessCommissionRate::create([
        'business_id' => $this->ep->id,
        'service_fields_id' => null,
        'price' => 2000,
        'commission' => 500,
    ]);

    activateEpChainSim($this);

    $this->agent->wallet->refresh();
    $this->ep->wallet->refresh();

    expect((float) $this->agent->wallet->bonus)->toBe(500.0);
    expect((float) $this->ep->wallet->bonus)->toBe(1500.0);

    $agentEarning = CommissionEarning::where('user_id', $this->agent->id)->where('role', 'business_agent')->first();
    $epEarning = CommissionEarning::where('user_id', $this->ep->id)->where('role', 'business')->first();
    expect((float) $agentEarning->amount)->toBe(500.0);
    expect((float) $epEarning->amount)->toBe(1500.0);
});

test('a category-specific rate overrides the default rate', function () {
    BusinessCommissionRate::create([
        'business_id' => $this->ep->id,
        'service_fields_id' => null,
        'price' => 5000,
        'commission' => 4000,
    ]);
    BusinessCommissionRate::create([
        'business_id' => $this->ep->id,
        'service_fields_id' => $this->field->id,
        'price' => 1800,
        'commission' => 300,
    ]);

    activateEpChainSim($this);

    $this->agent->wallet->refresh();
    $this->ep->wallet->refresh();

    expect((float) $this->agent->wallet->bonus)->toBe(300.0);
    expect((float) $this->ep->wallet->bonus)->toBe(1500.0);
});

test('a zero commission still credits the full price as EP margin', function () {
    BusinessCommissionRate::create([
        'business_id' => $this->ep->id,
        'service_fields_id' => null,
        'price' => 1200,
        'commission' => 0,
    ]);

    activateEpChainSim($this);

    $this->agent->wallet->refresh();
    $this->ep->wallet->refresh();

    expect((float) $this->agent->wallet->bonus)->toBe(0.0);
    expect((float) $this->ep->wallet->bonus)->toBe(1200.0);
    expect(CommissionEarning::where('role', 'business_agent')->count())->toBe(0);
});

test('AwardCommissions never fires for the EP chain (no ServicePrice rows for business/business_agent)', function () {
    BusinessCommissionRate::create([
        'business_id' => $this->ep->id,
        'service_fields_id' => null,
        'price' => 2000,
        'commission' => 500,
    ]);

    activateEpChainSim($this);

    // AwardCommissions credits via user_id too (the customer here) — confirm
    // no stray ServicePrice-driven commission rows exist for the EP roles.
    expect(CommissionEarning::whereIn('role', ['business', 'business_agent'])->count())->toBe(2);
});
