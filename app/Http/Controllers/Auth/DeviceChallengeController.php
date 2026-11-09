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

        if (!$challenge->channel || !$challenge->otp_hash || ($challenge->otp_expires_at && $challenge->otp_expires_at->isPast())) {
            $code = $challenge->generateOtp('email');
            app(DeviceOtpDispatcher::class)->resolve('email')->send($challenge->user, $code, $challenge);
            $challenge->refresh();
        }

        return view('auth.device-challenge', [
            'challenge' => $challenge,
            'availableChannels' => ['email'],
        ]);
    }

    public function send(Request $request, string $token): RedirectResponse
    {
        $challenge = $this->findValidChallenge($token);
        if (!$challenge) {
            return redirect()->route('login')->withErrors(['email' => 'This sign-in verification link has expired. Please log in again.']);
        }

        $channel = 'email';
        $dispatcher = app(DeviceOtpDispatcher::class);
        $code = $challenge->generateOtp($channel);
        $sent = $dispatcher->resolve($channel)->send($challenge->user, $code, $challenge);

        if (!$sent) {
            return redirect()->route('device-challenge.show', ['token' => $token])
                ->with('error', 'Could not send verification code right now. Please try again.');
        }

        return redirect()->route('device-challenge.show', ['token' => $token])
            ->with('success', 'A verification code has been sent to your email.');
    }

    public function resend(Request $request, string $token): RedirectResponse
    {
        $challenge = $this->findValidChallenge($token);
        if (!$challenge) {
            return redirect()->route('login')->withErrors(['email' => 'This sign-in verification link has expired. Please log in again.']);
        }

        if (!$challenge->canResend()) {
            return redirect()->route('device-challenge.show', ['token' => $token])
                ->with('error', 'You have requested too many codes. Please try again later.');
        }

        $channel = 'email';
        $dispatcher = app(DeviceOtpDispatcher::class);
        $code = $challenge->generateOtp($channel);
        $sent = $dispatcher->resolve($channel)->send($challenge->user, $code, $challenge);
        $challenge->registerResend();

        return redirect()->route('device-challenge.show', ['token' => $token])
            ->with($sent ? 'success' : 'error', $sent ? 'A new verification code has been sent to your email.' : 'Could not resend the code right now.');
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

        if (!\App\Support\StaffAccess::isInternal($user)) {
            session()->flash('pos_sim_notice', true);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function findValidChallenge(string $token): ?DeviceVerificationChallenge
    {
        return DeviceVerificationChallenge::valid()->where('token', $token)->first();
    }
}
