<?php

namespace App\Http\Controllers;

use App\Models\SimHistory;
use App\Models\User;
use App\Models\Wallet;
use App\Models\VirtualAccount;
use App\Models\Transaction;
use App\Models\SimRequest;
use App\Models\Ticket;
use App\Support\SimStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the user or admin dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user is super_admin
        if ($user->hasRole('super_admin')) {
            return $this->adminDashboard($request);
        }

        // Support staff have their own read-mostly console — see
        // Support\DashboardController — never the personal wallet dashboard.
        if ($user->hasRole('support')) {
            return redirect()->route('support-desk.dashboard');
        }

        return $this->userDashboard($user);
    }

    /**
     * Handle user dashboard metrics & rendering.
     */
    protected function userDashboard($user)
    {
        // 1. Wallet Balance and Bonus
        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletData = [
            'balance' => $wallet->balance ?? 0.00,
            'bonus'   => $wallet->bonus ?? 0.00,
            'status'  => $wallet->status ?? 'inactive',
        ];

        // 2. Virtual Account Details
        $virtualAccount = VirtualAccount::where('user_id', $user->id)->first();

        // 3. Last 10 Recent Transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // 4. Transaction Statistics
        $totalCredits = Transaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalDebits = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->sum('amount');

        $completedCount = Transaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Compile 7-day daily activity array for visual SVG charts
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayLabel = now()->subDays($i)->format('D');

            $creditSum = Transaction::where('user_id', $user->id)
                ->where('type', 'credit')
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $debitSum = Transaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $chartData[] = [
                'day'     => $dayLabel,
                'credits' => (float)$creditSum,
                'debits'  => (float)$debitSum,
            ];
        }

        // Spend Progress (target 50,000 for bonuses)
        $currentSpend = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount') ?? 0;

        $nextBonusTarget = 50000;
        $bonusProgress = $nextBonusTarget > 0 ? min(100, round(($currentSpend / $nextBonusTarget) * 100)) : 0;

        return view('dashboard', compact(
            'walletData',
            'virtualAccount',
            'recentTransactions',
            'totalCredits',
            'totalDebits',
            'completedCount',
            'chartData',
            'currentSpend',
            'nextBonusTarget',
            'bonusProgress'
        ));
    }

    /**
     * Handle super admin dashboard metrics & rendering.
     */
    protected function adminDashboard(Request $request)
    {
        // Time-range filter for the two ranged KPI cards (Transaction Volume,
        // SIM Activations) below. Other cards are current-state snapshots
        // (user counts, wallet balances, pending counts) and aren't
        // meaningfully rangeable, so they stay all-time regardless of this.
        $range = $request->input('range', '7days');
        [$rangeStart, $rangeEnd] = match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '3days' => [now()->subDays(2)->startOfDay(), now()->endOfDay()],
            'custom' => [
                $request->filled('period_start') ? Carbon::parse($request->period_start)->startOfDay() : now()->subDays(6)->startOfDay(),
                $request->filled('period_end') ? Carbon::parse($request->period_end)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()], // 7days
        };
        $periodStart = $rangeStart->format('Y-m-d');
        $periodEnd = $rangeEnd->format('Y-m-d');

        // 1. System Statistics / Metrics
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();

        $totalWalletBalance = Wallet::sum('balance') ?? 0.00;
        $totalWalletBonus = Wallet::sum('bonus') ?? 0.00;

        $totalTransactionsCount = Transaction::where('status', 'completed')->whereBetween('created_at', [$rangeStart, $rangeEnd])->count();
        $totalTransactionVolume = Transaction::where('status', 'completed')->whereBetween('created_at', [$rangeStart, $rangeEnd])->sum('amount') ?? 0.00;

        $simActivationsCount = SimHistory::where('to_status', SimStatus::ACTIVATED)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->count();

        $pendingUpgradesCount = User::where('upgrade_status', 'pending')->count();
        $pendingSimRequestsCount = SimRequest::where('status', 'pending')->count();
        $openTicketsCount = Ticket::where('status', 'open')->count();

        // 2. Recent Lists for admin view
        $recentTransactions = Transaction::with('user')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $pendingUpgrades = User::where('upgrade_status', 'pending')
            ->orderBy('upgrade_requested_at', 'asc')
            ->take(5)
            ->get();

        $openTickets = Ticket::with('user')
            ->where('status', 'open')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $pendingSimRequests = SimRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Breakdown of users by role
        $roleBreakdown = [
            'personal' => User::where('role', 'personal')->count(),
            'agent'    => User::where('role', 'agent')->count(),
            'partner'  => User::where('role', 'partner')->count(),
            'business' => User::where('role', 'business')->count(),
            'admins'   => User::whereIn('role', ['super_admin', 'staff', 'checker'])->count(),
        ];

        return view('admin.admin_dashboard', compact(
            'totalUsers',
            'activeUsers',
            'totalWalletBalance',
            'totalWalletBonus',
            'totalTransactionsCount',
            'totalTransactionVolume',
            'simActivationsCount',
            'range',
            'periodStart',
            'periodEnd',
            'pendingUpgradesCount',
            'pendingSimRequestsCount',
            'openTicketsCount',
            'recentTransactions',
            'pendingUpgrades',
            'openTickets',
            'pendingSimRequests',
            'roleBreakdown'
        ));
    }
}
