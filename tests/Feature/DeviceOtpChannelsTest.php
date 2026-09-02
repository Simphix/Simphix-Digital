<?php

use App\Mail\DeviceLoginOtpMail;
use App\Models\DeviceVerificationChallenge;
use App\Models\PushSubscription;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\DeviceOtp\DeviceOtpDispatcher;
use App\Services\WebPushService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Minishlink\WebPush\MessageSentReport;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

function openChallengeFor(User $user): DeviceVerificationChallenge
{
    return DeviceVerificationChallenge::open($user, request(), false);
}

test('the email channel sends a DeviceLoginOtpMail to the users address', function () {
    Mail::fake();
    $user = User::factory()->create();
    $challenge = openChallengeFor($user);

    $sent = app(DeviceOtpDispatcher::class)->resolve('email')->send($user, '123456', $challenge);

    expect($sent)->toBeTrue();
    Mail::assertSent(DeviceLoginOtpMail::class, fn ($mail) => $mail->hasTo($user->email) && $mail->otp === '123456');
});

test('the SMS channel posts the expected payload to Termii and reports success on a valid response', function () {
    Http::fake([
        '*' => Http::response(['message_id' => 'abc-123', 'message' => 'Successfully Sent'], 200),
    ]);
    $user = User::factory()->create(['phone' => '08031234567']);
    $challenge = openChallengeFor($user);

    $sent = app(DeviceOtpDispatcher::class)->resolve('sms')->send($user, '654321', $challenge);

    expect($sent)->toBeTrue();
    Http::assertSent(function (HttpRequest $request) {
        return str_contains($request->url(), 'termii')
            && $request['to'] === '08031234567'
            && str_contains($request['sms'], '654321');
    });
});

test('the SMS channel surfaces a failed Termii response as a channel failure, not an exception', function () {
    Http::fake(['*' => Http::response(['message' => 'Invalid API key'], 401)]);
    $user = User::factory()->create(['phone' => '08031234567']);
    $challenge = openChallengeFor($user);

    $sent = app(DeviceOtpDispatcher::class)->resolve('sms')->send($user, '654321', $challenge);

    expect($sent)->toBeFalse();
});

test('the push channel targets the users most-recently-used push subscription and includes the code', function () {
    $user = User::factory()->create();
    TrustedDevice::issueFor($user, request());
    $subscription = PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-otp-test',
        'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/device-otp-test'),
        'public_key' => 'pk',
        'auth_token' => 'at',
    ]);
    $challenge = openChallengeFor($user);
    $report = new MessageSentReport(new \GuzzleHttp\Psr7\Request('POST', 'https://example.test'));

    $this->mock(WebPushService::class, function ($mock) use ($subscription, $report) {
        $mock->shouldReceive('sendOne')
            ->withArgs(fn ($sub, $payload) => $sub->id === $subscription->id && str_contains($payload['body'], '999999'))
            ->once()
            ->andReturn($report);
    });

    $sent = app(DeviceOtpDispatcher::class)->resolve('push')->send($user, '999999', $challenge);

    expect($sent)->toBeTrue();
});

test('push is not offered as a channel when the user has no other trusted device', function () {
    $user = User::factory()->create();
    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/no-trusted-device',
        'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/no-trusted-device'),
        'public_key' => 'pk',
        'auth_token' => 'at',
    ]);

    $available = app(DeviceOtpDispatcher::class)->availableChannels($user);

    expect($available)->not->toContain('push');
});

test('a failed send on one channel does not corrupt challenge state, so a different channel can still be picked', function () {
    Http::fake(['*' => Http::response(['message' => 'down'], 500)]);
    $user = User::factory()->create(['phone' => '08031234567']);
    $challenge = openChallengeFor($user);

    $sent = app(DeviceOtpDispatcher::class)->resolve('sms')->send($user, '111111', $challenge);
    expect($sent)->toBeFalse();

    Mail::fake();
    $sentEmail = app(DeviceOtpDispatcher::class)->resolve('email')->send($user, '222222', $challenge->fresh());
    expect($sentEmail)->toBeTrue();
});
