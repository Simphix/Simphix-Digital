<?php

namespace App\Contracts;

use App\Models\DeviceVerificationChallenge;
use App\Models\User;

interface DeviceOtpChannelInterface
{
    /**
     * Deliver the given OTP code to the user via this channel.
     *
     * @return bool true if the send was dispatched successfully
     */
    public function send(User $user, string $code, DeviceVerificationChallenge $challenge): bool;
}
