<?php

namespace App\Http\Controllers;

use App\Models\EnterprisePartnerApplication;
use App\Models\EnterprisePartnerApplicationItem;
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
 * registration form (personal/business info only — no SIM selection),
 * lands on a cart page where they add/adjust SIM category+network line
 * items until their combined quantity reaches the 100-unit eligibility
 * minimum (see resolveAvailabilityMatrix()), then generates a virtual
 * account to pay for the order. Until payment clears, the new account is
 * locked out of the rest of the app by EnsureEnterprisePartnerPaid.
 */
class EnterprisePartnerController extends Controller
{
    /** Roles allowed to invite a new Enterprise Partner. */
    private const INVITER_ROLES = ['regional_manager', 'coordinator', 'partner'];

    /** Networks a SIM can be requested on — no dedicated provider table exists in this schema. */
    private const PROVIDERS = ['mtn', 'airtel', 'glo', '9mobile'];

    /** Eligibility is a minimum TOTAL quantity across every cart line item combined, not per item. */
    private const ELIGIBILITY_QUANTITY = 100;

    /** Sanity cap on a single add/update-quantity call — a fat-finger guard, not a product rule. */
    private const MAX_ITEM_QUANTITY = 5000;

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

            // An empty order header — the cart page (infoPage/cartPage) is
            // where the EP actually builds their order via addItem().
            EnterprisePartnerApplication::create([
                'user_id' => $ep->id,
                'total_amount_snapshot' => 0.00,
                'status' => 'pending_payment',
            ]);

            return $ep;
        });

        Auth::login($ep);
        $request->session()->regenerate();

        return redirect()->route('enterprise-partner.info')->with('success', 'Account created! Build your SIM order below.');
    }

    /**
     * The cart: an EP adds/adjusts SIM category+network line items here
     * until their combined quantity reaches the eligibility minimum, then
     * proceeds to generateAccountPage() to pay. Keeps the historical
     * "enterprise-partner.info" route name — it's already whitelisted in
     * EnsureEnterprisePartnerPaid and already sits in the right funnel spot
     * (post-registration, pre-payment).
     */
    public function infoPage()
    {
        $user = $this->authorizeBusiness();

        $application = EnterprisePartnerApplication::with('items')->where('user_id', $user->id)->first();
        $matrix = $this->resolveAvailabilityMatrix();
        $hasPayment = $application && EnterprisePartnerPayment::where('application_id', $application->id)->exists();
        $totalQuantity = $application ? $application->items->sum('quantity') : 0;
        $eligible = $totalQuantity >= self::ELIGIBILITY_QUANTITY;

        return view('enterprise-partner.info', compact('user', 'application', 'matrix', 'hasPayment', 'totalQuantity', 'eligible'))
            ->with('providers', self::PROVIDERS)
            ->with('eligibilityQuantity', self::ELIGIBILITY_QUANTITY);
    }

    /**
     * Add a SIM category/network line item to the cart, or increment its
     * quantity if that exact combo is already in the cart. Never trusts the
     * client's disabled options — re-validates the category/stock exactly
     * like register() used to before this moved into its own step.
     */
    public function addItem(Request $request)
    {
        $user = $this->authorizeBusiness();
        $application = EnterprisePartnerApplication::where('user_id', $user->id)->firstOrFail();
        $this->assertCartOpen($application);

        $validated = $request->validate([
            'sim_category' => ['required', 'string'],
            'provider' => ['required', 'string', 'in:' . implode(',', self::PROVIDERS)],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . self::MAX_ITEM_QUANTITY],
        ]);

        $field = $this->resolveActiveField($validated['sim_category']);
        if (!$field) {
            return back()->withErrors(['sim_category' => 'That SIM category is not available.'])->withInput();
        }

        try {
            DB::transaction(function () use ($application, $validated, $field) {
                $existing = EnterprisePartnerApplicationItem::where('application_id', $application->id)
                    ->where('sim_category', $validated['sim_category'])
                    ->where('provider', $validated['provider'])
                    ->lockForUpdate()
                    ->first();

                $prospectiveQuantity = ($existing?->quantity ?? 0) + $validated['quantity'];

                $stock = Sim::where('status', SimStatus::UNASSIGNED)
                    ->where('category', $validated['sim_category'])
                    ->where('provider', $validated['provider'])
                    ->count();

                if ($stock < $prospectiveQuantity) {
                    throw new \RuntimeException("Only {$stock} {$validated['sim_category']} (" . strtoupper($validated['provider']) . ") SIM(s) are currently in stock.");
                }

                $unitPrice = $field->getPriceForUserType('business');

                if ($existing) {
                    $existing->update([
                        'quantity' => $prospectiveQuantity,
                        'unit_price_snapshot' => $unitPrice,
                        'line_total_snapshot' => $prospectiveQuantity * $unitPrice,
                    ]);
                } else {
                    EnterprisePartnerApplicationItem::create([
                        'application_id' => $application->id,
                        'sim_category' => $validated['sim_category'],
                        'provider' => $validated['provider'],
                        'quantity' => $validated['quantity'],
                        'unit_price_snapshot' => $unitPrice,
                        'line_total_snapshot' => $validated['quantity'] * $unitPrice,
                    ]);
                }

                $this->recalculateTotal($application);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return redirect()->route('enterprise-partner.info')->with('success', 'Added to your order.');
    }

    /** Change an existing line item's quantity. */
    public function updateItemQuantity(Request $request, EnterprisePartnerApplicationItem $item)
    {
        $user = $this->authorizeBusiness();
        $application = EnterprisePartnerApplication::where('user_id', $user->id)->firstOrFail();

        if ($item->application_id !== $application->id) {
            abort(403);
        }

        $this->assertCartOpen($application);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:' . self::MAX_ITEM_QUANTITY],
        ]);

        try {
            DB::transaction(function () use ($application, $item, $validated) {
                $locked = EnterprisePartnerApplicationItem::where('id', $item->id)->lockForUpdate()->first();

                $stock = Sim::where('status', SimStatus::UNASSIGNED)
                    ->where('category', $locked->sim_category)
                    ->where('provider', $locked->provider)
                    ->count();

                if ($stock < $validated['quantity']) {
                    throw new \RuntimeException("Only {$stock} {$locked->sim_category} (" . strtoupper($locked->provider) . ") SIM(s) are currently in stock.");
                }

                $locked->update([
                    'quantity' => $validated['quantity'],
                    'line_total_snapshot' => $validated['quantity'] * $locked->unit_price_snapshot,
                ]);

                $this->recalculateTotal($application);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return redirect()->route('enterprise-partner.info')->with('success', 'Order updated.');
    }

    /** Remove a line item from the cart entirely. */
    public function removeItem(EnterprisePartnerApplicationItem $item)
    {
        $user = $this->authorizeBusiness();
        $application = EnterprisePartnerApplication::where('user_id', $user->id)->firstOrFail();

        if ($item->application_id !== $application->id) {
            abort(403);
        }

        $this->assertCartOpen($application);

        DB::transaction(function () use ($application, $item) {
            $item->delete();
            $this->recalculateTotal($application);
        });

        return redirect()->route('enterprise-partner.info')->with('success', 'Removed from your order.');
    }

    /**
     * The cart is locked the moment a payment account exists for it — the
     * PalmPay virtual account already has a fixed expected_amount baked in,
     * so changing items after that would desync the two. One source of
     * truth shared by every cart-mutation action and generateAccount()
     * itself; each caller decides how to respond (a raw 403 for the
     * mutation routes — normally unreachable since their forms are hidden
     * once the cart is locked — vs. a friendly redirect+flash for
     * generateAccount(), which is a normal path to land on after payment
     * already exists, e.g. via browser back).
     */
    private function cartIsLocked(EnterprisePartnerApplication $application): bool
    {
        return EnterprisePartnerPayment::where('application_id', $application->id)->exists();
    }

    private function assertCartOpen(EnterprisePartnerApplication $application): void
    {
        if ($this->cartIsLocked($application)) {
            abort(403, 'Your order is already locked in for payment and can no longer be changed.');
        }
    }

    private function recalculateTotal(EnterprisePartnerApplication $application): void
    {
        $application->update([
            'total_amount_snapshot' => $application->items()->sum('line_total_snapshot'),
        ]);
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

        $application = EnterprisePartnerApplication::with('items')->where('user_id', $user->id)->first();
        $payment = $application
            ? EnterprisePartnerPayment::where('application_id', $application->id)->latest()->first()
            : null;

        // No point showing a payment screen for an order that isn't
        // eligible yet — send them back to keep building it. Once a
        // payment already exists the order is locked in regardless, so
        // this only ever applies before checkout has started.
        if (!$payment && (!$application || $application->items->sum('quantity') < self::ELIGIBILITY_QUANTITY)) {
            return redirect()->route('enterprise-partner.info')->with('warning', 'Add at least ' . self::ELIGIBILITY_QUANTITY . ' SIMs to your order before proceeding to payment.');
        }

        return view('enterprise-partner.generate-account', compact('user', 'application', 'payment'));
    }

    public function generateAccount(Request $request)
    {
        $user = $this->authorizeBusiness();

        $application = EnterprisePartnerApplication::with('items')->where('user_id', $user->id)->first();
        if (!$application) {
            abort(404);
        }

        if ($this->cartIsLocked($application)) {
            return redirect()->route('enterprise-partner.generate-account')->with('error', 'A payment account has already been generated.');
        }

        // Never trust the client's disabled "Proceed to Payment" button —
        // re-verify the same eligibility total the cart page itself enforced.
        if ($application->items->sum('quantity') < self::ELIGIBILITY_QUANTITY) {
            return redirect()->route('enterprise-partner.info')->with('error', 'Add at least ' . self::ELIGIBILITY_QUANTITY . ' SIMs to your order before proceeding to payment.');
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
     * unavailable based on whether ANY UNASSIGNED SIMs exist for it — the
     * 100-unit eligibility minimum is a total across the whole cart (see
     * class docblock), not a per-combo minimum, so a combo with even 1 in
     * stock is a legitimate line item to add. Only the boolean is exposed
     * to the client, not the exact count — the cart shows "available" or
     * "not available," never a real-time inventory number.
     * addItem()/updateItemQuantity() re-run the live stock count
     * server-side against the actual requested quantity before ever
     * writing a row, so the client's disabled state can never be bypassed.
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
                    'available' => $stock > 0,
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
