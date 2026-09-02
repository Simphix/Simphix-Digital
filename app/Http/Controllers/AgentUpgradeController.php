<?php

namespace App\Http\Controllers;

use App\Models\AgentUpgradeSettings;
use App\Models\Report;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Self-service, instant upgrade from 'personal' to 'agent' — no admin
 * review. Eligibility is a pure read-only check against wallet.balance
 * (never debited). If the upgrading user's direct upline is a 'partner',
 * that partner earns a one-time, admin-configurable commission.
 */
class AgentUpgradeController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        if ($user->role !== 'personal') {
            return view('agent-upgrade.show', [
                'user' => $user,
                'notApplicable' => true,
                'alreadyAgent' => $user->role === 'agent',
            ]);
        }

        [$eligible, $balance, $threshold] = $this->resolveEligibility($user);

        return view('agent-upgrade.show', [
            'user' => $user,
            'notApplicable' => false,
            'eligible' => $eligible,
            'balance' => $balance,
            'threshold' => $threshold,
        ]);
    }

    public function process(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'personal') {
            abort(403, 'Agent upgrade is not available for your account.');
        }

        [$eligible] = $this->resolveEligibility($user);
        if (!$eligible) {
            return back()->with('error', 'Your wallet balance does not yet meet the minimum required for an agent upgrade.');
        }

        try {
            DB::transaction(function () use ($user) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$lockedUser || $lockedUser->role !== 'personal') {
                    throw new \Exception('This account is no longer eligible for an agent upgrade.');
                }

                $settings = AgentUpgradeSettings::current();
                $wallet = Wallet::where('user_id', $lockedUser->id)->lockForUpdate()->first();
                if (!$wallet || $wallet->balance < $settings->minimum_wallet_balance) {
                    throw new \Exception('Your wallet balance no longer meets the minimum required (₦' . number_format($settings->minimum_wallet_balance, 2) . ').');
                }

                $lockedUser->update(['role' => 'agent']);

                $this->creditReferrerCommission($lockedUser, $settings);
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('agent-upgrade.show')->with('success', 'Congratulations! Your account has been upgraded to Agent.');
    }

    /** @return array{0: bool, 1: float, 2: float} [eligible, balance, threshold] */
    private function resolveEligibility(User $user): array
    {
        $threshold = (float) AgentUpgradeSettings::current()->minimum_wallet_balance;
        $balance = (float) ($user->wallet->balance ?? 0.00);

        return [$balance >= $threshold, $balance, $threshold];
    }

    /**
     * Deliberately a plain Transaction+Report, not a CommissionEarning —
     * that model is tied to per-SIM-activation semantics and doesn't fit a
     * one-time upgrade bonus with no associated SIM. Mirrors
     * EnterprisePartnerFulfillmentService::creditReferralBonus().
     */
    private function creditReferrerCommission(User $agent, AgentUpgradeSettings $settings): void
    {
        if (!$settings->is_active || $settings->partner_commission_amount <= 0.00) {
            return;
        }

        $upline = $agent->referred_by ? User::where('id', $agent->referred_by)->lockForUpdate()->first() : null;
        if (!$upline || $upline->role !== 'partner') {
            return;
        }

        $wallet = Wallet::where('user_id', $upline->id)->lockForUpdate()->first();
        if (!$wallet) {
            return;
        }

        $amount = (float) $settings->partner_commission_amount;
        $oldBalance = $wallet->bonus;
        $wallet->increment('bonus', $amount);
        $newBalance = $wallet->bonus;

        $ref = 'AGUP-' . time() . '-' . rand(1000, 9999);

        Transaction::create([
            'transaction_ref' => $ref,
            'user_id'         => $upline->id,
            'amount'          => $amount,
            'fee'             => 0.00,
            'net_amount'      => $amount,
            'description'     => "Referral commission for {$agent->first_name} {$agent->last_name} upgrading to Agent",
            'type'            => 'credit',
            'status'          => 'completed',
            'performed_by'    => 'System',
        ]);

        Report::create([
            'user_id'      => $upline->id,
            'phone_number' => $agent->phone,
            'ref'          => $ref,
            'amount'       => $amount,
            'status'       => 'completed',
            'type'         => 'agent_upgrade_referral_bonus',
            'description'  => "Referral commission for {$agent->first_name} {$agent->last_name} upgrading to Agent",
            'old_balance'  => $oldBalance,
            'new_balance'  => $newBalance,
        ]);
    }
}
