<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MoniepointPosRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminMoniepointPosController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'super_admin', 403, 'Unauthorized access to POS administrative desk.');
    }

    /**
     * List all Moniepoint POS requests.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = MoniepointPosRequest::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('pos_type')) {
            $query->where('pos_type', $request->query('pos_type'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

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
            'total'      => MoniepointPosRequest::count(),
            'pending'    => MoniepointPosRequest::where('status', MoniepointPosRequest::STATUS_PENDING)->count(),
            'in_review'  => MoniepointPosRequest::whereIn('status', [MoniepointPosRequest::STATUS_UNDER_REVIEW, MoniepointPosRequest::STATUS_CONTACTED])->count(),
            'dispatched' => MoniepointPosRequest::where('status', MoniepointPosRequest::STATUS_DISPATCHED)->count(),
            'delivered'  => MoniepointPosRequest::where('status', MoniepointPosRequest::STATUS_DELIVERED)->count(),
            'declined'   => MoniepointPosRequest::where('status', MoniepointPosRequest::STATUS_DECLINED)->count(),
        ];

        return view('admin.moniepoint-pos.index', [
            'requests' => $requests,
            'stats'    => $stats,
        ]);
    }

    /**
     * Show single Moniepoint POS request details.
     */
    public function show(MoniepointPosRequest $posRequest): View
    {
        $this->authorizeAdmin();

        $posRequest->load('user');

        return view('admin.moniepoint-pos.show', [
            'posRequest' => $posRequest,
        ]);
    }

    /**
     * Update the status of a Moniepoint POS request.
     */
    public function updateStatus(Request $request, MoniepointPosRequest $posRequest): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'status'         => ['required', 'string', 'in:' . implode(',', array_keys(MoniepointPosRequest::STATUSES))],
            'admin_notes'    => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['status'];
        $updateData = [
            'status'         => $newStatus,
            'admin_notes'    => $validated['admin_notes'] ?? $posRequest->admin_notes,
            'customer_notes' => $validated['customer_notes'] ?? $posRequest->customer_notes,
        ];

        if ($newStatus === MoniepointPosRequest::STATUS_CONTACTED && !$posRequest->contacted_at) {
            $updateData['contacted_at'] = now();
        }

        if (in_array($newStatus, [MoniepointPosRequest::STATUS_DELIVERED, MoniepointPosRequest::STATUS_DECLINED], true) && !$posRequest->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $posRequest->update($updateData);

        return redirect()->route('admin.moniepoint-pos.show', $posRequest)
            ->with('success', "POS request {$posRequest->reference} status updated to \"{$posRequest->status_label}\".");
    }
}
