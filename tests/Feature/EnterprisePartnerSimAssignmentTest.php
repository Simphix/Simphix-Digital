<?php

use App\Models\Sim;
use App\Models\User;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function makeUnassignedPosSim(): Sim
{
    return Sim::create([
        'number' => '0801' . fake()->unique()->numerify('#######'),
        'category' => 'POS SIM',
        'provider' => 'mtn',
        'status' => SimStatus::UNASSIGNED,
    ]);
}

beforeEach(function () {
    $this->svc = app(SimAssignmentService::class);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->ep = User::factory()->create(['role' => 'business']);
    $this->agent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id]);
    $this->customer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->agent->id]);
});

test('adminAssign to business sets business_id but not user_id', function () {
    $sim = makeUnassignedPosSim();

    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $sim->refresh();

    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS);
    expect($sim->business_id)->toBe($this->ep->id);
    expect($sim->user_id)->toBeNull();
});

test('adminAssign to business_agent sets business_agent_id and user_id both to the agent', function () {
    $sim = makeUnassignedPosSim();

    $this->svc->adminAssign($sim, $this->agent, $this->admin);
    $sim->refresh();

    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS_AGENT);
    expect($sim->business_agent_id)->toBe($this->agent->id);
    expect($sim->user_id)->toBe($this->agent->id);
});

test('full cascade preserves the commission chain end to end', function () {
    $sim = makeUnassignedPosSim();

    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);
    $sim->refresh();
    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS_AGENT);
    expect($sim->business_agent_id)->toBe($this->agent->id);
    expect($sim->user_id)->toBe($this->agent->id);
    expect($sim->business_id)->toBe($this->ep->id);

    $this->svc->assignDown($sim, $this->agent, $this->customer);
    $sim->refresh();
    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS_AGENT);
    expect($sim->user_id)->toBe($this->customer->id);
    expect($sim->business_agent_id)->toBe($this->agent->id);
    expect($sim->business_id)->toBe($this->ep->id);

    $this->svc->activate($sim, $this->agent);
    $sim->refresh();
    expect($sim->status)->toBe(SimStatus::ACTIVATED);
    // Chain FKs survive activation — the commission listener needs them.
    expect($sim->business_id)->toBe($this->ep->id);
    expect($sim->business_agent_id)->toBe($this->agent->id);
});

test('collectUp reclaims from a business_agent back to the EP without touching business_id', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);

    $this->svc->collectUp($sim, $this->ep, $this->agent);
    $sim->refresh();

    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS);
    expect($sim->business_id)->toBe($this->ep->id);
    expect($sim->business_agent_id)->toBeNull();
    expect($sim->user_id)->toBeNull();
});

test('a collected SIM can be reassigned again', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);
    $this->svc->collectUp($sim, $this->ep, $this->agent);

    $otherAgent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id]);
    $this->svc->assignDown($sim, $this->ep, $otherAgent);
    $sim->refresh();

    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS_AGENT);
    expect($sim->business_agent_id)->toBe($otherAgent->id);
    expect($sim->user_id)->toBe($otherAgent->id);
});

test('swapHolder at the business tier swaps which EP holds a self-held SIM', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);

    $otherEp = User::factory()->create(['role' => 'business']);
    $this->svc->swapHolder($sim, $otherEp, $this->admin);
    $sim->refresh();

    expect($sim->business_id)->toBe($otherEp->id);
    expect($sim->status)->toBe(SimStatus::ASSIGNED_TO_BUSINESS);
});

test('swapHolder at the business_agent tier swaps the personal customer, not the agent', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);
    $this->svc->assignDown($sim, $this->agent, $this->customer);

    $otherCustomer = User::factory()->create(['role' => 'personal', 'referred_by' => $this->agent->id]);
    $this->svc->swapHolder($sim, $otherCustomer, $this->admin);
    $sim->refresh();

    expect($sim->user_id)->toBe($otherCustomer->id);
    expect($sim->business_agent_id)->toBe($this->agent->id); // unchanged
});

test('swapHolder refuses a business_agent SIM that is still self-held (not yet handed to a customer)', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);

    $otherAgent = User::factory()->create(['role' => 'business_agent', 'referred_by' => $this->ep->id]);

    expect(fn () => $this->svc->swapHolder($sim, $otherAgent, $this->admin))
        ->toThrow(RuntimeException::class, 'This SIM is not in a swappable state.');
});

test('unassign clears all six holder FKs including the new business ones', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);

    $this->svc->unassign($sim, $this->admin);
    $sim->refresh();

    expect($sim->status)->toBe(SimStatus::UNASSIGNED);
    expect($sim->business_id)->toBeNull();
    expect($sim->business_agent_id)->toBeNull();
    expect($sim->user_id)->toBeNull();
});

test('an agent cannot assign down to a role outside the hierarchy', function () {
    $sim = makeUnassignedPosSim();
    $this->svc->adminAssign($sim, $this->ep, $this->admin);
    $this->svc->assignDown($sim, $this->ep, $this->agent);

    $partner = User::factory()->create(['role' => 'partner']);

    expect(fn () => $this->svc->assignDown($sim, $this->agent, $partner))
        ->toThrow(RuntimeException::class);
});
