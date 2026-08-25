<?php

use App\Models\BusinessCommissionRate;
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
    $this->agent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id, 'first_name' => 'Ada', 'last_name' => 'Agent']);
    $this->customer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->agent->id]);

    foreach ([$this->ep, $this->agent, $this->customer] as $user) {
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'bonus' => 0,
            'wallet_number' => 'WLT' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
        ]);
    }

    BusinessCommissionRate::create([
        'business_id' => $this->ep->id,
        'service_fields_id' => null,
        'price' => 2000,
        'commission' => 600,
    ]);

    $sim = Sim::create([
        'number' => '0804' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::UNASSIGNED,
    ]);
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);
    $this->svc->assignDown($sim, $this->agent, $this->customer);
    $this->svc->activate($sim, $this->agent);
    $this->sim = $sim->fresh();
});

test('the EP sees a downline breakdown showing the agent name, price, agent commission, and their own kept margin', function () {
    $response = $this->actingAs($this->ep)->get(route('commissions.dashboard'));

    $response->assertOk();
    $response->assertViewHas('downlineBreakdown', function ($breakdown) {
        $row = $breakdown->first();

        return $row['agent_name'] === 'Ada Agent'
            && $row['agent_amount'] === 600.0
            && $row['ep_amount'] === 1400.0
            && $row['price'] === 2000.0;
    });

    $response->assertSee('Ada Agent');
    $response->assertSee('1,400.00', false);
});

test('the business_agent sees their own flat earnings list without a breakdown', function () {
    $response = $this->actingAs($this->agent)->get(route('commissions.dashboard'));

    $response->assertOk();
    $response->assertViewHas('downlineBreakdown', fn ($breakdown) => $breakdown->isEmpty());
    $response->assertSee('600.00', false);
});
