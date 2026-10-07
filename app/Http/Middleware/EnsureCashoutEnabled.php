<?php

namespace App\Http\Middleware;

use App\Models\CashoutSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every route that creates or pays out a cash-out while the admin
 * master switch (CashoutSettings) is off. Rejections are deliberately not
 * guarded so pending requests can still be refunded.
 */
class EnsureCashoutEnabled
{
    public const MESSAGE = 'Cash out is currently disabled. Please try again later.';

    public function handle(Request $request, Closure $next): Response
    {
        if (CashoutSettings::enabled()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => self::MESSAGE], 403);
        }

        // GET pages bounce to a page that always exists; actions go back.
        $redirect = $request->isMethod('GET')
            ? redirect()->route('dashboard')
            : redirect()->back(fallback: route('dashboard'));

        return $redirect->with('error', self::MESSAGE);
    }
}
