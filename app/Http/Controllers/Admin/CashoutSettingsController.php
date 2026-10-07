<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashoutSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Platform-wide cashout on/off switch. When off, user withdrawals, platform
 * wallet cash-outs and the approval payouts are all refused (see the
 * `cashout_enabled` middleware); the UI entry points are hidden too.
 */
class CashoutSettingsController extends Controller
{
    public function edit()
    {
        $settings = CashoutSettings::current();

        return view('admin.cash-out.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['is_enabled' => ['required', 'boolean']]);

        CashoutSettings::current()->update(['is_enabled' => $request->boolean('is_enabled')]);

        return back()->with('success', $request->boolean('is_enabled')
            ? 'Cash out has been switched ON across the platform.'
            : 'Cash out has been switched OFF across the platform.');
    }
}
