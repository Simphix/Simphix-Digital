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
    seedUnassignedPosSims(5);
    $item = $application->items->first();
    $regionalManager = User::factory()->create(['role' => 'regional_manager']);

    $this->actingAs($regionalManager)->get(route('admin.enterprise-partners.fulfillment.index'))->assertForbidden();
    $this->actingAs($regionalManager)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'quantities' => [$item->id => 5],
    ])->assertForbidden();

    // The EP themself certainly can't reach it either — while still
    // pending_fulfillment, EnsureEnterprisePartnerPaid (a global middleware
    // that runs before the route's super_admin check) redirects them back
    // to their cart instead of ever reaching the 403.
    $this->actingAs($ep)->get(route('admin.enterprise-partners.fulfillment.index'))->assertRedirect(route('enterprise-partner.info'));
});

test('a super_admin sees the pending order with live stock counts on the fulfillment index', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedPosSims(3);

    $response = $this->actingAs($this->admin)->get(route('admin.enterprise-partners.fulfillment.index'));

    $response->assertOk();
    $response->assertSee($ep->email);
    $response->assertSee('POS SIM');
});

test('a super_admin can fulfill an order with a partial quantity, unlocking the EP and updating the DB', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(10);
    seedUnassignedPosSims(6);
    $item = $application->items->first();

    $response = $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'quantities' => [$item->id => 6],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($ep->fresh()->status)->toBe('active');
    expect($application->fresh()->status)->toBe('fulfilled');
    expect($item->fresh()->fulfilled_quantity)->toBe(6);
    expect(Sim::where('business_id', $ep->id)->count())->toBe(6);
});

test('the EP stays locked out of the dashboard while awaiting fulfillment, and is unlocked immediately after a successful fulfill', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedPosSims(5);
    $item = $application->items->first();

    $this->actingAs($ep)->get(route('dashboard'))->assertRedirect(route('enterprise-partner.info'));

    $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'quantities' => [$item->id => 5],
    ]);

    $this->actingAs($ep->fresh())->get(route('dashboard'))->assertOk();
});

test('re-submitting fulfill on an already-fulfilled application is rejected, with no further state change', function () {
    [$_, $ep, $application] = makeAwaitingFulfillmentEp(5);
    seedUnassignedPosSims(5);
    $item = $application->items->first();

    $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application), [
        'quantities' => [$item->id => 5],
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.enterprise-partners.fulfillment.fulfill', $application->fresh()), [
        'quantities' => [$item->id => 5],
    ]);

    $response->assertSessionHas('error');
    expect(Sim::where('business_id', $ep->id)->count())->toBe(5);
});
