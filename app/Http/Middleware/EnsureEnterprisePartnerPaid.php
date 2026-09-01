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
     * (cart) page, the cart-mutation routes, the generate-account payment
     * flow, and logout redirects here. The fallback destination is the
     * cart, not generate-account — an EP with no route preference yet
     * should land on "build your order," not straight at a payment screen
     * for an order they may not have finished (or even started) building.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'business' && in_array($user->status, ['pending_eligibility', 'pending_payment'], true)) {
                if (!$request->routeIs('enterprise-partner.info', 'enterprise-partner.cart.*', 'enterprise-partner.generate-account*', 'logout')) {
                    return redirect()->route('enterprise-partner.info')->with('warning', 'Finish building your SIM order to unlock your dashboard.');
                }
            }
        }

        return $next($request);
    }
}
