<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Sim;
use App\Models\User;
use App\Services\SimAssignmentService;
use App\Support\SimStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * SIM inventory management for the support desk: browse the pool, collect
 * a SIM back from whoever holds it, then either hand it straight to a new
 * holder within the exact same upline chain (SimAssignmentService::
 * swapHolder() — every other FK on the SIM stays untouched, so the new
 * holder is "in the same hierarchy" simply by virtue of nothing else
 * moving) or unassign it completely (SimAssignmentService::unassign() —
 * every holder FK cleared, back to the general pool for anyone to be
 * assigned next). An already-UNASSIGNED SIM can be assigned directly to
 * any active user (SimAssignmentService::adminAssign()), same as the
 * super_admin console's SIM Plans page.
 *
 * Deliberately excludes ACTIVATED SIMs from collect/unassign — same rule
 * Admin\SimPlanController::resolveBulkCollectNumbers() already enforces —
 * a live customer's activated line isn't something this console reclaims.
 */
class SimInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sim::with(['user', 'partner', 'coordinator', 'regionalManager', 'business', 'businessAgent']);

        if ($request->filled('search')) {
            $query->where('number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        $sims = $query->orderBy('updated_at', 'desc')->paginate(20)->withQueryString();

        $totalUploaded = Sim::count();
        $totalAvailable = Sim::where('status', SimStatus::UNASSIGNED)->count();
        $totalAssigned = Sim::whereIn('status', [
            SimStatus::ASSIGNED_TO_RM,
            SimStatus::ASSIGNED_TO_COORDINATOR,
            SimStatus::ASSIGNED_TO_PARTNER,
            SimStatus::ASSIGNED_TO_BUSINESS,
            SimStatus::ASSIGNED_TO_BUSINESS_AGENT,
        ])->count();
        $totalActivated = Sim::where('status', SimStatus::ACTIVATED)->count();

        $categories = Sim::query()->select('category')->distinct()->whereNotNull('category')->orderBy('category')->pluck('category');
        $providers = Sim::query()->select('provider')->distinct()->whereNotNull('provider')->orderBy('provider')->pluck('provider');

        return view('support-desk.sim-inventory.index', compact(
            'sims', 'totalUploaded', 'totalAvailable', 'totalAssigned', 'totalActivated', 'categories', 'providers'
        ));
    }

    public function show(Sim $sim): View
    {
        $sim->load([
            'user', 'partner', 'coordinator', 'regionalManager', 'business', 'businessAgent',
            'history' => fn ($q) => $q->with('actor')->latest(),
        ]);

        $swapTargetRole = app(SimAssignmentService::class)->swapTargetRole($sim);

        return view('support-desk.sim-inventory.show', compact('sim', 'swapTargetRole'));
    }

    /** AJAX: active users matching a name/email/phone query, optionally scoped to one role. */
    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2', 'role' => 'nullable|string']);

        $query = $request->q;

        $users = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
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
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
            ]);

        return response()->json($users);
    }

    /** Directly assign an UNASSIGNED SIM to any active, non-admin user — same primitive the super_admin SIM Plans page uses. */
    public function assign(Request $request, Sim $sim): RedirectResponse
    {
        $validated = $request->validate(['user_id' => 'required|exists:users,id']);

        if ($sim->status !== SimStatus::UNASSIGNED) {
            return back()->with('error', 'Only an unassigned SIM can be assigned this way.');
        }

        $target = User::find($validated['user_id']);
        if ($target->role === 'super_admin' || $target->status !== 'active') {
            return back()->with('error', 'You can only assign to an active, non-admin account.');
        }

        app(SimAssignmentService::class)->adminAssign($sim, $target, $request->user());

        return back()->with('success', "SIM {$sim->number} has been assigned to {$target->name} ({$target->role}).");
    }

    /** Collect the SIM from its current holder and hand it straight to a new holder in the same hierarchy. */
    public function reassign(Request $request, Sim $sim): RedirectResponse
    {
        $validated = $request->validate(['user_id' => 'required|exists:users,id']);

        $service = app(SimAssignmentService::class);
        $expectedRole = $service->swapTargetRole($sim);

        if (!$expectedRole) {
            return back()->with('error', 'This SIM is not in a state that can be reassigned within its hierarchy — unassign it completely instead.');
        }

        $target = User::find($validated['user_id']);
        if ($target->role !== $expectedRole || $target->status !== 'active') {
            return back()->with('error', "You can only reassign this SIM to an active {$expectedRole} user.");
        }

        if ($target->id === $sim->user_id) {
            return back()->with('error', 'This SIM is already with that user.');
        }

        try {
            $service->swapHolder($sim, $target, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "SIM {$sim->number} has been collected and reassigned to {$target->name} within the same hierarchy.");
    }

    /** Collect the SIM and clear every holder FK, back to the general pool. */
    public function unassign(Request $request, Sim $sim): RedirectResponse
    {
        if ($sim->status === SimStatus::ACTIVATED) {
            return back()->with('error', 'An activated SIM cannot be unassigned from here.');
        }

        if ($sim->status === SimStatus::UNASSIGNED) {
            return back()->with('error', 'This SIM is already unassigned.');
        }

        app(SimAssignmentService::class)->unassign($sim, $request->user());

        return back()->with('success', "SIM {$sim->number} has been unassigned and is now available in the inventory for direct assignment.");
    }

    // -------------------------------------------------------------------------
    // Bulk actions (floating button on the inventory index) — same primitives
    // and eligibility rules as the single-SIM actions above, just applied to
    // a pasted batch of numbers. Mirrors Admin\SimPlanController's bulk-assign
    // / bulk-collect pages exactly.
    // -------------------------------------------------------------------------

    public function bulkAssignPage(): View
    {
        return view('admin.sim-plan.bulk-assign');
    }

    public function bulkCollectPage(): View
    {
        return view('admin.sim-plan.bulk-collect');
    }

    /** AJAX: resolve pasted SIM numbers for Bulk Assign — must exist and be UNASSIGNED. */
    public function resolveBulkAssignNumbers(Request $request): JsonResponse
    {
        $request->validate(['numbers' => 'required|string']);

        [$resolved, $errors] = $this->resolveNumbers($request->numbers, function (Sim $sim) {
            if ($sim->status === SimStatus::ACTIVATED) {
                return "{$sim->number} — Already activated";
            }

            if ($sim->status !== SimStatus::UNASSIGNED) {
                return "{$sim->number} — Already assigned";
            }

            return null;
        });

        return response()->json(['resolved' => $resolved, 'errors' => $errors]);
    }

    /** AJAX: resolve pasted SIM numbers for Bulk Collect — must exist and currently be held by someone. */
    public function resolveBulkCollectNumbers(Request $request): JsonResponse
    {
        $request->validate(['numbers' => 'required|string']);

        [$resolved, $errors] = $this->resolveNumbers($request->numbers, function (Sim $sim) {
            if ($sim->status === SimStatus::ACTIVATED) {
                return "{$sim->number} — Currently activated, cannot be collected";
            }

            if ($sim->status === SimStatus::UNASSIGNED) {
                return "{$sim->number} — Already unassigned, nothing to collect";
            }

            return null;
        });

        return response()->json(['resolved' => $resolved, 'errors' => $errors]);
    }

    /** Bulk Assign execute: assign every resolved, still-UNASSIGNED SIM to the picked user. */
    public function bulkAssign(Request $request): RedirectResponse
    {
        $request->validate([
            'sim_ids' => 'required|array|max:1000',
            'sim_ids.*' => 'exists:sims,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $targetUser = User::find($request->user_id);
        if ($targetUser->role === 'super_admin' || $targetUser->status !== 'active') {
            return back()->with('error', 'You can only assign to an active, non-admin account.');
        }

        $actor = $request->user();
        $service = app(SimAssignmentService::class);
        $sims = Sim::whereIn('id', $request->sim_ids)->get();
        $assignedCount = 0;

        DB::transaction(function () use ($sims, $targetUser, $actor, $service, &$assignedCount) {
            foreach ($sims as $sim) {
                if ($sim->status === SimStatus::UNASSIGNED) {
                    $service->adminAssign($sim, $targetUser, $actor);
                    $assignedCount++;
                }
            }
        });

        return back()->with('success', "Successfully assigned {$assignedCount} SIM number(s) to {$targetUser->first_name} {$targetUser->last_name} ({$targetUser->role}).");
    }

    /** Bulk Collect execute: fully unassign every resolved SIM actually held by the picked user. */
    public function bulkCollect(Request $request): RedirectResponse
    {
        $request->validate([
            'sim_ids' => 'required|array',
            'sim_ids.*' => 'exists:sims,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $targetUser = User::find($request->user_id);
        $actor = $request->user();
        $service = app(SimAssignmentService::class);

        $sims = Sim::whereIn('id', $request->sim_ids)->get();
        $collectedCount = 0;
        $skipped = [];

        DB::transaction(function () use ($sims, $targetUser, $actor, $service, &$collectedCount, &$skipped) {
            foreach ($sims as $sim) {
                $belongsToUser = $sim->user_id === $targetUser->id
                    || $sim->partner_id === $targetUser->id
                    || $sim->coordinator_id === $targetUser->id
                    || $sim->regional_manager_id === $targetUser->id
                    || $sim->business_id === $targetUser->id
                    || $sim->business_agent_id === $targetUser->id;

                if ($sim->status === SimStatus::ACTIVATED) {
                    $skipped[] = "{$sim->number} (activated)";
                    continue;
                }

                if (!$belongsToUser) {
                    $skipped[] = "{$sim->number} (not held by this user)";
                    continue;
                }

                $service->unassign($sim, $actor);
                $collectedCount++;
            }
        });

        $message = "Collected {$collectedCount} SIM number(s) from {$targetUser->first_name} {$targetUser->last_name} ({$targetUser->role}).";
        if (count($skipped) > 0) {
            $message .= ' Skipped: ' . implode(', ', $skipped) . '.';

            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Shared line-by-line parsing for both bulk pages — same format rules
     * as Admin\SimPlanController's resolveBulk*Numbers(): 11-digit numbers,
     * comma/newline separated, de-duplicated, each checked against a
     * caller-supplied eligibility rule.
     *
     * @return array{0: array<int, array{id: int, number: string}>, 1: array<int, string>}
     */
    private function resolveNumbers(string $rawInput, \Closure $eligibility): array
    {
        $rawNumbers = preg_split('/[\r\n,]+/', $rawInput);
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

            $rejection = $eligibility($sim);
            if ($rejection) {
                $errors[] = $rejection;
                continue;
            }

            $resolved[] = ['id' => $sim->id, 'number' => $sim->number];
        }

        return [$resolved, $errors];
    }
}
