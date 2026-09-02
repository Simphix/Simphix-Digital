<?php

namespace App\Services\DeviceOtp;

use App\Contracts\DeviceOtpChannelInterface;
use App\Models\DeviceVerificationChallenge;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Log;

/**
 * The device being verified is, by definition, not yet trusted and so has
 * no push subscription of its own — this channel instead notifies one of
 * the user's OTHER already-trusted devices. The code is included directly
 * in the push payload: safe, because a push payload is end-to-end encrypted
 * to that specific subscription's own keys, so only that trusted device's
 * browser/service worker ever decrypts it — the new/challenging device
 * never sees it.
 */
class PushDeviceOtpChannel implements DeviceOtpChannelInterface
{
    public function __construct(private WebPushService $webPush)
    {
    }

    public function send(User $user, string $code, DeviceVerificationChallenge $challenge): bool
    {
        $subscription = self::targetSubscription($user);

        if (!$subscription) {
            return false;
        }

        try {
            $this->webPush->sendOne($subscription, [
                'title' => 'New sign-in attempt',
                'body' => TrustedDevice::labelFromUserAgent($challenge->user_agent) . " tried to sign in from {$challenge->ip_address}. Code: {$code}",
                'url' => '/dashboard',
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Push device-OTP send failed', ['user_id' => $user->id, 'exception' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * The user's most-recently-used push subscription — only offered as a
     * channel when the user has at least one other trusted, non-revoked
     * device (there is no FK tying a PushSubscription to a TrustedDevice,
     * so "has a trusted device" plus "has a subscription" is the closest
     * available proxy for "an already-trusted device can receive this").
     */
    public static function targetSubscription(User $user): ?\App\Models\PushSubscription
    {
        $hasTrustedDevice = $user->trustedDevices()
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if (!$hasTrustedDevice) {
            return null;
        }

        return $user->pushSubscriptions()->orderByDesc('last_used_at')->orderByDesc('created_at')->first();
    }
}
