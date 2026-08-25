<?php

namespace App\Http\Controllers;

use App\Models\CommissionEarning;
use App\Models\Wallet;
use App\Support\SimAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionDashboardController extends Controller
{
    /**
     * The user's own commission earnings history; admins see every payout.
     * Personal accounts never earn SIM commissions, so they don't get this
     * page. Enterprise Partners (business) additionally see a downline
     * breakdown of what each business_agent kept vs. their own margin.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }
        if (!SimAccess::canBrowseCatalog($user) && !$user->hasRole('super_admin')) {
            abort(403, 'Commissions are only available to Regional Managers, Coordinators, Partners, Agents, Enterprise Partners, and Business Agents.');
        }

        if ($user->hasRole('super_admin')) {
            $earnings = CommissionEarning::with(['user', 'sim'])->latest()->paginate(20);
            $totalPaid = CommissionEarning::sum('amount');

            // Commission is credited straight into wallet.bonus (see
            // AwardCommissions) and only becomes spendable/withdrawable once
            // the user claims it via WalletController::claimBonus(), which
            // moves it into wallet.balance and zeroes bonus back out. So the
            // current bonus total across catalog roles *is* exactly
            // "commission earned but not yet withdrawn" — scoped to catalog
            // roles since personal/business accounts use the same bonus
            // column for an unrelated referral reward, not SIM commission.
            $totalPending = Wallet::whereHas('user', fn ($q) => $q->whereIn('role', SimAccess::CATALOG_ROLES))->sum('bonus');
        } else {
            $earnings = CommissionEarning::with('sim')->where('user_id', $user->id)->latest()->paginate(20);
            $totalPaid = CommissionEarning::where('user_id', $user->id)->sum('amount');
            $totalPending = Wallet::where('user_id', $user->id)->value('bonus') ?? 0.00;
        }

        $downlineBreakdown = collect();
        if ($user->role === 'business') {
            $simIds = $earnings->pluck('sim_id');
            $agentEarnings = CommissionEarning::with('user')
                ->where('role', 'business_agent')
                ->whereIn('sim_id', $simIds)
                ->get()
                ->keyBy('sim_id');

            $downlineBreakdown = $earnings->getCollection()->mapWithKeys(function (CommissionEarning $earning) use ($agentEarnings) {
                $agentEarning = $agentEarnings->get($earning->sim_id);

                return [$earning->id => [
                    'agent_name' => $agentEarning?->user ? trim($agentEarning->user->first_name . ' ' . $agentEarning->user->last_name) : null,
                    'agent_amount' => $agentEarning ? (float) $agentEarning->amount : 0.00,
                    'ep_amount' => (float) $earning->amount,
                    'price' => (float) $earning->amount + ($agentEarning ? (float) $agentEarning->amount : 0.00),
                ]];
            });
        }

        return view('commissions.dashboard', compact('user', 'earnings', 'totalPaid', 'totalPending', 'downlineBreakdown'));
    }
}
