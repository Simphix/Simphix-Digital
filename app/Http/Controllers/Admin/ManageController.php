<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\User;
use App\Support\DownlineMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManageController extends Controller
{
    /**
     * Display a list of all users.
     */
    public function users(Request $request): View
    {
        $query = User::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Compute global user statistics
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', '!=', 'active')->count();
        $transactingUsers = User::whereHas('wallet', function ($q) {
            $q->where('total_credited', '>', 0)->orWhere('total_debited', '>', 0);
        })->count();

        return view('admin.manage.users', compact('users', 'totalUsers', 'activeUsers', 'inactiveUsers', 'transactingUsers'));
    }

    /**
     * Show the form for editing a user.
     */
    public function editUser(User $user): View
    {
        return view('admin.manage.edit_user', compact('user'));
    }

    /**
     * Update the user details and status.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), Rule::unique(User::class)->ignore($user->id)],
            'status' => ['required', 'string', 'in:active,suspended,inactive,banned'],
            'role' => ['required', 'string', 'in:personal,agent,partner,business,business_agent,regional_manager,coordinator,business_sales_manager,support,super_admin'],
        ]);

        $previousRole = $user->role;

        $user->forceFill([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
            'role' => $request->role,
        ])->save();

        $this->clearStaleJurisdiction($user, $previousRole);

        return redirect()->route('admin.manage.users')->with('success', 'User ' . $user->first_name . ' has been updated successfully.');
    }

    /**
     * Show the detailed information for a user (KYC, Wallet, Virtual
     * Accounts, and downline/SIM metrics). Admin can view any user, not
     * just their own downline — DownlineMetrics::forUser() is the same
     * computation an upline sees for their own team, reused here.
     */
    public function showUser(User $user): View
    {
        $user->load(['wallet', 'virtualAccounts']);
        $metrics = DownlineMetrics::forUser($user);
        return view('admin.manage.show_user', array_merge($metrics, compact('user')));
    }

    /**
     * Paginated list of a user's immediate downline (invited + active),
     * reached by drilling into the "Total Onboarded" number on their
     * Metrics tab. Each row links back into showUser() for that member.
     */
    public function userTeam(Request $request, User $user): View
    {
        $query = $user->referrals()->with('wallet');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $team = $query->latest()->paginate(15)->withQueryString();
        return view('admin.manage.team', compact('user', 'team'));
    }

    /**
     * Delete the user and all associated wallet and virtual account records.
     */
    public function destroyUser(User $user): RedirectResponse
    {
        // Don't allow an admin to delete themselves
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Delete associated wallet
            if ($user->wallet) {
                $user->wallet->delete();
            }

            // Delete associated virtual accounts
            $user->virtualAccounts()->delete();

            // Delete the user
            $user->delete();

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('admin.manage.users')->with('success', 'User has been deleted successfully along with all associated wallet and virtual account records.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Display upgrade requests.
     */
    public function upgrades(Request $request): View
    {
        $pendingUpgrades = User::where('upgrade_status', 'pending')
            ->orderBy('upgrade_requested_at', 'asc')
            ->get();

        $historicalUpgrades = User::whereIn('upgrade_status', ['approved', 'rejected'])
            ->orderBy('upgrade_requested_at', 'desc')
            ->paginate(15);

        return view('admin.manage.upgrades', compact('pendingUpgrades', 'historicalUpgrades'));
    }

    /**
     * Approve upgrade request.
     */
    public function approveUpgrade(User $user): RedirectResponse
    {
        if ($user->upgrade_status !== 'pending') {
            return redirect()->back()->with('error', 'This user does not have a pending upgrade request.');
        }

        $user->forceFill([
            'role' => $user->pending_role,
            'upgrade_status' => 'approved',
        ])->save();

        return redirect()->back()->with('success', 'Upgrade request for ' . $user->first_name . ' to ' . strtoupper($user->role) . ' has been approved.');
    }

    /**
     * Reject upgrade request.
     */
    public function rejectUpgrade(User $user): RedirectResponse
    {
        if ($user->upgrade_status !== 'pending') {
            return redirect()->back()->with('error', 'This user does not have a pending upgrade request.');
        }

        $user->forceFill([
            'upgrade_status' => 'rejected',
        ])->save();

        return redirect()->back()->with('success', 'Upgrade request for ' . $user->first_name . ' has been rejected.');
    }

    /**
     * Display role access list.
     */
    public function access(Request $request): View
    {
        $query = User::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('role', 'asc')->paginate(15);

        return view('admin.manage.access', compact('users'));
    }

    /**
     * Update access role.
     */
    public function updateAccess(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:personal,agent,partner,business,business_agent,regional_manager,coordinator,business_sales_manager,support,super_admin'],
        ]);

        $previousRole = $user->role;

        $user->forceFill([
            'role' => $request->role,
        ])->save();

        $this->clearStaleJurisdiction($user, $previousRole);

        return redirect()->back()->with('success', 'Access permission for ' . $user->first_name . ' updated to ' . strtoupper($user->role) . '.');
    }

    /**
     * A role change away from regional_manager/coordinator must not leave
     * a stale jurisdiction assignment behind — clear it so the region/
     * district reverts to unassigned rather than silently pointing at a
     * user who no longer holds the role.
     */
    private function clearStaleJurisdiction(User $user, string $previousRole): void
    {
        if ($previousRole === 'regional_manager' && $user->role !== 'regional_manager') {
            Region::where('regional_manager_id', $user->id)->update(['regional_manager_id' => null]);
        }

        if ($previousRole === 'coordinator' && $user->role !== 'coordinator') {
            SenatorialDistrict::where('coordinator_id', $user->id)->update(['coordinator_id' => null]);
        }
    }
}
