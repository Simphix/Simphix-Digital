<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CacStatusUpdatedMail;
use App\Models\CacRegistration;
use App\Models\CacRegistrationDocument;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\CacRequirements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin console for CAC registrations: triage, respond with notes, upload
 * deliverables (certificate etc.), and move a registration through its
 * status pipeline. Rejecting/cancelling refunds the wallet fee. Pricing is
 * managed in /admin/services (the `cac` service), not here.
 */
class CacRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $query = CacRegistration::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $registrations = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => CacRegistration::count(),
            'new' => CacRegistration::where('status', 'submitted')->count(),
            'in_progress' => CacRegistration::whereIn('status', ['under_review', 'action_required', 'name_reservation', 'publication', 'awaiting_approval'])->count(),
            'completed' => CacRegistration::where('status', 'completed')->count(),
        ];

        $pricingService = Service::where('name', CacRequirements::SERVICE_NAME)->first();

        return view('admin.cac.index', compact('registrations', 'stats', 'pricingService'));
    }

    public function show(CacRegistration $registration): View
    {
        $registration->load(['user', 'documents', 'events.user']);

        return view('admin.cac.show', compact('registration'));
    }

    public function updateStatus(Request $request, CacRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(CacRegistration::STATUSES))],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        if (in_array($registration->status, CacRegistration::REFUND_STATUSES, true)) {
            return back()->with('error', 'This registration was ' . $registration->status . ' and refunded — it can no longer be changed.');
        }

        $to = $validated['status'];
        if ($to === $registration->status && empty($validated['message'])) {
            return back()->with('error', 'Status is unchanged.');
        }

        $refunded = false;

        DB::transaction(function () use ($registration, $to, $validated, $request, &$refunded) {
            $from = $registration->status;

            if (in_array($to, CacRegistration::REFUND_STATUSES, true)) {
                $refunded = $this->refund($registration);
            }

            $registration->update([
                'status' => $to,
                'completed_at' => $to === 'completed' ? now() : $registration->completed_at,
            ]);

            $registration->events()->create([
                'user_id' => $request->user()->id,
                'type' => 'status',
                'status_from' => $from,
                'status_to' => $to,
                'message' => trim(($validated['message'] ?? '') . ($refunded ? ' The registration fee has been refunded to your wallet.' : '')) ?: null,
                'is_admin' => true,
                'visible_to_user' => true,
            ]);
        });

        $this->notify($registration->fresh('user'), 'Your registration status is now: ' . $registration->fresh()->status_label . '.' . (!empty($validated['message']) ? ' ' . $validated['message'] : ''));

        return back()->with('success', 'Status updated to ' . CacRegistration::STATUSES[$to] . ($refunded ? ' and the fee was refunded.' : '.'));
    }

    /** Credit the wallet back with the fee paid; returns whether a refund was issued. */
    private function refund(CacRegistration $registration): bool
    {
        if ((float) $registration->amount <= 0) {
            return false;
        }

        $wallet = Wallet::where('user_id', $registration->user_id)->lockForUpdate()->first();
        if (!$wallet) {
            throw new \RuntimeException('Cannot refund: the user has no wallet.');
        }

        $wallet->increment('balance', $registration->amount);
        if (isset($wallet->total_credited)) {
            $wallet->increment('total_credited', $registration->amount);
        }

        Transaction::create([
            'transaction_ref' => 'CACREF-' . time() . '-' . random_int(1000, 9999),
            'user_id' => $registration->user_id,
            'amount' => $registration->amount,
            'fee' => 0.00,
            'net_amount' => $registration->amount,
            'description' => "Refund: CAC registration {$registration->reference}",
            'type' => 'refund',
            'status' => 'completed',
            'performed_by' => 'System',
        ]);

        return true;
    }

    public function addNote(Request $request, CacRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
            'visible_to_user' => ['nullable', 'boolean'],
        ]);

        $visible = $request->boolean('visible_to_user');

        $registration->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'note',
            'message' => $validated['message'],
            'is_admin' => true,
            'visible_to_user' => $visible,
        ]);

        if ($visible) {
            $this->notify($registration->load('user'), $validated['message']);
        }

        return back()->with('success', $visible ? 'Note sent to the applicant.' : 'Internal note saved.');
    }

    /** Upload one or more deliverables (certificate, constitution, status report…) for the applicant to download. */
    public function upload(Request $request, CacRegistration $registration): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx,zip', 'max:10240'],
            'label' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $names = [];
        foreach ($request->file('files') as $file) {
            $registration->documents()->create([
                'slot' => 'deliverable',
                'label' => $request->label ?: 'Document from SimPhix',
                'path' => $file->store("cac/{$registration->id}", 'local'),
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => 'admin',
                'is_deliverable' => true,
            ]);
            $names[] = $file->getClientOriginalName();
        }

        $note = 'Uploaded ' . implode(', ', $names) . '.' . ($request->filled('message') ? ' ' . $request->message : '');
        $registration->events()->create([
            'user_id' => $request->user()->id,
            'type' => 'upload',
            'message' => $note,
            'is_admin' => true,
            'visible_to_user' => true,
        ]);

        $this->notify($registration->load('user'), 'New document(s) available to download: ' . implode(', ', $names) . '.');

        return back()->with('success', count($names) . ' file(s) uploaded.');
    }

    public function destroyDocument(CacRegistration $registration, CacRegistrationDocument $document): RedirectResponse
    {
        abort_unless($document->cac_registration_id === $registration->id && $document->uploaded_by === 'admin', 403);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    public function download(CacRegistration $registration, CacRegistrationDocument $document): StreamedResponse
    {
        abort_unless($document->cac_registration_id === $registration->id, 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    private function notify(CacRegistration $registration, string $message): void
    {
        try {
            Mail::to($registration->user->email)->send(new CacStatusUpdatedMail($registration, $message));
        } catch (\Throwable $e) {
            Log::error("Failed to send CacStatusUpdatedMail for {$registration->reference}: " . $e->getMessage());
        }
    }
}
