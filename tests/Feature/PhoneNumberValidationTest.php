<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('KycController rejects a garbage phone number', function () {
    Mail::fake();
    $user = User::factory()->create(['account_tier' => 0]);

    $response = $this->actingAs($user)->post(route('kyc.submit'), [
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
        'phone'      => 'not-a-phone',
        'state'      => 'Lagos',
        'lga'        => 'Ikeja',
        'address'    => '1 Test Street',
        'agree'      => '1',
    ]);

    $response->assertSessionHasErrors('phone');
    expect($user->fresh()->account_tier)->toBe(0);
});

test('KycController accepts a +234-prefixed phone and stores it in normalized local format', function () {
    Mail::fake();
    $user = User::factory()->create(['account_tier' => 0, 'phone' => null]);
    // A fresh, test-only number (not the real number from the bug report,
    // which belongs to an actual dev-DB account and would collide with the
    // unique constraint).
    $localNumber = '081' . fake()->unique()->numerify('########');

    $response = $this->actingAs($user)->post(route('kyc.submit'), [
        'first_name' => 'Jane',
        'last_name'  => 'Doe',
        'phone'      => '+234' . substr($localNumber, 1),
        'state'      => 'Lagos',
        'lga'        => 'Ikeja',
        'address'    => '1 Test Street',
        'agree'      => '1',
    ]);

    $response->assertSessionHas('success');
    expect($user->fresh()->phone)->toBe($localNumber);
});

test('EnterprisePartnerController rejects a garbage phone number on registration', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'PHONEVALRM', 'status' => 'active']);

    $response = $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'PHONEVALRM',
        'first_name' => 'Biz',
        'last_name' => 'Owner',
        'email' => 'biz-owner-phonetest@example.test',
        'phone' => 'not-a-phone',
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Test Biz',
        'business_type' => 'sole_proprietor',
        'cac_number' => 'CAC12345',
    ]);

    $response->assertSessionHasErrors('phone');
    expect(User::where('email', 'biz-owner-phonetest@example.test')->exists())->toBeFalse();
});

test('EnterprisePartnerController accepts a +234-prefixed phone and stores it in normalized local format', function () {
    $rm = User::factory()->create(['role' => 'regional_manager', 'referral_code' => 'PHONEVALRM2', 'status' => 'active']);
    $localNumber = '081' . fake()->unique()->numerify('########');

    $response = $this->post(route('enterprise-partner.register.store'), [
        'ref' => 'PHONEVALRM2',
        'first_name' => 'Biz',
        'last_name' => 'Owner',
        'email' => 'biz-owner-phonetest2@example.test',
        'phone' => '+234' . substr($localNumber, 1),
        'password' => 'Passw0rd!123',
        'password_confirmation' => 'Passw0rd!123',
        'business_name' => 'Test Biz',
        'business_type' => 'sole_proprietor',
        'cac_number' => 'CAC12346',
    ]);

    $response->assertRedirect(route('enterprise-partner.info'));
    $ep = User::where('email', 'biz-owner-phonetest2@example.test')->first();
    expect($ep->phone)->toBe($localNumber);
});
