<?php

namespace App\Contracts;

interface SmeDataProviderInterface
{
    /**
     * Purchase a data plan for $mobile on $network.
     *
     * @return array{success: bool, data: array, api_data: array, transaction_ref: string, message: ?string}
     */
    public function purchase(string $mobile, string $network, string $planId, string $requestId, ?string $vendorAmount = null): array;
}
