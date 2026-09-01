<?php

namespace App\Services\DeviceOtp;

use App\Contracts\DeviceOtpChannelInterface;
use App\Mail\DeviceLoginOtpMail;
use App\Models\DeviceVerificationChallenge;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailDeviceOtpChannel implements DeviceOtpChannelInterface
{
    public function send(User $user, string $code, DeviceVerificationChallenge $challenge): bool
    {
        Mail::to($user->email)->send(new DeviceLoginOtpMail(
            otp: $code,
            deviceLabel: TrustedDevice::labelFromUserAgent($challenge->user_agent),
            ip: $challenge->ip_address,
            requestedAt: now()->format('F j, Y \a\t g:i A'),
        ));

        return true;
    }
}
