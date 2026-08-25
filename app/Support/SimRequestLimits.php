<?php

namespace App\Support;

use App\Models\User;

/**
 * Per-role ceiling on how many SIMs a single purchase request can ask for,
 * reflecting how much stock each tier realistically moves.
 */
class SimRequestLimits
{
    private const LIMITS = [
        'personal'         => 5,
        'agent'            => 5,
        'business_agent'   => 20,
        'business'         => 20,
        'partner'          => 20,
        'coordinator'      => 20,
        'regional_manager' => 500,
    ];

    public static function maxFor(User $user): int
    {
        return self::LIMITS[$user->role] ?? 20;
    }
}
