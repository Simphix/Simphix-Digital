<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use Illuminate\Http\Request;

class PlatformWelfareSettingsController extends Controller
{
    public function edit()
    {
        $settings = PlatformWelfareSettings::current();
        $welfareWallet = PlatformWallet::forType(PlatformWallet::TYPE_WELFARE, 'Platform Welfare Wallet');
        $simOperationsWallet = PlatformWallet::forType(PlatformWallet::TYPE_SIM_ACTIVATION, 'SIM Activation Wallet');

        return view('admin.platform-wallets.welfare-settings', compact('settings', 'welfareWallet', 'simOperationsWallet'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'amount_per_activation' => 'required|numeric|min:0',
            'welfare_type' => 'required|in:fixed,percent',
            'description' => 'nullable|string|max:255',
            'deduct_from_sim_operations' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = PlatformWelfareSettings::current();
        $settings->update([
            'amount_per_activation' => $validated['amount_per_activation'],
            'welfare_type' => $validated['welfare_type'],
            'description' => $validated['description'] ?: 'Platform Welfare Contribution',
            'deduct_from_sim_operations' => $request->boolean('deduct_from_sim_operations'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Platform welfare settings updated successfully.');
    }
}
