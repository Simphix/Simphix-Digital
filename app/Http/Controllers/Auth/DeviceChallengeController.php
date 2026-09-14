<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeviceVerificationChallenge;
use App\Models\TrustedDevice;
use App\Services\DeviceOtp\DeviceOtpDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * Handles the new-device OTP challenge that AuthenticatedSessionController
 * redirects to when a login succeeds but the device isn't a recognized
 * trusted one. The user is a GUEST for the entire duration of this
 * controller (AuthenticatedSessionController already logged them back out
 * before redirecting here) — access is gated purely by possessing the
 * unguessable challenge token in the URL, never by session/auth state.
 * This is the core safety property of the whole flow.
 */
class DeviceChallengeController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $challenge = DeviceVerificationChallenge::valid()->where('token', $token)->first();

        if (!$challenge) {
            return redirect()->route('login')->withErrors([
                'email' => 'This sign-in verification link has expired. Please log in again.',
            ]);
        }

        $available = app(DeviceOtpDispatcher::class)->availableChannels($challenge->user);

        return view('auth.device-challenge', [
            'challenge' => $challenge,
            'availableChannels' => $available,
        ]);
    }

    public function send(Request $request, string $token): RedirectResponse
    {
        $challenge = $this->findValidChallenge($token);
        if (!$challenge) {
            return redirect()->route('login')->withErrors(['email' => 'This sign-in verification link has expired. Please log in again.']);
        }

        $dispatcher = app(DeviceOtpDispatcher::class);
        $available = $dispatcher->availableChannels($challenge->user);

        $request->validate(['channel' => 'required|string|in:' . implode(',', $available)]);

        $code = $challenge->generateOtp($request->string('channel'));
        $sent = $dispatcher->resolve($request->string('channel'))->send($challenge->user, $code, $challenge);

        if (!$sent) {
            return redirect()->route('device-challenge.show', ['token' => $token])
                ->with('error', 'Could not send a code via that channel right now — please try another.');
        }

        return redirect()->route('device-challenge.show', ['token' => $token])
            ->with('success', 'A verification code has been sent.');
    }

    public function resend(Request $request, string $token): RedirectResponse
    {
        $challenge = $this->findValidChallenge($token);
        if (!$challenge) {
            return redirect()->route('login')->withErrors(['email' => 'This sign-in verification link has expired. Please log in again.']);
        }

        if (!$challenge->channel || !$challenge->canResend()) {
            return redirect()->route('device-challenge.show', ['token' => $token])
                ->with('error', 'You have requested too many codes. Please try again later.');
        }

        $dispatcher = app(DeviceOtpDispatcher::class);
        $code = $challenge->generateOtp($challenge->channel);
        $sent = $dispatcher->resolve($challenge->channel)->send($challenge->user, $code, $challenge);
        $challenge->registerResend();

        return redirect()->route('device-challenge.show', ['token' => $token])
            ->with($sent ? 'success' : 'error', $sent ? 'A new code has been sent.' : 'Could not resend the code right now.');
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $challenge = $this->findValidChallenge($token);
        if (!$challenge) {
            return redirect()->route('login')->withErrors(['email' => 'This sign-in verification link has expired. Please log in again.']);
        }

        $request->validate(['code' => 'required|string|size:6']);

        if (!$challenge->verify($request->string('code'))) {
            if ($challenge->isLocked()) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Too many incorrect attempts. Please log in again.',
                ]);
            }

            return redirect()->route('device-challenge.show', ['token' => $token])
                ->withErrors(['code' => 'That code is incorrect or has expired.']);
        }

        $user = $challenge->user;

        Auth::login($user, $challenge->remember);
        $request->session()->regenerate();

        $cookieValue = TrustedDevice::issueFor($user, $request);
        Cookie::queue(Cookie::make(
            TrustedDevice::cookieName(),
            $cookieValue,
            60 * 24 * TrustedDevice::trustDays(),
            null,
            null,
            $request->secure(),
            true,
            false,
            'lax',
        ));

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        if (!in_array($user->role, ['super_admin', 'staff', 'checker', 'support'], true)) {
            session()->flash('pos_sim_notice', true);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function findValidChallenge(string $token): ?DeviceVerificationChallenge
    {
        return DeviceVerificationChallenge::valid()->where('token', $token)->first();
    }
}
