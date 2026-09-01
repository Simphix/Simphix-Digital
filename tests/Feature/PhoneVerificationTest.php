<?php

use App\Models\DeviceVerificationChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('sending and verifying the phone OTP sets phone_verified_at', function () {
    Http::fake(['*' => Http::response(['message_id' => 'abc', 'message' => 'ok'], 200)]);
    $user = User::factory()->create(['phone' => '08031234567', 'phone_verified_at' => null]);

    $this->actingAs($user)->post(route('profile.phone.send-otp'));

    $code = null;
    Http::assertSent(function ($request) use (&$code) {
        preg_match('/code is (\d{6})/', $request['sms'], $m);
        $code = $m[1] ?? null;

        return true;
    });

    $response = $this->actingAs($user)->post(route('profile.phone.verify-otp'), ['code' => $code]);

    $response->assertSessionHas('success');
    expect($user->fresh()->phone_verified_at)->not->toBeNull();
});

test('a wrong phone verification code is rejected', function () {
    Http::fake(['*' => Http::response(['message_id' => 'abc', 'message' => 'ok'], 200)]);
    $user = User::factory()->create(['phone' => '08031234567', 'phone_verified_at' => null]);

    $this->actingAs($user)->post(route('profile.phone.send-otp'));

    $response = $this->actingAs($user)->post(route('profile.phone.verify-otp'), ['code' => '000000']);

    $response->assertSessionHasErrors(['code'], null, 'phoneVerification');
    expect($user->fresh()->phone_verified_at)->toBeNull();
});

test('an expired phone verification code is rejected', function () {
    Http::fake(['*' => Http::response(['message_id' => 'abc', 'message' => 'ok'], 200)]);
    $user = User::factory()->create(['phone' => '08031234567', 'phone_verified_at' => null]);

    $this->actingAs($user)->post(route('profile.phone.send-otp'));
    $user->refresh();
    $user->forceFill(['phone_verification_otp_expires_at' => now()->subMinute()])->save();

    $response = $this->actingAs($user)->post(route('profile.phone.verify-otp'), ['code' => '123456']);

    $response->assertSessionHasErrors(['code'], null, 'phoneVerification');
});

test('the device-otp dispatcher only offers sms once phone_verified_at is set', function () {
    $unverified = User::factory()->create(['phone' => '08031234567', 'phone_verified_at' => null]);
    $verified = User::factory()->create(['phone' => '08031234568', 'phone_verified_at' => now()]);

    expect(app(\App\Services\DeviceOtp\DeviceOtpDispatcher::class)->availableChannels($unverified))->not->toContain('sms');
    expect(app(\App\Services\DeviceOtp\DeviceOtpDispatcher::class)->availableChannels($verified))->toContain('sms');
});
