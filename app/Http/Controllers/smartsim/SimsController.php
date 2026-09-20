<?php

namespace App\Http\Controllers\smartsim;

use App\Http\Controllers\Controller;
use App\Models\Sim;
use App\Models\SimRequest;
use App\Models\SimSwapRequest;
use App\Models\User;
use App\Services\SimAssignmentService;
use App\Support\RoleHierarchy;
use App\Support\SimAccess;
use App\Support\SimStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimsController extends Controller
{
    /**
     * Device pages exposed via the sidebar, keyed by URL slug.
     * 'category' must match the exact service_fields.field_name / sims.category value in the database.
     */
    private const DEVICE_PAGES = [
        'pos' => [
            'label' => 'POS SIM',
            'category' => 'POS SIM',
            'desc' => 'For payment terminals',
            'icon' => 'credit-card',
            'illustration' => 'pages.welcome2.illustrations.pos-sim',
        ],
        'cctv' => [
            'label' => 'CCTV SIM',
            'category' => 'CCTV',
            'desc' => 'For surveillance systems',
            'icon' => 'video',
            'illustration' => 'pages.welcome2.illustrations.cctv-sim',
        ],
        'router' => [
            'label' => 'Router SIM',
            'category' => 'ROUTER SIM',
            'desc' => 'For mobile routers',
            'icon' => 'router',
            'illustration' => 'pages.welcome2.illustrations.router-sim',
        ],
    ];

    /**
     * Fetch SIM categories and pricing dynamically from the database.
     */
    private function resolveCategories($user): array
    {
        $simService = \App\Models\Service::where('name', 'simcard')->first();
        $categories = [];
        if ($simService) {
            foreach ($simService->fields()->active()->get() as $field) {
                $categories[] = [
                    'name'  => $field->field_name,
                    'price' => $field->priceForUser($user),
                ];
            }
        } else {
            foreach (['POS SIM' => 1000.00, 'CAMERA SIM' => 1500.00, 'CCTV' => 2000.00, 'ROUTER SIM' => 2500.00, 'GPS SIM' => 3000.00] as $name => $price) {
                $categories[] = [
                    'name'  => $name,
                    'price' => $price,
                ];
            }
        }

        return $categories;
    }

    /**
     * SIM Services catalog: illustrated overview of every device SIM, linking to its device page.
     */
    public function overview(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $categories = $this->resolveCategories($user);
        $categoryPrices = collect($categories)->pluck('price', 'name');

        // Roles outside the SIM catalog (Personal, Agent's downline, ...) go
        // straight to the request page — they don't browse/self-activate,
        // someone in the hierarchy (or admin) activates on their behalf.
        $goToRequest = !SimAccess::canBrowseCatalog($user);
        $canEarn = SimAccess::canBrowseCatalog($user);

        $devices = [];
        foreach (self::DEVICE_PAGES as $slug => $meta) {
            $devices[] = [
                'slug' => $slug,
                'label' => $meta['label'],
                'desc' => $meta['desc'],
                'illustration' => $meta['illustration'],
                'price' => $categoryPrices[$meta['category']] ?? null,
                'commission' => $canEarn ? $this->resolveCommissionForRole($user, $meta['category']) : null,
                'route' => $goToRequest ? route('sims.' . $slug . '.request') : route('sims.' . $slug),
                'comingSoon' => false,
            ];
        }

        // GPS Tracking SIM is not yet sold — shown as a disabled "coming soon" entry.
        $devices[] = [
            'slug' => 'gps',
            'label' => 'GPS Tracking SIM',
            'desc' => 'For tracking devices',
            'illustration' => 'pages.welcome2.illustrations.gps-sim',
            'price' => null,
            'commission' => null,
            'route' => null,
            'comingSoon' => true,
        ];

        return view('smartsimcard.overview', compact('user', 'devices'));
    }

    /**
     * The per-sale commission this user's role earns for a given SIM category,
     * read from ServicePrice — the same table admin manages per category/role
     * at /admin/services. Mirrors App\Listeners\AwardCommissions.
     */
    private function resolveCommissionForRole(User $user, string $category): ?float
    {
        $service = \App\Models\Service::where('name', 'simcard')->first();
        if (!$service) {
            return null;
        }

        $field = \App\Models\ServiceField::where('service_id', $service->id)->where('field_name', $category)->first();
        if (!$field) {
            return null;
        }

        $servicePrice = \App\Models\ServicePrice::where('service_fields_id', $field->id)
            ->where('user_type', $user->role)
            ->whereNull('user_id')
            ->first();

        return $servicePrice ? (float) $servicePrice->commission : null;
    }

    /**
     * Shared renderer for a single device's request/activation page. The
     * left-panel action differs by role: Regional Manager / Coordinator only
     * distribute a held SIM down to their next-tier downline; Partner
     * activates a held SIM for one of their downline Users (debiting that
     * user, not themselves); Agent keeps the old self-activate flow.
     */
    private function renderDevicePage(string $slug)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $meta = self::DEVICE_PAGES[$slug];
        $category = $meta['category'];
        $categories = $this->resolveCategories($user);
        $price = collect($categories)->firstWhere('name', $category)['price'] ?? null;
        $providers = ['mtn', 'airtel', 'glo', '9mobile'];

        $mode = match ($user->role) {
            'regional_manager', 'coordinator', 'business' => 'distribute',
            'partner', 'business_agent' => 'activate_for_user',
            default => 'self_activate',
        };

        $downlineUsers = collect();

        $heldSims = match ($mode) {
            'distribute' => (match ($user->role) {
                'regional_manager' => Sim::where('regional_manager_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_RM),
                'coordinator' => Sim::where('coordinator_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_COORDINATOR),
                'business' => Sim::where('business_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS),
            })->where('category', $category)->latest()->get(),
            'activate_for_user' => (match ($user->role) {
                'partner' => Sim::where('partner_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_PARTNER),
                'business_agent' => Sim::where('business_agent_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS_AGENT),
            })->where('category', $category)->latest()->get(),
            default => Sim::where('user_id', $user->id)
                ->where('category', $category)
                ->where('status', '!=', SimStatus::ACTIVATED)
                ->latest()->get(),
        };

        $personalPrice = null;
        $activationDisabled = false;

        if ($mode === 'distribute') {
            $nextRole = \App\Support\RoleHierarchy::nextRole($user->role);
            $downlineUsers = $user->referrals()->where('role', $nextRole)->where('status', 'active')->orderBy('first_name')->get();
        } elseif ($mode === 'activate_for_user') {
            $downlineUsers = $user->referrals()->where('role', 'personal')->where('status', 'active')->orderBy('first_name')->get();

            // The fee shown is what the selected downline User (always 'personal') pays, not the partner's own rate.
            $simService = \App\Models\Service::where('name', 'simcard')->first();
            $field = $simService ? \App\Models\ServiceField::where('service_id', $simService->id)->where('field_name', $category)->first() : null;
            $personalPrice = $field
                ? (float) (\App\Models\ServicePrice::where('service_fields_id', $field->id)->where('user_type', 'personal')->whereNull('user_id')->value('price') ?? $field->base_price)
                : null;
            $activationDisabled = (bool) ($field->activation_disabled ?? false);
        }

        return view('smartsimcard.device', [
            'user' => $user,
            'device' => $meta,
            'personalPrice' => $personalPrice,
            'slug' => $slug,
            'category' => $category,
            'price' => $price,
            'providers' => $providers,
            'mode' => $mode,
            'heldSims' => $heldSims,
            'downlineUsers' => $downlineUsers,
            'activationDisabled' => $activationDisabled,
        ]);
    }

    public function pos()
    {
        $this->authorizeCatalogAccess();

        return $this->renderDevicePage('pos');
    }

    public function cctv()
    {
        $this->authorizeCatalogAccess();

        return $this->renderDevicePage('cctv');
    }

    public function router()
    {
        $this->authorizeCatalogAccess();

        return $this->renderDevicePage('router');
    }

    /**
     * POS SIM / CCTV SIM / Inventory are only for the catalog roles
     * (Regional Manager, Coordinator, Partner, Agent) — everyone else
     * requests a SIM directly and has it activated for them.
     */
    private function authorizeCatalogAccess(): void
    {
        $user = Auth::user();
        if (!$user || !SimAccess::canBrowseCatalog($user)) {
            abort(403, 'This page is only available to Regional Managers, Coordinators, Partners, Agents, Enterprise Partners, and Business Agents.');
        }
    }

    /**
     * Shared renderer for a device's dedicated "Request a SIM" page.
     */
    private function renderRequestPage(string $slug)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $meta = self::DEVICE_PAGES[$slug];
        $providers = ['mtn', 'airtel', 'glo', '9mobile'];

        $uplinePartner = null;
        $unitPrice = 0.00;

        if (in_array($user->role, SimAccess::MINE_ROLES, true)) {
            $uplinePartner = $this->resolveUpline($user);

            if (!$uplinePartner) {
                $simService = \App\Models\Service::where('name', 'simcard')->first();
                $serviceField = $simService
                    ? \App\Models\ServiceField::where('service_id', $simService->id)->where('field_name', $meta['category'])->first()
                    : null;
                $unitPrice = $serviceField ? $serviceField->priceForUser($user) : 0.00;
            }
        }

        return view('smartsimcard.request', [
            'user' => $user,
            'device' => $meta,
            'slug' => $slug,
            'category' => $meta['category'],
            'providers' => $providers,
            'uplinePartner' => $uplinePartner,
            'unitPrice' => $unitPrice,
            'maxQuantity' => \App\Support\SimRequestLimits::maxFor($user),
        ]);
    }

    public function posRequestForm()
    {
        return $this->renderRequestPage('pos');
    }

    public function cctvRequestForm()
    {
        return $this->renderRequestPage('cctv');
    }

    public function routerRequestForm()
    {
        return $this->renderRequestPage('router');
    }

    /**
     * The user's SIM portfolio, split into three tabs: Activated, sitting
     * with the user right now (Assigned to Me), and further down their
     * downline (Assigned to Others — some of which may be swap-eligible).
     */
    public function inventory(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }
        $this->authorizeCatalogAccess();

        $categories = $this->resolveCategories($user);
        $providers = ['mtn', 'airtel', 'glo', '9mobile'];

        $baseQuery = function () use ($request, $user) {
            $query = $this->scopeToUserPortfolio(Sim::query(), $user);
            if ($request->filled('category')) {
                $query->where('category', $request->string('category'));
            }
            if ($request->filled('provider')) {
                $query->where('provider', $request->string('provider'));
            }
            return $query;
        };

        $activatedSims = $baseQuery()
            ->whereIn('status', [SimStatus::ACTIVATED, SimStatus::DEACTIVATED, SimStatus::SUSPENDED])
            ->orderBy('category')->orderBy('provider')
            ->paginate(15, ['*'], 'activated_page')->withQueryString();

        $assignedToMeQuery = $baseQuery();
        $this->applyOwnTierScope($assignedToMeQuery, $user);
        $assignedToMeSims = $assignedToMeQuery
            ->orderBy('category')->orderBy('provider')
            ->paginate(15, ['*'], 'mine_page')->withQueryString();

        $assignedToOthersQuery = $baseQuery();
        $this->applyOthersScope($assignedToOthersQuery, $user);
        $assignedToOthersSims = $assignedToOthersQuery
            ->with(['user', 'partner', 'coordinator'])
            ->orderBy('category')->orderBy('provider')
            ->paginate(15, ['*'], 'others_page')->withQueryString();

        // Flag which "Assigned to Others" rows the user can swap, and resolve
        // who currently holds each one for the "Held By" column — derived
        // purely from the SIM's own state, so it's correct regardless of
        // which tier is viewing (a Coordinator's "Others" tab can show rows
        // still held by a Partner *and* rows that Partner has already
        // handed to their own downline user).
        $assignedToOthersSims->getCollection()->transform(function (Sim $sim) use ($user) {
            $sim->swap_eligible = (bool) $this->resolveSwapHolder($sim, $user);
            $sim->current_holder = match (true) {
                $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR => $sim->coordinator,
                // At ASSIGNED_TO_PARTNER, user_id === partner_id means it's
                // still self-held by the partner; anything else means it's
                // been handed to that downline personal user.
                $sim->status === SimStatus::ASSIGNED_TO_PARTNER && $sim->user_id !== $sim->partner_id => $sim->user,
                $sim->status === SimStatus::ASSIGNED_TO_PARTNER => $sim->partner,
                default => null,
            };

            return $sim;
        });

        // Quick counts per category, for the summary cards — scoped to this user's own SIMs.
        $stockCounts = $this->scopeToUserPortfolio(Sim::query(), $user)
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        // Who this user can bulk-assign/swap held SIMs down to — empty for
        // agents, who sit at the bottom of the chain and only self-activate.
        $nextRole = RoleHierarchy::nextRole($user->role);
        $downlineUsers = $nextRole
            ? $user->referrals()->where('role', $nextRole)->where('status', 'active')->orderBy('first_name')->get()
            : collect();

        return view('smartsimcard.inventory', compact(
            'user', 'categories', 'providers', 'stockCounts', 'downlineUsers',
            'activatedSims', 'assignedToMeSims', 'assignedToOthersSims'
        ));
    }

    /**
     * Constrain $query to SIMs currently held AT $user's own tier (not yet
     * cascaded further, not activated).
     */
    private function applyOwnTierScope($query, User $user): void
    {
        match ($user->role) {
            'regional_manager' => $query->where('regional_manager_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_RM),
            'coordinator' => $query->where('coordinator_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_COORDINATOR),
            // A partner's own held SIMs share status+partner_id with SIMs
            // they've already handed to a downline user — both adminAssign()
            // and assignDown() set user_id to the partner's OWN id when it's
            // still with them (never left null), overwriting it with the
            // downline user's id only once handed further down. So
            // user_id = partner_id is what "still with the partner" means.
            'partner' => $query->where('partner_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_PARTNER)->whereColumn('user_id', 'partner_id'),
            'business' => $query->where('business_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS),
            'business_agent' => $query->where('business_agent_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS_AGENT)->whereColumn('user_id', 'business_agent_id'),
            default => $query->where('user_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_PARTNER), // agent
        };
    }

    /**
     * Constrain $query to SIMs cascaded further down $user's downline than
     * their own tier, not yet activated.
     */
    private function applyOthersScope($query, User $user): void
    {
        match ($user->role) {
            'regional_manager' => $query->where('regional_manager_id', $user->id)
                ->whereIn('status', [SimStatus::ASSIGNED_TO_COORDINATOR, SimStatus::ASSIGNED_TO_PARTNER]),
            'coordinator' => $query->where('coordinator_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_PARTNER),
            'partner' => $query->where('partner_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_PARTNER)
                ->whereColumn('user_id', '!=', 'partner_id'),
            'business' => $query->where('business_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS_AGENT),
            'business_agent' => $query->where('business_agent_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS_AGENT)
                ->whereColumn('user_id', '!=', 'business_agent_id'),
            default => $query->whereRaw('1 = 0'), // agent has no downline
        };
    }

    /**
     * Whether $sim is swap-eligible for $user at their direct next tier,
     * and if so, who currently holds it and at what tier. Returns null if
     * not eligible. Single source of truth, used both to flag rows on the
     * Inventory page and to re-validate requestSwap() submissions.
     */
    private function resolveSwapHolder(Sim $sim, User $user): ?array
    {
        return match (true) {
            $user->role === 'regional_manager'
                && $sim->regional_manager_id === $user->id
                && $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR
                => ['holder_id' => $sim->coordinator_id, 'holder_role' => 'coordinator'],

            $user->role === 'coordinator'
                && $sim->coordinator_id === $user->id
                && $sim->status === SimStatus::ASSIGNED_TO_PARTNER
                && $sim->user_id === $sim->partner_id
                => ['holder_id' => $sim->partner_id, 'holder_role' => 'partner'],

            $user->role === 'partner'
                && $sim->partner_id === $user->id
                && $sim->status === SimStatus::ASSIGNED_TO_PARTNER
                && $sim->user_id !== $sim->partner_id
                => ['holder_id' => $sim->user_id, 'holder_role' => 'personal'],

            $user->role === 'business'
                && $sim->business_id === $user->id
                && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT
                && $sim->user_id === $sim->business_agent_id
                => ['holder_id' => $sim->business_agent_id, 'holder_role' => 'business_agent'],

            $user->role === 'business_agent'
                && $sim->business_agent_id === $user->id
                && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT
                && $sim->user_id !== $sim->business_agent_id
                => ['holder_id' => $sim->user_id, 'holder_role' => 'personal'],

            default => null,
        };
    }

    /**
     * User requests that a SIM currently held by one of their direct
     * downline be moved to a different downline peer at that same tier.
     * The actual move only happens once an admin approves it.
     */
    public function requestSwap(Request $request)
    {
        $request->validate([
            'sim_id' => 'required|exists:sims,id',
            'to_holder_id' => 'required|exists:users,id',
        ]);

        $requester = Auth::user();
        $sim = Sim::find($request->sim_id);

        $context = $this->resolveSwapHolder($sim, $requester);
        if (!$context) {
            return back()->with('error', 'This SIM is not eligible for a swap.');
        }

        $toHolder = User::find($request->to_holder_id);

        if ((int) $toHolder->id === (int) $context['holder_id']) {
            return back()->with('error', 'Please pick a different downline account to swap to.');
        }

        if ($toHolder->role !== $context['holder_role'] || $toHolder->referred_by !== $requester->id || $toHolder->status !== 'active') {
            return back()->with('error', 'You can only swap to another active account in your own downline at that tier.');
        }

        if (SimSwapRequest::where('sim_id', $sim->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'A swap request for this SIM is already pending admin approval.');
        }

        SimSwapRequest::create([
            'sim_id' => $sim->id,
            'requested_by' => $requester->id,
            'from_holder_id' => $context['holder_id'],
            'to_holder_id' => $toHolder->id,
            'holder_role' => $context['holder_role'],
            'status' => 'pending',
        ]);

        return back()->with('success', "Swap request for {$sim->number} submitted for review by our team.");
    }

    /**
     * Scope a SIM query to only the rows tied to this user via their role's
     * FK column — their full portfolio, at any status, since earlier tiers'
     * FK columns are never cleared as a SIM cascades further down the chain.
     */
    private function scopeToUserPortfolio($query, User $user)
    {
        return match ($user->role) {
            'regional_manager' => $query->where('regional_manager_id', $user->id),
            'coordinator' => $query->where('coordinator_id', $user->id),
            'partner' => $query->where('partner_id', $user->id),
            'business' => $query->where('business_id', $user->id),
            'business_agent' => $query->where('business_agent_id', $user->id),
            default => $query->where('user_id', $user->id), // agent
        };
    }

    /**
     * "My SIM" — the user's own registered SIM cards and submitted requests.
     */
    public function mine(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }
        if (!SimAccess::canViewMine($user)) {
            abort(403, 'My SIM is only available to Personal accounts.');
        }

        $sims = Sim::where('user_id', $user->id)->latest()->paginate(10, ['*'], 'sims_page');

        $requests = SimRequest::with('sim')
            ->where('user_id', $user->id)
            ->latest()->paginate(10, ['*'], 'requests_page');

        return view('smartsimcard.mine', compact('user', 'sims', 'requests'));
    }

    /**
     * Get available numbers based on category and provider (AJAX endpoint).
     */
    public function getAvailableNumbers(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'provider' => 'required|string',
        ]);

        $numbers = Sim::where('category', $request->category)
            ->where('provider', $request->provider)
            ->where('status', SimStatus::UNASSIGNED)
            ->orderBy('number')
            ->get(['id', 'number']);

        return response()->json($numbers);
    }

    /**
     * A requester's immediate upline is only usable for self-service SIM
     * fulfillment when it's an active user in one of the correct parent
     * roles for the chain step below them: personal -> partner OR
     * business_agent -> coordinator -> regional_manager, and separately
     * business_agent -> business. Anything else (no referrer, a suspended
     * upline, a referrer from outside the distribution chain — e.g. another
     * personal user via the leaderboard referral system, or a role with no
     * parent tier at all — agent, regional_manager, business) is treated as
     * "unclaimed".
     */
    private function resolveUpline(User $user): ?User
    {
        // A 'personal' requester's referrer might legitimately be a
        // 'partner' OR a 'business_agent' now that there are two parallel
        // chains terminating at 'personal' — so this checks a list of
        // acceptable parent roles for the requester's own role, not a
        // single hardcoded one.
        //
        // 'business' (Enterprise Partner) is deliberately absent here — it
        // no longer resolves an upline for self-service SIM requests at
        // all. It gets its inventory via the eligibility payment flow
        // instead, and doesn't necessarily have a 'partner' upline to
        // request from the way the old generic 'business' role did.
        $allowedParentRoles = match ($user->role) {
            'personal' => ['partner', 'business_agent'],
            'business_agent' => ['business'],
            'partner' => ['coordinator'],
            'coordinator' => ['regional_manager'],
            default => [],
        };

        if (empty($allowedParentRoles)) {
            return null;
        }

        $referrer = $user->referrer;

        return ($referrer && in_array($referrer->role, $allowedParentRoles, true) && $referrer->status === 'active') ? $referrer : null;
    }

    /**
     * User submits a request for N SIM numbers of a category/network — they
     * no longer pick specific numbers; whoever fulfills the request chooses
     * exactly which SIMs to hand out.
     *
     * Personal/Business requesters split two ways: if they have an active
     * partner upline, the request is routed to that partner to fulfill from
     * their own held stock first (free — the admin queue still sees it as a
     * fallback). Otherwise ("unclaimed" — no upline, or an upline that isn't
     * an active partner), the requester pays for the quantity up front,
     * mirroring activateSim()'s charge-then-request pattern below, before
     * the request lands in the admin queue.
     */
    public function requestSim(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'category' => 'required|string',
            'provider' => 'required|string',
            'quantity' => ['required', 'integer', 'min:1', 'max:' . \App\Support\SimRequestLimits::maxFor($user)],
        ]);

        if (!in_array($user->role, SimAccess::MINE_ROLES, true)) {
            $upline = $this->resolveUpline($user);

            try {
                DB::transaction(function () use ($user, $request, $upline) {
                    SimRequest::create([
                        'user_id'      => $user->id,
                        'upline_id'    => $upline?->id,
                        'sim_id'       => null,
                        'number'       => null,
                        'category'     => $request->category,
                        'provider'     => $request->provider,
                        'quantity'     => $request->quantity,
                        'request_type' => 'purchase',
                        'status'       => 'pending',
                        'amount'       => 0.00,
                    ]);
                });

                $message = $upline
                    ? "Your request for {$request->quantity} x {$request->category} has been sent to your upline, {$upline->first_name} {$upline->last_name}, to fulfill from their stock — our team will step in if they can't."
                    : "Your request for {$request->quantity} x {$request->category} has been submitted successfully.";

                return back()->with('success', $message);
            } catch (\Exception $e) {
                Log::error('SIM purchase request failed.', ['user_id' => $user->id, 'exception' => $e->getMessage()]);

                return back()->with('error', $e->getMessage());
            }
        }

        $uplinePartner = $this->resolveUpline($user);

        try {
            if ($uplinePartner) {
                DB::transaction(function () use ($user, $request, $uplinePartner) {
                    SimRequest::create([
                        'user_id'      => $user->id,
                        'upline_id'    => $uplinePartner->id,
                        'sim_id'       => null,
                        'number'       => null,
                        'category'     => $request->category,
                        'provider'     => $request->provider,
                        'quantity'     => $request->quantity,
                        'request_type' => 'purchase',
                        'status'       => 'pending',
                        'amount'       => 0.00,
                    ]);
                });

                return back()->with('success', "Your request for {$request->quantity} x {$request->category} has been sent to your upline, {$uplinePartner->first_name} {$uplinePartner->last_name}, to fulfill from their stock — our team will step in if they can't.");
            }

            // Unclaimed: no active-partner upline to fulfill from, so payment
            // is collected up front, same pattern as activateSim() below.
            $simService = \App\Models\Service::where('name', 'simcard')->first();
            $serviceField = $simService
                ? \App\Models\ServiceField::where('service_id', $simService->id)->where('field_name', $request->category)->first()
                : null;
            $unitPrice = $serviceField ? $serviceField->priceForUser($user) : 0.00;
            $totalAmount = $unitPrice * $request->quantity;

            DB::transaction(function () use ($user, $request, $totalAmount, $simService) {
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet || $wallet->balance < $totalAmount) {
                    throw new \Exception('Insufficient wallet balance! You need ₦' . number_format($totalAmount, 2));
                }

                $oldBalance = $wallet->balance;
                $wallet->decrement('balance', $totalAmount);
                $newBalance = $wallet->balance;

                $ref = 'SIMREQ-' . time() . '-' . rand(1000, 9999);

                \App\Models\Transaction::create([
                    'transaction_ref' => $ref,
                    'user_id'         => $user->id,
                    'amount'          => $totalAmount,
                    'fee'             => 0.00,
                    'net_amount'      => $totalAmount,
                    'description'     => "SIM Card Request: {$request->quantity} x {$request->category} (Network: " . strtoupper($request->provider) . ")",
                    'type'            => 'debit',
                    'status'          => 'completed',
                    'performed_by'    => $user->first_name . ' ' . $user->last_name,
                    'approved_by'     => $user->id,
                ]);

                \App\Models\Report::create([
                    'user_id'      => $user->id,
                    'phone_number' => null,
                    'network'      => $request->provider,
                    'ref'          => $ref,
                    'amount'       => $totalAmount,
                    'status'       => 'completed',
                    'type'         => 'sim_purchase_request',
                    'description'  => "SIM Card Request: {$request->quantity} x {$request->category}",
                    'old_balance'  => $oldBalance,
                    'new_balance'  => $newBalance,
                    'service_id'   => $simService ? $simService->id : null,
                ]);

                SimRequest::create([
                    'user_id'      => $user->id,
                    'upline_id'    => null,
                    'sim_id'       => null,
                    'number'       => null,
                    'category'     => $request->category,
                    'provider'     => $request->provider,
                    'quantity'     => $request->quantity,
                    'request_type' => 'purchase',
                    'status'       => 'pending',
                    'amount'       => $totalAmount,
                ]);
            });

            return back()->with('success', "Your request for {$request->quantity} x {$request->category} has been submitted and ₦" . number_format($totalAmount, 2) . " has been charged from your wallet. Our team will assign your SIM number(s) shortly.");
        } catch (\Exception $e) {
            Log::error('Paid SIM purchase request failed.', ['user_id' => $user->id, 'exception' => $e->getMessage()]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * User submits request to activate their assigned SIM.
     */
    /**
     * This action was always exclusively Agent's self-activation flow — now
     * that Agent no longer distributes/holds SIMs at all (it's a
     * discount-only tier, see AgentUpgradeController), no role reaches this
     * successfully anymore.
     */
    public function activateSim(Request $request)
    {
        abort(403, 'Self-activation is no longer available. Regional Managers and Coordinators distribute SIMs down the chain; Partners activate for their downline Users.');
    }

    /**
     * A Regional Manager / Coordinator / Partner delegates a SIM they currently
     * hold down to their direct subordinate (Regional Manager -> Coordinator,
     * Coordinator -> Partner, Partner -> User).
     */
    public function partnerAssignSim(Request $request)
    {
        $request->validate([
            'sim_id'  => 'required|exists:sims,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $from = Auth::user();
        $sim = Sim::find($request->sim_id);
        $targetUser = User::find($request->user_id);

        if ($targetUser->role === 'personal') {
            return back()->with('error', 'Personal users cannot be assigned SIMs — activate the SIM for them instead.');
        }

        // Hierarchy path: Regional Manager -> Coordinator, Coordinator -> Partner, Partner -> User.
        try {
            app(SimAssignmentService::class)->assignDown($sim, $from, $targetUser);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "SIM number {$sim->number} successfully assigned to {$targetUser->first_name} {$targetUser->last_name}.");
    }

    /**
     * Bulk variant of the hierarchy path above — lets a Regional Manager /
     * Coordinator / Partner multi-select held SIMs on the Inventory table
     * and delegate them all to one downline user in a single action.
     */
    public function bulkAssignSim(Request $request)
    {
        $request->validate([
            'sim_ids'   => 'required|array|max:1000',
            'sim_ids.*' => 'exists:sims,id',
            'user_id'   => 'required|exists:users,id',
        ]);

        $from = Auth::user();
        $targetUser = User::find($request->user_id);
        if ($targetUser->role === 'personal') {
            return back()->with('error', 'Personal users cannot be assigned SIMs — activate the SIM for them instead.');
        }
        $sims = Sim::whereIn('id', $request->sim_ids)->get();
        $service = app(SimAssignmentService::class);

        $assignedCount = 0;
        $skipped = 0;

        DB::transaction(function () use ($sims, $from, $targetUser, $service, &$assignedCount, &$skipped) {
            foreach ($sims as $sim) {
                try {
                    $service->assignDown($sim, $from, $targetUser);
                    $assignedCount++;
                } catch (\RuntimeException $e) {
                    $skipped++;
                }
            }
        });

        $message = "Successfully assigned {$assignedCount} SIM number(s) to {$targetUser->first_name} {$targetUser->last_name}.";
        if ($skipped > 0) {
            $message .= " {$skipped} SIM(s) were skipped (no longer eligible).";

            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * A Partner (or Business Agent) activates a SIM they hold on behalf of a
     * User in their downline. The activation fee is debited from that User's
     * wallet (not the actor's), then the SIM is marked ACTIVATED — which
     * fires the commission payout up the whole chain (partner/coordinator/
     * regional manager, or business_agent/business for the Enterprise
     * Partner chain). Regional Managers, Coordinators, and Enterprise
     * Partners never activate — they only distribute, via
     * partnerAssignSim()/bulk-assign above.
     */
    public function activateForDownlineUser(Request $request)
    {
        $request->validate([
            'sim_id'  => 'required|exists:sims,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $partner = Auth::user();
        if (!in_array($partner->role, ['partner', 'business_agent'], true)) {
            return back()->with('error', 'Only partners or business agents can activate a SIM for a downline user.');
        }

        $targetUser = User::find($request->user_id);
        $holderColumn = $partner->role === 'business_agent' ? 'business_agent_id' : 'partner_id';

        if ($targetUser->role !== 'personal' || $targetUser->referred_by !== $partner->id) {
            return back()->with('error', 'You can only activate a SIM for a User in your downline.');
        }

        try {
            $activatedSim = DB::transaction(function () use ($request, $partner, $targetUser, $holderColumn) {
                // Lock the SIM row before trusting anything about its current
                // state — without this, two concurrent requests for the same
                // sim_id (double-submit, a retried request) each work off
                // their own stale, unlocked copy: the second one can sail
                // past SimAssignmentService::activate()'s "already activated"
                // guard (checked against the in-memory object, not a fresh
                // read) and re-activate, double-debiting the target wallet
                // and double-paying commissions. Locking + re-validating here
                // mirrors fulfillDownlineRequest()/SimPlanController::approveRequest().
                $sim = Sim::where('id', $request->sim_id)->lockForUpdate()->first();

                if (!$sim || $sim->{$holderColumn} !== $partner->id) {
                    throw new \Exception('You can only activate numbers currently allocated to you.');
                }

                if ($sim->status === SimStatus::ACTIVATED) {
                    throw new \Exception('This SIM card is already active.');
                }

                $this->debitAndActivate($sim, $partner, $targetUser);

                return $sim;
            });

            return back()->with('success', "Activated {$activatedSim->number} for {$targetUser->first_name} {$targetUser->last_name}.");
        } catch (\Exception $e) {
            Log::error('SIM activation for downline user failed.', [
                'sim_id' => $request->sim_id,
                'target_user_id' => $targetUser->id,
                'partner_id' => $partner->id,
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Debit the personal user's wallet the activation price and activate the
     * SIM for them (commission chain fires). Shared by direct activation and
     * by fulfilling a personal user's SIM request — a personal user never
     * receives an assignment, only an activation. Call inside a transaction
     * with $sim already locked and verified as held by $partner.
     */
    private function debitAndActivate(Sim $sim, User $partner, User $targetUser): void
    {
        $simService = \App\Models\Service::where('name', 'simcard')->first();
        $serviceField = $simService
            ? \App\Models\ServiceField::where('service_id', $simService->id)->where('field_name', $sim->category)->first()
            : null;
        $payableAmount = $serviceField ? $serviceField->priceForUser($targetUser) : 0.00;

        $wallet = \App\Models\Wallet::where('user_id', $targetUser->id)->lockForUpdate()->first();
        if (!$wallet || $wallet->balance < $payableAmount) {
            throw new \Exception("Insufficient wallet balance for {$targetUser->first_name}. They need ₦" . number_format($payableAmount, 2) . ' in their wallet.');
        }

        $oldBalance = $wallet->balance;
        $wallet->decrement('balance', $payableAmount);
        $newBalance = $wallet->balance;

        $ref = 'ACT-' . time() . '-' . rand(1000, 9999);

        \App\Models\Transaction::create([
            'transaction_ref' => $ref,
            'user_id'         => $targetUser->id,
            'amount'          => $payableAmount,
            'fee'             => 0.00,
            'net_amount'      => $payableAmount,
            'description'     => "SIM Card Activation: {$sim->number} (Category: {$sim->category}, Network: " . strtoupper($sim->provider) . '), activated by ' . str_replace('_', ' ', $partner->role) . " {$partner->first_name} {$partner->last_name}",
            'type'            => 'debit',
            'status'          => 'completed',
            'performed_by'    => $partner->first_name . ' ' . $partner->last_name,
        ]);

        \App\Models\Report::create([
            'user_id'      => $targetUser->id,
            'phone_number' => $sim->number,
            'network'      => $sim->provider,
            'ref'          => $ref,
            'amount'       => $payableAmount,
            'status'       => 'completed',
            'type'         => 'sim_activation',
            'description'  => "SIM Card Activation: {$sim->number} (Category: {$sim->category}), activated by " . str_replace('_', ' ', $partner->role),
            'old_balance'  => $oldBalance,
            'new_balance'  => $newBalance,
            'service_id'   => $simService ? $simService->id : null,
        ]);

        $sim->update(['user_id' => $targetUser->id]);

        app(SimAssignmentService::class)->activate($sim, $partner);
    }

    /**
     * Restrict the downline-requests actions below to the roles that hold
     * stock a direct downline's SimRequest.upline_id can point to (see
     * resolveUpline()): Partner (-> personal), Coordinator (-> partner),
     * Regional Manager (-> coordinator), Business (-> business_agent),
     * Business Agent (-> personal).
     */
    private function authorizeUplineFulfiller(): User
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['partner', 'coordinator', 'regional_manager', 'business', 'business_agent'], true)) {
            abort(403, 'This page is only available to Partners, Coordinators, Regional Managers, Enterprise Partners, and Business Agents.');
        }

        return $user;
    }

    /**
     * The FK/status pair each upline role's own held stock lives under,
     * scoped to a category/provider — mirrors the same role match
     * SimAssignmentService::assignDown() uses internally to check whether
     * the "from" user actually holds a given SIM.
     */
    private function heldStockQuery(User $user, string $category, string $provider)
    {
        return (match ($user->role) {
            'regional_manager' => Sim::where('regional_manager_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_RM),
            'coordinator'       => Sim::where('coordinator_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_COORDINATOR),
            'business'          => Sim::where('business_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS),
            'business_agent'    => Sim::where('business_agent_id', $user->id)->where('user_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_BUSINESS_AGENT),
            default             => Sim::where('partner_id', $user->id)->where('user_id', $user->id)->where('status', SimStatus::ASSIGNED_TO_PARTNER),
        })->where('category', $category)->where('provider', $provider);
    }

    /**
     * Whether $user currently holds $sim at their own tier (Regional
     * Manager/Coordinator/Partner/Business/Business Agent), the same
     * condition heldStockQuery() checks in bulk, evaluated here against an
     * already-loaded Sim.
     */
    private function holdsSim(User $user, Sim $sim): bool
    {
        return match ($user->role) {
            'regional_manager' => $sim->regional_manager_id === $user->id && $sim->status === SimStatus::ASSIGNED_TO_RM,
            'coordinator'       => $sim->coordinator_id === $user->id && $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR,
            'business'          => $sim->business_id === $user->id && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS,
            'business_agent'    => $sim->business_agent_id === $user->id && $sim->user_id === $user->id && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
            default             => $sim->partner_id === $user->id && $sim->user_id === $user->id && $sim->status === SimStatus::ASSIGNED_TO_PARTNER,
        };
    }

    /**
     * Restrict the four bulk-assign/collect actions below to Regional
     * Managers, Coordinators, and Enterprise Partners (business) — the
     * roles with a direct downline delegation cascade (assignDown()/
     * collectUp()) worth a dedicated bulk paste-numbers flow. Partner and
     * Business Agent keep the existing checkbox picker on Inventory
     * unchanged (they activate one at a time, not in bulk).
     */
    private function authorizeBulkDelegator(): User
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['regional_manager', 'coordinator', 'business'], true)) {
            abort(403, 'This page is only available to Regional Managers, Coordinators, and Enterprise Partners.');
        }

        return $user;
    }

    /**
     * Dedicated page: paste a large batch of SIM numbers currently held by
     * the actor, then search and pick a direct downline user to hand them
     * all to via assignDown() — mirrors Admin\SimPlanController's Bulk
     * Assign page, scoped to the actor's own held stock instead of the
     * global unassigned pool.
     */
    public function bulkAssignPage()
    {
        $this->authorizeBulkDelegator();

        return view('smartsimcard.bulk-assign');
    }

    /**
     * Dedicated page: paste a large batch of SIM numbers currently sitting
     * one tier below the actor, then search and pick which direct downline
     * user to reclaim them from via collectUp().
     */
    public function bulkCollectPage()
    {
        $this->authorizeBulkDelegator();

        return view('smartsimcard.bulk-collect');
    }

    /**
     * AJAX: resolve pasted SIM numbers for the RM/Coordinator Bulk Assign
     * page — must be held by the actor right now, in any category/provider
     * (no parent request to match against here).
     */
    public function resolveBulkAssignNumbers(Request $request)
    {
        $user = $this->authorizeBulkDelegator();

        $request->validate(['numbers' => 'required|string']);

        $rawNumbers = preg_split('/[\r\n,]+/', $request->numbers);
        $resolved = [];
        $errors = [];
        $seen = [];

        foreach ($rawNumbers as $rawNumber) {
            $number = trim($rawNumber);
            if ($number === '' || isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;

            if (strlen($number) !== 11 || !ctype_digit($number)) {
                $errors[] = "{$number} — Invalid format (must be 11 digits)";
                continue;
            }

            $sim = Sim::where('number', $number)->first();

            if (!$sim) {
                $errors[] = "{$number} — Not found";
                continue;
            }

            if (!$this->holdsSim($user, $sim)) {
                $errors[] = "{$number} — Not in your held stock";
                continue;
            }

            $resolved[] = ['id' => $sim->id, 'number' => $sim->number];
        }

        return response()->json(['resolved' => $resolved, 'errors' => $errors]);
    }

    /**
     * AJAX: resolve pasted SIM numbers for the RM/Coordinator Bulk Collect
     * page — must currently sit one tier below the actor AND trace back to
     * this specific actor (the same condition collectUp() re-verifies once
     * a specific downline user is picked, which hasn't happened yet here).
     */
    public function resolveBulkCollectNumbers(Request $request)
    {
        $user = $this->authorizeBulkDelegator();
        $expectedHolderRole = RoleHierarchy::nextRole($user->role);

        $request->validate(['numbers' => 'required|string']);

        $rawNumbers = preg_split('/[\r\n,]+/', $request->numbers);
        $resolved = [];
        $errors = [];
        $seen = [];

        foreach ($rawNumbers as $rawNumber) {
            $number = trim($rawNumber);
            if ($number === '' || isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;

            if (strlen($number) !== 11 || !ctype_digit($number)) {
                $errors[] = "{$number} — Invalid format (must be 11 digits)";
                continue;
            }

            $sim = Sim::where('number', $number)->first();

            if (!$sim) {
                $errors[] = "{$number} — Not found";
                continue;
            }

            if ($sim->status === SimStatus::ACTIVATED) {
                $errors[] = "{$number} — Currently activated, cannot be collected";
                continue;
            }

            $tracesBackToActor = match ($expectedHolderRole) {
                'coordinator' => $sim->coordinator_id !== null && $sim->status === SimStatus::ASSIGNED_TO_COORDINATOR && $sim->regional_manager_id === $user->id,
                'partner'     => $sim->partner_id !== null && $sim->user_id === $sim->partner_id && $sim->status === SimStatus::ASSIGNED_TO_PARTNER && $sim->coordinator_id === $user->id,
                'business_agent' => $sim->business_agent_id !== null && $sim->user_id === $sim->business_agent_id && $sim->status === SimStatus::ASSIGNED_TO_BUSINESS_AGENT && $sim->business_id === $user->id,
                default       => false,
            };

            if (!$tracesBackToActor) {
                $errors[] = "{$number} — Not currently down your line";
                continue;
            }

            $resolved[] = ['id' => $sim->id, 'number' => $sim->number];
        }

        return response()->json(['resolved' => $resolved, 'errors' => $errors]);
    }

    /**
     * AJAX: search this actor's own direct downline by name/email/phone,
     * for the Bulk Assign/Collect pages' user picker — scoped to their own
     * referrals at the correct next tier, not a global user search (unlike
     * Admin\SimPlanController::searchUsers()).
     */
    public function searchUsers(Request $request)
    {
        $user = $this->authorizeBulkDelegator();
        $nextRole = RoleHierarchy::nextRole($user->role);

        $request->validate(['q' => 'required|string|min:2']);

        $query = $request->q;

        $downlineUsers = $user->referrals()
            ->where('role', $nextRole)
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('middle_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->orderBy('first_name')
            ->limit(10)
            ->get()
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role'  => $u->role,
            ]);

        return response()->json($downlineUsers);
    }

    /**
     * Bulk Collect execute: reclaim pasted SIMs from the selected direct
     * downline user back into the actor's own held stock via collectUp().
     * Skips (rather than aborts on) any SIM that's raced away or doesn't
     * actually belong to the selected user, matching bulkAssignSim()'s own
     * catch-and-skip convention.
     */
    public function bulkCollect(Request $request)
    {
        $user = $this->authorizeBulkDelegator();
        $nextRole = RoleHierarchy::nextRole($user->role);

        $request->validate([
            'sim_ids'   => 'required|array|max:1000',
            'sim_ids.*' => 'exists:sims,id',
            'user_id'   => 'required|exists:users,id',
        ]);

        $currentHolder = User::find($request->user_id);
        if (!$currentHolder || $currentHolder->role !== $nextRole) {
            return back()->with('error', 'You can only collect from a ' . str_replace('_', ' ', (string) $nextRole) . '.');
        }

        $sims = Sim::whereIn('id', $request->sim_ids)->get();
        $service = app(SimAssignmentService::class);

        $collectedCount = 0;
        $skipped = 0;

        DB::transaction(function () use ($sims, $user, $currentHolder, $service, &$collectedCount, &$skipped) {
            foreach ($sims as $sim) {
                try {
                    $service->collectUp($sim, $user, $currentHolder);
                    $collectedCount++;
                } catch (\RuntimeException $e) {
                    $skipped++;
                }
            }
        });

        $message = "Successfully collected {$collectedCount} SIM number(s) from {$currentHolder->first_name} {$currentHolder->last_name}.";
        if ($skipped > 0) {
            $message .= " {$skipped} SIM(s) were skipped (no longer eligible).";

            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Requests from this upline's own direct downline (users who named them
     * as upline at submission time), still pending. Shown alongside how
     * many matching SIMs the upline currently holds so they immediately
     * know whether they can fulfill it themselves.
     */
    public function downlineRequests()
    {
        $user = $this->authorizeUplineFulfiller();

        $requests = SimRequest::with('user')
            ->where('upline_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(function (SimRequest $req) use ($user) {
                $req->held_count = $this->heldStockQuery($user, $req->category, $req->provider)->count();

                return $req;
            });

        return view('smartsimcard.downline-requests', ['requests' => $requests]);
    }

    /**
     * AJAX source for the "pick which SIM(s) to hand over" picker on the
     * Downline Requests page — mirrors Admin\SimPlanController::availableSims(),
     * scoped to the upline's own held stock instead of the central pool.
     */
    public function downlineRequestAvailableSims(Request $request)
    {
        $user = $this->authorizeUplineFulfiller();

        $request->validate([
            'category' => 'required|string',
            'provider' => 'required|string',
        ]);

        $sims = $this->heldStockQuery($user, $request->category, $request->provider)
            ->orderBy('number')
            ->get(['id', 'number']);

        return response()->json($sims);
    }

    /**
     * AJAX: resolve pasted SIM numbers (comma/newline separated) into
     * validated sim_ids for a downline request, mirroring
     * Admin\SimPlanController::resolveRequestNumbers() but scoped to the
     * upline's own held stock rather than the global unassigned pool.
     */
    public function resolveDownlineRequestNumbers(Request $request, SimRequest $simRequest)
    {
        $user = $this->authorizeUplineFulfiller();

        if ((int) $simRequest->upline_id !== $user->id || $simRequest->status !== 'pending') {
            return response()->json(['resolved' => [], 'errors' => ['This request is not open for SIM selection.']]);
        }

        $request->validate(['numbers' => 'required|string']);

        $rawNumbers = preg_split('/[\r\n,]+/', $request->numbers);
        $resolved = [];
        $errors = [];
        $seen = [];

        foreach ($rawNumbers as $rawNumber) {
            $number = trim($rawNumber);
            if ($number === '') {
                continue;
            }

            if (isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;

            if (strlen($number) !== 11 || !ctype_digit($number)) {
                $errors[] = "{$number} — Invalid format (must be 11 digits)";
                continue;
            }

            $sim = Sim::where('number', $number)->first();

            if (!$sim) {
                $errors[] = "{$number} — Not found";
                continue;
            }

            if ($sim->category !== $simRequest->category || $sim->provider !== $simRequest->provider) {
                $errors[] = "{$number} — Wrong category/provider for this request";
                continue;
            }

            if (!$this->holdsSim($user, $sim)) {
                $errors[] = "{$number} — Not in your held stock";
                continue;
            }

            $resolved[] = ['id' => $sim->id, 'number' => $sim->number];
        }

        return response()->json(['resolved' => $resolved, 'errors' => $errors]);
    }

    /**
     * Upline fulfills a downline request directly from their own held
     * stock via the same assignDown() delegation they'd otherwise do
     * manually from Inventory/the device page.
     */
    public function fulfillDownlineRequest(Request $request, SimRequest $simRequest)
    {
        $user = $this->authorizeUplineFulfiller();

        if ((int) $simRequest->upline_id !== $user->id) {
            abort(403, 'This request was not routed to you.');
        }

        if ($simRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $request->validate([
            'sim_ids'   => 'required|array',
            'sim_ids.*' => 'exists:sims,id',
        ]);

        $processedCount = 0;
        $fulfilledRole = User::where('id', $simRequest->user_id)->value('role');

        try {
            DB::transaction(function () use ($simRequest, $user, $request, &$processedCount) {
                $lockedRequest = SimRequest::where('id', $simRequest->id)->lockForUpdate()->first();
                if (!$lockedRequest || $lockedRequest->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $requester = User::where('id', $lockedRequest->user_id)->lockForUpdate()->first();
                if (!$requester) {
                    throw new \Exception('Requester user not found.');
                }

                $originalQuantity = $lockedRequest->quantity;
                // Never process more than was actually requested, even if
                // more sim_ids were submitted than the request called for.
                $simIds = array_slice($request->sim_ids, 0, $originalQuantity);
                $sims = Sim::whereIn('id', $simIds)->lockForUpdate()->get();

                $service = app(SimAssignmentService::class);
                foreach ($sims as $sim) {
                    if (!$this->holdsSim($user, $sim)
                        || $sim->category !== $lockedRequest->category || $sim->provider !== $lockedRequest->provider) {
                        // Raced away since the number was resolved client-side —
                        // skip it rather than aborting the whole batch.
                        continue;
                    }
                    if ($requester->role === 'personal') {
                        // Personal users never hold inventory: fulfilling their
                        // request activates the SIM (wallet debit + commissions).
                        $this->debitAndActivate($sim, $user, $requester);
                    } else {
                        $service->assignDown($sim, $user, $requester);
                    }
                    $processedCount++;
                }

                if ($processedCount === 0) {
                    throw new \Exception('None of the selected SIMs are still yours to assign. Please try again.');
                }

                $lockedRequest->update([
                    'status'      => 'approved',
                    'quantity'    => $processedCount,
                    'admin_notes' => $processedCount < $originalQuantity
                        ? "Fulfilled with {$processedCount} of {$originalQuantity} requested SIM(s) by upline {$user->first_name} {$user->last_name} ({$user->role})."
                        : "Fulfilled by upline {$user->first_name} {$user->last_name} ({$user->role}).",
                ]);
            });

            return back()->with('success', "Request fulfilled: {$processedCount} SIM(s) " . ($fulfilledRole === 'personal' ? 'activated.' : 'assigned.'));
        } catch (\Exception $e) {
            Log::error('Downline SIM request fulfillment failed.', ['sim_request_id' => $simRequest->id, 'upline_id' => $user->id, 'exception' => $e->getMessage()]);

            return back()->with('error', $e->getMessage());
        }
    }

}
