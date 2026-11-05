<?php

namespace App\Http\Controllers;

use App\Mail\KycWelcomeMail;
use App\Models\LeaderboardTier;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Repositories\VirtualAccountRepository;
use App\Support\DownlineAuthorization;
use App\Support\DownlineMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * The team-member detail page reached from Network > Team — Info, Metrics,
 * Tier, and Actions tabs for one specific downline user. Every method here
 * is scoped to a $user route-model-bound target, never Auth::user(), and
 * every entry point is gated by DownlineAuthorization::authorize() (own
 * immediate downline only, or super_admin).
 */
class DownlineDetailController extends Controller
{
    public function show(User $user)
    {
        DownlineAuthorization::authorize($user);

        $user->loadMissing('wallet');
        $wallet = $user->wallet ?? Wallet::firstOrCreateForUser($user->id);
        $virtualAccount = VirtualAccount::where('user_id', $user->id)->first();

        $metrics = DownlineMetrics::forUser($user);

        // Tier progress only applies to partners (the leaderboard's only ranked role).
        $currentTier = $nextTier = $progressPercent = null;
        if ($user->role === 'partner') {
            $currentTier = LeaderboardTier::resolveForCount($metrics['periodActivations']);
            $nextTier = LeaderboardTier::nextTier($metrics['periodActivations']);
            $progressPercent = $nextTier
                ? min(100, (int) round(($metrics['periodActivations'] / $nextTier->activation_target) * 100))
                : ($currentTier ? 100 : 0);
        }

        $coordinatorDistrict = null;
        $availableDistricts = collect();
        $regionStates = collect();
        $actorRegion = null;
        $canManageTerritory = false;

        $actor = auth()->user();
        if ($user->role === 'coordinator') {
            $coordinatorDistrict = $user->districtAsCoordinator()->with(['state.region'])->first();
            $canManageTerritory = ($actor && ($actor->role === 'regional_manager' || $actor->hasRole('super_admin')));

            if ($canManageTerritory) {
                if ($actor->role === 'regional_manager') {
                    $actorRegion = $actor->regionAsManager;
                } elseif ($actor->hasRole('super_admin')) {
                    $actorRegion = $user->referrer?->regionAsManager ?? $coordinatorDistrict?->state?->region;
                }

                if ($actorRegion) {
                    $regionStates = $actorRegion->states()->orderBy('name')->get();
                    $availableDistricts = SenatorialDistrict::whereIn('state_id', $regionStates->pluck('id'))
                        ->where(function ($q) use ($user) {
                            $q->whereNull('coordinator_id')
                              ->orWhere('coordinator_id', $user->id);
                        })
                        ->with('state')
                        ->orderBy('name')
                        ->get();
                } else {
                    $regionStates = State::orderBy('name')->get();
                    $availableDistricts = SenatorialDistrict::where(function ($q) use ($user) {
                            $q->whereNull('coordinator_id')
                              ->orWhere('coordinator_id', $user->id);
                        })
                        ->with('state')
                        ->orderBy('name')
                        ->get();
                }
            }
        }

        return view('network.downline-show', array_merge($metrics, compact(
            'user',
            'wallet',
            'virtualAccount',
            'currentTier',
            'nextTier',
            'progressPercent',
            'coordinatorDistrict',
            'availableDistricts',
            'regionStates',
            'actorRegion',
            'canManageTerritory'
        )));
    }

    /**
     * Upline fills the mandatory profile fields on behalf of a downline user
     * who hasn't cleared the pop-up onboarding modal yet (account_tier = 0
     * — the same signal layouts/app.blade.php checks to force that modal).
     * Mirrors KycController::submit() exactly, minus the "agree to terms"
     * checkbox — that's the account holder's own attestation, not the
     * upline's to give.
     */
    public function completeOnboarding(Request $request, User $user)
    {
        DownlineAuthorization::authorize($user);

        if ($user->account_tier > 0) {
            return back()->with('error', "{$user->first_name} {$user->last_name} has already completed onboarding.");
        }

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $validated = $request->validate([
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone,' . $user->id],
            'state'       => ['required', 'string', 'max:255'],
            'lga'         => ['required', 'string', 'max:255'],
            'address'     => ['required', 'string', 'max:500'],
        ]);

        $user->forceFill($validated + ['account_tier' => 1])->save();

        try {
            Mail::to($user->email)->send(new KycWelcomeMail($user));
        } catch (\Exception $e) {
            Log::warning('KYC welcome email failed to send: ' . $e->getMessage());
        }

        return back()->with('success', "Onboarding completed for {$user->first_name} {$user->last_name}.");
    }

    /**
     * Upline generates a virtual funding account for a downline user, same
     * BVN/NIN flow the user would use themselves (WalletController::createWallet()),
     * scoped to $user instead of Auth::user(). VirtualAccountRepository
     * already accepts an arbitrary user id — no repository changes needed.
     */
    public function generateVirtualAccount(Request $request, User $user)
    {
        DownlineAuthorization::authorize($user);

        if (VirtualAccount::where('user_id', $user->id)->exists()) {
            return back()->with('error', "{$user->first_name} {$user->last_name} already has a virtual account.");
        }

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $validated = $request->validate([
            'phone'         => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone,' . $user->id],
            'identity_type' => 'required|in:bvn,nin',
            'bvn'           => 'required_if:identity_type,bvn|nullable|string|digits:11|unique:users,bvn,' . $user->id,
            'nin'           => 'required_if:identity_type,nin|nullable|string|digits:11|unique:users,nin,' . $user->id,
        ]);

        $identityType = $validated['identity_type'];

        $user->phone = $validated['phone'];
        if ($identityType === 'nin') {
            $user->nin = $validated['nin'];
        } else {
            $user->bvn = $validated['bvn'];
        }
        $user->save();

        $result = app(VirtualAccountRepository::class)->createVirtualAccount($user->id, $identityType);

        if (!is_array($result) || empty($result['success'])) {
            $message = is_array($result) && isset($result['message']) ? $result['message'] : 'Virtual account creation failed. Please try again later.';

            return back()->with('error', $message);
        }

        return back()->with('success', "Virtual account generated for {$user->first_name} {$user->last_name}.");
    }

    /**
     * Assign or reassign a senatorial district territory to a coordinator.
     */
    public function assignDistrict(Request $request, User $user)
    {
        $actor = DownlineAuthorization::authorize($user);

        if (!$actor->hasRole('regional_manager') && !$actor->hasRole('super_admin')) {
            abort(403, 'Only regional managers and administrators can assign territories.');
        }

        if ($user->role !== 'coordinator') {
            return back()->with('error', 'Territory can only be assigned to a coordinator.');
        }

        $actorRegion = null;
        if ($actor->role === 'regional_manager') {
            $actorRegion = $actor->regionAsManager;
            if (!$actorRegion) {
                return back()->with('error', 'You do not have a managed region assigned to your account.');
            }
        } elseif ($actor->hasRole('super_admin')) {
            $actorRegion = $user->referrer?->regionAsManager ?? $user->districtAsCoordinator?->state?->region;
        }

        $regionStateIds = $actorRegion ? $actorRegion->states()->pluck('id')->all() : [];

        $territoryMode = $request->input('territory_mode', 'existing');

        if ($territoryMode === 'new') {
            $rules = [
                'new_district_name' => ['required', 'string', 'max:255', 'unique:senatorial_districts,name'],
                'new_district_state_id' => ['required', 'exists:states,id'],
            ];

            if (!empty($regionStateIds)) {
                $rules['new_district_state_id'][] = Rule::in($regionStateIds);
            }

            $validated = $request->validate($rules, [
                'new_district_name.unique' => 'A senatorial district with this name already exists.',
                'new_district_state_id.in' => 'The selected state must belong to your region.',
            ]);

            DB::transaction(function () use ($user, $validated) {
                SenatorialDistrict::where('coordinator_id', $user->id)->update(['coordinator_id' => null]);

                SenatorialDistrict::create([
                    'name' => trim($validated['new_district_name']),
                    'state_id' => $validated['new_district_state_id'],
                    'coordinator_id' => $user->id,
                ]);
            });

            return back()->with('success', "Senatorial district '{$validated['new_district_name']}' created and assigned to {$user->first_name} {$user->last_name}.");
        }

        // Existing district mode
        $validated = $request->validate([
            'district_id' => ['required', 'exists:senatorial_districts,id'],
        ], [
            'district_id.required' => 'Please select a senatorial district to assign.',
        ]);

        $district = SenatorialDistrict::with('state')->findOrFail($validated['district_id']);

        if (!empty($regionStateIds) && !in_array($district->state_id, $regionStateIds, true)) {
            return back()->with('error', 'The selected district does not belong to your managed region.');
        }

        if ($district->coordinator_id && $district->coordinator_id !== $user->id) {
            return back()->with('error', 'This senatorial district is already assigned to another coordinator.');
        }

        DB::transaction(function () use ($user, $district) {
            SenatorialDistrict::where('coordinator_id', $user->id)
                ->where('id', '!=', $district->id)
                ->update(['coordinator_id' => null]);

            $district->update(['coordinator_id' => $user->id]);
        });

        return back()->with('success', "Senatorial district '{$district->name}' assigned to {$user->first_name} {$user->last_name}.");
    }

    /**
     * Unassign a senatorial district territory from a coordinator.
     */
    public function unassignDistrict(Request $request, User $user)
    {
        $actor = DownlineAuthorization::authorize($user);

        if (!$actor->hasRole('regional_manager') && !$actor->hasRole('super_admin')) {
            abort(403, 'Only regional managers and administrators can unassign territories.');
        }

        if ($user->role !== 'coordinator') {
            return back()->with('error', 'Territory can only be unassigned from a coordinator.');
        }

        $district = $user->districtAsCoordinator()->with('state')->first();
        if (!$district) {
            return back()->with('error', "{$user->first_name} {$user->last_name} does not have an assigned senatorial district.");
        }

        if ($actor->role === 'regional_manager') {
            $actorRegion = $actor->regionAsManager;
            if ($actorRegion && $district->state?->region_id !== $actorRegion->id) {
                return back()->with('error', 'This district does not belong to your managed region.');
            }
        }

        $district->update(['coordinator_id' => null]);

        return back()->with('success', "Senatorial district '{$district->name}' unassigned from {$user->first_name} {$user->last_name}.");
    }
}
