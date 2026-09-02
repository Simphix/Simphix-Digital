<?php

namespace App\Http\Controllers;

use App\Mail\KycWelcomeMail;
use App\Models\LeaderboardTier;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Repositories\VirtualAccountRepository;
use App\Support\DownlineAuthorization;
use App\Support\DownlineMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The team-member detail page reached from Network > Team — Info, Metrics,
 * Tier, and Actions tabs for one specific downline user. Every method here
 * is scoped to a $user route-model-bound target, never Auth::user(), and
 * every entry point is gated by DownlineAuthorization::authorize() (own
 * immediate downline only, or super_admin).
 */
class DownlineDetailController extends Controller
{
    public function show(User $user)
    {
        DownlineAuthorization::authorize($user);

        $user->loadMissing('wallet');
        $wallet = $user->wallet ?? Wallet::firstOrCreateForUser($user->id);
        $virtualAccount = VirtualAccount::where('user_id', $user->id)->first();

        $metrics = DownlineMetrics::forUser($user);

        // Tier progress only applies to partners (the leaderboard's only ranked role).
        $currentTier = $nextTier = $progressPercent = null;
        if ($user->role === 'partner') {
            $currentTier = LeaderboardTier::resolveForCount($metrics['periodActivations']);
            $nextTier = LeaderboardTier::nextTier($metrics['periodActivations']);
            $progressPercent = $nextTier
                ? min(100, (int) round(($metrics['periodActivations'] / $nextTier->activation_target) * 100))
                : ($currentTier ? 100 : 0);
        }

        return view('network.downline-show', array_merge($metrics, compact(
            'user',
            'wallet',
            'virtualAccount',
            'currentTier',
            'nextTier',
            'progressPercent'
        )));
    }

    /**
     * Upline fills the mandatory profile fields on behalf of a downline user
     * who hasn't cleared the pop-up onboarding modal yet (account_tier = 0
     * — the same signal layouts/app.blade.php checks to force that modal).
     * Mirrors KycController::submit() exactly, minus the "agree to terms"
     * checkbox — that's the account holder's own attestation, not the
     * upline's to give.
     */
    public function completeOnboarding(Request $request, User $user)
    {
        DownlineAuthorization::authorize($user);

        if ($user->account_tier > 0) {
            return back()->with('error', "{$user->first_name} {$user->last_name} has already completed onboarding.");
        }

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $validated = $request->validate([
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone,' . $user->id],
            'state'       => ['required', 'string', 'max:255'],
            'lga'         => ['required', 'string', 'max:255'],
            'address'     => ['required', 'string', 'max:500'],
        ]);

        $user->forceFill($validated + ['account_tier' => 1])->save();

        try {
            Mail::to($user->email)->send(new KycWelcomeMail($user));
        } catch (\Exception $e) {
            Log::warning('KYC welcome email failed to send: ' . $e->getMessage());
        }

        return back()->with('success', "Onboarding completed for {$user->first_name} {$user->last_name}.");
    }

    /**
     * Upline generates a virtual funding account for a downline user, same
     * BVN/NIN flow the user would use themselves (WalletController::createWallet()),
     * scoped to $user instead of Auth::user(). VirtualAccountRepository
     * already accepts an arbitrary user id — no repository changes needed.
     */
    public function generateVirtualAccount(Request $request, User $user)
    {
        DownlineAuthorization::authorize($user);

        if (VirtualAccount::where('user_id', $user->id)->exists()) {
            return back()->with('error', "{$user->first_name} {$user->last_name} already has a virtual account.");
        }

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $validated = $request->validate([
            'phone'         => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone,' . $user->id],
            'identity_type' => 'required|in:bvn,nin',
            'bvn'           => 'required_if:identity_type,bvn|nullable|string|digits:11|unique:users,bvn,' . $user->id,
            'nin'           => 'required_if:identity_type,nin|nullable|string|digits:11|unique:users,nin,' . $user->id,
        ]);

        $identityType = $validated['identity_type'];

        $user->phone = $validated['phone'];
        if ($identityType === 'nin') {
            $user->nin = $validated['nin'];
        } else {
            $user->bvn = $validated['bvn'];
        }
        $user->save();

        $result = app(VirtualAccountRepository::class)->createVirtualAccount($user->id, $identityType);

        if (!is_array($result) || empty($result['success'])) {
            $message = is_array($result) && isset($result['message']) ? $result['message'] : 'Virtual account creation failed. Please try again later.';

            return back()->with('error', $message);
        }

        return back()->with('success', "Virtual account generated for {$user->first_name} {$user->last_name}.");
    }
}
