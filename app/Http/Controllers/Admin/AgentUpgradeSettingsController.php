<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentUpgradeSettings;
use Illuminate\Http\Request;

class AgentUpgradeSettingsController extends Controller
{
    public function edit()
    {
        $settings = AgentUpgradeSettings::current();

        return view('admin.agent-upgrade.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'minimum_wallet_balance' => 'required|numeric|min:0',
            'partner_commission_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        AgentUpgradeSettings::current()->update([
            'minimum_wallet_balance' => $validated['minimum_wallet_balance'],
            'partner_commission_amount' => $validated['partner_commission_amount'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Agent upgrade settings updated.');
    }
}
