<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnterprisePartnerReferralSettings;
use Illuminate\Http\Request;

class EnterprisePartnerReferralSettingsController extends Controller
{
    public function edit()
    {
        $settings = EnterprisePartnerReferralSettings::current();

        return view('admin.enterprise-partners.referral-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'regional_manager_amount' => 'required|numeric|min:0',
            'coordinator_amount' => 'required|numeric|min:0',
            'partner_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = EnterprisePartnerReferralSettings::current();
        $settings->update([
            'regional_manager_amount' => $validated['regional_manager_amount'],
            'coordinator_amount' => $validated['coordinator_amount'],
            'partner_amount' => $validated['partner_amount'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Enterprise Partner referral settings updated.');
    }
}
