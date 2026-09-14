<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Transaction lookup for the support desk — by unique identifier only
 * (exact transaction reference or id, or a customer's exact email/phone to
 * pull up their own transaction history). No search means no query at
 * all: there is no default listing and no platform-wide totals here, so
 * this page never reveals general transaction volume/activity — only what
 * an agent already has an identifier for. See Admin\TransactionController
 * for the unrestricted, browsable version used by super_admin.
 */
class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $transactions = collect();

        if ($search !== '') {
            $transactions = Transaction::with('user')
                ->where(function ($q) use ($search) {
                    $q->where('transaction_ref', $search)
                      ->when(ctype_digit($search), fn ($q2) => $q2->orWhere('id', $search))
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('email', $search)->orWhere('phone', $search);
                      });
                })
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        }

        return view('support-desk.transactions.index', compact('search', 'transactions'));
    }
}
