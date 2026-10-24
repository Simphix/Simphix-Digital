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
     * P2P Transfer isn't available to Agent — it's a discount-only tier now
     * (see AgentUpgradeController), not a wallet-movement role. Named
     * distinctly from a general "block agents" guard since this controller
     * also owns unrelated wallet methods (index, claimBonus, createWallet,
     * getBalance) that must stay open to agents.
     */
    private function blockAgentsFromP2p(): void
    {
        if (Auth::user()?->role === 'agent') {
            abort(403, 'This feature is not available for your account type.');
        }
    }

    /**
     * Show P2P transfer view
     */
    public function p2p()
    {
        $this->blockAgentsFromP2p();

        $user = Auth::user();

        // Fetch last 15 unique transfer recipients from report table (type: transfer)
        $recentRecipients = \App\Models\Report::where('user_id', $user->id)
            ->where('type', 'transfer')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'bank_code' => $report->bank_code ?? '',
                    'account_no' => $report->account_number,
                    'account_name' => $report->account_name,
                    'bank_name' => $report->bank_name ?? 'SimPhix Wallet',
                    'bank_url' => null
                ];
            })
            ->filter(fn($item) => !empty($item['account_no']))
            ->unique(fn($item) => $item['account_no'])
            ->values()
            ->take(15);

        return view('wallet.p2p', compact('recentRecipients'));
    }

    /**
     * Verify P2P recipient
     */
    public function verifyUser(Request $request)
    {
        $this->blockAgentsFromP2p();

        $identifier = trim($request->input('wallet_id'));

        if (empty($identifier)) {
            return response()->json(['success' => false, 'message' => 'Please enter a Wallet ID, Phone, or Email.']);
        }

        $sender = Auth::user();

        // Check if identifier is email or phone
        $recipient = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        // If not found, check by wallet_number on Wallet model
        if (!$recipient) {
            $wallet = Wallet::where('wallet_number', $identifier)->first();
            if ($wallet) {
                $recipient = $wallet->user;
            }
        }

        if (!$recipient) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        if ($recipient->id === $sender->id) {
            return response()->json(['success' => false, 'message' => 'You cannot transfer funds to yourself.']);
        }

        $photoUrl = $recipient->profile_photo ? asset($recipient->profile_photo) : null;
        $walletNumber = $recipient->wallet->wallet_number ?? 'N/A';

        return response()->json([
            'success' => true,
            'user_name' => $recipient->name,
            'photo' => $photoUrl,
            'wallet_id' => $walletNumber
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

    /**
     * Process P2P transfer
     */
    public function processTransfer(Request $request)
    {
        $this->blockAgentsFromP2p();

        $request->validate([
            'wallet_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|string|digits:4',
        ]);

        $sender = Auth::user();
        $lockKey = 'pin_attempts_' . $sender->id;
        $attempts = Cache::get($lockKey, 0);

        // Lockout after 5 failed attempts for 15 minutes
        if ($attempts >= 5) {
            return back()->with('error', 'Too many incorrect PIN attempts. Please try again in 15 minutes.');
        }

        if (!$sender->transaction_pin) {
            return back()->with('error', 'Transaction PIN is not set. Please set one in your profile.');
        }

        // 1. PIN verification
        if (!Hash::check($request->pin, $sender->transaction_pin)) {
            Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));

            Log::warning('Failed PIN verification attempt during transfer', [
                'user_id'        => $sender->id,
                'ip'             => $request->ip(),
                'attempt_number' => $attempts + 1,
            ]);

            return back()->with('error', 'Incorrect transaction PIN.');
        }

        // Reset counter on success
        Cache::forget($lockKey);

        // 2. Resolve recipient
        $recipientWallet = Wallet::where('wallet_number', $request->wallet_id)->first();
        if (!$recipientWallet) {
            return back()->with('error', 'Recipient wallet not found.');
        }

        $recipient = $recipientWallet->user;
        if (!$recipient) {
            return back()->with('error', 'Recipient not found.');
        }

        if ($recipient->id === $sender->id) {
            return back()->with('error', 'You cannot transfer funds to yourself.');
        }

        // 3. Limit Check
        if ($request->amount > $sender->limit) {
            return back()->with('error', 'Amount exceeds your single transaction limit of ₦' . number_format($sender->limit, 2));
        }

        // 4. Run database transaction with row level locking
        DB::beginTransaction();
        try {
            // Lock sender wallet
            $senderWallet = Wallet::where('user_id', $sender->id)->lockForUpdate()->first();
            // Lock recipient wallet
            $receiverWallet = Wallet::where('user_id', $recipient->id)->lockForUpdate()->first();

            if (!$senderWallet || $senderWallet->status !== 'active') {
                throw new \Exception('Your wallet is not active.');
            }

            if (!$receiverWallet || $receiverWallet->status !== 'active') {
                throw new \Exception('Recipient wallet is inactive.');
            }

            if ($senderWallet->balance < $request->amount) {
                throw new \Exception('Insufficient wallet balance.');
            }

            $senderOldBalance = $senderWallet->balance;
            $senderNewBalance = $senderOldBalance - $request->amount;

            $receiverOldBalance = $receiverWallet->balance;
            $receiverNewBalance = $receiverOldBalance + $request->amount;

            // Deduct sender balance
            $senderWallet->balance = $senderNewBalance;
            $senderWallet->save();

            // Credit recipient balance
            $receiverWallet->balance = $receiverNewBalance;
            $receiverWallet->save();

            $transferRef = 'TRF' . strtoupper(Str::random(12));
            $senderName = trim($sender->first_name . ' ' . $sender->last_name);
            $recipientName = trim($recipient->first_name . ' ' . $recipient->last_name);

            // Fetch or create P2P transfer service
            $service = \App\Models\Service::firstOrCreate(
                ['name' => 'P2P Transfer'],
                ['description' => 'Peer-to-Peer Wallet Transfer Service', 'is_active' => true]
            );

            // Create transactions
            // 1. Debit Transaction for Sender
            Transaction::create([
                'transaction_ref' => $transferRef,
                'user_id' => $sender->id,
                'amount' => $request->amount,
                'fee' => 0.00,
                'net_amount' => $request->amount,
                'description' => "P2P Transfer to {$recipientName} ({$request->wallet_id})",
                'type' => 'debit',
                'status' => 'completed',
                'performed_by' => $senderName,
                'metadata' => [
                    'service' => 'p2p_transfer',
                    'recipient_wallet' => $request->wallet_id,
                    'recipient_name' => $recipientName,
                ],
            ]);

            // 2. Credit Transaction for Recipient
            Transaction::create([
                'transaction_ref' => $transferRef . '-REC',
                'user_id' => $recipient->id,
                'amount' => $request->amount,
                'fee' => 0.00,
                'net_amount' => $request->amount,
                'description' => "P2P Transfer from {$senderName} ({$senderWallet->wallet_number})",
                'type' => 'credit',
                'status' => 'completed',
                'performed_by' => $senderName,
                'metadata' => [
                    'service' => 'p2p_transfer',
                    'sender_wallet' => $senderWallet->wallet_number,
                    'sender_name' => $senderName,
                ],
            ]);

            // Create reports
            // 1. Report for Sender
            \App\Models\Report::create([
                'user_id' => $sender->id,
                'phone_number' => $recipient->phone,
                'account_number' => $request->wallet_id,
                'account_name' => $recipientName,
                'bank_code' => 'p2p',
                'bank_name' => 'SimPhix Wallet',
                'network' => 'P2P',
                'ref' => $transferRef,
                'amount' => $request->amount,
                'status' => 'completed',
                'type' => 'transfer',
                'description' => "P2P Transfer to {$recipientName}",
                'old_balance' => $senderOldBalance,
                'new_balance' => $senderNewBalance,
                'service_id' => $service->id,
            ]);

            // 2. Report for Recipient
            \App\Models\Report::create([
                'user_id' => $recipient->id,
                'phone_number' => $sender->phone,
                'account_number' => $senderWallet->wallet_number,
                'account_name' => $senderName,
                'bank_code' => 'p2p',
                'bank_name' => 'SimPhix Wallet',
                'network' => 'P2P',
                'ref' => $transferRef . '-REC',
                'amount' => $request->amount,
                'status' => 'completed',
                'type' => 'transfer',
                'description' => "P2P Transfer from {$senderName}",
                'old_balance' => $receiverOldBalance,
                'new_balance' => $receiverNewBalance,
                'service_id' => $service->id,
            ]);

            DB::commit();

            return redirect()->route('wallet')->with('success', 'Transfer of ₦' . number_format($request->amount, 2) . ' to ' . $recipientName . ' completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }
}