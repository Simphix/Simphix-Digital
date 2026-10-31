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
     * Submit a Moniepoint POS terminal request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'phone'            => ['required', 'string', 'max:30'],
            'email'            => ['required', 'email', 'max:255'],
            'pos_type'         => ['required', 'string', 'in:' . implode(',', array_keys(MoniepointPosRequest::POS_TYPES))],
            'business_name'    => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'quantity'         => ['nullable', 'integer', 'min:1', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $posRequest = MoniepointPosRequest::create([
            'reference'        => MoniepointPosRequest::generateReference(),
            'user_id'          => Auth::id(),
            'name'             => trim($validated['name']),
            'phone'            => trim($validated['phone']),
            'email'            => trim($validated['email']),
            'pos_type'         => $validated['pos_type'],
            'business_name'    => $validated['business_name'] ?? null,
            'delivery_address' => $validated['delivery_address'] ?? null,
            'quantity'         => (int) ($validated['quantity'] ?? 1),
            'notes'            => $validated['notes'] ?? null,
            'status'           => MoniepointPosRequest::STATUS_PENDING,
        ]);

        return redirect()->route('moniepoint.pos.tracking', ['ref' => $posRequest->reference])
            ->with('success', "Your Moniepoint POS request has been submitted successfully! Your tracking reference code is {$posRequest->reference}. Our deployment agent will contact you shortly.");
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
