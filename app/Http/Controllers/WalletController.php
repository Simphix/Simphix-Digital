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
    /**
     * Create Virtual Wallet / Funding Account.
     * Both BVN and NIN are required by Fintava BaaS specifications.
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

        \App\Support\PhoneNumber::normalizeRequestInput($request);

        $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => ['required', 'string', 'max:20', \App\Support\PhoneNumber::validationRule(), 'unique:users,phone,' . $loginUserId],
            'bvn'           => ['required', 'string', 'digits:11', 'unique:users,bvn,' . $loginUserId],
            'nin'           => ['required', 'string', 'digits:11', 'unique:users,nin,' . $loginUserId],
            'date_of_birth' => 'nullable|date',
            'address'       => 'nullable|string|max:500',
            'state'         => 'nullable|string|max:100',
        ], [
            'bvn.required' => 'Your 11-digit Bank Verification Number (BVN) is required.',
            'nin.required' => 'Your 11-digit National Identification Number (NIN) is required.',
            'bvn.digits'   => 'The BVN must be exactly 11 digits.',
            'nin.digits'   => 'The NIN must be exactly 11 digits.',
        ]);

        // Save submitted details to user profile
        $fullName = trim((string) $request->input('name'));
        $nameParts = preg_split('/\s+/', $fullName, 2);
        if (!empty($nameParts[0])) {
            $user->first_name = $nameParts[0];
        }
        if (!empty($nameParts[1])) {
            $user->last_name = $nameParts[1];
        }
        $user->name = $fullName;
        $user->phone = $request->input('phone');
        $user->bvn = $request->input('bvn');
        $user->nin = $request->input('nin');
        if ($request->filled('date_of_birth')) {
            $user->date_of_birth = $request->input('date_of_birth');
        }
        if ($request->filled('address')) {
            $user->address = $request->input('address');
        }
        if ($request->filled('state')) {
            $user->state = $request->input('state');
        }
        $user->save();

        // Repository call
        $repObj2 = new VirtualAccountRepository;
        $result = $repObj2->createVirtualAccount($loginUserId);

        // Handle failure
        if (!is_array($result) || !isset($result['success']) || !$result['success']) {
            $message = is_array($result) && isset($result['message'])
                ? $result['message']
                : 'Virtual account creation failed. Please check your details and try again.';

            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 400);
            }
            return redirect()->route('wallet')->withInput()->with(['error' => $message]);
        }

        // Success
        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $result['message'], 'data' => $result]);
        }
        return redirect()->route('wallet')->with(['success' => $result['message']]);
    }

    /**
     * One-click virtual account generation from the dashboard for users
     * who already have both BVN and NIN on their profile.
     */
    public function quickCreateWallet(Request $request)
    {
        $loginUserId = Auth::id();
        $user = User::find($loginUserId);

        if (!$user) {
            return redirect()->route('login');
        }

        if (VirtualAccount::where('user_id', $loginUserId)->exists()) {
            return redirect()->route('dashboard')->with('error', 'You already have a virtual account generated.');
        }

        if (empty($user->bvn) || empty($user->nin)) {
            return redirect()->route('wallet')->with('error', 'Please provide both your BVN and NIN to generate a virtual funding account.');
        }

        $rep = new VirtualAccountRepository;
        $result = $rep->createVirtualAccount($loginUserId);

        if (!is_array($result) || empty($result['success'])) {
            $message = is_array($result) && isset($result['message'])
                ? $result['message']
                : 'Virtual account generation failed. Please verify your details.';

            // Redirect to the wallet form with all existing details pre-filled and clear error so they can edit and retry
            return redirect()->route('wallet')->with('error', $message);
        }

        return redirect()->route('dashboard')->with('success', 'Virtual funding account generated successfully! You can now make instant deposit transfers.');
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