<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DeviceVerificationChallenge;
use App\Models\TrustedDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * When device registration is enabled for the user and the request
     * doesn't carry a valid "device_trust" cookie for them, the login
     * Auth::attempt() just performed is immediately undone — nothing beyond
     * "is this device known?" may leak before an OTP confirms it — and the
     * user is redirected to the device-challenge flow instead. That flow
     * (DeviceChallengeController) completes the real login itself once the
     * code is verified, so Auth::logoutOtherDevices() below only ever runs
     * for an already-trusted device: a freshly-verified device's own new
     * session can never be logged out by itself, and single-device
     * enforcement still applies normally to the user's other stale sessions.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        $device = null;
        $trusted = true;

        if ($user->device_registration_enabled) {
            $device = TrustedDevice::resolveFromCookie($request->cookie(TrustedDevice::cookieName()));
            $trusted = $device && $device->user_id === $user->id;
        }

        if (!$trusted) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $challenge = DeviceVerificationChallenge::open($user, $request, $request->boolean('remember'));

            return redirect()->route('device-challenge.show', ['token' => $challenge->token]);
        }

        $request->session()->regenerate();

        // Terminate all other existing sessions for this user (Single Device Enforcement)
        Auth::logoutOtherDevices($request->password);

        $device?->recordUse($request);
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        if (!in_array($user->role, ['super_admin', 'staff', 'checker'], true)) {
            session()->flash('pos_sim_notice', true);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
