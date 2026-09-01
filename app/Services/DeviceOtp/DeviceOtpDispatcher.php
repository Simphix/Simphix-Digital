<?php

namespace App\Services\DeviceOtp;

use App\Contracts\DeviceOtpChannelInterface;
use App\Models\User;
use App\Services\TermiiSmsApi;
use App\Services\WebPushService;

/**
 * Resolves a channel name to its DeviceOtpChannelInterface implementation
 * and computes which channels are actually usable for a given user right
 * now — same spirit as SmeDataProviderFactory::forProvider(), one place
 * that knows about every concrete channel so callers (the challenge
 * controller, the picker view) don't have to.
 */
class DeviceOtpDispatcher
{
    public const CHANNELS = ['email', 'sms', 'push'];

    public function resolve(string $channel): DeviceOtpChannelInterface
    {
        return match ($channel) {
            'email' => new EmailDeviceOtpChannel(),
            'sms' => new SmsDeviceOtpChannel(app(TermiiSmsApi::class)),
            'push' => new PushDeviceOtpChannel(app(WebPushService::class)),
            default => throw new \InvalidArgumentException("Unknown device OTP channel: {$channel}"),
        };
    }

    /**
     * @return array<int, string> the channels available to this user right now
     */
    public function availableChannels(User $user): array
    {
        $channels = ['email'];

        if ($user->phone && $user->phone_verified_at) {
            $channels[] = 'sms';
        }

        if (PushDeviceOtpChannel::targetSubscription($user)) {
            $channels[] = 'push';
        }

        return $channels;
    }
}
