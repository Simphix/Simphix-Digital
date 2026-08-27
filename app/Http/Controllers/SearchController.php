<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\SimRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Perform a unified, role-based search query.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));
        $user = auth()->user();

        if (!$user) {
            return response()->json([]);
        }

        $results = [];

        if (empty($query)) {
            return response()->json($results);
        }

        if ($user->isStaff()) {
            // ==========================================
            // --- ADMIN / STAFF ROLE SEARCH ---
            // ==========================================

            // 1. Search Users
            $users = User::where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('middle_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

            foreach ($users as $u) {
                $results[] = [
                    'title' => $u->name,
                    'description' => "User (" . ucfirst($u->role) . ") • {$u->email} • {$u->phone}",
                    'url' => route('admin.manage.users.show', $u->id),
                    'icon' => 'user',
                    'category' => 'Users'
                ];
            }

            // 2. Search Upgrade Requests
            $upgrades = User::where('upgrade_status', 'pending')
                ->where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit(3)
                ->get();

            foreach ($upgrades as $upg) {
                $results[] = [
                    'title' => "Upgrade Request: {$upg->name}",
                    'description' => "Wants upgrade to " . strtoupper($upg->pending_role) . " • Requested: " . ($upg->upgrade_requested_at ? $upg->upgrade_requested_at->diffForHumans() : 'Recently'),
                    'url' => route('admin.manage.upgrades'),
                    'icon' => 'arrow-up-circle',
                    'category' => 'Upgrade Requests'
                ];
            }

            // 3. Search Support Tickets (System-wide)
            $tickets = Ticket::where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%")
                  ->orWhere('priority', 'like', "%{$query}%")
                  ->orWhere('status', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($uq) use ($query) {
                      $uq->where('email', 'like', "%{$query}%")
                         ->orWhere('first_name', 'like', "%{$query}%")
                         ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->with('user')
            ->limit(5)
            ->get();

            foreach ($tickets as $ticket) {
                $results[] = [
                    'title' => "Support Ticket: {$ticket->subject}",
                    'description' => "User: {$ticket->user->email} • Priority: " . strtoupper($ticket->priority) . " • Status: " . strtoupper($ticket->status),
                    'url' => route('admin.manage.support.show', $ticket),
                    'icon' => 'message-square',
                    'category' => 'Support Tickets'
                ];
            }

            // 4. Search SIM Requests (System-wide)
            $simRequests = SimRequest::where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                  ->orWhere('provider', 'like', "%{$query}%")
                  ->orWhere('status', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($uq) use ($query) {
                      $uq->where('email', 'like', "%{$query}%")
                         ->orWhere('first_name', 'like', "%{$query}%")
                         ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->with('user')
            ->limit(5)
            ->get();

            foreach ($simRequests as $simReq) {
                $results[] = [
                    'title' => "SIM Order: {$simReq->provider} SIM",
                    'description' => "Num: " . ($simReq->number ?? 'N/A') . " • User: {$simReq->user->email} • Status: " . strtoupper($simReq->status),
                    'url' => route('admin.sim-plan.index'),
                    'icon' => 'cpu',
                    'category' => 'SIM Orders'
                ];
            }

            // 5. Search System Transactions (System-wide)
            $transactions = Transaction::where(function ($q) use ($query) {
                $q->where('transaction_ref', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('status', 'like', "%{$query}%")
                  ->orWhere('amount', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($uq) use ($query) {
                      $uq->where('email', 'like', "%{$query}%")
                         ->orWhere('first_name', 'like', "%{$query}%")
                         ->orWhere('last_name', 'like', "%{$query}%");
                  });
            })
            ->with('user')
            ->limit(5)
            ->get();

            foreach ($transactions as $tx) {
                $results[] = [
                    'title' => "Transaction: {$tx->description}",
                    'description' => "Ref: {$tx->transaction_ref} • User: {$tx->user->email} • Amount: ₦" . number_format($tx->amount, 2) . " • Status: " . strtoupper($tx->status),
                    'url' => route('admin.transactions'),
                    'icon' => 'receipt',
                    'category' => 'System Transactions'
                ];
            }

            // 6. Search Admin Control Pages
            $adminPages = [
                ['title' => 'Manage Users', 'description' => 'View, edit, suspend or delete users.', 'url' => route('admin.manage.users'), 'icon' => 'users'],
                ['title' => 'Manage Upgrades', 'description' => 'Approve or reject account tier upgrade requests.', 'url' => route('admin.manage.upgrades'), 'icon' => 'arrow-up-circle'],
                ['title' => 'Manage Access & Roles', 'description' => 'Change access roles for system staff and users.', 'url' => route('admin.manage.access'), 'icon' => 'shield-check'],
                ['title' => 'SME Data Plans Management', 'description' => 'Add, update or delete SME data plans and bundles.', 'url' => route('admin.sme-plans.index'), 'icon' => 'wifi'],
                ['title' => 'SIM Plans & Inventory', 'description' => 'Configure SIM plans, assign numbers, upload inventory Excel.', 'url' => route('admin.sim-plan.index'), 'icon' => 'cpu'],
                ['title' => 'Services pricing & Fees', 'description' => 'Set custom profit margins and charges for services.', 'url' => route('admin.services.index'), 'icon' => 'server'],
                ['title' => 'All System Transactions', 'description' => 'Trace system-wide funding and purchases statements.', 'url' => route('admin.transactions'), 'icon' => 'receipt'],
                ['title' => 'Support Desk Tickets', 'description' => 'Reply to customer queries and solve issues.', 'url' => route('admin.manage.support.index'), 'icon' => 'message-square'],
                ['title' => 'My Profile Settings', 'description' => 'Update your password and security details.', 'url' => route('profile.edit'), 'icon' => 'user-cog'],
                ['title' => 'Super Admin Console', 'description' => 'Go to main administrative dashboard.', 'url' => route('dashboard'), 'icon' => 'layout-dashboard'],
            ];

            foreach ($adminPages as $page) {
                if (stripos($page['title'], $query) !== false || stripos($page['description'], $query) !== false) {
                    $page['category'] = 'Pages & Navigation';
                    $results[] = $page;
                }
            }
        } else {
            // ==========================================
            // --- REGULAR USER ROLE SEARCH ---
            // ==========================================

            // 1. Search Personal Virtual Accounts
            $virtualAccounts = VirtualAccount::where('user_id', $user->id)
                ->where(function ($q) use ($query) {
                    $q->where('bank_name', 'like', "%{$query}%")
                      ->orWhere('account_number', 'like', "%{$query}%");
                })
                ->limit(3)
                ->get();

            foreach ($virtualAccounts as $acc) {
                $results[] = [
                    'title' => "Virtual Account: {$acc->bank_name}",
                    'description' => "Acc No: {$acc->account_number} • Name: {$acc->account_name}",
                    'url' => route('wallet'),
                    'icon' => 'credit-card',
                    'category' => 'My Virtual Accounts'
                ];
            }

            // 2. Search My Personal Transactions (Safe & Not Sensitive)
            $myTransactions = Transaction::where('user_id', $user->id)
                ->where(function ($q) use ($query) {
                    $q->where('transaction_ref', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('amount', 'like', "%{$query}%")
                      ->orWhere('status', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get();

            foreach ($myTransactions as $tx) {
                $results[] = [
                    'title' => $tx->description,
                    'description' => "Ref: {$tx->transaction_ref} • Amount: ₦" . number_format($tx->amount, 2) . " • " . $tx->created_at->format('M d, Y') . " • Status: " . strtoupper($tx->status),
                    'url' => route('transactions'),
                    'icon' => 'receipt',
                    'category' => 'My Transactions'
                ];
            }

            // 3. Search My Support Tickets
            $myTickets = Ticket::where('user_id', $user->id)
                ->where(function ($q) use ($query) {
                    $q->where('subject', 'like', "%{$query}%")
                      ->orWhere('category', 'like', "%{$query}%")
                      ->orWhere('status', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get();

            foreach ($myTickets as $ticket) {
                $results[] = [
                    'title' => "My Ticket: {$ticket->subject}",
                    'description' => "Category: " . ucfirst($ticket->category) . " • Priority: " . strtoupper($ticket->priority) . " • Status: " . strtoupper($ticket->status),
                    'url' => route('support.show', $ticket),
                    'icon' => 'message-square',
                    'category' => 'My Support Tickets'
                ];
            }

            // 4. Search My SIM Requests
            $mySimRequests = SimRequest::where('user_id', $user->id)
                ->where(function ($q) use ($query) {
                    $q->where('number', 'like', "%{$query}%")
                      ->orWhere('provider', 'like', "%{$query}%")
                      ->orWhere('status', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get();

            foreach ($mySimRequests as $simReq) {
                $results[] = [
                    'title' => "SIM Request: {$simReq->provider}",
                    'description' => "Num: " . ($simReq->number ?? 'N/A') . " • Amount: ₦" . number_format($simReq->amount, 2) . " • Status: " . strtoupper($simReq->status),
                    'url' => route('sims.mine'),
                    'icon' => 'cpu',
                    'category' => 'My SIM Orders'
                ];
            }

            // 5. Search Static Service Operations & Action Items (Correct verified paths)
            $services = [
                ['title' => 'SIM Plans & Services', 'description' => 'Request new SIM number or perform activations.', 'url' => route('sims.index'), 'icon' => 'cpu'],
                ['title' => 'Send Cash / Transfer', 'description' => 'Perform peer-to-peer wallet transfers to other users.', 'url' => route('transfer'), 'icon' => 'send'],
                ['title' => 'Bank Withdrawal', 'description' => 'Payout money from wallet balance to your bank account.', 'url' => route('withdraw'), 'icon' => 'banknote'],
                ['title' => 'My Transactions Statements', 'description' => 'Trace your history payments and download receipt statement.', 'url' => route('transactions'), 'icon' => 'receipt'],
                ['title' => 'Support Helpdesk Tickets', 'description' => 'Get help or open support ticket for technical/billing queries.', 'url' => route('support'), 'icon' => 'message-square'],
                ['title' => 'My Wallet & Funding', 'description' => \App\Support\SimAccess::canBrowseCatalog($user) ? 'View deposit history, claim commission, and fund balance.' : 'View deposit history, claim referral bonuses, and fund balance.', 'url' => route('wallet'), 'icon' => 'wallet'],
                ['title' => 'Profile Settings & Security', 'description' => 'Edit profile info, update transaction PIN, change password.', 'url' => route('profile.edit'), 'icon' => 'user-cog'],
                ['title' => 'KYC Profile Verification', 'description' => 'Complete profile details, submit BVN/NIN info.', 'url' => route('profile.edit') . '#kyc', 'icon' => 'shield-alert'],
                ['title' => 'Request Account Upgrade', 'description' => 'Request upgrade to Agent, Business, or Partner tiers for cheaper rates.', 'url' => route('profile.edit') . '#upgrade', 'icon' => 'trending-up'],
            ];

            foreach ($services as $svc) {
                if (stripos($svc['title'], $query) !== false || stripos($svc['description'], $query) !== false) {
                    $svc['category'] = 'Services & Actions';
                    $results[] = $svc;
                }
            }
        }

        return response()->json($results);
    }
}
