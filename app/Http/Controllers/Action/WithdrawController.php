<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Models\CashoutSettings;
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
     * Personal and agent accounts cannot request cash outs.
     */
    private function blockRestrictedRoles(): void
    {
        if (in_array(Auth::user()?->role, ['agent', 'personal'], true)) {
            abort(403, 'This feature is not available for your account type.');
        }
    }

    /**
     * Show withdrawal index page.
     */
    public function index()
    {
        $this->blockRestrictedRoles();

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

        $cashoutSettings = CashoutSettings::current();
        $withdrawalAccount = $user->withdrawalAccount;
        $roleLimits = $cashoutSettings->getLimitsForRole($user->role);

        return view('wallet.withdraw', compact('user', 'totalVolume', 'eligibilityAmount', 'withdrawalAccount', 'roleLimits', 'cashoutSettings'));
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
        $this->blockRestrictedRoles();

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
        $this->blockRestrictedRoles();

        $user = Auth::user();
        $roleLimits = CashoutSettings::limitsForRole($user->role);
        $minAmount = (float) $roleLimits['min'];
        $maxAmount = (float) $roleLimits['max'];

        $request->validate([
            'amount' => ['required', 'numeric', "min:{$minAmount}", "max:{$maxAmount}"],
            'pin' => 'required|digits:4',
        ], [
            'amount.min' => 'The minimum cash out amount for your role is ₦' . number_format($minAmount, 2) . '.',
            'amount.max' => 'Amount exceeds your maximum cash out limit of ₦' . number_format($maxAmount, 2) . '.',
        ]);

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

        $cashoutSettings = CashoutSettings::current();
        $charges = $cashoutSettings->calculateCashoutCharges((float) $request->amount, $user);
        $fee = (float) $charges['base_fee'];
        $tax = (float) $charges['threshold_fee'];
        $totalCharge = (float) $request->amount + $fee;
        $totalRequired = (float) $charges['total_debit']; // amount + fee + threshold_fee

        // Fail fast on insufficient balance, before the PIN check burns a
        // rate-limited attempt or the eligibility check does real work on a
        // request that can't succeed anyway. This is a plain read — the
        // authoritative, race-condition-safe check still happens below
        // inside the locked DB transaction right before the debit.
        $wallet = $user->wallet;
        if ($wallet && $wallet->is_locked) {
            return back()->with('error', 'Your account is under a Post No Debit (PND) restriction. Contact support or your upline.');
        }
        if (!$wallet || $wallet->balance < $totalRequired) {
            $taxNotice = $tax > 0 ? " (including {$charges['threshold_description']}: ₦" . number_format($tax, 2) . ")" : '';
            return back()->with('error', 'Insufficient wallet balance. Total required: ₦' . number_format($totalRequired, 2) . $taxNotice);
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

            // 4. Role-Based Withdrawal Limit Check
            if ($request->amount < $minAmount) {
                return back()->with('error', 'The minimum cash out amount for your role is ₦' . number_format($minAmount, 2) . '.');
            }

            if ($request->amount > $maxAmount) {
                return back()->with('error', 'Amount exceeds your maximum cash out limit of ₦' . number_format($maxAmount, 2) . '.');
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

                if ($wallet->balance < $totalRequired) {
                    $taxNotice = $tax > 0 ? " (including {$charges['threshold_description']}: ₦" . number_format($tax, 2) . ")" : '';
                    throw new \Exception('Insufficient wallet balance. Total required: ₦' . number_format($totalRequired, 2) . $taxNotice);
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
                            'amount' => (float) $request->amount,
                            'fee' => $fee,
                            'fee_description' => $charges['base_fee_description'],
                            'tax' => $tax,
                            'threshold_description' => $charges['threshold_description'],
                            'total_debit' => $totalRequired,
                            'total' => $totalCharge,
                        ],
                        'charges' => $charges,
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
