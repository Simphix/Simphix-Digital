<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KycController extends Controller
{

    /**
     * Handle the submission of KYC information.
     */
    public function submit(Request $request): RedirectResponse
    {
        $user = Auth::user();

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $request->validate([
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone'       => [
                'required',
                'string',
                'max:20',
                \App\Support\PhoneNumber::validationRule(),
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'state'       => ['required', 'string', 'max:255'],
            'lga'         => ['required', 'string', 'max:255'],
            'address'     => ['required', 'string', 'max:500'],
            'agree'       => ['required', 'accepted'],
        ], [
            'agree.accepted' => 'You must agree to the service agreement to proceed.',
        ]);

        $user->forceFill([
            'first_name'   => $request->first_name,
            'middle_name'  => $request->middle_name,
            'last_name'    => $request->last_name,
            'phone'        => $request->phone,
            'state'        => $request->state,
            'lga'          => $request->lga,
            'address'      => $request->address,
            'account_tier' => 1, // Upgrade to Basic KYC tier
        ])->save();

        // Dispatch Welcome Email Notification
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\KycWelcomeMail($user));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('KYC welcome email failed to send: ' . $e->getMessage());
        }

        return redirect()->route('dashboard')
            ->with('success', 'Your KYC profile has been completed and verified successfully!')
            ->with('welcome', "Welcome aboard, {$user->first_name}! Your account has been verified to Tier 1 status. Explore our top-tier data packages, instant wallet funding, and swift utility bill payments. Experience why SimPhix is the best in the market!");
    }
}
