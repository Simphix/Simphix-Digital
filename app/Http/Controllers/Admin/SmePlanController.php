<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivationBonusSettings;
use App\Models\SmeData;
use App\Models\SmeDataProviderSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmePlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SmeData::query();

        // Search by Data ID or Size
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('data_id', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%");
            });
        }

        // Filter by Vendor
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by Network
        if ($request->filled('network')) {
            $query->where('network', $request->network);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        if ($request->sort == 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $plans = $query->paginate(10)->appends(request()->query());

        // Calculate stats
        $totalPlansCount = SmeData::count();
        $activePlansCount = SmeData::where('status', 'enabled')->count();
        $disabledPlansCount = SmeData::where('status', 'disabled')->count();

        $bonusSettingsByProvider = ActivationBonusSettings::allProviders();
        $activeDataProvider = SmeDataProviderSetting::activeProvider();
        // Scoped to the active vendor so an admin can't wire a bonus to a
        // plan ID that belongs to a different vendor's namespace — a plan
        // only stays selectable here while its vendor is the one actually
        // serving the storefront.
        $bonusEligiblePlansByNetwork = SmeData::where('status', 'enabled')
            ->where('provider', $activeDataProvider)
            ->orderBy('size')
            ->get()
            ->groupBy('network');

        $dataProviders = SmeDataProviderSetting::allProviders();

        return view('admin.sme-plans.index', compact(
            'plans',
            'totalPlansCount',
            'activePlansCount',
            'disabledPlansCount',
            'bonusSettingsByProvider',
            'bonusEligiblePlansByNetwork',
            'dataProviders',
            'activeDataProvider'
        ));
    }

    /**
     * Update a vendor's base URL/API key.
     */
    public function updateProviderSettings(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:' . implode(',', SmeDataProviderSetting::PROVIDERS)],
            'base_url' => ['required', 'url'],
            'api_key' => ['nullable', 'string'],
            'auth_base_url' => ['nullable', 'url'],
            'secret_key' => ['nullable', 'string'],
            'debit_account' => ['nullable', 'string'],
        ]);

        $settings = SmeDataProviderSetting::forProvider($validated['provider']);
        $update = ['base_url' => $validated['base_url']];

        // Blank means "leave the stored value as-is" — never blank out a
        // working credential just because the admin left the field empty.
        if (filled($validated['api_key'] ?? null)) {
            $update['api_key'] = $validated['api_key'];
        }
        if (filled($validated['auth_base_url'] ?? null)) {
            $update['auth_base_url'] = $validated['auth_base_url'];
        }
        if (filled($validated['secret_key'] ?? null)) {
            $update['secret_key'] = $validated['secret_key'];
        }
        if (filled($validated['debit_account'] ?? null)) {
            $update['debit_account'] = $validated['debit_account'];
        }

        $settings->update($update);

        return back()->with('success', 'Vendor settings updated.');
    }

    /**
     * Make a vendor the active one for the storefront — only its plans
     * become purchasable by users.
     */
    public function activateProvider(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:' . implode(',', SmeDataProviderSetting::PROVIDERS)],
        ]);

        SmeDataProviderSetting::activate($validated['provider']);

        return back()->with('success', 'Active SME data vendor updated.');
    }

    /**
     * Update the silent post-activation data bonus for one SIM provider:
     * on/off and which of that network's plans to grant.
     */
    public function updateActivationBonus(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:' . implode(',', ActivationBonusSettings::PROVIDERS)],
            'is_active' => ['nullable', 'boolean'],
            'sme_data_id' => [
                'nullable',
                Rule::exists('sme_data', 'id')
                    ->where('network', strtoupper($request->input('provider')))
                    ->where('provider', SmeDataProviderSetting::activeProvider()),
            ],
        ]);

        ActivationBonusSettings::forProvider($validated['provider'])->update([
            'is_active' => $request->boolean('is_active'),
            'sme_data_id' => $validated['sme_data_id'] ?? null,
        ]);

        return back()->with('success', strtoupper($validated['provider']) . ' activation bonus settings updated.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_id' => 'required|string|unique:sme_data,data_id',
            'provider' => 'required|string|in:' . implode(',', SmeDataProviderSetting::PROVIDERS),
            'network' => 'required|string|in:MTN,GLO,AIRTEL,9MOBILE',
            'plan_type' => 'required|string',
            'personal_price' => 'required|numeric|min:0',
            'agent_price' => 'required|numeric|min:0',
            'partner_price' => 'required|numeric|min:0',
            'business_price' => 'required|numeric|min:0',
            'vendor_amount' => 'nullable|numeric|min:0',
            'size' => 'required|string|max:255',
            'validity' => 'required|string|max:100',
            'status' => 'required|string|in:enabled,disabled',
        ]);

        SmeData::create($validated);

        return back()->with('success', 'SME Data Plan created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SmeData $plan)
    {
        $validated = $request->validate([
            'data_id' => 'required|string|unique:sme_data,data_id,' . $plan->id,
            'provider' => 'required|string|in:' . implode(',', SmeDataProviderSetting::PROVIDERS),
            'network' => 'required|string|in:MTN,GLO,AIRTEL,9MOBILE',
            'plan_type' => 'required|string',
            'personal_price' => 'required|numeric|min:0',
            'agent_price' => 'required|numeric|min:0',
            'partner_price' => 'required|numeric|min:0',
            'business_price' => 'required|numeric|min:0',
            'vendor_amount' => 'nullable|numeric|min:0',
            'size' => 'required|string|max:255',
            'validity' => 'required|string|max:100',
            'status' => 'required|string|in:enabled,disabled',
        ]);

        $plan->update($validated);

        return back()->with('success', 'SME Data Plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SmeData $plan)
    {
        $plan->delete();

        return back()->with('success', 'SME Data Plan deleted successfully.');
    }
}
