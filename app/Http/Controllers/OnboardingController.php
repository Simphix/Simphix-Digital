<?php

namespace App\Http\Controllers;

use App\Mail\OnboardingInviteMail;
use App\Models\User;
use App\Models\Wallet;
use App\Support\RoleHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class OnboardingController extends Controller
{
    /**
     * Form to invite the role directly beneath the current user.
     */
    public function inviteForm(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $nextRole = RoleHierarchy::nextRole($user->role);
        if (!$nextRole) {
            abort(403, 'Your role cannot onboard a subordinate.');
        }

        return view('onboarding.invite', compact('user', 'nextRole'));
    }

    /**
     * Create the subordinate account and email a signed set-password link.
     */
    public function sendInvite(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $nextRole = RoleHierarchy::nextRole($user->role);
        if (!$nextRole) {
            abort(403, 'Your role cannot onboard a subordinate.');
        }

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone'],
        ]);

        DB::beginTransaction();
        try {
            $invitee = User::create([
                'first_name'    => $request->first_name,
                'last_name'     => $request->last_name,
                'email'         => $request->email,
                'phone'         => $request->phone,
                'password'      => Hash::make(Str::random(40)),
                'role'          => $nextRole,
                'status'        => 'invited',
                'referral_code' => strtoupper(Str::random(8)),
                'referred_by'   => $user->id,
                'limit'         => 20000.00,
            ]);

            Wallet::create([
                'user_id'        => $invitee->id,
                'balance'        => 0.00,
                'bonus'          => 0.00,
                'hold_amount'    => 0.00,
                'total_credited' => 0.00,
                'total_debited'  => 0.00,
                'wallet_number'  => 'WLT' . str_pad($invitee->id, 7, '0', STR_PAD_LEFT),
                'currency'       => 'NGN',
                'daily_limit'    => 100000.00,
                'monthly_limit'  => 1000000.00,
                'status'         => 'active',
                'is_locked'      => false,
                'last_activity'  => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Could not send invite: ' . $e->getMessage());
        }

        $acceptUrl = URL::temporarySignedRoute('onboarding.accept', now()->addDays(3), ['user' => $invitee->id]);

        Mail::to($invitee->email)->send(new OnboardingInviteMail($invitee, $user, $acceptUrl));

        return redirect()->route('network')->with('success', "Invite sent to {$invitee->email}.");
    }

    /**
     * Signed invite-acceptance page — the invitee sets their password here.
     */
    public function acceptInviteForm(Request $request, User $user)
    {
        if ($user->status !== 'invited') {
            abort(404);
        }

        return view('onboarding.accept', ['invitee' => $user]);
    }

    /**
     * Complete the invite: set password, activate, and log the invitee in.
     */
    public function completeInvite(Request $request, User $user)
    {
        if ($user->status !== 'invited') {
            abort(404);
        }

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $user->forceFill([
            'password'          => Hash::make($request->password),
            'status'            => 'active',
            'email_verified_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome! Your account is ready.');
    }
}
