<?php

namespace App\Http\Controllers;

use App\Models\CacRegistration;
use App\Models\CacRegistrationDocument;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\CacRequirements;
use App\Support\StaffAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer side of CAC registration: category overview (same idea as the SIM
 * Services overview), a per-category application form, and history/tracking.
 * Fees are debited from the wallet on submit and refunded automatically if
 * an admin rejects/cancels (see Admin\CacRegistrationController).
 */
class CacRegistrationController extends Controller
{
    private function authorizeCustomer(): User
    {
        $user = Auth::user();
        abort_if(!$user || StaffAccess::isInternal($user), 403, 'CAC registration is only available to customer accounts.');

        return $user;
    }

    /** The configured ServiceField for a category (admin-managed price + on/off). */
    private function priceField(string $key): ?ServiceField
    {
        $service = Service::where('name', CacRequirements::SERVICE_NAME)->first();

        return $service
            ? ServiceField::where('service_id', $service->id)->where('field_code', CacRequirements::get($key)['field_code'])->first()
            : null;
    }

    public function overview(): View
    {
        $user = $this->authorizeCustomer();

        $categories = [];
        foreach (CacRequirements::all() as $key => $def) {
            $field = $this->priceField($key);
            $categories[] = $def + [
                'key' => $key,
                'price' => $field ? $field->priceForUser($user) : null,
                'available' => (bool) ($field && $field->is_active),
                'route' => route('cac.create', $def['slug']),
            ];
        }

        $activeCount = CacRegistration::where('user_id', $user->id)->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count();

        return view('cac.overview', compact('categories', 'activeCount'));
    }

    public function create(string $slug): View
    {
        $user = $this->authorizeCustomer();
        $key = CacRequirements::fromSlug($slug);
        abort_unless($key, 404);

        $field = $this->priceField($key);
        abort_unless($field && $field->is_active, 404, 'This registration type is currently unavailable.');

        $def = CacRequirements::get($key);
        $price = $field->priceForUser($user);
        $balance = (float) (Wallet::where('user_id', $user->id)->value('balance') ?? 0);

        return view('cac.form', compact('key', 'def', 'price', 'balance', 'user'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $user = $this->authorizeCustomer();
        $key = CacRequirements::fromSlug($slug);
        abort_unless($key, 404);

        $field = $this->priceField($key);
        abort_unless($field && $field->is_active, 404, 'This registration type is currently unavailable.');

        $def = CacRequirements::get($key);
        $request->validate(CacRequirements::rules($key), [], CacRequirements::attributes($key));

        $people = array_values($request->input('people', []));
        $errors = [];

        if ($def['kind'] === 'shareholders') {
            if (count($people) === 1) {
                $people[0]['percent'] = 100;
            } else {
                $total = 0;
                foreach ($people as $i => $p) {
                    if (!isset($p['percent']) || $p['percent'] === '') {
                        $errors["people.$i.percent"] = 'Enter this shareholder\'s percentage.';
                    }
                    $total += (float) ($p['percent'] ?? 0);
                }
                if (!$errors && abs($total - 100) > 0.01) {
                    $errors['people'] = 'Shareholding percentages must add up to exactly 100% (currently ' . rtrim(rtrim(number_format($total, 2), '0'), '.') . '%).';
                }
            }
        }

        if ($def['kind'] === 'trustees') {
            foreach (['chairman', 'secretary'] as $role) {
                if ((int) $request->input($role) >= count($people)) {
                    $errors[$role] = 'Pick one of the trustees you entered.';
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $price = $field->priceForUser($user);
        $regId = null;

        try {
            $registration = DB::transaction(function () use ($request, $user, $key, $def, $people, $price, &$regId) {
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet || $wallet->balance < $price) {
                    throw new \RuntimeException('Insufficient wallet balance. This registration costs ₦' . number_format($price, 2) . '.');
                }

                $reference = 'CAC-' . strtoupper(Str::random(8));

                $formData = [
                    'names' => array_map('trim', array_values($request->input('names'))),
                    'people' => array_map(fn ($p) => Arr::only($p, [
                        'first_name', 'surname', 'other_names', 'full_name', 'phone', 'email', 'address', 'nin',
                        'state_of_origin', 'lga', 'dob', 'occupation', 'id_type', 'percent',
                    ]), $people),
                ];
                foreach (['business_address', 'business_type', 'objectives', 'registered_address'] as $f) {
                    if ($request->filled($f)) {
                        $formData[$f] = $request->input($f);
                    }
                }
                if ($def['kind'] === 'trustees') {
                    $formData['chairman'] = (int) $request->input('chairman');
                    $formData['secretary'] = (int) $request->input('secretary');
                }
                if ($def['kind'] === 'shareholders') {
                    $formData['witness'] = $request->input('witness');
                    $secretary = array_filter($request->input('secretary_details', []));
                    if ($secretary) {
                        $formData['secretary_details'] = $secretary;
                    }
                }

                $registration = CacRegistration::create([
                    'reference' => $reference,
                    'user_id' => $user->id,
                    'category' => $key,
                    'status' => 'submitted',
                    'amount' => $price,
                    'transaction_ref' => 'CACREG-' . time() . '-' . random_int(1000, 9999),
                    'form_data' => $formData,
                    'submitted_at' => now(),
                ]);
                $regId = $registration->id;

                $this->storeUploads($request, $registration);

                $wallet->decrement('balance', $price);
                if (isset($wallet->total_debited)) {
                    $wallet->increment('total_debited', $price);
                }

                Transaction::create([
                    'transaction_ref' => $registration->transaction_ref,
                    'user_id' => $user->id,
                    'amount' => $price,
                    'fee' => 0.00,
                    'net_amount' => $price,
                    'description' => "CAC Registration ({$def['label']}) — {$reference}",
                    'type' => 'debit',
                    'status' => 'completed',
                    'performed_by' => $user->first_name . ' ' . $user->last_name,
                ]);

                $registration->events()->create([
                    'user_id' => $user->id,
                    'type' => 'status',
                    'status_to' => 'submitted',
                    'message' => 'Registration submitted and fee paid from wallet.',
                    'is_admin' => false,
                ]);

                return $registration;
            });
        } catch (\RuntimeException $e) {
            if ($regId) {
                Storage::disk('local')->deleteDirectory("cac/{$regId}");
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($regId) {
                Storage::disk('local')->deleteDirectory("cac/{$regId}");
            }
            report($e);

            return back()->withInput()->with('error', 'Could not submit your registration. Please try again.');
        }

        return redirect()->route('cac.show', $registration)->with('success', "Registration {$registration->reference} submitted. We'll keep you updated here.");
    }

    /** Persist every uploaded file from the form's `files` tree as a document row. */
    private function storeUploads(Request $request, CacRegistration $registration): void
    {
        $files = $request->file('files', []);

        foreach ($files as $group => $value) {
            if ($group === 'people') {
                foreach ($value as $i => $slots) {
                    foreach ($slots as $slot => $file) {
                        $this->storeOne($registration, "person_{$i}_{$slot}", $file);
                    }
                }
            } else {
                $this->storeOne($registration, $group, $value);
            }
        }
    }

    private function storeOne(CacRegistration $registration, string $slot, $file, string $uploadedBy = 'user'): CacRegistrationDocument
    {
        return $registration->documents()->create([
            'slot' => $slot,
            'label' => CacRequirements::slotLabel($slot),
            'path' => $file->store("cac/{$registration->id}", 'local'),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $uploadedBy,
            'is_deliverable' => false,
        ]);
    }

    public function history(Request $request): View
    {
        $user = $this->authorizeCustomer();

        $query = CacRegistration::where('user_id', $user->id);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $registrations = $query->latest()->paginate(10)->withQueryString();

        return view('cac.history', compact('registrations'));
    }

    public function show(CacRegistration $registration): View
    {
        $user = $this->authorizeCustomer();
        abort_unless($registration->user_id === $user->id, 403);

        $registration->load(['documents', 'events' => fn ($q) => $q->where('visible_to_user', true)->with('user')]);

        return view('cac.show', compact('registration'));
    }

    /** User adds a note (and optionally one more document) to an open registration. */
    public function reply(Request $request, CacRegistration $registration): RedirectResponse
    {
        $user = $this->authorizeCustomer();
        abort_unless($registration->user_id === $user->id, 403);

        if ($registration->isFinal()) {
            return back()->with('error', 'This registration is closed.');
        }

        $request->validate([
            'message' => ['required', 'string', 'max:3000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:6144'],
        ]);

        DB::transaction(function () use ($request, $registration, $user) {
            if ($request->hasFile('attachment')) {
                $this->storeOne($registration, 'additional', $request->file('attachment'));
            }

            $registration->events()->create([
                'user_id' => $user->id,
                'type' => 'note',
                'message' => $request->message . ($request->hasFile('attachment') ? ' [attachment: ' . $request->file('attachment')->getClientOriginalName() . ']' : ''),
                'is_admin' => false,
            ]);

            if ($registration->status === 'action_required') {
                $registration->events()->create([
                    'user_id' => $user->id,
                    'type' => 'status',
                    'status_from' => 'action_required',
                    'status_to' => 'under_review',
                    'message' => 'Applicant responded.',
                ]);
                $registration->update(['status' => 'under_review']);
            }
        });

        return back()->with('success', 'Your message has been sent.');
    }

    /** Owner downloads one of their own uploads or any admin deliverable. */
    public function download(CacRegistration $registration, CacRegistrationDocument $document): StreamedResponse
    {
        $user = $this->authorizeCustomer();
        abort_unless($registration->user_id === $user->id && $document->cac_registration_id === $registration->id, 403);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
