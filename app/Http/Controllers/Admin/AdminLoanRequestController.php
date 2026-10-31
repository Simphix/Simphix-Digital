<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoanRequestController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'super_admin', 403, 'Unauthorized access to Business Loans administrative desk.');
    }

    /**
     * List all Business Loan requests.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $query = LoanRequest::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('moniepoint_account_number', 'like', "%{$search}%")
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
            'total'          => LoanRequest::count(),
            'pending'        => LoanRequest::where('status', LoanRequest::STATUS_PENDING)->count(),
            'in_review'      => LoanRequest::whereIn('status', [LoanRequest::STATUS_UNDER_REVIEW, LoanRequest::STATUS_CONTACTED])->count(),
            'approved'       => LoanRequest::where('status', LoanRequest::STATUS_APPROVED)->count(),
            'disbursed'      => LoanRequest::where('status', LoanRequest::STATUS_DISBURSED)->count(),
            'total_volume'   => (float) LoanRequest::whereIn('status', [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DISBURSED])->sum('amount_requested'),
            'declined'       => LoanRequest::where('status', LoanRequest::STATUS_DECLINED)->count(),
        ];

        return view('admin.loans.index', [
            'requests' => $requests,
            'stats'    => $stats,
        ]);
    }

    /**
     * Show single Loan request details.
     */
    public function show(LoanRequest $loanRequest): View
    {
        $this->authorizeAdmin();

        $loanRequest->load('user');

        return view('admin.loans.show', [
            'loanRequest' => $loanRequest,
        ]);
    }

    /**
     * Update the status of a Business Loan request.
     */
    public function updateStatus(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'status'         => ['required', 'string', 'in:' . implode(',', array_keys(LoanRequest::STATUSES))],
            'admin_notes'    => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['status'];
        $updateData = [
            'status'         => $newStatus,
            'admin_notes'    => $validated['admin_notes'] ?? $loanRequest->admin_notes,
            'customer_notes' => $validated['customer_notes'] ?? $loanRequest->customer_notes,
        ];

        if ($newStatus === LoanRequest::STATUS_CONTACTED && !$loanRequest->contacted_at) {
            $updateData['contacted_at'] = now();
        }

        if (in_array($newStatus, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DISBURSED, LoanRequest::STATUS_DECLINED], true) && !$loanRequest->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $loanRequest->update($updateData);

        return redirect()->route('admin.loan-requests.show', $loanRequest)
            ->with('success', "Loan request {$loanRequest->reference} status updated to \"{$loanRequest->status_label}\".");
    }
}
