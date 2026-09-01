<?php

use App\Models\EnterprisePartnerApplication;
use App\Models\Sim;
use App\Models\User;
use App\Support\SimStatus;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'super_admin']);
});

test('a non-super_admin is forbidden from the fulfillment index and fulfill routes', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();
    $regionalManager = User::factory()->create(['role' => 'regional_manager']);

    $this->actingAs($regionalManager)->get(route('admin.enterprise-partners.fulfillment.index'))->assertForbidden();
    $this->actingAs($regionalManager)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'sim_ids' => [$item->id => $simIds],
    ])->assertForbidden();

    // The EP themself certainly can't reach it either — while still
    // pending_fulfillment, EnsureEnterprisePartnerPaid (a global middleware
    // that runs before the route's super_admin check) redirects them back
    // to their cart instead of ever reaching the 403.
    $this->actingAs($ep)->get(route('admin.enterprise-partners.fulfillment.index'))->assertRedirect(route('enterprise-partner.info'));
});

test('a super_admin sees the pending order with live stock counts on the fulfillment index', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedSimIds(3);

    $response = $this->actingAs($this->admin)->get(route('admin.enterprise-partners.fulfillment.index'));

    $response->assertOk();
    $response->assertSee($ep->email);
    $response->assertSee('POS SIM');
});

test('a super_admin can fulfill an order with a specific set of sim ids, unlocking the EP and updating the DB', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(10);
    $simIds = seedUnassignedSimIds(6);
    $item = $application->items->first();

    $response = $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'sim_ids' => [$item->id => $simIds],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($ep->fresh()->status)->toBe('active');
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($item->fresh()->fulfilled_quantity)->toBe(6);
    expect(Sim::whereIn('id', $simIds)->where('business_id', $ep->id)->count())->toBe(6);
});

test('the EP stays locked out of the dashboard while awaiting fulfillment, and is unlocked immediately after a successful fulfill', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();

    $this->actingAs($ep)->get(route('dashboard'))->assertRedirect(route('enterprise-partner.info'));

    $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'sim_ids' => [$item->id => $simIds],
    ]);

    $this->actingAs($ep->fresh())->get(route('dashboard'))->assertOk();
});

test('re-submitting fulfill on an already-fulfilled application is rejected, with no further state change', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();

    $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'sim_ids' => [$item->id => $simIds],
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application->fresh()), [
        'sim_ids' => [$item->id => $simIds],
    ]);

    $response->assertSessionHas('error');
    expect(Sim::where('business_id', $ep->id)->count())->toBe(5);
});

test('resolveItemNumbers resolves a valid, matching, unassigned number', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $item = $application->items->first();
    $sim = Sim::find(seedUnassignedSimIds(1, $item->sim_category, $item->provider)[0]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => $sim->number,
    ]);

    $response->assertOk();
    expect($response->json('resolved'))->toHaveCount(1);
    expect($response->json('resolved.0.id'))->toBe($sim->id);
    expect($response->json('errors'))->toBe([]);
});

test('resolveItemNumbers rejects an invalid-format number', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $item = $application->items->first();

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => '12345',
    ]);

    expect($response->json('resolved'))->toBe([]);
    expect($response->json('errors.0'))->toContain('Invalid format');
});

test('resolveItemNumbers reports a number that does not exist', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $item = $application->items->first();

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => '08099999999',
    ]);

    expect($response->json('resolved'))->toBe([]);
    expect($response->json('errors.0'))->toContain('Not found');
});

test('resolveItemNumbers rejects a number belonging to a different category/provider than the item', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $item = $application->items->first();
    $wrongSim = Sim::find(seedUnassignedSimIds(1, 'CAMERA SIM', 'glo')[0]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => $wrongSim->number,
    ]);

    expect($response->json('resolved'))->toBe([]);
    expect($response->json('errors.0'))->toContain('Wrong category/provider');
});

test('resolveItemNumbers rejects an already-activated number', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $item = $application->items->first();
    $sim = Sim::find(seedUnassignedSimIds(1, $item->sim_category, $item->provider)[0]);
    $sim->update(['status' => SimStatus::ACTIVATED]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => $sim->number,
    ]);

    expect($response->json('resolved'))->toBe([]);
    expect($response->json('errors.0'))->toContain('Already activated');
});

test('resolveItemNumbers rejects an already-assigned (non-activated) number', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $item = $application->items->first();
    $sim = Sim::find(seedUnassignedSimIds(1, $item->sim_category, $item->provider)[0]);
    $sim->update(['status' => SimStatus::ASSIGNED_TO_PARTNER]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => $sim->number,
    ]);

    expect($response->json('resolved'))->toBe([]);
    expect($response->json('errors.0'))->toContain('Already assigned');
});

test('resolveItemNumbers refuses to resolve anything once the application is no longer awaiting fulfillment', function () {
    [$admin, $ep, $application] = makeAwaitingFulfillmentEp(5);
    $simIds = seedUnassignedSimIds(5);
    $item = $application->items->first();
    app(\App\Services\EnterprisePartnerFulfillmentService::class)->fulfill($application, [$item->id => $simIds], $admin);
    $anotherSim = Sim::find(seedUnassignedSimIds(1, $item->sim_category, $item->provider)[0]);

    $response = $this->actingAs($this->admin)->postJson(route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item), [
        'numbers' => $anotherSim->number,
    ]);

    expect($response->json('resolved'))->toBe([]);
    expect($response->json('errors.0'))->toContain('not awaiting fulfillment');
});
