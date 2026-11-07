<?php

namespace App\Http\Controllers\Action;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\SmeData;
use App\Models\SmeDataProviderSetting;
use App\Services\SmeDataProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SmeDataController extends Controller
{
    /**
     * Show SME Data Purchase Page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $wallet = Wallet::firstOrCreateForUser($user->id);

        $activeProvider = SmeDataProviderSetting::activeProvider();

        $networks = SmeData::select('network')->where('provider', $activeProvider)->where('status', 'enabled')->distinct()->get();

        // Query plans from the new table, grouped by network — only the
        // currently-active vendor's plans are purchasable by users.
        $mtnPlans = SmeData::where('provider', $activeProvider)->where('network', 'MTN')->where('status', 'enabled')->get();
        $gloPlans = SmeData::where('provider', $activeProvider)->where('network', 'GLO')->where('status', 'enabled')->get();
        $airtelPlans = SmeData::where('provider', $activeProvider)->where('network', 'AIRTEL')->where('status', 'enabled')->get();
        $mobile9Plans = SmeData::where('provider', $activeProvider)->where('network', '9MOBILE')->where('status', 'enabled')->get();

        return view('utilities.buy-sme-data', compact(
            'user', 
            'wallet', 
            'networks',
            'mtnPlans',
            'gloPlans',
            'airtelPlans',
            'mobile9Plans'
        ));
    }

    /**
     * Fetch Data Types for a Network
     */
    public function fetchDataType(Request $request)
    {
        $network = $request->id;
        $types = SmeData::where('provider', SmeDataProviderSetting::activeProvider())
            ->where('network', $network)
            ->where('status', 'enabled')
            ->select('plan_type')
            ->distinct()
            ->get();
        return response()->json($types);
    }

    /**
     * Fetch Data Plans for a Network and Type
     */
    public function fetchDataPlan(Request $request)
    {
        $network = $request->id;
        $type = $request->type;
        $plans = SmeData::where('provider', SmeDataProviderSetting::activeProvider())
            ->where('network', $network)
            ->where('plan_type', $type)
            ->where('status', 'enabled')
            ->get();
        return response()->json($plans);
    }

    /**
     * Fetch Plan Price
     */
    public function fetchSmeBundlePrice(Request $request)
    {
        $planId = $request->id;
        $plan = SmeData::where('provider', SmeDataProviderSetting::activeProvider())->where('data_id', $planId)->first();
        
        if (!$plan) {
            return response()->json("0.00");
        }

        $user = Auth::user();
        $finalPrice = $plan->calculatePriceForRole($user->role ?? 'personal');

        return response()->json(number_format((float)$finalPrice, 2));
    }

    /**
     * Buy SME Data Bundle
     */
    public function buySMEdata(Request $request)
    {
        $request->validate([
            'network'  => 'required|string',
            'type'     => 'required|string',
            'plan'     => 'required|string',
            'mobileno' => 'required|numeric|digits:11'
        ]);

        $user = Auth::user();
        $mobile = $request->mobileno;
        $planId = $request->plan;
        
        $plan = SmeData::where('data_id', $planId)->first();
        if (!$plan) {
            return back()->with('error', 'Invalid data plan selected.');
        }

        if ($plan->provider !== SmeDataProviderSetting::activeProvider()) {
            return back()->with('error', 'This plan is no longer available. Please refresh and try again.');
        }

        $payableAmount = $plan->calculatePriceForRole($user->role ?? 'personal');
        $description = "{$plan->size} {$plan->plan_type} for {$mobile} ({$plan->network})";

        // Check Wallet Balance (preliminary check)
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet || $wallet->balance < $payableAmount) {
            return redirect()->back()->with('error', 'Insufficient wallet balance! You need ₦' . number_format($payableAmount, 2));
        }

        // 0. Preliminary Status Checks
        if ($user->status !== 'active') {
            return redirect()->back()->with('error', "Your account is currently {$user->status}. Access denied.");
        }

        // 0.0 PIN Verification
        if (empty($user->transaction_pin)) {
            return redirect()->back()->with('error', 'You have not set a transaction PIN. Please set one in your profile.');
        }

        // Check PIN lockout
        $pinLockKey = 'pin_attempts_' . $user->id;
        $pinAttempts = Cache::get($pinLockKey, 0);
        if ($pinAttempts >= 5) {
            return redirect()->back()->with('error', 'Your PIN has been temporarily locked due to multiple incorrect attempts. Please try again in 15 minutes.');
        }

        if (!Hash::check($request->pin, $user->transaction_pin)) {
            Cache::put($pinLockKey, $pinAttempts + 1, now()->addMinutes(15));
            Log::warning('Failed SME data PIN attempt', [
                'user_id'        => $user->id,
                'ip'             => $request->ip(),
                'attempt_number' => $pinAttempts + 1,
            ]);
            return redirect()->back()->with('error', 'Invalid transaction PIN.');
        }

        // Correct PIN — reset lockout counter
        Cache::forget($pinLockKey);

        // Double-click/Concurrency lock (reject rapid concurrent requests)
        $lockKey = 'sme_data_purchase_lock_' . $user->id;
        $lock = Cache::lock($lockKey, 30); // 30-second lock

        if (!$lock->get()) {
            return redirect()->back()->with('error', 'A transaction is already in progress. Please wait a moment.');
        }

        // 1. Idempotency Check (Prevent duplicate requests within 60 seconds)
        $recentTransaction = \App\Models\Report::where('user_id', $user->id)
            ->where('phone_number', $mobile)
            ->where('amount', $payableAmount)
            ->where('type', 'data')
            ->where('created_at', '>=', now()->subMinute())
            ->first();

        if ($recentTransaction) {
            $lock->release();
            return redirect()->back()->with('error', 'A similar transaction was recently processed. Please wait a moment before trying again.');
        }

        // Find the ServiceField for tracking
        $service = \App\Models\Service::where('name', 'smedata')->first();
        $serviceField = null;
        if ($service) {
            $networkKey = strtolower($plan->network);
            $serviceField = \App\Models\ServiceField::where('service_id', $service->id)
                ->where(function($q) use ($networkKey) {
                    $q->where('field_code', $networkKey)
                      ->orWhere('field_code', "sme_{$networkKey}")
                      ->orWhere('field_name', 'LIKE', "%{$networkKey}%");
                })->first();
        }

        $requestId = RequestIdHelper::generateRequestId();

        // 2. Start DB Transaction & Debit user first
        DB::beginTransaction();
        try {
            // Refetch wallet with lock
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet || $wallet->balance < $payableAmount) {
                DB::rollBack();
                $lock->release();
                return redirect()->back()->with('error', 'Insufficient wallet balance! You need ₦' . number_format($payableAmount, 2));
            }

            $oldBalance = $wallet->balance;
            $wallet->decrement('balance', $payableAmount);
            $newBalance = $wallet->balance;

            $transaction = Transaction::create([
                'transaction_ref' => $requestId,
                'user_id'         => $user->id,
                'amount'          => $payableAmount,
                'fee'             => 0,
                'net_amount'      => $payableAmount,
                'description'     => "SME Data purchase: " . $description,
                'type'            => 'debit',
                'status'          => 'processing',
                'metadata'        => json_encode([
                    'phone'        => $mobile,
                    'network'      => $plan->network,
                    'plan_type'    => $plan->plan_type,
                    'data_id'      => $planId,
                    'provider'     => $plan->provider,
                ]),
                'performed_by' => $user->first_name . ' ' . $user->last_name,
                'approved_by'  => $user->id,
            ]);

            $report = \App\Models\Report::create([
                'user_id'      => $user->id,
                'phone_number' => $mobile,
                'network'      => $plan->network,
                'ref'          => $requestId,
                'amount'       => $payableAmount,
                'status'       => 'processing',
                'type'         => 'data',
                'description'  => "SME Data purchase: " . $description,
                'old_balance'  => $oldBalance,
                'new_balance'  => $newBalance,
                'service_id'   => $serviceField ? $serviceField->service_id : null,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $lock->release();
            return redirect()->back()->with('error', 'Database error: ' . $e->getMessage());
        }

        // API Call to the active SME data vendor
        try {
            $result = SmeDataProviderFactory::make()->purchase($mobile, $plan->network, $planId, $requestId, $plan->vendor_amount);
            $data = $result['data'];
            $isSuccess = $result['success'];

            if ($isSuccess) {
                // Success path
                $apiData = $result['api_data'];
                $transactionRef = $result['transaction_ref'];

                // Extract plan info from response if available, otherwise use our description
                $apiDescription = $data['message'] ?? $apiData['plan'] ?? $description;

                $transaction->update([
                    'transaction_ref' => $transactionRef,
                    'description'     => "SME Data purchase: " . $apiDescription,
                    'status'          => 'completed',
                    'metadata'        => json_encode([
                        'phone'        => $mobile,
                        'network'      => $plan->network,
                        'plan_type'    => $plan->plan_type,
                        'data_id'      => $planId,
                        'provider'     => $plan->provider,
                        'api_response' => $data,
                        'api_data'     => $apiData
                    ]),
                ]);

                $report->update([
                    'ref'          => $transactionRef,
                    'status'       => 'completed',
                    'description'  => "SME Data purchase: " . $apiDescription,
                ]);

                // Award commission if configured
                $commissionAmount = 0.00;
                if ($serviceField) {
                    $servicePrice = \App\Models\ServicePrice::where('service_fields_id', $serviceField->id)
                        ->where('user_type', $user->role ?? 'personal')
                        ->whereNull('user_id')
                        ->first();

                    if ($servicePrice && $servicePrice->commission > 0) {
                        $commissionAmount = (float) $servicePrice->commission;
                    }
                }

                if ($commissionAmount > 0) {
                    DB::transaction(function () use ($user, $commissionAmount, $mobile, $plan, $service, $transactionRef) {
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
                                'description'     => "Commission earned on SME Data purchase (" . $mobile . ")",
                                'type'            => 'credit',
                                'status'          => 'completed',
                                'metadata'        => json_encode([
                                    'mobile'  => $mobile,
                                    'network' => $plan->network,
                                    'type'    => 'data_commission',
                                    'parent_ref' => $transactionRef,
                                ]),
                                'performed_by' => 'System',
                            ]);

                            \App\Models\Report::create([
                                'user_id'      => $user->id,
                                'phone_number' => $mobile,
                                'network'      => $plan->network,
                                'ref'          => $commRef,
                                'amount'       => $commissionAmount,
                                'status'       => 'completed',
                                'type'         => 'commission',
                                'description'  => "Commission earned on SME Data Purchase: " . $mobile,
                                'old_balance'  => $oldBal,
                                'new_balance'  => $newBal,
                                'service_id'   => $service ? $service->id : null,
                            ]);
                        }
                    });
                }

                $lock->release();
                return redirect()->back()->with('success', "Data purchase of {$plan->size} ({$plan->plan_type}) for {$mobile} was successful!");
            } else {
                $errorMessage = $data['message'] ?? $data['msg'] ?? 'Data purchase failed. Please try again.';
                DB::transaction(function () use ($wallet, $payableAmount, $transaction, $report, $errorMessage) {
                    $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                    if ($w) {
                        $w->increment('balance', $payableAmount);
                    }
                    $transaction->update(['status' => 'failed']);
                    $report->update([
                        'status'      => 'failed',
                        'description' => "Failed: " . $errorMessage,
                    ]);
                });
                $lock->release();
                return redirect()->back()->with('error', $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('SME Data API Connection Error: ' . $e->getMessage());
            DB::transaction(function () use ($wallet, $payableAmount, $transaction, $report, $e) {
                $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                if ($w) {
                    $w->increment('balance', $payableAmount);
                }
                $transaction->update(['status' => 'failed']);
                $report->update([
                    'status'      => 'failed',
                    'description' => "Failed: Connection Error - " . $e->getMessage(),
                ]);
            });
            $lock->release();
            return redirect()->back()->with('error', 'Could not connect to data provider. Please try again later.');
        } finally {
            $lock->release();
        }
    }
}
