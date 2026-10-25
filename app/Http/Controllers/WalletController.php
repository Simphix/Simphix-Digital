<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VirtualAccount;
use App\Repositories\VirtualAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;



class WalletController extends Controller
{
    /**
     * Show wallet dashboard
     */
    public function index()
    {
        $userId = Auth::id();

        $virtualAccount = VirtualAccount::where('user_id', $userId)->first();
        $wallet = Wallet::where('user_id', $userId)->first();

        $walletData = [
            'balance'           => $wallet->balance ?? 0,
            'bonus'             => $wallet->bonus ?? 0,
            'status'            => $wallet->status ?? 'inactive',
            'available_balance' => $wallet->balance ?? 0,
        ];

        // Dynamic rewards & bonus spend progress
        $currentSpend = Transaction::where('user_id', $userId)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount') ?? 0;

        $bonusTarget = 50000;
        $nextBonusTarget = 50000;
        $bonusProgress = $bonusTarget > 0 ? min(100, round(($currentSpend / $bonusTarget) * 100)) : 0;

        return view('wallet.index', compact(
            'virtualAccount', 
            'walletData', 
            'currentSpend', 
            'bonusTarget', 
            'nextBonusTarget', 
            'bonusProgress'
        ));
    }

    /**
     * Create Virtual Wallet
     */
    public function createWallet(Request $request)
    {
        $loginUserId = Auth::id(); 
        $user = User::find($loginUserId);

        // Check if virtual account already exists
        if (VirtualAccount::where('user_id', $loginUserId)->exists()) {
            return redirect()->route('wallet')->with([
                'error' => 'You already have a virtual account generated.'
            ]);
        }

        // Validate details — identity_type picks which of BVN/NIN is
        // required (NIN is a fallback path for users whose BVN fails
        // PalmPay's own verification; see VirtualAccountRepository).
        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone,' . $loginUserId],
            'identity_type' => 'required|in:bvn,nin',
            'bvn'           => 'required_if:identity_type,bvn|nullable|string|digits:11|unique:users,bvn,' . $loginUserId,
            'nin'           => 'required_if:identity_type,nin|nullable|string|digits:11|unique:users,nin,' . $loginUserId,
        ]);

        $identityType = $request->input('identity_type');

        // Save submitted details to user profile — only overwrite whichever
        // identity field was actually chosen, never blank out the other.
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        if ($identityType === 'nin') {
            $user->nin = $request->input('nin');
        } else {
            $user->bvn = $request->input('bvn');
        }
        $user->save();

        // Check KYC details
        $identityValue = $identityType === 'nin' ? $user->nin : $user->bvn;
        if (empty($identityValue) || empty($user->phone)) {
            return redirect()->route('wallet')->with([
                'error' => 'Please complete your registration by providing your BVN or NIN and Phone Number to open a virtual account.'
            ]);
        }

        // Repository call
        $repObj2 = new VirtualAccountRepository;
        $result = $repObj2->createVirtualAccount($loginUserId, $identityType);

        // Handle failure
        if (!is_array($result) || !isset($result['success']) || !$result['success']) {
            $message = is_array($result) && isset($result['message'])
                ? $result['message']
                : 'Wallet creation failed. Please try again later.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 400);
            }
            return redirect()->route('wallet')->with(['error' => $message]);
        }

        // Success
        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $result['message'], 'data' => $result]);
        }
        return redirect()->route('wallet')->with(['success' => $result['message']]);
    }

    /**
     * Claim bonus: move bonus to wallet_balance and record transaction
     */
    public function claimBonus(Request $request)
    {
        $userId = Auth::id();

        try {
            $bonusAmount = DB::transaction(function () use ($userId) {
                // Fetch the wallet with a pessimistic lock INSIDE the active database transaction
                $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();

                if (!$wallet || $wallet->bonus <= 0) {
                    throw new \Exception('No bonus available to claim.');
                }

                $bonus = $wallet->bonus;

                // Update wallet balances
                $wallet->balance += $bonus;
                $wallet->bonus = 0;
                $wallet->save();

                // Performed by
                $user = User::find($userId);
                $performedBy = $user ? $user->first_name . ' ' . $user->last_name : 'System';

                // Catalog (reseller) roles earn this balance as commission,
                // not a referral/reward bonus — reflect that in the history.
                $description = $user && \App\Support\SimAccess::canBrowseCatalog($user)
                    ? 'Commission claimed and credited to wallet balance'
                    : 'Bonus claimed and credited to wallet balance';

                // Save transaction
                Transaction::create([
                    'user_id'         => $userId,
                    'type'            => 'credit',
                    'amount'          => $bonus,
                    'fee'             => 0.00,
                    'net_amount'      => $bonus,
                    'description'     => $description,
                    'status'          => 'completed',
                    'transaction_ref' => 'BONUS-' . strtoupper(uniqid()),
                    'performed_by'    => $performedBy,
                ]);

                return $bonus;
            });

            return redirect()->route('wallet')->with([
                'success' => 'Bonus of ₦' . number_format($bonusAmount, 2) . ' successfully claimed and added to your wallet balance.'
            ]);

        } catch (\Exception $e) {
            return redirect()->route('wallet')->with([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the user's current wallet balance
     */
    public function getBalance()
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();
        return response()->json([
            'success' => true,
            'balance' => $wallet ? $wallet->balance : 0
        ]);
    }

    /**
     * Verify User PIN via AJAX
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string',
        ]);

        $user    = Auth::user();
        $lockKey = 'pin_attempts_' . $user->id;
        $attempts = Cache::get($lockKey, 0);

        // Lockout after 5 failed attempts for 15 minutes
        if ($attempts >= 5) {
            return response()->json([
                'valid'   => false,
                'message' => 'Too many incorrect PIN attempts. Please try again in 15 minutes.',
            ], 429);
        }

        if (!$user->transaction_pin) {
            return response()->json(['valid' => false, 'message' => 'Transaction PIN is not set.']);
        }

        if (Hash::check($request->pin, $user->transaction_pin)) {
            Cache::forget($lockKey); // Reset counter on success
            return response()->json(['valid' => true]);
        }

        // Increment attempt counter — expires after 15 minutes
        Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));

        Log::warning('Failed PIN verification attempt', [
            'user_id'          => $user->id,
            'ip'               => $request->ip(),
            'attempt_number'   => $attempts + 1,
        ]);

        return response()->json(['valid' => false, 'message' => 'Incorrect PIN.']);
    }
}