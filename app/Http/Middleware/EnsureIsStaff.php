<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsStaff
{
    /**
     * Handle an incoming request. Gates administrative request desks —
     * accessible to Super Admins, Business Sales Managers, and Support Staff.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->isStaff()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access. Only administrative staff (Super Admin, BSM, or Support) can access this desk.');
    }
}
