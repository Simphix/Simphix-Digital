<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsSupportStaff
{
    /**
     * Handle an incoming request. Gates the /support-desk console — a
     * lighter, read-mostly role distinct from super_admin (see
     * EnsureIsSuperAdmin) that can triage tickets and look up transactions,
     * SIMs, and users without wallet/cash-out/account-restriction powers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'support') {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
