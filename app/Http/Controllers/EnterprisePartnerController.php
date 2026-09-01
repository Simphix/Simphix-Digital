<?php

namespace App\Http\Controllers;

use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerPayment;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Sim;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\VirtualAccountRepository;
use App\Support\SimStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

/**
 * The Enterprise Partner funnel: an existing Regional Manager / Coordinator
 * / Partner shares a referral link, the invitee fills a dedicated
 * registration form (eligibility = buying a minimum of 100 SIMs, in any
 * category/network that currently has enough stock to fulfill it — see
 * resolveAvailabilityMatrix()), lands on an info page explaining what's
 * next, then (PR9) generates a
 * virtual account to pay for the SIMs. Until payment clears (PR10), the new
 * account is locked out of the rest of the app by EnsureEnterprisePartnerPaid.
 */
class EnterprisePartnerController extends Controller
{
    /** Roles allowed to invite a new Enterprise Partner. */
    private const INVITER_ROLES = ['regional_manager', 'coordinator', 'partner'];

    /** Networks a SIM can be requested on — no dedicated provider table exists in this schema. */
    private const PROVIDERS = ['mtn', 'airtel', 'glo', '9mobile'];

    /** Every eligibility purchase is a fixed 100-unit minimum, regardless of category/network chosen. */
    private const ELIGIBILITY_QUANTITY = 100;

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

        return view('enterprise-partner.register', [
            'inviter' => $inviter,
            'matrix' => $this->resolveAvailabilityMatrix(),
            'providers' => self::PROVIDERS,
            'quantity' => self::ELIGIBILITY_QUANTITY,
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
            'sim_category' => ['required', 'string'],
            'provider' => ['required', 'string', 'in:' . implode(',', self::PROVIDERS)],
        ]);

        // Never trust the client's disabled options — re-run the exact same
        // availability check the registration page itself was rendered
        // with, before creating any record.
        $field = $this->resolveActiveField($validated['sim_category']);
        if (!$field) {
            return back()->withErrors(['sim_category' => 'That SIM category is not available.'])->withInput();
        }

        $stock = Sim::where('status', SimStatus::UNASSIGNED)
            ->where('category', $validated['sim_category'])
            ->where('provider', $validated['provider'])
            ->count();

        if ($stock < self::ELIGIBILITY_QUANTITY) {
            return back()->withErrors(['sim_category' => 'That category/network combination is currently unavailable. Please choose another.'])->withInput();
        }

        $unitPrice = $field->getPriceForUserType('business');
        $quantity = self::ELIGIBILITY_QUANTITY;

        $ep = DB::transaction(function () use ($validated, $inviter, $unitPrice, $quantity) {
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

            EnterprisePartnerApplication::create([
                'user_id' => $ep->id,
                'sim_category' => $validated['sim_category'],
                'provider' => $validated['provider'],
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
        $user = $this->authorizeBusiness();

        $application = EnterprisePartnerApplication::where('user_id', $user->id)->first();

        return view('enterprise-partner.info', compact('user', 'application'));
    }

    /**
     * Shows the pending payment's virtual account details + amount due if
     * one has already been generated, else a BVN/NIN identity form to
     * generate one — mirrors WalletController::createWallet()'s identity
     * verification step.
     */
    public function generateAccountPage()
    {
        $user = $this->authorizeBusiness();

        $application = EnterprisePartnerApplication::where('user_id', $user->id)->first();
        $payment = $application
            ? EnterprisePartnerPayment::where('application_id', $application->id)->latest()->first()
            : null;

        return view('enterprise-partner.generate-account', compact('user', 'application', 'payment'));
    }

    public function generateAccount(Request $request)
    {
        $user = $this->authorizeBusiness();

        $application = EnterprisePartnerApplication::where('user_id', $user->id)->first();
        if (!$application) {
            abort(404);
        }

        if (EnterprisePartnerPayment::where('application_id', $application->id)->exists()) {
            return redirect()->route('enterprise-partner.generate-account')->with('error', 'A payment account has already been generated.');
        }

        $validated = $request->validate([
            'identity_type' => 'required|in:bvn,nin',
            'bvn' => 'required_if:identity_type,bvn|nullable|string|digits:11|unique:users,bvn,' . $user->id,
            'nin' => 'required_if:identity_type,nin|nullable|string|digits:11|unique:users,nin,' . $user->id,
        ]);

        $identityType = $validated['identity_type'];
        if ($identityType === 'nin') {
            $user->nin = $validated['nin'];
        } else {
            $user->bvn = $validated['bvn'];
        }
        $user->save();

        $result = (new VirtualAccountRepository())->createVirtualAccountForEnterprisePartner($application, $user, $identityType);

        if (!$result['success']) {
            return redirect()->route('enterprise-partner.generate-account')->with('error', $result['message']);
        }

        return redirect()->route('enterprise-partner.generate-account')->with('success', $result['message']);
    }

    private function authorizeBusiness(): User
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'business') {
            abort(403);
        }

        return $user;
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
     * Every active SIM category × network combo, each flagged available/
     * unavailable based on whether at least ELIGIBILITY_QUANTITY UNASSIGNED
     * SIMs exist for it — the registration page disables unavailable
     * combos, and register() re-runs this exact same check server-side
     * before ever creating a record, so the client's disabled state can
     * never be bypassed.
     */
    private function resolveAvailabilityMatrix(): array
    {
        $service = Service::where('name', 'simcard')->first();
        $fields = $service ? ServiceField::where('service_id', $service->id)->active()->get() : collect();

        $matrix = [];
        foreach ($fields as $field) {
            $providers = [];
            foreach (self::PROVIDERS as $provider) {
                $stock = Sim::where('status', SimStatus::UNASSIGNED)
                    ->where('category', $field->field_name)
                    ->where('provider', $provider)
                    ->count();

                $providers[$provider] = [
                    'available' => $stock >= self::ELIGIBILITY_QUANTITY,
                    'stock' => $stock,
                ];
            }

            $matrix[$field->field_name] = [
                'unit_price' => $field->getPriceForUserType('business'),
                'providers' => $providers,
            ];
        }

        return $matrix;
    }

    /** The active ServiceField for a chosen category, or null if it isn't a real/active SIM category. */
    private function resolveActiveField(string $category): ?ServiceField
    {
        $service = Service::where('name', 'simcard')->first();

        return $service
            ? ServiceField::where('service_id', $service->id)->where('field_name', $category)->active()->first()
            : null;
    }
}
