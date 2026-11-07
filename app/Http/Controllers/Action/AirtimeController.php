<?php

namespace App\Http\Controllers\Action;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Http\Requests\Action\BuyAirtimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


use Illuminate\Support\Facades\Cache;


use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AirtimeController extends Controller implements HasMiddleware
{
    protected $loginUserId;

    public static function middleware(): array
    {
        return [
            new Middleware('throttle:6,1', only: ['buyAirtime']),
        ];
    }

    public function __construct()
    {
        $this->loginUserId = Auth::id();
    }

    /**
     * Show Airtime purchase form
     */
    public function airtime()
    {
        $user = Auth::user();

        // Wallet is already ensured via middleware or should be checked here
        $wallet = Wallet::firstOrCreateForUser($user->id);

        // Fetch recent airtime purchases
        $recentRecipients = \App\Models\Report::where('user_id', $user->id)
            ->where('type', 'airtime')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($report) {
                $network = strtolower($report->network);
                $img = match (true) {
                    str_contains($network, 'mtn')      => 'mtn.jpg',
                    str_contains($network, 'airtel')   => 'Airtel.png',
                    str_contains($network, 'glo')      => 'glo.jpg',
                    str_contains($network, 'etisalat'),
                    str_contains($network, '9mobile')  => '9Mobile.jpg',
                    default                            => 'default.png',
                };

                return [
                    'account_no'   => $report->phone_number,
                    'account_name' => $report->phone_number,
                    'bank_name'    => strtoupper($report->network),
                    'bank_code'    => $report->network,
                    'bank_url'     => asset('assets/images/apps/' . $img),
                    'amount'       => $report->amount,
                    'status'       => $report->status,
                    'date'         => $report->created_at ? $report->created_at->format('M d, h:i A') : 'N/A'
                ];
            })
            ->values();

        return view('utilities.index', [
            'user'             => $user,
            'wallet'           => $wallet,
            'recentRecipients' => $recentRecipients
        ]);
    }

    /**
     * Handle Airtime Purchase
     */
    /**
     * Handle Airtime Purchase
     */
    public function buyAirtime(BuyAirtimeRequest $request)
    {
        $user       = Auth::user();
        $networkKey = strtolower($request->network);
        $mobile     = $request->mobileno;
        $amount     = $request->amount;
        $requestId  = RequestIdHelper::generateRequestId();
        
        // 0. Preliminary Status Checks
        if ($user->status !== 'active') {
             if ($request->wantsJson()) {
                 return response()->json(['status' => 'error', 'message' => "Your account is currently {$user->status}. Access denied."], 403);
             }
             return redirect()->back()->with('error', "Your account is currently {$user->status}. Access denied.");
        }

        // 0.0 PIN Verification — check PIN is set first
        if (empty($user->transaction_pin)) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'You have not set a transaction PIN. Please set one in your profile.'], 403);
            }
            return redirect()->back()->with('error', 'You have not set a transaction PIN. Please set one in your profile.');
        }

        // Check PIN lockout
        $pinLockKey = 'pin_attempts_' . $user->id;
        $pinAttempts = Cache::get($pinLockKey, 0);
        if ($pinAttempts >= 5) {
            $msg = 'Your PIN has been temporarily locked due to multiple incorrect attempts. Please try again in 15 minutes.';
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 429);
            }
            return redirect()->back()->with('error', $msg);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->pin, $user->transaction_pin)) {
            Cache::put($pinLockKey, $pinAttempts + 1, now()->addMinutes(15));
            Log::warning('Failed airtime PIN attempt', [
                'user_id'        => $user->id,
                'ip'             => $request->ip(),
                'attempt_number' => $pinAttempts + 1,
            ]);
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Invalid transaction PIN.'], 403);
            }
            return redirect()->back()->with('error', 'Invalid transaction PIN.');
        }

        // Correct PIN — reset lockout counter
        Cache::forget($pinLockKey);

        // 0.1 Purchase Limit Check (Max ₦5,000 per transaction)
        if ($amount > 5000) {
             $limitMsg = 'Purchase limit exceeded! The maximum airtime amount allowed per transaction is ₦5,000.';
             if ($request->wantsJson()) {
                 return response()->json(['status' => 'error', 'message' => $limitMsg], 422);
             }
             return redirect()->back()->with('error', $limitMsg)->withInput();
        }

        // Double-click/Concurrency lock (reject rapid concurrent requests)
        $lockKey = 'airtime_purchase_lock_' . $user->id;
        $lock = Cache::lock($lockKey, 30); // 30-second lock

        if (!$lock->get()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'A transaction is already in progress. Please wait a moment.'], 429);
            }
            return redirect()->back()->with('error', 'A transaction is already in progress. Please wait a moment.');
        }


        // 1. Idempotency Check (Prevent duplicate requests within 60 seconds)
        $recentTransaction = \App\Models\Report::where('user_id', $user->id)
            ->where('phone_number', $mobile)
            ->where('amount', $amount)
            ->where('type', 'airtime')
            ->where('created_at', '>=', now()->subMinute())
            ->first();

        if ($recentTransaction) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'A similar transaction was recently processed. Please wait a moment.'], 422);
            }
            return redirect()->back()->with('error', 'A similar transaction was recently processed. Please wait a moment before trying again.');
        }


        // 2. Find the Airtime Service & Field
        $service = Service::where('name', 'Airtime')->first();
        if (!$service) {
             $service = Service::firstOrCreate(['name' => 'Airtime'], ['is_active' => true]);
        }

        // Exact match preferred for security over LIKE
        $serviceField = \App\Models\ServiceField::where('service_id', $service->id)
            ->where(function($q) use ($networkKey) {
                $q->where('field_code', $networkKey)
                  ->orWhere('field_name', 'LIKE', "%{$networkKey}%");
            })->orderByRaw("field_code = ? DESC", [$networkKey]) // Prioritize exact code match
            ->first();

        // 3. Calculate Discount
        $discountPercentage = 0;
        if ($serviceField) {
            $userRole = $user->role ?? 'personal'; 
            $servicePrice = \App\Models\ServicePrice::where('service_fields_id', $serviceField->id)
                ->where('user_type', $userRole)
                ->first();

            $discountPercentage = $servicePrice ? $servicePrice->price : ($serviceField->base_price ?? 0);
        }

        $discountAmount = ($amount * $discountPercentage) / 100;
        $payableAmount = $amount - $discountAmount;

        // 4. Start DB Transaction & Initialize Records
        DB::beginTransaction();
        try {
            // Fetch Wallet with Row Locking to prevent concurrent race conditions
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                $msg = 'Wallet not found.';
                if ($request->wantsJson()) return response()->json(['status' => 'error', 'message' => $msg], 404);
                return redirect()->back()->with('error', $msg);
            }

            if ($wallet->balance < $payableAmount) {
                DB::rollBack();
                $msg = 'Insufficient wallet balance! You need ₦' . number_format($payableAmount, 2);
                if ($request->wantsJson()) return response()->json(['status' => 'error', 'message' => $msg], 422);
                return redirect()->back()->with('error', $msg);
            }

            if ($wallet->status !== 'active') {
                DB::rollBack();
                $msg = 'Your wallet is not active. Please contact support.';
                if ($request->wantsJson()) return response()->json(['status' => 'error', 'message' => $msg], 403);
                return redirect()->back()->with('error', $msg);
            }

            $oldBalance = $wallet->balance;
            $wallet->decrement('balance', $payableAmount);
            $newBalance = $wallet->balance;

            $transaction = Transaction::create([
                'transaction_ref' => $requestId,
                'user_id'         => $user->id,
                'amount'          => $amount,
                'fee'             => 0,
                'net_amount'      => $payableAmount,
                'description'     => "Airtime purchase of ₦{$amount} for {$mobile} ({$networkKey})",
                'type'            => 'debit',
                'status'          => 'processing',
                'performed_by'    => $user->first_name . ' ' . $user->last_name,
                'approved_by'     => $user->id,
            ]);


            $report = \App\Models\Report::create([
                'user_id'      => $user->id,
                'phone_number' => $mobile,
                'network'      => $networkKey,
                'ref'          => $requestId,
                'amount'       => $amount,
                'status'       => 'completed',
                'type'         => 'airtime',
                'description'  => "Processing: Airtime purchase for {$mobile}",
                'old_balance'  => $oldBalance,
                'new_balance'  => $newBalance,
                'service_id'   => $serviceField ? $serviceField->service_id : null,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Airtime Initialization Error: ' . $e->getMessage());
            if ($request->wantsJson()) return response()->json(['status' => 'error', 'message' => 'Failed to initialize transaction.'], 500);
            return redirect()->back()->with('error', 'Failed to initialize transaction. Please try again.');
        }


        // 6. Call Airtime API (OUTSIDE the DB Transaction)
        try {
            $result = \App\Services\VtpassAirtimeApi::purchase($mobile, $networkKey, $amount, $requestId);
            $data = $result['data'];
            $isSuccessful = $result['success'];

            if ($isSuccessful) {
                // Award commission if configured
                $commissionAmount = 0;
                if (isset($servicePrice) && $servicePrice->commission > 0) {
                    $commissionAmount = ($amount * (float) $servicePrice->commission) / 100;
                }

                if ($commissionAmount > 0) {
                    DB::transaction(function () use ($user, $commissionAmount, $mobile, $networkKey, $service) {
                        $w = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                        if ($w) {
                            $oldBal = $w->balance;
                            $w->increment('balance', $commissionAmount);
                            $newBal = $w->balance;

                            $commRef = 'COMM-' . time() . '-' . rand(1000, 9999);

                            Transaction::create([
                                'transaction_ref' => $commRef,
                                'user_id'         => $user->id,
                                'amount'          => $commissionAmount,
                                'fee'             => 0.00,
                                'net_amount'      => $commissionAmount,
                                'description'     => "Commission earned on Airtime purchase (" . $mobile . ")",
                                'type'            => 'credit',
                                'status'          => 'completed',
                                'metadata'        => json_encode([
                                    'mobile'  => $mobile,
                                    'network' => $networkKey,
                                    'type'    => 'airtime_commission'
                                ]),
                                'performed_by' => 'System',
                            ]);

                            \App\Models\Report::create([
                                'user_id'      => $user->id,
                                'phone_number' => $mobile,
                                'network'      => $networkKey,
                                'ref'          => $commRef,
                                'amount'       => $commissionAmount,
                                'status'       => 'completed',
                                'type'         => 'commission',
                                'description'  => "Commission earned on Airtime Purchase: " . $mobile,
                                'old_balance'  => $oldBal,
                                'new_balance'  => $newBal,
                                'service_id'   => $service ? $service->id : null,
                            ]);
                        }
                    });
                }

                $transaction->update([
                    'status'   => 'completed',
                    'metadata' => json_encode([
                        'phone'        => $mobile,
                        'network'      => $networkKey,
                        'discount'     => $discountAmount,
                        'commission'   => $commissionAmount,
                        'api_response' => $data,
                    ]),
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Airtime purchase successful!',
                        'data'    => [
                            'ref'     => $requestId,
                            'mobile'  => $mobile,
                            'amount'  => $amount,
                            'paid'    => $payableAmount,
                            'network' => $networkKey,
                        ]
                    ]);
                }

                $successMsg = "✅ ₦" . number_format($payableAmount, 2) . " airtime sent to {$mobile} (" . strtoupper($networkKey) . "). Ref: {$requestId}";
                return redirect()->route('airtime')->with('success', $successMsg);
            }

            // API Failed - REFUND
            DB::transaction(function () use ($wallet, $payableAmount, $transaction, $report, $data) {
                $wallet->increment('balance', $payableAmount);
                $transaction->update(['status' => 'failed']);
                $report->update([
                    'status'      => 'failed',
                    'description' => "Failed: " . ($data['message'] ?? 'API Provider Error'),
                ]);
            });

            Log::error('Airtime API Error', ['response' => $data, 'ref' => $requestId]);
            $msg = 'Airtime purchase failed. ' . ($data['message'] ?? 'Unknown error') . ' Your wallet has been refunded.';
            if ($request->wantsJson()) return response()->json(['status' => 'error', 'message' => $msg], 400);
            return redirect()->route('airtime')->with('error', $msg);

        } catch (\Exception $e) {
            // Unexpected Error (e.g. timeout) - Mark as failed and REFUND
            DB::transaction(function () use ($wallet, $payableAmount, $transaction, $report, $e) {
                $wallet->increment('balance', $payableAmount);
                $transaction->update(['status' => 'failed']);
                $report->update([
                    'status'      => 'failed',
                    'description' => "Error: " . $e->getMessage(),
                ]);
            });

            Log::error('Airtime API Exception: ' . $e->getMessage());
            if ($request->wantsJson()) return response()->json(['status' => 'error', 'message' => 'Connection error. Your wallet has been refunded.'], 500);
            return redirect()->route('airtime')->with('error', 'Connection error. Please try again. Your wallet has been refunded.');
        }

    }
}
