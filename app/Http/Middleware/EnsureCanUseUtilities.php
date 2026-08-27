<?php

namespace App\Http\Middleware;

use App\Support\UtilityAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanUseUtilities
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && UtilityAccess::canUse($request->user())) {
            return $next($request);
        }

        $message = UtilityAccess::UTILITIES_SUSPENDED
            ? 'Airtime and Data purchases are temporarily unavailable. Please check back later.'
            : 'This feature is not available for your account type.';

        abort(403, $message);
    }
}
