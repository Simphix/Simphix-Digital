<?php

namespace App\Http\Controllers;

use App\Models\PassportRequest;
use App\Services\RequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PassportRequestController extends Controller
{
    /**
     * Landing page for Passport application overview & requirements.
     */
    public function index(): View
    {
        $myRequests = Auth::check()
            ? PassportRequest::where('user_id', Auth::id())->latest()->take(5)->get()
            : collect();

        return view('passport.index', [
            'userRequests' => $myRequests,
            'myRequests'   => $myRequests,
        ]);
    }

    /**
     * Show application form.
     */
    public function create(Request $request): View
    {
        $selectedType = $request->query('type', PassportRequest::TYPE_NEW);
        if (!in_array($selectedType, [PassportRequest::TYPE_NEW, PassportRequest::TYPE_RENEWAL], true)) {
            $selectedType = PassportRequest::TYPE_NEW;
        }

        return view('passport.create', [
            'selectedType' => $selectedType,
            'type'         => $selectedType,
            'user'         => Auth::user(),
        ]);
    }

    /**
     * Store new application request with document uploads.
     */
    public function store(Request $request): RedirectResponse
    {
        // Normalize alias input fields from either form naming
        $phone = $request->input('phone') ?? $request->input('mobile_number');
        $address = $request->input('residential_address') ?? $request->input('address');
        $gender = $request->input('gender') ?? $request->input('sex');
        if ($gender) {
            $gender = strtolower($gender);
        }

        $request->merge([
            'phone'               => $phone,
            'residential_address' => $address,
            'gender'              => $gender,
        ]);

        $validated = $request->validate([
            'application_type'         => ['required', 'string', 'in:new,renewal'],
            'previous_passport_number' => ['nullable', 'string', 'max:50'],
            'nin'                      => ['required', 'string', 'size:11', 'regex:/^[0-9]+$/'],
            'surname'                  => ['required', 'string', 'max:100'],
            'first_name'               => ['required', 'string', 'max:100'],
            'middle_name'              => ['nullable', 'string', 'max:100'],
            'date_of_birth'            => ['required', 'date', 'before:today'],
            'state_of_origin'          => ['required', 'string', 'max:80'],
            'lga'                      => ['required', 'string', 'max:100'],
            'hometown'                 => ['required', 'string', 'max:120'],
            'place_of_birth'           => ['required', 'string', 'max:120'],
            'phone'                    => ['required', 'string', 'max:30'],
            'email'                    => ['required', 'email', 'max:120'],
            'residential_address'      => ['required', 'string', 'max:500'],
            'gender'                   => ['required', 'string', 'in:male,female'],
            'occupation'               => ['required', 'string', 'max:120'],
            'next_of_kin_name'         => ['required', 'string', 'max:150'],
            'next_of_kin_relationship' => ['required', 'string', 'max:80'],
            'next_of_kin_phone'        => ['required', 'string', 'max:30'],
            'next_of_kin_address'      => ['required', 'string', 'max:500'],
            'customer_notes'           => ['nullable', 'string', 'max:1000'],
            'notes'                    => ['nullable', 'string', 'max:2000'],
            'capturing_center'         => ['nullable', 'string', 'max:255'],
        ]);

        $photoFile = $request->file('passport_photo');
        $originFile = $request->file('state_origin_cert') ?? $request->file('state_of_origin_cert');
        $birthFile = $request->file('birth_cert') ?? $request->file('birth_certificate');
        $ninFile = $request->file('nin_doc') ?? $request->file('nin_document');
        $sigFile = $request->file('signature');
        $prevFile = $request->file('previous_passport');

        if (!$photoFile) {
            return back()->withInput()->withErrors(['passport_photo' => 'Passport photograph is required.']);
        }
        if (!$originFile) {
            return back()->withInput()->withErrors(['state_of_origin_cert' => 'State of origin certificate is required.']);
        }
        if (!$birthFile) {
            return back()->withInput()->withErrors(['birth_certificate' => 'Birth certificate is required.']);
        }
        if (!$ninFile) {
            return back()->withInput()->withErrors(['nin_document' => 'NIN document / slip is required.']);
        }
        if (!$sigFile) {
            return back()->withInput()->withErrors(['signature' => 'Specimen signature on white paper is required.']);
        }

        $reference = PassportRequest::generateReference();
        $folder = "passport_documents/{$reference}";

        // Store documents
        $photoPath = $photoFile->store($folder, 'public');
        $stateOriginPath = $originFile->store($folder, 'public');
        $birthCertPath = $birthFile->store($folder, 'public');
        $ninDocPath = $ninFile->store($folder, 'public');
        $signaturePath = $sigFile->store($folder, 'public');
        $prevPassportPath = $prevFile ? $prevFile->store($folder, 'public') : null;

        $passportRequest = PassportRequest::create([
            'reference'                => $reference,
            'user_id'                  => Auth::id(),
            'application_type'         => $validated['application_type'],
            'previous_passport_number' => $validated['previous_passport_number'] ?? null,
            'nin'                      => $validated['nin'],
            'surname'                  => $validated['surname'],
            'first_name'               => $validated['first_name'],
            'middle_name'              => $validated['middle_name'] ?? null,
            'date_of_birth'            => $validated['date_of_birth'],
            'state_of_origin'          => $validated['state_of_origin'],
            'lga'                      => $validated['lga'],
            'hometown'                 => $validated['hometown'],
            'place_of_birth'           => $validated['place_of_birth'],
            'phone'                    => $validated['phone'],
            'email'                    => $validated['email'],
            'residential_address'      => $validated['residential_address'],
            'gender'                   => $validated['gender'],
            'occupation'               => $validated['occupation'],
            'next_of_kin_name'         => $validated['next_of_kin_name'],
            'next_of_kin_relationship' => $validated['next_of_kin_relationship'],
            'next_of_kin_phone'        => $validated['next_of_kin_phone'],
            'next_of_kin_address'      => $validated['next_of_kin_address'],
            'passport_photo_path'      => $photoPath,
            'state_origin_cert_path'   => $stateOriginPath,
            'birth_cert_path'          => $birthCertPath,
            'nin_doc_path'             => $ninDocPath,
            'signature_path'           => $signaturePath,
            'previous_passport_path'   => $prevPassportPath,
            'customer_notes'           => $validated['customer_notes'] ?? ($validated['notes'] ?? null),
            'capturing_center'         => $validated['capturing_center'] ?? null,
            'status'                   => PassportRequest::STATUS_PENDING,
        ]);

        return redirect()->route('passport.show', $passportRequest)->with('success', "Passport application {$reference} submitted successfully. You can track your status anytime with this reference.");
    }

    /**
     * Application tracker lookup.
     */
    public function tracking(Request $request): View|RedirectResponse
    {
        $search = trim($request->input('search', ''));
        $foundRequest = null;

        if ($search !== '') {
            $foundRequest = PassportRequest::where('reference', $search)
                ->orWhere('nin', $search)
                ->orWhere('phone', $search)
                ->first();

            if ($foundRequest && $foundRequest->reference === $search) {
                return redirect()->route('passport.show', $foundRequest);
            }
        }

        $myRequests = Auth::check()
            ? PassportRequest::where('user_id', Auth::id())->latest()->paginate(10)
            : null;

        return view('passport.tracking', [
            'search'       => $search,
            'foundRequest' => $foundRequest,
            'myRequests'   => $myRequests,
        ]);
    }

    /**
     * Show detailed application status.
     */
    public function show(PassportRequest $passportRequest): View
    {
        // Allow owner, staff, or anyone if session verified
        if (Auth::check() && !Auth::user()->isStaff() && $passportRequest->user_id && $passportRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to view this application.');
        }

        return view('passport.show', [
            'passportRequest' => $passportRequest,
            'request'         => $passportRequest,
        ]);
    }

    /**
     * Download customer uploaded document.
     */
    public function downloadDocument(PassportRequest $passportRequest, string $type): StreamedResponse
    {
        if (Auth::check() && !Auth::user()->isStaff() && $passportRequest->user_id && $passportRequest->user_id !== Auth::id()) {
            abort(403);
        }

        $path = match ($type) {
            'photo', 'passport_photo'                                => $passportRequest->passport_photo_path,
            'state_origin', 'state_of_origin_cert', 'origin'         => $passportRequest->state_origin_cert_path,
            'birth_cert', 'birth_certificate'                        => $passportRequest->birth_cert_path,
            'nin', 'nin_doc', 'nin_document'                         => $passportRequest->nin_doc_path,
            'signature'                                              => $passportRequest->signature_path,
            'previous_passport', 'prev_passport'                     => $passportRequest->previous_passport_path,
            default                                                  => null,
        };

        abort_unless($path, 404, 'Document not specified.');

        $disk = Storage::disk('public')->exists($path) ? 'public' : (Storage::disk('local')->exists($path) ? 'local' : null);
        abort_unless($disk, 404, 'Document file not found.');

        $filename = "{$passportRequest->reference}_{$type}." . pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk($disk)->download($path, $filename);
    }

    /**
     * Customer A4 PDF download.
     */
    public function downloadPdf(PassportRequest $passportRequest, RequestPdfService $pdfService): Response
    {
        if (Auth::check() && !Auth::user()->isStaff() && $passportRequest->user_id && $passportRequest->user_id !== Auth::id()) {
            abort(403);
        }

        return $pdfService->generatePassportPdf($passportRequest, Auth::user());
    }
}
