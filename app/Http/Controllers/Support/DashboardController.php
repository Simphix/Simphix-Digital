<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Sim;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Support\SimStatus;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Support desk landing page: a quick-glance view of what needs
     * attention (open tickets) plus the day's transaction/activation
     * volume, so an agent can triage before drilling into a specific
     * ticket, transaction, SIM, or user.
     */
    public function index(): View
    {
        $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)->count();
        $respondedTickets = Ticket::where('status', Ticket::STATUS_RESPONDED)->count();
        $closedTickets = Ticket::where('status', Ticket::STATUS_CLOSED)->count();

        $todayTransactionsCount = Transaction::whereDate('created_at', now()->toDateString())->count();
        $todayTransactionsVolume = Transaction::whereDate('created_at', now()->toDateString())
            ->where('status', 'completed')
            ->sum('amount');
        $failedTransactionsToday = Transaction::whereDate('created_at', now()->toDateString())
            ->where('status', 'failed')
            ->count();

        $todayActivations = Sim::whereHas('history', function ($q) {
            $q->where('to_status', SimStatus::ACTIVATED)
              ->whereDate('created_at', now()->toDateString());
        })->count();

        $failedActivationsCount = Report::where('type', 'activation_bonus_data')->where('status', 'failed')->count();

        $inventoryAvailable = Sim::where('status', SimStatus::UNASSIGNED)->count();

        $latestOpenTickets = Ticket::with('user')
            ->where('status', Ticket::STATUS_OPEN)
            ->orderBy('created_at', 'asc')
            ->take(6)
            ->get();

        return view('support-desk.dashboard', compact(
            'openTickets',
            'respondedTickets',
            'closedTickets',
            'todayTransactionsCount',
            'todayTransactionsVolume',
            'failedTransactionsToday',
            'todayActivations',
            'failedActivationsCount',
            'inventoryAvailable',
            'latestOpenTickets'
        ));
    }
}
