<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsBusinessSalesManager
{
    /**
     * Handle an incoming request. Gates the /bsm console —
     * accessible to Business Sales Managers and Super Admins.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && in_array($request->user()->role, ['business_sales_manager', 'super_admin'], true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access. Only Business Sales Managers can access this console.');
    }
}
