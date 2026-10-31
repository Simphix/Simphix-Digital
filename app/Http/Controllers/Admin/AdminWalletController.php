<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformWallet;
use App\Models\PlatformWelfareSettings;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminWalletController extends Controller
{
    /**
     * Display the Admin Wallet dashboard.
     */
    public function index()
    {
        // Calculate statistics for manual adjustments (completed transactions of type manual_credit or manual_debit)
        $monthlyManualCredit = Transaction::where('type', 'manual_credit')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $monthlyManualDebit = Transaction::where('type', 'manual_debit')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $totalUsers = User::count();

        // Fetch recent manual transactions
        $transactions = Transaction::with('user')
            ->whereIn('type', ['manual_credit', 'manual_debit'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $welfareWallet = PlatformWallet::forType(PlatformWallet::TYPE_WELFARE, 'Platform Welfare Wallet');
        $utilitiesWallet = PlatformWallet::forType(PlatformWallet::TYPE_UTILITIES_REVENUE, 'Utilities Revenue Wallet');
        $simActivationWallet = PlatformWallet::forType(PlatformWallet::TYPE_SIM_ACTIVATION, 'SIM Activation Wallet');

        $welfareSettings = PlatformWelfareSettings::current();
        $welfareConfigured = $welfareSettings->is_active && $welfareSettings->amount_per_activation > 0;

        return view('admin.manage.adminwallet', compact(
            'monthlyManualCredit',
            'monthlyManualDebit',
            'totalUsers',
            'transactions',
            'welfareWallet',
            'utilitiesWallet',
            'simActivationWallet',
            'welfareConfigured'
        ));
    }

    /**
     * Verify a user via AJAX request.
     */
    public function verifyUser(Request $request)
    {
        $identifier = trim($request->input('identifier'));

        if (empty($identifier)) {
            return response()->json(['success' => false, 'message' => 'Please enter an Email, Phone, or Wallet Number.']);
        }

        // Find user by email or phone
        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        // If not found, check by wallet_number
        if (!$user) {
            $wallet = Wallet::where('wallet_number', $identifier)->first();
            if ($wallet) {
                $user = $wallet->user;
            }
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        $wallet = Wallet::firstOrCreateForUser($user->id);

        return response()->json([
            'success' => true,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? 'No Phone',
            'wallet_number' => $wallet->wallet_number ?? 'N/A',
            'balance' => number_format($wallet->balance, 2),
        ]);
    }

    /**
     * Process manual adjustment for a single user.
     */
    public function adjustSingle(Request $request)
    {
        $request->validate([
            'identifier'  => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'type'        => 'required|in:credit,debit',
            'description' => 'required|string|max:255',
            'password'    => 'required|string',
        ]);

        $admin = Auth::user();

        // Verify admin password
        if (!Hash::check($request->password, $admin->password)) {
            return back()->with('error', 'Incorrect admin password. Action aborted.');
        }

        // Resolve user
        $user = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (!$user) {
            $wallet = Wallet::where('wallet_number', $request->identifier)->first();
            if ($wallet) {
                $user = $wallet->user;
            }
        }

        if (!$user) {
            return back()->with('error', 'Target user not found.');
        }

        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'status' => 'active',
                ]);
            }

            $oldBalance = $wallet->balance;
            $amount = $request->amount;
            $type = $request->type;
            $description = $request->description;

            if ($type === 'credit') {
                $wallet->balance += $amount;
                $wallet->total_credited += $amount;
            } else {
                if ($wallet->balance < $amount) {
                    throw new \Exception('Insufficient wallet balance. Current balance: ₦' . number_format($wallet->balance, 2));
                }
                $wallet->balance -= $amount;
                $wallet->total_debited += $amount;
            }

            $wallet->last_activity = now();
            $wallet->save();

            $newBalance = $wallet->balance;
            $ref = 'MAN' . ($type === 'credit' ? 'C' : 'D') . strtoupper(Str::random(12));
            $adminName = trim($admin->first_name . ' ' . $admin->last_name) ?: $admin->email;

            // Log Transaction
            Transaction::create([
                'transaction_ref' => $ref,
                'user_id'         => $user->id,
                'amount'          => $amount,
                'fee'             => 0.00,
                'net_amount'      => $amount,
                'description'     => $description,
                'type'            => $type === 'credit' ? 'manual_credit' : 'manual_debit',
                'status'          => 'completed',
                'performed_by'    => $adminName,
                'metadata'        => [
                    'action' => 'single_' . $type,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                ]
            ]);

            // Log Report
            \App\Models\Report::create([
                'user_id'        => $user->id,
                'phone_number'   => $user->phone ?? 'N/A',
                'account_number' => $wallet->wallet_number ?? 'N/A',
                'account_name'   => $user->name,
                'bank_code'      => 'system',
                'bank_name'      => 'System Adjustment',
                'network'        => 'SYSTEM',
                'ref'            => $ref,
                'amount'         => $amount,
                'status'         => 'completed',
                'type'           => $type === 'credit' ? 'credit' : 'debit',
                'description'    => $description,
                'old_balance'    => $oldBalance,
                'new_balance'    => $newBalance,
            ]);

            DB::commit();

            $msgAction = $type === 'credit' ? 'credited with' : 'debited';
            return back()->with('success', "User successfully {$msgAction} ₦" . number_format($amount, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Process manual adjustment for all users generally.
     */
    public function adjustGeneral(Request $request)
    {
        $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'type'            => 'required|in:credit,debit',
            'description'     => 'required|string|max:255',
            'confirm_general' => 'required|accepted',
            'password'        => 'required|string',
        ]);

        $admin = Auth::user();

        // Verify admin password
        if (!Hash::check($request->password, $admin->password)) {
            return back()->with('error', 'Incorrect admin password. Action aborted.');
        }

        $amount = $request->amount;
        $type = $request->type;
        $description = $request->description;
        $adminName = trim($admin->first_name . ' ' . $admin->last_name) ?: $admin->email;

        DB::beginTransaction();
        try {
            $userCount = 0;

            // Chunk users to prevent memory exhaustion and execution timeout issues
            User::chunk(100, function ($users) use ($amount, $type, $description, $adminName, &$userCount) {
                foreach ($users as $user) {
                    $wallet = Wallet::firstOrCreateForUser($user->id);

                    // Refetch with lock
                    $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

                    $oldBalance = $wallet->balance;

                    if ($type === 'credit') {
                        $wallet->balance += $amount;
                        $wallet->total_credited += $amount;
                    } else {
                        // For general debit, we only deduct up to what is available to avoid negative balance issues
                        $deductAmount = min($amount, $wallet->balance);
                        if ($deductAmount <= 0) {
                            continue; // Skip users with 0 or negative balance
                        }
                        $wallet->balance -= $deductAmount;
                        $wallet->total_debited += $deductAmount;
                    }

                    $wallet->last_activity = now();
                    $wallet->save();

                    $newBalance = $wallet->balance;
                    $actualAmt = ($type === 'credit') ? $amount : $deductAmount;
                    $ref = 'MAN' . ($type === 'credit' ? 'C' : 'D') . strtoupper(Str::random(12));

                    // Log Transaction
                    Transaction::create([
                        'transaction_ref' => $ref,
                        'user_id'         => $user->id,
                        'amount'          => $actualAmt,
                        'fee'             => 0.00,
                        'net_amount'      => $actualAmt,
                        'description'     => $description,
                        'type'            => $type === 'credit' ? 'manual_credit' : 'manual_debit',
                        'status'          => 'completed',
                        'performed_by'    => $adminName,
                        'metadata'        => [
                            'action' => 'general_' . $type,
                            'old_balance' => $oldBalance,
                            'new_balance' => $newBalance,
                        ]
                    ]);

                    // Log Report
                    \App\Models\Report::create([
                        'user_id'        => $user->id,
                        'phone_number'   => $user->phone ?? 'N/A',
                        'account_number' => $wallet->wallet_number ?? 'N/A',
                        'account_name'   => $user->name,
                        'bank_code'      => 'system',
                        'bank_name'      => 'System Adjustment',
                        'network'        => 'SYSTEM',
                        'ref'            => $ref,
                        'amount'         => $actualAmt,
                        'status'         => 'completed',
                        'type'           => $type === 'credit' ? 'credit' : 'debit',
                        'description'    => $description,
                        'old_balance'    => $oldBalance,
                        'new_balance'    => $newBalance,
                    ]);

                    $userCount++;
                }
            });

            DB::commit();

            $msgAction = $type === 'credit' ? 'credited' : 'debited';
            return back()->with('success', "General adjustment completed. {$userCount} users successfully {$msgAction}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'General adjustment failed: ' . $e->getMessage());
        }
    }
}
