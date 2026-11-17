<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PassportRequest;
use App\Services\RequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPassportRequestController extends Controller
{
    /**
     * Display a listing of all passport requests with metrics and filters.
     */
    public function index(Request $request): View
    {
        $query = PassportRequest::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('application_type')) {
            $query->where('application_type', $request->query('application_type'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('nin', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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
            'total'               => PassportRequest::count(),
            'pending'             => PassportRequest::where('status', PassportRequest::STATUS_PENDING)->count(),
            'under_review'        => PassportRequest::where('status', PassportRequest::STATUS_UNDER_REVIEW)->count(),
            'capturing_scheduled' => PassportRequest::where('status', PassportRequest::STATUS_CAPTURING_SCHEDULED)->count(),
            'completed'           => PassportRequest::where('status', PassportRequest::STATUS_COMPLETED)->count(),
            'rejected'            => PassportRequest::where('status', PassportRequest::STATUS_REJECTED)->count(),
        ];

        return view('admin.passport-requests.index', [
            'requests' => $requests,
            'stats'    => $stats,
        ]);
    }

    /**
     * Show full administrative passport dossier.
     */
    public function show(PassportRequest $passportRequest): View
    {
        $passportRequest->load('user');

        return view('admin.passport-requests.show', [
            'passportRequest' => $passportRequest,
        ]);
    }

    /**
     * Update the processing status of a passport request.
     */
    public function updateStatus(Request $request, PassportRequest $passportRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status'           => ['required', 'string', 'in:' . implode(',', array_keys(PassportRequest::STATUSES))],
            'capturing_date'   => ['nullable', 'date'],
            'capturing_center' => ['nullable', 'string', 'max:255'],
            'admin_notes'      => ['nullable', 'string', 'max:2000'],
            'customer_notes'   => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['status'];
        $updateData = [
            'status'           => $newStatus,
            'capturing_date'   => $validated['capturing_date'] ?? $passportRequest->capturing_date,
            'capturing_center' => $validated['capturing_center'] ?? $passportRequest->capturing_center,
            'admin_notes'      => $validated['admin_notes'] ?? $passportRequest->admin_notes,
            'customer_notes'   => $validated['customer_notes'] ?? $passportRequest->customer_notes,
        ];

        if ($newStatus === PassportRequest::STATUS_UNDER_REVIEW && !$passportRequest->reviewed_at) {
            $updateData['reviewed_at'] = now();
        }

        if (in_array($newStatus, [PassportRequest::STATUS_COMPLETED, PassportRequest::STATUS_REJECTED], true) && !$passportRequest->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $passportRequest->update($updateData);

        return redirect()->route('admin.passport-requests.show', $passportRequest)
            ->with('success', "Passport request {$passportRequest->reference} status updated to \"{$passportRequest->status_label}\".");
    }

    /**
     * Download an uploaded applicant e-copy document.
     */
    public function downloadDocument(PassportRequest $passportRequest, string $type): StreamedResponse
    {
        $allowedTypes = [
            'passport_photo',
            'state_of_origin_cert',
            'birth_certificate',
            'nin_document',
            'signature',
            'previous_passport',
        ];

        abort_unless(in_array($type, $allowedTypes, true), 404, 'Invalid document type.');

        $path = $passportRequest->{$type};
        abort_unless($path && Storage::disk('public')->exists($path), 404, 'Document not found.');

        $filename = "{$passportRequest->reference}_{$type}." . pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Export complete A4 printable PDF dossier for physical submission/archival.
     */
    public function exportPdf(PassportRequest $passportRequest, RequestPdfService $pdfService)
    {
        $passportRequest->load('user');

        return $pdfService->generatePassportPdf($passportRequest);
    }
}
