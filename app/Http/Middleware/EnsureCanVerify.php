<?php

namespace App\Http\Middleware;

use App\Support\UtilityAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanVerify
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! \App\Models\Service::isAvailable('verification')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identity Verification services are currently disabled by platform administration.',
                ], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Identity Verification services are currently disabled by platform administration.');
        }

        if ($request->user() && UtilityAccess::canVerify($request->user())) {
            return $next($request);
        }

        abort(403, 'This feature is not available for your account type.');
    }
}
