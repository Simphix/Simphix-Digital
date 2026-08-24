<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformCashOutRequest;
use App\Models\PlatformWallet;
use App\Models\PlatformWithdrawalAccount;
use App\Services\PlatformWalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformWalletCashOutController extends Controller
{
    public function show(string $walletType)
    {
        $wallet = PlatformWallet::where('type', $walletType)->firstOrFail();
        $account = PlatformWithdrawalAccount::current();

        return view('admin.platform-wallets.cashout', compact('wallet', 'account'));
    }

    public function store(Request $request, string $walletType)
    {
        $wallet = PlatformWallet::where('type', $walletType)->firstOrFail();
        $account = PlatformWithdrawalAccount::current();

        if (!$account) {
            return back()->with('error', 'Add a platform withdrawal account before requesting a cash out.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $amount = (float) $validated['amount'];

        if ($wallet->balance < $amount) {
            return back()->with('error', 'Requested amount exceeds the wallet balance.')->withInput();
        }

        try {
            $walletTransaction = app(PlatformWalletLedger::class)->debit(
                $walletType,
                $amount,
                'cashout_debit',
                'cashout',
                "Cash-out request for {$wallet->name}"
            );

            PlatformCashOutRequest::create([
                'platform_wallet_id' => $wallet->id,
                'platform_wallet_transaction_id' => $walletTransaction->id,
                'bank_code' => $account->bank_code,
                'bank_name' => $account->bank_name,
                'account_no' => $account->account_no,
                'account_name' => $account->account_name,
                'amount' => $amount,
                'status' => 'pending',
                'requested_by' => Auth::id(),
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('admin.platform-wallets.approvals.index')
            ->with('success', 'Cash-out request submitted for approval.');
    }
}
