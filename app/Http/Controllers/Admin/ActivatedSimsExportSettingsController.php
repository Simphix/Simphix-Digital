<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SimExportSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin-configurable on/off + recipient for the nightly "email every
 * activated SIM number to an inbox" job (see the `sims:export-activated`
 * console command and ActivatedSimsExportMail). Surfaced inline on the
 * SIMs Operations page — no dedicated edit view of its own.
 */
class ActivatedSimsExportSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        SimExportSettings::current()->update([
            'recipient_email' => $validated['recipient_email'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Nightly activated-SIMs export settings updated.');
    }
}
