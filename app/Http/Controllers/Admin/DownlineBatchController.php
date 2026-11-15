<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownlineBatch;
use App\Models\DownlineBatchUser;
use App\Models\User;
use App\Services\DownlineBatchTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DownlineBatchController extends Controller
{
    public function __construct(private readonly DownlineBatchTransferService $transferService)
    {
    }

    /**
     * Dashboard listing all downline batches holding detached downlines.
     */
    public function index(Request $request): View
    {
        $query = DownlineBatch::with(['sourceUser', 'masterManager', 'creator'])->latest();
        $validStatuses = [
            DownlineBatch::STATUS_HELD,
            DownlineBatch::STATUS_PARTIALLY_TRANSFERRED,
            DownlineBatch::STATUS_TRANSFERRED,
        ];
        $activeStatus = $request->query('status');
        if (!in_array($activeStatus, $validStatuses, true)) {
            $activeStatus = null;
        }

        if ($activeStatus !== null) {
            $query->where('status', $activeStatus);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('batch_reference', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhereHas('sourceUser', function ($sq) use ($term) {
                        $sq->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%");
                    });
            });
        }

        $batches = $query->paginate(15)->withQueryString();
        $statusCounts = DownlineBatch::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $batchTabs = [
            ['label' => 'All', 'status' => null, 'count' => (int) $statusCounts->sum()],
            ['label' => 'Held', 'status' => DownlineBatch::STATUS_HELD, 'count' => (int) ($statusCounts[DownlineBatch::STATUS_HELD] ?? 0)],
            ['label' => 'Partially Transferred', 'status' => DownlineBatch::STATUS_PARTIALLY_TRANSFERRED, 'count' => (int) ($statusCounts[DownlineBatch::STATUS_PARTIALLY_TRANSFERRED] ?? 0)],
            ['label' => 'Transferred', 'status' => DownlineBatch::STATUS_TRANSFERRED, 'count' => (int) ($statusCounts[DownlineBatch::STATUS_TRANSFERRED] ?? 0)],
        ];

        $stats = [
            'total_batches'     => DownlineBatch::count(),
            'held_batches'      => DownlineBatch::where('status', DownlineBatch::STATUS_HELD)->count(),
            'total_held_users'  => DownlineBatch::sum('current_count'),
            'transferred_users' => DownlineBatch::sum('transferred_count'),
        ];

        return view('admin.manage.downline_batches.index', compact('batches', 'stats', 'batchTabs', 'activeStatus'));
    }

    /**
     * Detail view of a batch showing all members and destination manager picker for bulk transfer.
     */
    public function show(Request $request, DownlineBatch $batch): View
    {
        $batch->load(['sourceUser', 'masterManager', 'creator']);

        $userQuery = $batch->batchUsers()->with(['user', 'originalReferredBy', 'transferredToUser']);

        if ($request->filled('status')) {
            $userQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $userQuery->whereHas('user', function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('middle_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $batchUsers = $userQuery->paginate(25)->withQueryString();

        // Determine eligible upline destination roles for this batch
        $transferRole = $request->query('new_role');
        if (!in_array($transferRole, $this->transferService->transferableRoles(), true)) {
            $transferRole = null;
        }

        $eligibleRoles = $this->transferService->eligibleDestinationRoles($batch, $transferRole);
        $eligibleRolesLabel = implode(', ', array_map(fn ($r) => ucwords(str_replace('_', ' ', $r)), $eligibleRoles));

        // Query eligible destination managers (filterable by manager_search)
        $managerSearch = $request->query('manager_search');
        $destinationManagers = $this->transferService->destinationManagerQuery($batch, $managerSearch, null, $transferRole)
            ->limit(50)
            ->get();

        $destinationManagersData = $destinationManagers->map(fn (User $u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'phone'          => $u->phone,
            'role'           => $u->role,
            'role_label'     => ucwords(str_replace('_', ' ', $u->role)),
            'downline_count' => $u->referrals()->count(),
        ])->values()->all();
        $transferRoles = $this->transferService->transferableRoles();

        return view('admin.manage.downline_batches.show', compact(
            'batch',
            'batchUsers',
            'destinationManagers',
            'destinationManagersData',
            'eligibleRoles',
            'eligibleRolesLabel',
            'transferRoles',
            'managerSearch',
            'transferRole'
        ));
    }

    /**
     * Search endpoint returning eligible destination managers matching a phone, email, or name query.
     */
    public function searchDestinations(Request $request, DownlineBatch $batch): JsonResponse
    {
        $query = $request->query('search') ?? $request->query('q');
        $newRole = $request->query('new_role');
        if ($newRole !== null && !in_array($newRole, $this->transferService->transferableRoles(), true)) {
            abort(422, 'Invalid transfer role.');
        }

        $managers = $this->transferService->destinationManagerQuery($batch, $query, null, $newRole)
            ->limit(30)
            ->get();

        return response()->json($managers->map(fn (User $u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'phone'          => $u->phone,
            'role'           => $u->role,
            'role_label'     => ucwords(str_replace('_', ' ', $u->role)),
            'downline_count' => $u->referrals()->count(),
        ]));
    }

    /**
     * Execute bulk transfer of selected or all held users in this batch to a destination manager.
     */
    public function transfer(Request $request, DownlineBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'destination_user_id' => ['required', 'exists:users,id'],
            'user_ids'            => ['nullable', 'array'],
            'user_ids.*'          => ['exists:users,id'],
            'new_role'            => ['nullable', 'string', Rule::in($this->transferService->transferableRoles())],
        ], [
            'destination_user_id.required' => 'Please select an eligible destination manager to receive the transferred downlines.',
        ]);

        $destination = User::findOrFail($validated['destination_user_id']);

        // Enforce role eligibility check
        $newRole = $validated['new_role'] ?? null;
        $eligibleRoles = $this->transferService->eligibleDestinationRoles($batch, $newRole);
        if (!in_array($destination->role, $eligibleRoles, true)) {
            $readable = implode(', ', array_map(fn ($r) => ucwords(str_replace('_', ' ', $r)), $eligibleRoles));
            $actual = ucwords(str_replace('_', ' ', $destination->role));
            return back()->with('error', "Selected destination manager has an ineligible role ({$actual}). Downlines from this batch can only be transferred to: {$readable}.");
        }

        try {
            $count = $this->transferService->transfer(
                $batch,
                $destination,
                $request->input('user_ids', []),
                $request->user(),
                $newRole
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $roleLabel = ucfirst(str_replace('_', ' ', $destination->role));
        $roleChangeMessage = $newRole ? ' Their role was changed to ' . ucwords(str_replace('_', ' ', $newRole)) . '.' : '';
        return back()->with('success', "Successfully transferred {$count} user(s) to {$destination->name} ({$roleLabel}) in bulk operation.{$roleChangeMessage}");
    }
}
