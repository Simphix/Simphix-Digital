<?php

namespace App\Http\Controllers;

use App\Mail\OnboardingInviteMail;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
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
use Illuminate\Validation\Rule;
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

        $region = null;
        $states = collect();
        $districts = collect();

        if ($nextRole === 'coordinator') {
            $region = $user->regionAsManager;
            if ($region) {
                $states = $region->states()->orderBy('name')->get();
                $districts = SenatorialDistrict::whereIn('state_id', $states->pluck('id'))
                    ->whereNull('coordinator_id')
                    ->with('state')
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('onboarding.invite', compact('user', 'nextRole', 'region', 'states', 'districts'));
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

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone'],
        ];

        $region = null;
        $regionStateIds = [];

        if ($nextRole === 'coordinator') {
            $region = $user->regionAsManager;
            if (!$region) {
                return back()->withInput()->with('error', 'You must have a region assigned to your account before onboarding a coordinator.');
            }

            $regionStateIds = $region->states()->pluck('id')->all();
            if (empty($regionStateIds)) {
                return back()->withInput()->with('error', 'Your region does not have any states assigned yet.');
            }

            $territoryMode = $request->input('territory_mode', 'existing');

            if ($territoryMode === 'new') {
                $rules['new_district_name'] = ['required', 'string', 'max:255', 'unique:senatorial_districts,name'];
                $rules['new_district_state_id'] = [
                    'required',
                    'integer',
                    Rule::in($regionStateIds),
                ];
            } else {
                $rules['district_id'] = [
                    'required',
                    'integer',
                    Rule::exists('senatorial_districts', 'id')->where(function ($query) use ($regionStateIds) {
                        $query->whereIn('state_id', $regionStateIds)
                              ->whereNull('coordinator_id');
                    }),
                ];
            }
        }

        $request->validate($rules, [
            'district_id.required' => 'Please select a senatorial district to assign.',
            'district_id.exists' => 'The selected district is invalid, already assigned to another coordinator, or outside your region.',
            'new_district_name.required' => 'Please enter a name for the new district.',
            'new_district_name.unique' => 'A senatorial district with this name already exists.',
            'new_district_state_id.required' => 'Please select a state under your region for the new district.',
            'new_district_state_id.in' => 'The selected state does not belong to your managed region.',
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

            $assignedDistrict = null;
            if ($nextRole === 'coordinator') {
                if ($request->input('territory_mode') === 'new') {
                    $assignedDistrict = SenatorialDistrict::create([
                        'name' => trim($request->new_district_name),
                        'state_id' => $request->new_district_state_id,
                        'coordinator_id' => $invitee->id,
                    ]);
                } else {
                    $assignedDistrict = SenatorialDistrict::where('id', $request->district_id)
                        ->whereIn('state_id', $regionStateIds)
                        ->whereNull('coordinator_id')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $assignedDistrict->update([
                        'coordinator_id' => $invitee->id,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Could not send invite: ' . $e->getMessage());
        }

        $acceptUrl = URL::temporarySignedRoute('onboarding.accept', now()->addDays(3), ['user' => $invitee->id]);

        Mail::to($invitee->email)->send(new OnboardingInviteMail($invitee, $user, $acceptUrl));

        $districtNotice = $assignedDistrict ? " and assigned to {$assignedDistrict->name} district" : '';

        return redirect()->route('network')->with('success', "Invite sent to {$invitee->email}{$districtNotice}.");
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
