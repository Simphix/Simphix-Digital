<?php

namespace App\Services\DeviceOtp;

use App\Contracts\DeviceOtpChannelInterface;
use App\Contracts\SmsProviderInterface;
use App\Models\DeviceVerificationChallenge;
use App\Models\User;

class SmsDeviceOtpChannel implements DeviceOtpChannelInterface
{
    public function __construct(private SmsProviderInterface $sms)
    {
    }

    public function send(User $user, string $code, DeviceVerificationChallenge $challenge): bool
    {
        $message = "Your SmartSIM sign-in code is {$code}. Do not share this. Expires in 10 minutes.";

        $result = $this->sms->sendSms($user->phone, $message);

        return $result['success'];
    }
}
