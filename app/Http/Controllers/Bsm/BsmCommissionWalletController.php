<?php

namespace App\Http\Controllers\Bsm;

use App\Http\Controllers\Controller;
use App\Models\PlatformBsmCommissionSettings;
use App\Models\PlatformWallet;
use App\Models\PlatformWalletTransaction;
use Illuminate\Http\Request;

class BsmCommissionWalletController extends Controller
{
    /**
     * Display the view-only BSM Central Commission Wallet.
     */
    public function index(Request $request)
    {
        $wallet = PlatformWallet::forType(PlatformWallet::TYPE_BSM_COMMISSION, 'BSM Central Commission Wallet');
        $settings = PlatformBsmCommissionSettings::current();

        $query = PlatformWalletTransaction::where('platform_wallet_id', $wallet->id)
            ->with('related')
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $transactions = $query->paginate(20)->withQueryString();

        // Calculate metrics
        $thisMonthCredited = PlatformWalletTransaction::where('platform_wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $totalTransactionsCount = PlatformWalletTransaction::where('platform_wallet_id', $wallet->id)->count();

        return view('bsm.commission-wallet', compact(
            'wallet',
            'settings',
            'transactions',
            'thisMonthCredited',
            'totalTransactionsCount'
        ));
    }
}
