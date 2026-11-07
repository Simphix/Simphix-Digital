<?php

namespace App\Http\Controllers;

use App\Models\MoniepointPosRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MoniepointPosController extends Controller
{
    /**
     * Show Moniepoint POS terminals catalog & request portal.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $userRequests = $user ? MoniepointPosRequest::where('user_id', $user->id)->latest()->take(5)->get() : collect();

        $selectedType = $request->query('type', 'smart_pos');

        return view('moniepoint.pos.index', [
            'user'         => $user,
            'userRequests' => $userRequests,
            'selectedType' => $selectedType,
        ]);
    }

    /**
     * Show dedicated POS terminal application page.
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $selectedType = $request->query('type', 'smart_pos');
        if (! array_key_exists($selectedType, MoniepointPosRequest::POS_TYPES)) {
            $selectedType = 'smart_pos';
        }

        return view('moniepoint.pos.create', [
            'user'         => $user,
            'selectedType' => $selectedType,
        ]);
    }

    /**
     * Submit a Moniepoint POS terminal request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'passport_photo'      => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
            'name'                => ['required', 'string', 'max:255'],
            'phone'               => ['required', 'string', 'max:30'],
            'email'               => ['required', 'email', 'max:255'],
            'bvn'                 => ['nullable', 'string', 'max:20'],
            'nin'                 => ['nullable', 'string', 'max:20'],
            'shop_address'        => ['nullable', 'string', 'max:500'],
            'state'               => ['nullable', 'string', 'max:100'],
            'lga'                 => ['nullable', 'string', 'max:100'],
            'home_address'        => ['nullable', 'string', 'max:500'],
            'next_of_kin_name'    => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone'   => ['nullable', 'string', 'max:30'],
            'next_of_kin_address' => ['nullable', 'string', 'max:500'],
            'referral_name'       => ['nullable', 'string', 'max:255'],
            'referral_phone'      => ['nullable', 'string', 'max:30'],
            'pos_type'            => ['required', 'string', 'in:' . implode(',', array_keys(MoniepointPosRequest::POS_TYPES))],
            'business_name'       => ['nullable', 'string', 'max:255'],
            'delivery_address'    => ['nullable', 'string', 'max:1000'],
            'quantity'            => ['nullable', 'integer', 'min:1', 'max:100'],
            'notes'               => ['nullable', 'string', 'max:2000'],
        ]);

        $passportPath = null;
        if ($request->hasFile('passport_photo')) {
            $passportPath = $request->file('passport_photo')->store('pos-passports', 'public');
        }

        $deliveryAddress = $validated['delivery_address']
            ?? ($validated['shop_address'] ?? ($validated['home_address'] ?? null));

        $posRequest = MoniepointPosRequest::create([
            'reference'           => MoniepointPosRequest::generateReference(),
            'user_id'             => Auth::id(),
            'name'                => trim($validated['name']),
            'phone'               => trim($validated['phone']),
            'email'               => trim($validated['email']),
            'passport_photo'      => $passportPath,
            'bvn'                 => ! empty($validated['bvn']) ? trim($validated['bvn']) : null,
            'nin'                 => ! empty($validated['nin']) ? trim($validated['nin']) : null,
            'shop_address'        => ! empty($validated['shop_address']) ? trim($validated['shop_address']) : null,
            'state'               => ! empty($validated['state']) ? trim($validated['state']) : null,
            'lga'                 => ! empty($validated['lga']) ? trim($validated['lga']) : null,
            'home_address'        => ! empty($validated['home_address']) ? trim($validated['home_address']) : null,
            'next_of_kin_name'    => ! empty($validated['next_of_kin_name']) ? trim($validated['next_of_kin_name']) : null,
            'next_of_kin_phone'   => ! empty($validated['next_of_kin_phone']) ? trim($validated['next_of_kin_phone']) : null,
            'next_of_kin_address' => ! empty($validated['next_of_kin_address']) ? trim($validated['next_of_kin_address']) : null,
            'referral_name'       => ! empty($validated['referral_name']) ? trim($validated['referral_name']) : null,
            'referral_phone'      => ! empty($validated['referral_phone']) ? trim($validated['referral_phone']) : null,
            'pos_type'            => $validated['pos_type'],
            'business_name'       => $validated['business_name'] ?? null,
            'delivery_address'    => $deliveryAddress,
            'quantity'            => (int) ($validated['quantity'] ?? 1),
            'notes'               => $validated['notes'] ?? null,
            'status'              => MoniepointPosRequest::STATUS_PENDING,
        ]);

        return redirect()->route('moniepoint.pos.tracking', ['ref' => $posRequest->reference])
            ->with('success', "Your POS request has been submitted successfully! Your tracking reference code is {$posRequest->reference}. Our deployment agent will contact you shortly.");
    }

    /**
     * Track a Moniepoint POS request by reference code or view user history.
     */
    public function tracking(Request $request): View
    {
        $user = Auth::user();
        $queryRef = trim((string) $request->query('ref'));
        $searchPhone = trim((string) $request->query('phone'));

        $matchedRequest = null;
        if ($queryRef !== '') {
            $matchedRequest = MoniepointPosRequest::where('reference', $queryRef)->first();
        } elseif ($searchPhone !== '') {
            $matchedRequest = MoniepointPosRequest::where('phone', 'like', "%{$searchPhone}%")->latest()->first();
        }

        $myRequests = collect();
        if ($user) {
            $myRequests = MoniepointPosRequest::where('user_id', $user->id)
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return view('moniepoint.pos.tracking', [
            'user'           => $user,
            'queryRef'       => $queryRef,
            'matchedRequest' => $matchedRequest,
            'myRequests'     => $myRequests,
        ]);
    }
}
