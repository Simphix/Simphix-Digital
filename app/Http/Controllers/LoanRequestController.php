<?php

namespace App\Http\Controllers;

use App\Models\LoanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoanRequestController extends Controller
{
    /**
     * Show Business Loans portal (with Moniepoint business owner disclaimer).
     */
    public function index(): View
    {
        $user = Auth::user();
        $userRequests = $user ? LoanRequest::where('user_id', $user->id)->latest()->take(5)->get() : collect();

        return view('loans.index', [
            'user'         => $user,
            'userRequests' => $userRequests,
        ]);
    }

    /**
     * Show the Business Loan application form page.
     */
    public function create(): View
    {
        $user = Auth::user();

        return view('loans.create', [
            'user' => $user,
        ]);
    }

    /**
     * Submit a loan request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'phone'                     => ['required', 'string', 'max:30'],
            'email'                     => ['nullable', 'email', 'max:255'],
            'moniepoint_account_number' => ['required', 'string', 'max:30'],
            'amount_requested'          => ['required', 'numeric', 'min:1000', 'max:50000000'],
            'business_name'             => ['nullable', 'string', 'max:255'],
            'loan_tenure'               => ['nullable', 'string', 'in:' . implode(',', array_keys(LoanRequest::TENURES))],
            'purpose'                   => ['nullable', 'string', 'max:2000'],
            'referral_name'             => ['nullable', 'string', 'max:255'],
            'referral_phone'            => ['nullable', 'string', 'max:30'],
        ]);

        $loanRequest = LoanRequest::create([
            'reference'                 => LoanRequest::generateReference(),
            'user_id'                   => Auth::id(),
            'name'                      => trim($validated['name']),
            'phone'                     => trim($validated['phone']),
            'email'                     => !empty($validated['email']) ? trim($validated['email']) : null,
            'moniepoint_account_number' => trim($validated['moniepoint_account_number']),
            'amount_requested'          => $validated['amount_requested'],
            'business_name'             => $validated['business_name'] ?? null,
            'loan_tenure'               => $validated['loan_tenure'] ?? null,
            'purpose'                   => $validated['purpose'] ?? null,
            'referral_name'             => !empty($validated['referral_name']) ? trim($validated['referral_name']) : null,
            'referral_phone'            => !empty($validated['referral_phone']) ? trim($validated['referral_phone']) : null,
            'status'                    => LoanRequest::STATUS_PENDING,
        ]);

        return redirect()->route('loans.tracking', ['ref' => $loanRequest->reference])
            ->with('success', "Your business loan application has been submitted successfully! Your tracking reference code is {$loanRequest->reference}. Our credit assessment desk will verify your Moniepoint account and contact you.");
    }

    /**
     * Track a loan request by reference or phone.
     */
    public function tracking(Request $request): View
    {
        $user = Auth::user();
        $queryRef = trim((string) $request->query('ref'));
        $searchPhone = trim((string) $request->query('phone'));

        $matchedRequest = null;
        if ($queryRef !== '') {
            $matchedRequest = LoanRequest::where('reference', $queryRef)->first();
        } elseif ($searchPhone !== '') {
            $matchedRequest = LoanRequest::where('phone', 'like', "%{$searchPhone}%")->latest()->first();
        }

        $myRequests = collect();
        if ($user) {
            $myRequests = LoanRequest::where('user_id', $user->id)
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return view('loans.tracking', [
            'user'           => $user,
            'queryRef'       => $queryRef,
            'matchedRequest' => $matchedRequest,
            'myRequests'     => $myRequests,
        ]);
    }
}
