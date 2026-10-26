<?php

use App\Models\Service;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('landing page displays the actual prices configured on the platform', function () {
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);

    $pos = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'pos_sim', 'base_price' => 1000, 'is_active' => true, 'activation_disabled' => false]
    );
    ServicePrice::updateOrCreate(
        ['service_fields_id' => $pos->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1000, 'commission' => 0]
    );

    $cctv = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'CCTV'],
        ['field_code' => 'cctv_sim', 'base_price' => 2000, 'is_active' => true, 'activation_disabled' => false]
    );
    ServicePrice::updateOrCreate(
        ['service_fields_id' => $cctv->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 2000, 'commission' => 0]
    );

    $router = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'ROUTER SIM'],
        ['field_code' => 'router_sim', 'base_price' => 2500, 'is_active' => true, 'activation_disabled' => false]
    );
    ServicePrice::updateOrCreate(
        ['service_fields_id' => $router->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 2500, 'commission' => 0]
    );

    $gps = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'GPS SIM'],
        ['field_code' => 'gps_sim', 'base_price' => 3000, 'is_active' => true, 'activation_disabled' => false]
    );
    ServicePrice::updateOrCreate(
        ['service_fields_id' => $gps->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 3000, 'commission' => 0]
    );

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('1,000');
    $response->assertSee('2,000');
    $response->assertSee('2,500');
    $response->assertSee('3,000');
    $response->assertDontSee('7,999');
    $response->assertDontSee('19,999');
    $response->assertDontSee('17,750');
});

test('landing page reflects updated platform service prices dynamically', function () {
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);

    $pos = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'POS SIM'],
        ['field_code' => 'pos_sim', 'base_price' => 1000, 'is_active' => true, 'activation_disabled' => false]
    );

    // Update POS SIM price to 1,450
    ServicePrice::updateOrCreate(
        ['service_fields_id' => $pos->id, 'user_type' => 'personal', 'user_id' => null],
        ['service_id' => $service->id, 'price' => 1450, 'commission' => 0]
    );

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('1,450');
});

test('landing page displays coming soon when a category is disabled on the platform', function () {
    $service = Service::firstOrCreate(['name' => 'simcard'], ['is_active' => true]);

    $gps = ServiceField::firstOrCreate(
        ['service_id' => $service->id, 'field_name' => 'GPS SIM'],
        ['field_code' => 'gps_sim', 'base_price' => 3000, 'is_active' => true, 'activation_disabled' => false]
    );

    // Admin flips activation killswitch for GPS SIM
    $gps->update(['activation_disabled' => true]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Coming Soon');
});


