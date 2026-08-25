<?php

namespace App\Http\Controllers;

use App\Models\EnterprisePartnerApplication;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

/**
 * The Enterprise Partner funnel: an existing Regional Manager / Coordinator
 * / Partner shares a referral link, the invitee fills a dedicated
 * registration form (eligibility = buying a minimum 100 MTN POS SIMs),
 * lands on an info page explaining what's next, then (PR9) generates a
 * virtual account to pay for the SIMs. Until payment clears (PR10), the new
 * account is locked out of the rest of the app by EnsureEnterprisePartnerPaid.
 */
class EnterprisePartnerController extends Controller
{
    /** Roles allowed to invite a new Enterprise Partner. */
    private const INVITER_ROLES = ['regional_manager', 'coordinator', 'partner'];

    /**
     * Shareable registration link for an eligible upline, keyed off their
     * existing referral_code — same identifier the standard registration
     * page already resolves via ?ref=.
     */
    public function inviteForm(Request $request)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, self::INVITER_ROLES, true)) {
            abort(403, 'Only Regional Managers, Coordinators, and Partners can invite an Enterprise Partner.');
        }

        $inviteUrl = route('enterprise-partner.register', ['ref' => $user->referral_code]);

        return view('enterprise-partner.invite', compact('user', 'inviteUrl'));
    }

    public function registerForm(Request $request)
    {
        $inviter = $this->resolveInviter($request);
        if (!$inviter) {
            abort(404);
        }

        [$unitPrice, $quantity] = $this->resolveEligibility();

        return view('enterprise-partner.register', [
            'inviter' => $inviter,
            'unitPrice' => $unitPrice,
            'quantity' => $quantity,
            'totalAmount' => $unitPrice * $quantity,
        ]);
    }

    public function register(Request $request)
    {
        $inviter = $this->resolveInviter($request);
        if (!$inviter) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'in:sole_proprietor,llc,partnership'],
            'cac_number' => ['required', 'string', 'max:50'],
        ]);

        $ep = DB::transaction(function () use ($validated, $inviter) {
            $ep = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'business',
                // Accepted vetted-referral member — same OTP-bypass precedent
                // as OnboardingController::completeInvite(), not public
                // self-signup.
                'status' => 'pending_eligibility',
                'business_name' => $validated['business_name'],
                'business_type' => $validated['business_type'],
                'cac_number' => $validated['cac_number'],
                'referral_code' => strtoupper(Str::random(8)),
                'referred_by' => $inviter->id,
                'limit' => 20000.00,
            ]);

            // email_verified_at isn't mass-assignable — set it the same way
            // OnboardingController::completeInvite() does for its own
            // accepted-referral bypass.
            $ep->forceFill(['email_verified_at' => now()])->save();

            Wallet::create([
                'user_id' => $ep->id,
                'balance' => 0.00,
                'bonus' => 0.00,
                'hold_amount' => 0.00,
                'total_credited' => 0.00,
                'total_debited' => 0.00,
                'wallet_number' => 'WLT' . str_pad((string) $ep->id, 7, '0', STR_PAD_LEFT),
                'currency' => 'NGN',
                'daily_limit' => 100000.00,
                'monthly_limit' => 1000000.00,
                'status' => 'active',
                'is_locked' => false,
                'last_activity' => now(),
            ]);

            [$unitPrice, $quantity] = $this->resolveEligibility();

            EnterprisePartnerApplication::create([
                'user_id' => $ep->id,
                'sim_category' => 'POS SIM',
                'provider' => 'mtn',
                'quantity' => $quantity,
                'unit_price_snapshot' => $unitPrice,
                'total_amount_snapshot' => $unitPrice * $quantity,
                'status' => 'pending_payment',
            ]);

            return $ep;
        });

        Auth::login($ep);
        $request->session()->regenerate();

        return redirect()->route('enterprise-partner.info')->with('success', 'Application received! Review the eligibility details below.');
    }

    public function infoPage()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'business') {
            abort(403);
        }

        $application = EnterprisePartnerApplication::where('user_id', $user->id)->first();

        return view('enterprise-partner.info', compact('user', 'application'));
    }

    /**
     * Resolve & validate the inviter referenced by ?ref= against their
     * referral_code — must be an active member of one of the three roles
     * eligible to invite an Enterprise Partner.
     */
    private function resolveInviter(Request $request): ?User
    {
        $code = $request->input('ref');
        if (!$code) {
            return null;
        }

        $inviter = User::where('referral_code', $code)->first();
        if (!$inviter || !in_array($inviter->role, self::INVITER_ROLES, true) || $inviter->status !== 'active') {
            return null;
        }

        return $inviter;
    }

    /**
     * The eligibility purchase is always priced off the MTN POS SIM
     * category specifically (per the confirmed product decision — the only
     * SIM type that counts toward the 100-unit eligibility purchase), using
     * whatever role-priced rate is configured for 'business' — resolved the
     * same way both before and after the account exists, so the price
     * quoted on the registration page always matches what's actually
     * charged (the target role is always 'business', known upfront).
     */
    private function resolveEligibility(): array
    {
        $service = Service::where('name', 'simcard')->first();
        $field = $service
            ? ServiceField::where('service_id', $service->id)->where('field_name', 'POS SIM')->first()
            : null;

        $unitPrice = $field ? $field->getPriceForUserType('business') : 0.00;

        return [$unitPrice, 100];
    }
}
