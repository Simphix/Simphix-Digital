<?php

namespace App\Http\Controllers\smartsim;

use App\Http\Controllers\Controller;
use App\Models\BusinessCommissionRate;
use App\Models\Service;
use App\Models\ServiceField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets an Enterprise Partner (business role) configure their own downline
 * pricing: a price and commission per SIM category (or a default rate for
 * any category without its own override), keeping whatever margin
 * (price - commission) is left over once their business_agent's cut is
 * credited. See App\Listeners\AwardBusinessAgentCommissions for how this
 * feeds the payout.
 */
class BusinessCommissionRateController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->authorizeBusiness();

        $service = Service::where('name', 'simcard')->first();
        $fields = $service ? $service->fields()->active()->get() : collect();

        $rates = BusinessCommissionRate::where('business_id', $user->id)
            ->get()
            ->keyBy(fn (BusinessCommissionRate $rate) => $rate->service_fields_id ?? 'default');

        // Platform (system) prices, shown for reference only — the rates below
        // completely override them for this EP's whole chain.
        $platformPrices = $fields->mapWithKeys(fn ($f) => [$f->id => $f->getPriceForUserType('personal')]);

        return view('smartsimcard.business-commission-rates', compact('fields', 'rates', 'platformPrices'));
    }

    public function store(Request $request)
    {
        $user = $this->authorizeBusiness();

        $validated = $request->validate([
            'service_fields_id' => 'nullable|exists:service_fields,id',
            'price' => 'required|numeric|min:0',
            'commission' => 'required|numeric|min:0|lte:price',
        ]);

        BusinessCommissionRate::updateOrCreate(
            [
                'business_id' => $user->id,
                'service_fields_id' => $validated['service_fields_id'] ?? null,
            ],
            [
                'price' => $validated['price'],
                'commission' => $validated['commission'],
            ]
        );

        return back()->with('success', 'Commission rate saved.');
    }

    private function authorizeBusiness(): \App\Models\User
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'business') {
            abort(403, 'This page is only available to Enterprise Partners.');
        }

        return $user;
    }
}
