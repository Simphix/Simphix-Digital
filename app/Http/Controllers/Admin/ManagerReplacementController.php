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
     * Confirmation screen: pick the vacant account that will take over
     * $outgoing's seat, after seeing exactly what that involves.
     */
    public function show(User $user): View
    {
        if (!ManagerReplacementService::roleColumn($user->role)) {
            abort(404, 'This role does not hold a downline/jurisdiction position that can be replaced.');
        }

        $candidates = $this->service->vacantCandidates($user);
        $impact = $this->service->impactSummary($user);

        return view('admin.manage.replace_manager', [
            'outgoing' => $user,
            'candidates' => $candidates,
            'impact' => $impact,
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'incoming_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', $user->role),
            ],
        ]);

        $incoming = User::findOrFail($validated['incoming_user_id']);

        try {
            $this->service->replace($user, $incoming, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.manage.users.show', $incoming)
            ->with('success', "{$incoming->name} has taken over {$user->name}'s seat: downline, jurisdiction, and SIM holdings have all moved over.");
    }
}
