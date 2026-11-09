<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A verified Fintava deposit that could not be attributed to any of our
 * funding accounts. The money has arrived at Fintava but no wallet can be
 * credited, so it must be recorded where an admin will see it, not treated
 * as handled.
 */
class UnmatchedFintavaWebhook extends RuntimeException
{
}
