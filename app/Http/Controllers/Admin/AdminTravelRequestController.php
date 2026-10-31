<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminTravelRequestController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'super_admin', 403, 'Unauthorized access to Travel & Tours administrative requests.');
    }

    /**
     * Display listing of all user travel requests with filters & metrics.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = TravelRequest::with(['travelPackage', 'user']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter by package type / category
        if ($request->filled('package_type')) {
            $query->where('package_type', $request->query('package_type'));
        }

        // Search by reference, name, phone, email, or package name
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_range')) {
            $range = $request->query('date_range');
            if ($range === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($range === '7days') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($range === '30days') {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'     => TravelRequest::count(),
            'pending'   => TravelRequest::where('status', TravelRequest::STATUS_PENDING)->count(),
            'active'    => TravelRequest::whereIn('status', [TravelRequest::STATUS_REVIEWING, TravelRequest::STATUS_CONTACTED])->count(),
            'approved'  => TravelRequest::whereIn('status', [TravelRequest::STATUS_APPROVED, TravelRequest::STATUS_COMPLETED])->count(),
            'cancelled' => TravelRequest::where('status', TravelRequest::STATUS_CANCELLED)->count(),
        ];

        return view('admin.travel-requests.index', [
            'requests' => $requests,
            'stats'    => $stats,
        ]);
    }

    /**
     * View detailed travel request.
     */
    public function show(TravelRequest $travelRequest): View
    {
        $this->authorizeAdmin();

        $travelRequest->load(['travelPackage', 'user']);

        return view('admin.travel-requests.show', [
            'travelRequest' => $travelRequest,
        ]);
    }

    /**
     * Update the status and administrative notes of a travel request.
     */
    public function updateStatus(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'status'         => ['required', 'string', 'in:' . implode(',', array_keys(TravelRequest::STATUSES))],
            'admin_notes'    => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['status'];
        $updateData = [
            'status'         => $newStatus,
            'admin_notes'    => $validated['admin_notes'] ?? $travelRequest->admin_notes,
            'customer_notes' => $validated['customer_notes'] ?? $travelRequest->customer_notes,
        ];

        if ($newStatus === TravelRequest::STATUS_CONTACTED && !$travelRequest->contacted_at) {
            $updateData['contacted_at'] = now();
        }

        if (in_array($newStatus, [TravelRequest::STATUS_APPROVED, TravelRequest::STATUS_COMPLETED, TravelRequest::STATUS_CANCELLED], true) && !$travelRequest->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $travelRequest->update($updateData);

        return back()->with('success', "Travel request {$travelRequest->reference} status updated to \"{$travelRequest->status_label}\".");
    }
}
