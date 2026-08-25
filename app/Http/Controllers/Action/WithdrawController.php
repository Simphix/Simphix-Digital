<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PalmPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WithdrawController extends Controller
{
    protected $palmPay;

    public function __construct(PalmPayService $palmPay)
    {
        $this->palmPay = $palmPay;
    }

    /**
     * Cash Out isn't available to Agent — it's a discount-only tier now
     * (see AgentUpgradeController), not a wallet-movement role.
     */
    private function blockAgents(): void
    {
        if (Auth::user()?->role === 'agent') {
            abort(403, 'This feature is not available for your account type.');
        }
    }

    /**
     * Show withdrawal index page.
     */
    public function index()
    {
        $this->blockAgents();

        $user = Auth::user();

        // Ensure Withdrawal service and fields exist
        $service = Service::firstOrCreate(
            ['name' => 'Withdrawal'],
            ['description' => 'Wallet Withdrawal Service', 'is_active' => true]
        );

        ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'withdrawal fee'],
            ['field_code' => 'WDL_001', 'description' => 'Fee charged for withdrawals', 'base_price' => 0, 'is_active' => true]
        );

        ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'withdrawal eligibility'],
            ['field_code' => 'WDL_002', 'description' => 'Minimum transaction volume for eligibility', 'base_price' => 2000000, 'is_active' => true]
        );

        // Calculate total transaction volume
        $totalVolume = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->where('status', 'completed')
            ->sum('amount');

        // Fetch the "withdrawal eligibility" service field and calculate role-based amount
        $eligibilityField = ServiceField::where('field_name', 'withdrawal eligibility')->first();
        $eligibilityAmount = $eligibilityField ? $eligibilityField->priceForUser($user) : 2000000;

        $feeField = ServiceField::where('field_name', 'withdrawal fee')->first();
        $withdrawalFee = $feeField ? $feeField->priceForUser($user) : 0;

        $withdrawalAccount = $user->withdrawalAccount;

        return view('wallet.withdraw', compact('user', 'totalVolume', 'eligibilityAmount', 'withdrawalFee', 'withdrawalAccount'));
    }

    /**
     * Sync banks from PalmPay.
     */
    public function syncBanks()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        \App\Jobs\SyncPalmPayBankListJob::dispatch();

        return back()->with('success', 'Bank sync queued — the list will update in a moment.');
    }

    /**
     * Verify bank account name.
     */
    public function verifyAccount(Request $request)
    {
        $this->blockAgents();

        $request->validate([
            'bankCode' => 'required|string',
            'account_no' => 'required|string|digits:10',
        ]);

        try {
            $response = $this->palmPay->queryBankAccount($request->bankCode, $request->account_no);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reach the bank verification service. Please try again shortly.',
            ]);
        }

        if (isset($response['respCode']) && $response['respCode'] === '00000000') {
            if ($response['data']['Status'] === 'Success') {
                return response()->json([
                    'success' => true,
                    'account_name' => $response['data']['accountName']
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => $response['data']['errorMessage'] ?? 'Account verification failed.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'PalmPay API Error: ' . ($response['respMsg'] ?? 'Unable to verify account.')
        ]);
    }

    /**
     * Process withdrawal.
     */
    public function processWithdrawal(Request $request)
    {
        $this->blockAgents();

        $request->validate([
            'amount' => 'required|numeric|min:100', // Minimum 100 NGN
            'pin' => 'required|digits:4',
        ]);

        $user = Auth::user();

        $account = $user->withdrawalAccount;
        if (!$account) {
            return back()->with('error', 'Add a withdrawal account in Settings before cashing out.');
        }

        // Fetch Withdrawal fee early for duplicate detection and amount calculation
        $service = Service::firstOrCreate(
            ['name' => 'Withdrawal'],
            ['description' => 'Wallet Withdrawal Service', 'is_active' => true]
        );

        if (!$service->is_active) {
            return back()->with('error', 'Withdrawal service is currently inactive.');
        }

        $feeField = ServiceField::firstOrCreate(
            ['service_id' => $service->id, 'field_name' => 'withdrawal fee'],
            ['field_code' => 'WDL_001', 'description' => 'Fee charged for withdrawals', 'base_price' => 0, 'is_active' => true]
        );
        $fee = $feeField->priceForUser($user);
        $totalCharge = $request->amount + $fee;

        // Calculate Withdrawal Tax for transactions >= 10,000 NGN
        $tax = 0;
        if ($request->amount >= 10000) {
            $taxField = ServiceField::firstOrCreate(
                ['service_id' => $service->id, 'field_name' => 'withdrawal tax'],
                ['field_code' => 'WDL_003', 'description' => 'Tax charged for withdrawals ₦10,000 and above', 'base_price' => 50, 'is_active' => true]
            );
            $tax = $taxField->priceForUser($user);
        }

        // Fail fast on insufficient balance, before the PIN check burns a
        // rate-limited attempt or the eligibility check does real work on a
        // request that can't succeed anyway. This is a plain read — the
        // authoritative, race-condition-safe check still happens below
        // inside the locked DB transaction right before the debit.
        $wallet = $user->wallet;
        if ($wallet && $wallet->is_locked) {
            return back()->with('error', 'Your account is under a Post No Debit (PND) restriction. Contact support or your upline.');
        }
        if (!$wallet || $wallet->balance < ($totalCharge + $tax)) {
            return back()->with('error', 'Insufficient wallet balance. Total required (including tax): ' . number_format($totalCharge + $tax, 2));
        }

        // 1. One Active Withdrawal at a Time (Check for Processing/Pending)
        $hasPending = \App\Models\Report::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return back()->with('error', 'You have an active withdrawal being processed. Please wait for it to complete.');
        }

        // 2. Lock User While Transaction is Processing
        $lockKey = 'withdrawal_lock_' . $user->id;
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 60); // 60 seconds lock

        if (!$lock->get()) {
            return back()->with('error', 'Your previous transaction is still processing.');
        }

        try {
            // 3. Prevent Duplicate: Redirect to old result for identical recent request (within 2 mins)
            $recentDuplicate = Transaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->whereIn('status', ['completed', 'pending'])
                ->where('amount', $totalCharge)
                ->where('metadata->account_no', $account->account_no)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($recentDuplicate) {
                return redirect()->route('thankyou', ['ref' => $recentDuplicate->transaction_ref]);
            }

            // 4. PIN Verification & Biometric Support
            $isBiometricValid = $request->biometric_auth && 
                               session('biometric_verified_at') && 
                               (now()->timestamp - session('biometric_verified_at')) < 60;

            if ($isBiometricValid) {
                \Illuminate\Support\Facades\RateLimiter::clear('withdraw_pin_' . $user->id);
                session()->forget('biometric_verified_at');
            } else {
                $pinLimiterKey = 'withdraw_pin_' . $user->id;

                if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($pinLimiterKey, 5)) {
                    $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($pinLimiterKey);
                    return back()->with('error', "Too many incorrect PIN attempts. Please try again in {$seconds} seconds.");
                }

                if (!Hash::check($request->pin, $user->transaction_pin)) {
                    \Illuminate\Support\Facades\RateLimiter::hit($pinLimiterKey, 900); // 15 mins
                    return back()->with('error', 'Incorrect transaction PIN.');
                }

                \Illuminate\Support\Facades\RateLimiter::clear($pinLimiterKey);
            }

            // Fetch Eligibility field and calculate role-based amount
            $eligibilityField = ServiceField::firstOrCreate(
                ['service_id' => $service->id, 'field_name' => 'withdrawal eligibility'],
                ['field_code' => 'WDL_002', 'description' => 'Minimum transaction volume for eligibility', 'base_price' => 2000000, 'is_active' => true]
            );
            $eligibilityAmount = $eligibilityField->priceForUser($user);

            // Eligibility Check - Total Transaction Volume
            $totalVolume = Transaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->where('status', 'completed')
                ->sum('amount');

            if ($totalVolume < $eligibilityAmount) {
                return back()->with('error', 'You must perform at least ' . number_format($eligibilityAmount, 2) . ' in total transactions to be eligible for withdrawal.');
            }

            // 4. Withdrawal Limit Check
            if ($request->amount > $user->limit) {
                return back()->with('error', 'Amount exceeds your withdrawal limit of ' . number_format($user->limit, 2));
            }

            // Phase 1: Deduct Wallet & Create Pending Records safely inside a DB lock
            DB::beginTransaction();
            try {
                // 5. Lock wallet row and check balance/status
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

                if (!$wallet) {
                    throw new \Exception('Wallet not found.');
                }

                if ($wallet->status !== 'active') {
                    throw new \Exception('Your wallet is not active.');
                }

                if ($wallet->balance < ($totalCharge + $tax)) {
                    throw new \Exception('Insufficient wallet balance. Total required (including tax): ' . number_format($totalCharge + $tax, 2));
                }

                $oldBalance = $wallet->balance;
                $newBalance = $oldBalance - $totalCharge;

                // 6. Create Transaction Record (Pending — stays pending until an
                // admin approves/rejects the matching CashOutRequest below)
                $transactionRef = 'WDL' . strtoupper(Str::random(12));
                $performedBy = trim($user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name);
                $detailedDescription = "Cash out from {$performedBy} to {$account->bank_name}: {$account->account_no} ({$account->account_name})";

                $transaction = Transaction::create([
                    'transaction_ref' => $transactionRef,
                    'user_id' => $user->id,
                    'amount' => $totalCharge,
                    'fee' => $fee,
                    'net_amount' => $totalCharge,
                    'description' => $detailedDescription,
                    'type' => 'debit',
                    'status' => 'pending',
                    'performed_by' => $performedBy,
                    'metadata' => [
                        'service' => 'withdrawal',
                        'bankCode' => $account->bank_code,
                        'bankName' => $account->bank_name,
                        'account_no' => $account->account_no,
                        'account_name' => $account->account_name,
                        'user_role' => $user->role,
                        'price_details' => [
                            'amount' => $request->amount,
                            'fee' => $fee,
                            'total' => $totalCharge,
                        ],
                    ],
                ]);

                // 7. Create Report Record (Pending)
                \App\Models\Report::create([
                    'user_id' => $user->id,
                    'phone_number' => $account->account_no,
                    'account_number' => $account->account_no,
                    'account_name' => $account->account_name,
                    'bank_code' => $account->bank_code,
                    'bank_name' => $account->bank_name,
                    'network' => 'Withdrawal',
                    'ref' => $transactionRef,
                    'amount' => $totalCharge,
                    'status' => 'pending',
                    'type' => 'withdrawal',
                    'description' => $detailedDescription,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'service_id' => $service->id,
                ]);

                // 8. Debit Wallet
                $wallet->balance = $newBalance;
                $wallet->save();

                // 9. Create the Cash Out request — this is what an admin acts on.
                // Bank details are snapshotted from $account so a later account
                // change never retroactively alters this request.
                \App\Models\CashOutRequest::create([
                    'user_id' => $user->id,
                    'transaction_ref' => $transactionRef,
                    'bank_code' => $account->bank_code,
                    'bank_name' => $account->bank_name,
                    'account_no' => $account->account_no,
                    'account_name' => $account->account_name,
                    'amount' => $request->amount,
                    'fee' => $fee,
                    'tax' => $tax,
                    'total_charge' => $totalCharge,
                    'status' => 'pending',
                ]);

                DB::commit(); // SAFELY COMMIT FUNDS DEDUCTION BEFORE ADMIN REVIEW
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Cash Out DB Initialization failed: ' . $e->getMessage());
                return back()->with('error', 'Cash out initialization failed: ' . $e->getMessage());
            }

            return redirect()->route('thankyou', ['ref' => $transaction->transaction_ref])
                ->with('success', 'Your cash-out request has been submitted and is awaiting review from our team.');
        } finally {
            // Unlock immediately after transaction processing completes
            if (isset($lock)) {
                $lock->release();
            }
        }
    }
}
