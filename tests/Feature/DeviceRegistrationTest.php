<?php

use App\Models\DeviceVerificationChallenge;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function deviceRegUser(bool $enabled = true, string $password = 'Passw0rd!123'): User
{
    return User::factory()->create([
        'password' => Hash::make($password),
        'status' => 'active',
        'device_registration_enabled' => $enabled,
    ]);
}

test('login from an unrecognized device with the feature on redirects to the challenge screen and does not authenticate', function () {
    $user = deviceRegUser();

    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('device-challenge');
    expect(Auth::check())->toBeFalse();
    expect(DeviceVerificationChallenge::where('user_id', $user->id)->exists())->toBeTrue();
});

test('login from an unrecognized device with the feature off logs straight in as before', function () {
    $user = deviceRegUser(enabled: false);

    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('completing the OTP challenge with the correct code logs in, sets the trust cookie, and creates a trusted device', function () {
    Mail::fake();
    $user = deviceRegUser();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);
    $challenge = DeviceVerificationChallenge::where('user_id', $user->id)->first();

    $this->post(route('device-challenge.send', $challenge->token), ['channel' => 'email']);

    // The code is only ever stored as a hash — read the real value back out
    // of the mail payload, which is what the user actually receives.
    Mail::assertSent(\App\Mail\DeviceLoginOtpMail::class, function ($mail) use (&$sentOtp) {
        $sentOtp = $mail->otp;

        return true;
    });

    $response = $this->post(route('device-challenge.verify', $challenge->token), ['code' => $sentOtp]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $response->assertCookie(TrustedDevice::cookieName());
    expect(TrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->exists())->toBeTrue();
});

test('a wrong code is rejected and does not authenticate; 5 wrong attempts kill the challenge', function () {
    Mail::fake();
    $user = deviceRegUser();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);
    $challenge = DeviceVerificationChallenge::where('user_id', $user->id)->first();
    $this->post(route('device-challenge.send', $challenge->token), ['channel' => 'email']);

    for ($i = 0; $i < 5; $i++) {
        $response = $this->post(route('device-challenge.verify', $challenge->token), ['code' => '000000']);
    }

    expect(Auth::check())->toBeFalse();
    expect($challenge->fresh()->isLocked())->toBeTrue();
    // The challenge is now expired — verifying again (even with a fresh
    // guess) is rejected as an expired link, not re-checked.
    $response = $this->post(route('device-challenge.verify', $challenge->token), ['code' => '111111']);
    $response->assertRedirect(route('login'));
});

test('an expired OTP is rejected', function () {
    Mail::fake();
    $user = deviceRegUser();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);
    $challenge = DeviceVerificationChallenge::where('user_id', $user->id)->first();
    $this->post(route('device-challenge.send', $challenge->token), ['channel' => 'email']);

    $challenge->update(['otp_expires_at' => now()->subMinute()]);

    $response = $this->post(route('device-challenge.verify', $challenge->token), ['code' => '123456']);

    expect(Auth::check())->toBeFalse();
    $response->assertSessionHasErrors('code');
});

test('resending issues a new code', function () {
    Mail::fake();
    $user = deviceRegUser();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);
    $challenge = DeviceVerificationChallenge::where('user_id', $user->id)->first();
    $this->post(route('device-challenge.send', $challenge->token), ['channel' => 'email']);

    $firstHash = $challenge->fresh()->otp_hash;

    $this->post(route('device-challenge.resend', $challenge->token));

    $secondHash = $challenge->fresh()->otp_hash;
    expect($secondHash)->not->toBe($firstHash);
    expect($challenge->fresh()->resend_count)->toBe(1);

    Mail::assertSent(\App\Mail\DeviceLoginOtpMail::class, 2);
});

test('a request carrying a valid trust cookie for that user skips the challenge entirely', function () {
    $user = deviceRegUser();
    $rawCookie = TrustedDevice::issueFor($user, request());

    $response = $this->withCookie(TrustedDevice::cookieName(), $rawCookie)
        ->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('a trust cookie belonging to a different user is not honored', function () {
    $owner = deviceRegUser();
    $other = deviceRegUser();
    $rawCookie = TrustedDevice::issueFor($owner, request());

    $response = $this->withCookie(TrustedDevice::cookieName(), $rawCookie)
        ->post(route('login'), ['email' => $other->email, 'password' => 'Passw0rd!123']);

    expect(Auth::check())->toBeFalse();
    expect($response->headers->get('Location'))->toContain('device-challenge');
});

test('toggling the feature off via settings makes the next login skip the challenge', function () {
    $user = deviceRegUser();
    $this->actingAs($user)->post(route('profile.device-registration.update'), [
        'password' => 'Passw0rd!123',
        'enabled' => 0,
    ]);
    Auth::logout();

    expect($user->fresh()->device_registration_enabled)->toBeFalse();

    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('wrong password on the settings toggle returns the deviceRegistration bag and does not change the flag', function () {
    $user = deviceRegUser();

    $response = $this->actingAs($user)->post(route('profile.device-registration.update'), [
        'password' => 'wrong-password',
        'enabled' => 0,
    ]);

    $response->assertSessionHasErrors(['password' => 'The password you entered is incorrect.'], null, 'deviceRegistration');
    expect($user->fresh()->device_registration_enabled)->toBeTrue();
});

test('revoking a trusted device removes it from the list and a subsequent login is challenged again', function () {
    $user = deviceRegUser();
    $rawCookie = TrustedDevice::issueFor($user, request());
    $device = TrustedDevice::where('user_id', $user->id)->first();

    $this->actingAs($user)->delete(route('profile.trusted-devices.revoke', $device));

    expect($device->fresh()->revoked_at)->not->toBeNull();

    Auth::logout();
    $response = $this->withCookie(TrustedDevice::cookieName(), $rawCookie)
        ->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);

    expect(Auth::check())->toBeFalse();
    expect($response->headers->get('Location'))->toContain('device-challenge');
});

test('a user cannot revoke another users trusted device', function () {
    $owner = deviceRegUser();
    $intruder = deviceRegUser();
    TrustedDevice::issueFor($owner, request());
    $device = TrustedDevice::where('user_id', $owner->id)->first();

    $response = $this->actingAs($intruder)->delete(route('profile.trusted-devices.revoke', $device));

    $response->assertForbidden();
    expect($device->fresh()->revoked_at)->toBeNull();
});

test('a user with no verified phone and no other trusted device only sees email as an available channel', function () {
    $user = deviceRegUser();

    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'Passw0rd!123']);
    $challenge = DeviceVerificationChallenge::where('user_id', $user->id)->first();

    $show = $this->get(route('device-challenge.show', $challenge->token));

    $show->assertOk();
    $show->assertViewHas('availableChannels', ['email']);
});

test('a freshly-registered user has device registration enabled by default (the migration only backfilled pre-existing rows to false)', function () {
    // Neither the factory nor create() sets this column explicitly, so a
    // freshly-created row relies entirely on the DB column default —
    // ->fresh() re-reads it, unlike the in-memory model returned by create().
    $freshUser = User::factory()->create()->fresh();

    expect($freshUser->device_registration_enabled)->toBeTrue();
});
