<?php

namespace App\Http\Controllers\Verification;

use App\Http\Controllers\Controller;
use App\Helpers\RequestIdHelper;
use App\Helpers\ServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Verification;
use App\Models\Transaction;
use App\Models\Service;
use App\Models\ServiceField;
use App\Models\Wallet;
use App\Repositories\BVN_PDF_Repository;
use Carbon\Carbon;

class BvnverificationController extends Controller
{

    /**
     * Show BVN verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Bvn verification', 'code' => '600', 'price' => 70],
            ['name' => 'standard slip', 'code' => '601', 'price' => 50],
            ['name' => 'preminum slip', 'code' => '602', 'price' => 100],
            ['name' => 'plastic slip', 'code' => '603', 'price' => 150],
        ]);
        
        // Get Prices
        $verificationPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;
        $plasticSlipPrice = 0;

        if ($service) {
            $verificationField = $service->fields()->where('field_code', '600')->first();
            $standardSlipField = $service->fields()->where('field_code', '601')->first();
            $premiumSlipField = $service->fields()->where('field_code', '602')->first();
            $plasticSlipField = $service->fields()->where('field_code', '603')->first();

            $verificationPrice = $verificationField ? $verificationField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardSlipField ? $standardSlipField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumSlipField ? $premiumSlipField->getPriceForUserType($user->role) : 0;
            $plasticSlipPrice = $plasticSlipField ? $plasticSlipField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.bvn-verification', [
            'wallet' => $wallet,
            'verificationPrice' => $verificationPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
            'plasticSlipPrice' => $plasticSlipPrice,
        ]);
    }

    /**
     * Store new BVN verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bvn' => 'required|string|size:11|regex:/^[0-9]{11}$/',
        ]);

        // 1. Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Bvn verification', 'code' => '600', 'price' => 70],
        ]);

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get BVN Verification ServiceField (600)
        $serviceField = $service->fields()
            ->where('field_code', '600')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'BVN verification service is not available.'
            ]);
        }

        // 3. Determine service price based on user role
        $servicePrice = $serviceField->getPriceForUserType($user->role);

        // 4. Start DB Transaction & Debit user first
        DB::beginTransaction();
        try {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet) {
                DB::rollBack();
                return back()->with([
                    'status' => 'error',
                    'message' => 'Wallet not found.'
                ]);
            }

            if ($wallet->status !== 'active') {
                DB::rollBack();
                return back()->with([
                    'status' => 'error',
                    'message' => 'Your wallet is not active.'
                ]);
            }

            if ($wallet->balance < $servicePrice) {
                DB::rollBack();
                return back()->with([
                    'status' => 'error',
                    'message' => 'Insufficient wallet balance. You need NGN ' . number_format($servicePrice - $wallet->balance, 2)
                ]);
            }

            // Deduct wallet balance
            $wallet->decrement('balance', $servicePrice);

            $transactionRef = 'Ver-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'transaction_ref' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'description' => "BVN Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'processing',
                'performed_by'    => $performedBy,
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'bvn' => $request->bvn,
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                ],
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with([
                'status' => 'error',
                'message' => 'Failed to initialize transaction: ' . $e->getMessage()
            ]);
        }

        // 5. Call API outside the transaction
        try {
            $requestId = RequestIdHelper::generateRequestId();
            $response = \App\Services\ArewaVerificationApi::verifyBvn($request->bvn, $requestId);

            $decodedData = $response->json();

            if (!$response->successful() || 
                (isset($decodedData['status']) && $decodedData['status'] === 'error') || 
                !isset($decodedData['data'])
            ) {
                // Refund
                DB::transaction(function () use ($wallet, $servicePrice, $transaction) {
                    $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                    if ($w) {
                        $w->increment('balance', $servicePrice);
                    }
                    $transaction->update(['status' => 'failed']);
                });

                return back()->with([
                    'status' => 'error',
                    'message' => $decodedData['message'] ?? 'Verification failed or no data returned.'
                ]);
            }

            $status = $decodedData['status'] ?? 'UNKNOWN';

            if ($status === 'success') {
                 return $this->finalizeSuccessTransaction(
                    $wallet,
                    $servicePrice,
                    $user,
                    $serviceField,
                    $service,
                    $decodedData,
                    $transaction,
                    $transactionRef,
                    $performedBy
                 );
            } else {
                // Refund
                DB::transaction(function () use ($wallet, $servicePrice, $transaction) {
                    $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                    if ($w) {
                        $w->increment('balance', $servicePrice);
                    }
                    $transaction->update(['status' => 'failed']);
                });

                return back()->with([
                    'status' => 'error',
                    'message' => $decodedData['message'] ?? 'Verification failed.'
                ]);
            }

        } catch (\Exception $e) {
             // Refund
             try {
                 DB::transaction(function () use ($wallet, $servicePrice, $transaction) {
                     $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                     if ($w) {
                         $w->increment('balance', $servicePrice);
                     }
                     $transaction->update(['status' => 'failed']);
                 });
             } catch (\Exception $refException) {
                 Log::error('Refund failed after verification exception: ' . $refException->getMessage());
             }

             return back()->with([
                'status' => 'error',
                'message' => 'System Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful transaction (Finalize transaction & Verification Record)
     */
    private function finalizeSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $bvnData, $transaction, $transactionRef, $performedBy)
    {
        // Normalize bvnData structure if nested under api_response
        if (isset($bvnData['data']['api_response']['data'])) {
            $bvnData['data'] = $bvnData['data']['api_response']['data'];
        }

        DB::beginTransaction();

        try {
            $transaction->update([
                'transaction_ref' => $transactionRef,
                'status' => 'completed',
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'bvn' => $bvnData['data']['bvn'] ?? 'N/A',
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'API',
                    'api_response' => $bvnData
                ],
            ]);

            $apiData = $bvnData['data'] ?? [];

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'idno' => $apiData['bvn'] ?? '',
                'firstname' => $apiData['firstName'] ?? ($apiData['first_name'] ?? ''),
                'middlename' => $apiData['middleName'] ?? ($apiData['middle_name'] ?? ''),
                'surname' => $apiData['lastName'] ?? ($apiData['last_name'] ?? ''),
                'birthdate' =>  $apiData['dob'] ?? ($apiData['birthday'] ?? ''),
                'gender' => $apiData['gender'] ?? '',
                'telephoneno' => $apiData['phoneNumber'] ?? ($apiData['phone'] ?? ''),
                'photo_path' => $apiData['photo'] ?? '',
                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now()
            ]);

            DB::commit();

            // Flash normalized verification data for Blade
            session()->flash('verification', $bvnData);

            return redirect()->route('bvn.verification.index')->with([
                'status' => 'success',
                'message' => "BVN Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            // Since finalizing failed, we should try to refund the user
            try {
                DB::transaction(function () use ($wallet, $servicePrice, $transaction) {
                    $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                    if ($w) {
                        $w->increment('balance', $servicePrice);
                    }
                    $transaction->update(['status' => 'failed']);
                });
            } catch (\Exception $refException) {
                Log::error('Refund failed after verification finalization failure: ' . $refException->getMessage());
            }

            return back()->with([
                'status' => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Charge for Slip Download
     */
    private function chargeForSlip($user, $fieldCode, $bvn)
    {
         // 1. Get Verification Service using ServiceManager
         $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'standard slip', 'code' => '601', 'price' => 50],
            ['name' => 'preminum slip', 'code' => '602', 'price' => 100],
            ['name' => 'plastic slip', 'code' => '603', 'price' => 150],
        ]);

        if (!$service) {
            throw new \Exception('Verification service not available.');
        }

        // 2. Get ServiceField
        $serviceField = $service->fields()
            ->where('field_code', $fieldCode)
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
             throw new \Exception('Slip service not available.');
        }

        // 3. Determine service price based on user role
        $servicePrice = $serviceField->getPriceForUserType($user->role);

        DB::beginTransaction();
        try {
             // 4. Fetch and lock wallet
             $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
             if (!$wallet) {
                 throw new \Exception('Wallet not found.');
             }

             if ($wallet->status !== 'active') {
                  throw new \Exception('Your wallet is not active.');
             }

             if ($wallet->balance < $servicePrice) {
                  throw new \Exception('Insufficient wallet balance.');
             }

             $transactionRef = 'Slip-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
             $performedBy = $user->first_name . ' ' . $user->last_name;
  
             Transaction::create([
                 'transaction_ref' => $transactionRef,
                 'user_id' => $user->id,
                 'amount' => $servicePrice,
                 'description' => "Slip Download: {$serviceField->field_name}",
                 'type' => 'debit',
                 'status' => 'completed',
                 'performed_by'    => $performedBy,
                 'metadata' => [
                     'service' => 'slip_download',
                     'service_field' => $serviceField->field_name,
                     'field_code' => $serviceField->field_code,
                     'user_role' => $user->role,
                     'bvn' => $bvn,
                     'price_details' => [
                         'base_price' => $serviceField->base_price,
                         'user_price' => $servicePrice,
                     ],
                 ],
             ]);
  
             // Deduct wallet balance
             $wallet->decrement('balance', $servicePrice);
             
             DB::commit();
             return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * Download PDF slips
     */
    public function standardBVN($bvn_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $bvn_no)) {
                return response()->json([
                    "message" => "Error",
                    "errors" => array("Validation" => "Invalid BVN number format.")
                ], 422);
            }

            $record = Verification::where('idno', $bvn_no)->latest()->first();
            if (!$record) {
                return response()->json([
                    "message" => "Error",
                    "errors" => array("NotFound" => "Verification record not found.")
                ], 404);
            }

            $this->chargeForSlip(Auth::user(), '601', $bvn_no); // Charge for Standard Slip
            
            $veridiedRecord = $record;
            $view = view('freeBVN', compact('veridiedRecord'))->render();
            return response()->json(['view' => $view]);
        } catch (\Exception $e) {
             return response()->json([
                "message" => "Error",
                "errors" => array("Charge Failed" => $e->getMessage())
            ], 422);
        }
    }

    public function premiumBVN($bvn_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $bvn_no)) {
                return response()->json([
                    "message" => "Error",
                    "errors" => array("Validation" => "Invalid BVN number format.")
                ], 422);
            }

            $record = Verification::where('idno', $bvn_no)->latest()->first();
            if (!$record) {
                return response()->json([
                    "message" => "Error",
                    "errors" => array("NotFound" => "Verification record not found.")
                ], 404);
            }

            $this->chargeForSlip(Auth::user(), '602', $bvn_no); // Charge for Premium Slip

            $veridiedRecord = $record;
            $view = view('PremiumBVN', compact('veridiedRecord'))->render();
            return response()->json(['view' => $view]);
        } catch (\Exception $e) {
            return response()->json([
               "message" => "Error",
               "errors" => array("Charge Failed" => $e->getMessage())
           ], 422);
       }
    }

    public function plasticBVN($bvn_no)
    {
         try {
            if (!preg_match('/^[0-9]{11}$/', $bvn_no)) {
                return back()->with('error', 'Invalid BVN number format.');
            }

            $record = Verification::where('idno', $bvn_no)->latest()->first();
            if (!$record) {
                return back()->with('error', 'Verification record not found.');
            }

            $this->chargeForSlip(Auth::user(), '603', $bvn_no); // Charge for Plastic Slip
            
            $repObj = new BVN_PDF_Repository();
            return $repObj->plasticPDF($bvn_no);
         } catch (\Exception $e) {
             // For plastic PDF, we might need to return a view or redirect with error since it's a direct link
             return back()->with('error', $e->getMessage())
                         ->with('status', 'error')
                         ->with('message', $e->getMessage());
        }
    }
}
