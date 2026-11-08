<?php

namespace App\Exceptions;

use Exception;

/**
 * The payment provider answered, and refused the funding account because of
 * something in the user's own details (a BVN/NIN name mismatch, a bad date of
 * birth, ...) — as opposed to a system, auth or connection failure, which is
 * a plain Exception. Carries the provider's raw messages for the log; users
 * only ever see App\Support\FundingAccountErrors::forUser() of them.
 */
class FundingAccountRejected extends Exception
{
    /** @param list<string> $messages */
    public function __construct(public readonly array $messages)
    {
        parent::__construct('Funding account rejected: ' . implode(' | ', $messages));
    }
}
