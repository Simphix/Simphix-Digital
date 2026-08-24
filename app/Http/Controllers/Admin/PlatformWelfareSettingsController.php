<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformWelfareSettings;
use Illuminate\Http\Request;

class PlatformWelfareSettingsController extends Controller
{
    public function edit()
    {
        $settings = PlatformWelfareSettings::current();

        return view('admin.platform-wallets.welfare-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'amount_per_activation' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $settings = PlatformWelfareSettings::current();
        $settings->update([
            'amount_per_activation' => $validated['amount_per_activation'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Welfare wallet settings updated.');
    }
}
