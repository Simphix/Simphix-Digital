<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashoutSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Platform-wide cashout control and role-based cashout threshold settings.
 * Allows admins to switch cash out on/off globally, and configure minimum and
 * maximum cashout amounts per user role (including Enterprise Business and Business Agent).
 */
class CashoutSettingsController extends Controller
{
    public function edit()
    {
        $settings = CashoutSettings::current();
        $roleLimits = $settings->getAllRoleLimits();

        return view('admin.cash-out.settings', compact('settings', 'roleLimits'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = CashoutSettings::current();

        if ($request->has('role_limits')) {
            $validated = $request->validate([
                'role_limits' => ['required', 'array'],
                'role_limits.*.min' => ['required', 'numeric', 'min:0'],
                'role_limits.*.max' => ['required', 'numeric', 'gte:role_limits.*.min'],
            ], [
                'role_limits.*.min.required' => 'The minimum cash out amount is required.',
                'role_limits.*.min.numeric'  => 'The minimum cash out amount must be a number.',
                'role_limits.*.min.min'      => 'The minimum cash out amount cannot be negative.',
                'role_limits.*.max.required' => 'The maximum cash out amount is required.',
                'role_limits.*.max.numeric'  => 'The maximum cash out amount must be a number.',
                'role_limits.*.max.gte'      => 'The maximum amount must be greater than or equal to the minimum amount.',
            ]);

            $currentLimits = $settings->role_limits ?? [];
            foreach ($validated['role_limits'] as $role => $amounts) {
                if (array_key_exists($role, CashoutSettings::CONFIGURABLE_ROLES)) {
                    $currentLimits[$role] = [
                        'min' => (float) $amounts['min'],
                        'max' => (float) $amounts['max'],
                    ];
                }
            }

            $settings->update(['role_limits' => $currentLimits]);

            return back()->with('success', 'Role cash out limits updated successfully.');
        }

        if ($request->has('cashout_charges_form')) {
            $validated = $request->validate([
                'cashout_fee' => ['nullable', 'numeric', 'min:0'],
                'cashout_fee_type' => ['required', 'in:fixed,percent'],
                'cashout_fee_description' => ['nullable', 'string', 'max:255'],
                'cashout_threshold_amount' => ['nullable', 'numeric', 'min:0'],
                'cashout_threshold_fee' => ['nullable', 'numeric', 'min:0'],
                'cashout_threshold_description' => ['nullable', 'string', 'max:255'],
            ]);

            $settings->update([
                'cashout_fee' => $validated['cashout_fee'] !== null && $validated['cashout_fee'] !== '' ? (float) $validated['cashout_fee'] : 0,
                'cashout_fee_type' => $validated['cashout_fee_type'] ?? 'fixed',
                'cashout_fee_description' => $validated['cashout_fee_description'] ?: 'Withdrawal Processing Fee',
                'cashout_threshold_amount' => $validated['cashout_threshold_amount'] !== null && $validated['cashout_threshold_amount'] !== '' ? (float) $validated['cashout_threshold_amount'] : null,
                'cashout_threshold_fee' => $validated['cashout_threshold_fee'] !== null && $validated['cashout_threshold_fee'] !== '' ? (float) $validated['cashout_threshold_fee'] : 0,
                'cashout_threshold_description' => $validated['cashout_threshold_description'] ?: 'Transaction Levy / Stamp Duty',
            ]);

            return back()->with('success', 'Cash out charges and threshold settings updated successfully.');
        }

        if ($request->has('inflow_charges_form')) {
            $validated = $request->validate([
                'inflow_fee' => ['nullable', 'numeric', 'min:0'],
                'inflow_fee_type' => ['required', 'in:fixed,percent'],
                'inflow_fee_description' => ['nullable', 'string', 'max:255'],
                'inflow_threshold_amount' => ['nullable', 'numeric', 'min:0'],
                'inflow_threshold_fee' => ['nullable', 'numeric', 'min:0'],
                'inflow_threshold_description' => ['nullable', 'string', 'max:255'],
            ]);

            $settings->update([
                'inflow_fee' => $validated['inflow_fee'] !== null && $validated['inflow_fee'] !== '' ? (float) $validated['inflow_fee'] : 0,
                'inflow_fee_type' => $validated['inflow_fee_type'] ?? 'fixed',
                'inflow_fee_description' => $validated['inflow_fee_description'] ?: 'Wallet Funding Fee',
                'inflow_threshold_amount' => $validated['inflow_threshold_amount'] !== null && $validated['inflow_threshold_amount'] !== '' ? (float) $validated['inflow_threshold_amount'] : null,
                'inflow_threshold_fee' => $validated['inflow_threshold_fee'] !== null && $validated['inflow_threshold_fee'] !== '' ? (float) $validated['inflow_threshold_fee'] : 0,
                'inflow_threshold_description' => $validated['inflow_threshold_description'] ?: 'Electronic Money Transfer Levy',
            ]);

            return back()->with('success', 'Money inflow charges and threshold settings updated successfully.');
        }

        if ($request->has('is_enabled')) {
            $request->validate(['is_enabled' => ['required', 'boolean']]);

            $isEnabled = $request->boolean('is_enabled');
            $settings->update(['is_enabled' => $isEnabled]);

            return back()->with('success', $isEnabled
                ? 'Cash out has been switched ON across the platform.'
                : 'Cash out has been switched OFF across the platform.');
        }

        return back();
    }
}
