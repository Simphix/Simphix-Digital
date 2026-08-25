<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnterprisePartnerPaid
{
    /**
     * A newly-registered Enterprise Partner (business role) can't use the
     * rest of the app until their eligibility payment clears (see
     * EnterprisePartnerActivationService) — everything except the info
     * page, the generate-account payment flow, and logout redirects here.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'business' && in_array($user->status, ['pending_eligibility', 'pending_payment'], true)) {
                if (!$request->routeIs('enterprise-partner.info', 'enterprise-partner.generate-account*', 'logout')) {
                    return redirect('/enterprise-partner/generate-account')->with('warning', 'Complete your eligibility payment to access your dashboard.');
                }
            }
        }

        return $next($request);
    }
}
