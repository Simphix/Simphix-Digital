<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ManagerReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManagerReplacementController extends Controller
{
    public function __construct(private readonly ManagerReplacementService $service)
    {
    }

    /**
     * Search-first picker: choose which manager (role in
     * ManagerReplacementService::replaceableRoles()) to replace, before
     * landing on their individual confirmation screen.
     */
    public function index(Request $request): View
    {
        $roles = ManagerReplacementService::replaceableRoles();

        $query = User::query()->whereIn('role', $roles);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->role, $roles, true)) {
            $query->where('role', $request->role);
        }

        $managers = $query->orderBy('role')->orderBy('first_name')->paginate(15)->withQueryString();

        return view('admin.manage.replace_manager_picker', compact('managers', 'roles'));
    }

    /**
     * Confirmation screen: pick any account on the platform that will take over
     * $outgoing's seat. Automatically upgrades the selected user to the exact role
     * and batches downlines of both managers to the Platform Level Master Manager.
     */
    public function show(Request $request, User $user): View
    {
        if (!ManagerReplacementService::roleColumn($user->role)) {
            abort(404, 'This role does not hold a downline/jurisdiction position that can be replaced.');
        }

        $search = $request->query('search');
        $candidates = $this->service->candidateQuery($user, $search)->paginate(20)->withQueryString();
        $impact = $this->service->impactSummary($user);
        $masterManager = User::platformMasterManager();

        return view('admin.manage.replace_manager', [
            'outgoing'      => $user,
            'candidates'    => $candidates,
            'impact'        => $impact,
            'masterManager' => $masterManager,
            'search'        => $search,
        ]);
    }

    /**
     * Live search endpoint returning candidates matching a phone, email, or name query.
     */
    public function searchCandidates(Request $request, User $user)
    {
        $search = $request->query('search') ?? $request->query('q');
        $candidates = $this->service->candidates($user, $search, 30);

        return response()->json($candidates->map(fn (User $u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'phone'          => $u->phone,
            'role'           => $u->role,
            'role_label'     => ucfirst(str_replace('_', ' ', $u->role)),
            'needs_upgrade'  => $u->role !== $user->role,
            'downline_count' => $u->referrals()->count(),
        ]));
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'incoming_user_id' => [
                'required',
                Rule::exists('users', 'id')->whereNot('id', $user->id),
            ],
        ]);

        $incoming = User::findOrFail($validated['incoming_user_id']);

        try {
            $result = $this->service->replace($user, $incoming, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = "{$incoming->name} has taken over {$user->name}'s seat.";
        if (!empty($result['role_upgraded'])) {
            $msg .= " Upgraded from " . ucfirst(str_replace('_', ' ', $result['old_role'])) . " to " . ucfirst(str_replace('_', ' ', $user->role)) . ".";
        }
        if (!empty($result['outgoing_downlines_count'])) {
            $msg .= " Outgoing downline ({$result['outgoing_downlines_count']} users) moved to Platform Master Manager in Batch #{$result['outgoing_batch']->batch_reference}.";
        }
        if (!empty($result['incoming_downlines_count'])) {
            $msg .= " Replacement downline ({$result['incoming_downlines_count']} users) moved to Platform Master Manager in Batch #{$result['incoming_batch']->batch_reference}.";
        }

        return redirect()
            ->route('admin.manage.users.show', $incoming)
            ->with('success', $msg);
    }
}
