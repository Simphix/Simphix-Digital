<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Sim;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * User lookup for the support desk — by unique identifier only (exact
 * email, exact phone, or account id), never a general browsable list. No
 * search means no query at all, so the page never reveals who else is on
 * the platform. Deliberately does not reuse admin.manage.show_user for the
 * detail page — that page embeds PND/suspend/reinstate/lien mutation
 * buttons, which are out of scope here; this controller only ever hands
 * the view data to display.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $users = collect();

        if ($search !== '') {
            $users = User::where('email', $search)
                ->orWhere('phone', $search)
                ->when(ctype_digit($search), fn ($q) => $q->orWhere('id', $search))
                ->limit(5)
                ->get();
        }

        return view('support-desk.users.index', compact('search', 'users'));
    }

    public function show(User $user): View
    {
        $user->load(['wallet', 'virtualAccounts']);

        $sims = Sim::where('user_id', $user->id)->orderBy('updated_at', 'desc')->get();
        $recentTransactions = Transaction::where('user_id', $user->id)->orderBy('created_at', 'desc')->take(10)->get();
        $recentTickets = Ticket::where('user_id', $user->id)->orderBy('created_at', 'desc')->take(5)->get();

        return view('support-desk.users.show', compact('user', 'sims', 'recentTransactions', 'recentTickets'));
    }
}
