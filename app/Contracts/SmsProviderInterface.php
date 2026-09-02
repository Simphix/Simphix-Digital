<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    /**
     * @return array{success: bool, message_id: ?string, message: ?string}
     */
    public function sendSms(string $to, string $message): array;
}
