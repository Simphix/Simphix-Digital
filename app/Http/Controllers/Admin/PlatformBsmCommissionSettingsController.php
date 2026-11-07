<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformBsmCommissionSettings;
use App\Models\PlatformWallet;
use Illuminate\Http\Request;

class PlatformBsmCommissionSettingsController extends Controller
{
    public function edit()
    {
        $settings = PlatformBsmCommissionSettings::current();
        $bsmCommissionWallet = PlatformWallet::forType(PlatformWallet::TYPE_BSM_COMMISSION, 'BSM Central Commission Wallet');
        $simOperationsWallet = PlatformWallet::forType(PlatformWallet::TYPE_SIM_ACTIVATION, 'SIM Activation Wallet');

        return view('admin.platform-wallets.bsm-commission-settings', compact('settings', 'bsmCommissionWallet', 'simOperationsWallet'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'amount_per_activation' => 'required|numeric|min:0',
            'commission_type' => 'required|in:fixed,percent',
            'description' => 'nullable|string|max:255',
            'deduct_from_sim_operations' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = PlatformBsmCommissionSettings::current();
        $settings->update([
            'amount_per_activation' => $validated['amount_per_activation'],
            'commission_type' => $validated['commission_type'],
            'description' => $validated['description'] ?: 'BSM Central Commission',
            'deduct_from_sim_operations' => $request->boolean('deduct_from_sim_operations'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'BSM central commission settings updated successfully.');
    }
}
