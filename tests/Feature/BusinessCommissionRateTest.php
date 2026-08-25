<?php

use App\Models\BusinessCommissionRate;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    $this->ep = User::factory()->create(['role' => 'business', 'status' => 'active']);
    $this->service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);
    $this->field = ServiceField::firstOrCreate(
        ['service_id' => $this->service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'POS-' . fake()->unique()->numerify('####'), 'base_price' => 1000, 'is_active' => true]
    );
});

test('non-business roles are refused the commission-rates page', function () {
    $partner = User::factory()->create(['role' => 'partner']);

    $this->actingAs($partner)->get(route('sims.business.commission-rates.index'))->assertForbidden();
});

test('EP can view the commission-rates page listing active SIM categories', function () {
    $response = $this->actingAs($this->ep)->get(route('sims.business.commission-rates.index'));

    $response->assertOk();
    $response->assertViewHas('fields', fn ($fields) => $fields->contains('id', $this->field->id));
});

test('EP sets a default rate applying to any category without its own override', function () {
    $this->actingAs($this->ep)->post(route('sims.business.commission-rates.store'), [
        'price' => 2000,
        'commission' => 500,
    ])->assertRedirect();

    $rate = BusinessCommissionRate::where('business_id', $this->ep->id)->whereNull('service_fields_id')->first();
    expect($rate)->not->toBeNull();
    expect((float) $rate->price)->toBe(2000.0);
    expect((float) $rate->commission)->toBe(500.0);
});

test('EP sets a category-specific rate that overrides the default, and re-saving updates rather than duplicates', function () {
    $this->actingAs($this->ep)->post(route('sims.business.commission-rates.store'), [
        'service_fields_id' => $this->field->id,
        'price' => 1800,
        'commission' => 400,
    ])->assertRedirect();

    $this->actingAs($this->ep)->post(route('sims.business.commission-rates.store'), [
        'service_fields_id' => $this->field->id,
        'price' => 1900,
        'commission' => 450,
    ])->assertRedirect();

    $rates = BusinessCommissionRate::where('business_id', $this->ep->id)->where('service_fields_id', $this->field->id)->get();
    expect($rates)->toHaveCount(1);
    expect((float) $rates->first()->price)->toBe(1900.0);
    expect((float) $rates->first()->commission)->toBe(450.0);
});

test('commission cannot exceed price', function () {
    $response = $this->actingAs($this->ep)->post(route('sims.business.commission-rates.store'), [
        'price' => 1000,
        'commission' => 1500,
    ]);

    $response->assertSessionHasErrors('commission');
    expect(BusinessCommissionRate::where('business_id', $this->ep->id)->count())->toBe(0);
});

test('rates from a different EP do not leak into this EP\'s page', function () {
    $otherEp = User::factory()->create(['role' => 'business', 'status' => 'active']);
    BusinessCommissionRate::create([
        'business_id' => $otherEp->id,
        'service_fields_id' => null,
        'price' => 9999,
        'commission' => 1,
    ]);

    $response = $this->actingAs($this->ep)->get(route('sims.business.commission-rates.index'));

    $response->assertOk();
    $response->assertViewHas('rates', fn ($rates) => $rates->isEmpty());
});
