<?php

namespace App\Http\Middleware;

use App\Models\Service;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $serviceKey
     */
    public function handle(Request $request, Closure $next, string $serviceKey): Response
    {
        if (! Service::isAvailable($serviceKey)) {
            $label = ucwords(str_replace('_', ' ', $serviceKey));

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "The {$label} service is currently disabled by platform administration.",
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', "The {$label} service is currently disabled by platform administration.");
        }

        return $next($request);
    }
}
