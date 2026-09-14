<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Bank;
use App\Models\TrustedDevice;
use App\Models\WithdrawalAccount;
use App\Services\PalmPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'banks' => Bank::where('is_active', true)->orderBy('bank_name')->get(),
            'withdrawalAccount' => $request->user()->withdrawalAccount,
            'trustedDevices' => $request->user()->trustedDevices()
                ->whereNull('revoked_at')
                ->orderByDesc('last_used_at')
                ->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        abort_if(\App\Support\StaffAccess::isInternal($request->user()), 403, 'Personal info editing is not available for internal staff accounts.');

        $user = $request->user();
        $user->fill($request->safe()->except(['profile_photo']));

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');

            // Delete old photo if it exists
            if ($user->profile_photo) {
                if (str_starts_with($user->profile_photo, 'uploads/')) {
                    $oldPath = public_path($user->profile_photo);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                } else {
                    $oldPath = str_replace('storage/', '', $user->profile_photo);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
            }

            // Securely store profile photo on the public disk
            $path = $file->store('profile_photos', 'public');
            $user->profile_photo = 'storage/' . $path;
        }

        $user->save();

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')
            ->with('success', 'Profile information updated successfully.');
    }

    /**
     * Set or update user's transaction PIN.
     */
    public function updatePin(Request $request): RedirectResponse
    {
        // Step 1: Validate fields are present and PIN format is correct
        $request->validateWithBag('updatePin', [
            'password'        => ['required', 'string'],
            'transaction_pin' => ['required', 'string', 'digits:4', 'confirmed'],
        ], [
            'transaction_pin.digits'    => 'Transaction PIN must be exactly 4 digits.',
            'transaction_pin.confirmed' => 'The two PIN entries do not match.',
        ]);

        // Step 2: Manually verify password — fetch fresh from DB to avoid cached/hidden model issues
        $user = \App\Models\User::find(Auth::id());

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->getAuthPassword())) {
            return Redirect::route('profile.edit')
                ->withErrors(['password' => 'The password you entered is incorrect.'], 'updatePin')
                ->withInput();
        }

        // Step 3: Save the new PIN (auto-hashed via User model cast)
        $user->transaction_pin = $request->transaction_pin;
        $user->pin_set_at      = now();
        $user->save();

        // Audit log: record PIN change with IP and device info
        Log::info('Transaction PIN updated', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
            'ua'      => $request->userAgent(),
            'at'      => now()->toDateTimeString(),
        ]);

        return Redirect::route('profile.edit')->with('success', 'Transaction PIN updated successfully. You can now use it to authorise purchases.');
    }

    /**
     * Enable/disable the new-device OTP verification feature for this
     * account. Password-reconfirmed, same as updatePin() — this is a
     * security-relevant toggle, not an ordinary preference.
     */
    public function toggleDeviceRegistration(Request $request): RedirectResponse
    {
        $request->validateWithBag('deviceRegistration', [
            'password' => ['required', 'string'],
            'enabled'  => ['required', 'boolean'],
        ]);

        $user = \App\Models\User::find(Auth::id());

        if (!Hash::check($request->password, $user->getAuthPassword())) {
            return Redirect::route('profile.edit')
                ->withErrors(['password' => 'The password you entered is incorrect.'], 'deviceRegistration')
                ->withInput();
        }

        $user->device_registration_enabled = $request->boolean('enabled');
        $user->save();

        Log::info('Device registration setting changed', [
            'user_id' => $user->id,
            'enabled' => $user->device_registration_enabled,
            'ip'      => $request->ip(),
            'ua'      => $request->userAgent(),
        ]);

        $message = $user->device_registration_enabled
            ? 'New-device sign-in verification is now enabled.'
            : 'New-device sign-in verification is now disabled.';

        return Redirect::route('profile.edit')->with('success', $message);
    }

    /**
     * Revoke a single trusted device — a pure downgrade action (the user is
     * only making their own account harder, not easier, to access), so no
     * password reconfirmation is required, same as unsubscribing from push.
     */
    public function revokeTrustedDevice(Request $request, TrustedDevice $device): RedirectResponse
    {
        if ($device->user_id !== Auth::id()) {
            abort(403);
        }

        $device->revoke();

        Log::info('Trusted device revoked', [
            'user_id' => Auth::id(),
            'device_id' => $device->id,
            'ip' => $request->ip(),
        ]);

        return Redirect::route('profile.edit')->with('success', 'That device will need to verify again next time it signs in.');
    }

    /**
     * Send a one-time code to the phone number on file, to confirm the user
     * actually controls it before SMS can be offered as a device-OTP
     * delivery channel (phone is otherwise just unverified free text set
     * during KYC).
     */
    public function sendPhoneVerificationOtp(Request $request): RedirectResponse
    {
        $user = \App\Models\User::find(Auth::id());

        if (!$user->phone) {
            return Redirect::route('profile.edit')->with('error', 'Add a phone number to your profile first.');
        }

        $code = $user->generatePhoneVerificationOtp();
        $sent = app(\App\Services\TermiiSmsApi::class)->sendSms(
            $user->phone,
            "Your SmartSIM phone verification code is {$code}. Do not share this."
        );

        if (!$sent['success']) {
            return Redirect::route('profile.edit')->with('error', 'Could not send the verification code right now. Please try again shortly.');
        }

        return Redirect::route('profile.edit')->with('success', 'A verification code has been sent to your phone.');
    }

    public function verifyPhoneOtp(Request $request): RedirectResponse
    {
        $request->validateWithBag('phoneVerification', [
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = \App\Models\User::find(Auth::id());

        if (!$user->verifyPhoneVerificationOtp($request->code)) {
            return Redirect::route('profile.edit')
                ->withErrors(['code' => 'That code is incorrect or has expired.'], 'phoneVerification');
        }

        return Redirect::route('profile.edit')->with('success', 'Phone number verified — SMS is now available as a sign-in verification channel.');
    }

    /**
     * Set or replace the user's single saved cash-out withdrawal account.
     */
    public function updateWithdrawalAccount(Request $request, PalmPayService $palmPay): RedirectResponse
    {
        abort_if(\App\Support\StaffAccess::isInternal($request->user()), 403, 'Withdrawal accounts are not available for internal staff accounts.');

        $request->validateWithBag('updateWithdrawal', [
            'bankCode' => ['required', 'string', 'exists:banks,bank_code'],
            'account_no' => ['required', 'digits:10'],
        ]);

        $bank = Bank::where('bank_code', $request->bankCode)->first();

        // Resolve the account name server-side rather than trusting whatever
        // the client submits — this is the account money will actually be
        // sent to, so it must come from PalmPay's own lookup, not the form.
        try {
            $response = $palmPay->queryBankAccount($request->bankCode, $request->account_no);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return Redirect::route('profile.edit')
                ->withErrors(['account_no' => 'Unable to reach the bank verification service. Please try again shortly.'], 'updateWithdrawal')
                ->withInput();
        }

        $accountName = null;
        if (isset($response['respCode']) && $response['respCode'] === '00000000' && ($response['data']['Status'] ?? null) === 'Success') {
            $accountName = $response['data']['accountName'] ?? null;
        }

        if (!$accountName) {
            $message = $response['data']['errorMessage'] ?? $response['respMsg'] ?? 'Could not verify that account number.';

            return Redirect::route('profile.edit')
                ->withErrors(['account_no' => $message], 'updateWithdrawal')
                ->withInput();
        }

        WithdrawalAccount::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'bank_code' => $request->bankCode,
                'bank_name' => $bank->bank_name ?? 'Bank',
                'account_no' => $request->account_no,
                'account_name' => $accountName,
            ]
        );

        return Redirect::route('profile.edit')->with('success', 'Withdrawal account saved. You can now cash out to this account.');
    }

    /**
     * Handle business or partner account upgrade requests. Agent upgrades
     * moved to a separate, instant, wallet-balance-gated self-service flow
     * (see AgentUpgradeController) — no admin review, no business fields.
     */
    public function requestUpgrade(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(\App\Support\StaffAccess::isInternal($user), 403, 'Account upgrades are not available for internal staff accounts.');

        if ($request->input('role') === 'agent') {
            return Redirect::route('agent-upgrade.show')->with('error', 'Agent upgrades are now instant — use the Upgrade to Agent option instead.');
        }

        // Prevent multiple upgrade request submissions
        if ($user->upgrade_status === 'pending' || $user->upgrade_status === 'approved' || in_array($user->role, ['agent', 'partner', 'business'])) {
            return Redirect::route('profile.edit')->with('error', 'You already have a pending or approved upgrade request and cannot submit another one.');
        }

        $request->validate([
            'role' => ['required', 'string', 'in:partner,business'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'in:sole_proprietor,llc,partnership'],
            'cac_number' => ['required', 'string', 'max:50'],
        ]);

        // Explicit security check to prevent role injection attacks
        $allowedRoles = ['partner', 'business'];
        if (!in_array($request->role, $allowedRoles)) {
            abort(403, 'Invalid or unauthorized role request.');
        }

        $user->forceFill([
            'gender' => $request->gender,
            'pending_role' => $request->role,
            'upgrade_status' => 'pending',
            'upgrade_requested_at' => now(),
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'cac_number' => $request->cac_number,
        ])->save();

        return Redirect::route('profile.edit')->with('success', 'Your account upgrade request to ' . ucfirst($request->role) . ' has been submitted successfully and is currently pending review from our team.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_if(\App\Support\StaffAccess::isInternal($request->user()), 403, 'Internal staff accounts cannot self-delete.');

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Update status to inactive to preserve user information
        $user->forceFill([
            'status' => 'inactive',
        ])->save();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
