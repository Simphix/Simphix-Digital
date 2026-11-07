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
use App\Repositories\NIN_PDF_Repository;
use Carbon\Carbon;

class NINverificationController extends Controller
{
    /**
     * Show NIN verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Verify NIN', 'code' => '610', 'price' => 80],
            ['name' => 'Regular Slip', 'code' => 'V102', 'price' => 100],
            ['name' => 'standard slip', 'code' => '611', 'price' => 100],
            ['name' => 'preminum slip', 'code' => '612', 'price' => 150],
            ['name' => '1Vnin slip', 'code' => '616', 'price' => 100],
        ]);
        
        // Get Prices
        $verificationPrice = 0;
        $regularSlipPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;
        $vninSlipPrice = 0;

        if ($service) {
            $verificationField = $service->fields()->where('field_code', '610')->first();
            $regularSlipField = $service->fields()->where('field_code', 'V102')->first();
            $standardSlipField = $service->fields()->where('field_code', '611')->first();
            $premiumSlipField = $service->fields()->where('field_code', '612')->first();
            $vninSlipField = $service->fields()->where('field_code', '616')->first();

            $verificationPrice = $verificationField ? $verificationField->getPriceForUserType($user->role) : 0;
            $regularSlipPrice = $regularSlipField ? $regularSlipField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardSlipField ? $standardSlipField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumSlipField ? $premiumSlipField->getPriceForUserType($user->role) : 0;
            $vninSlipPrice = $vninSlipField ? $vninSlipField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-verification', [
            'wallet' => $wallet,
            'verificationPrice' => $verificationPrice,
            'regularSlipPrice' => $regularSlipPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
            'vninSlipPrice' => $vninSlipPrice,
        ]);
    }

    /**
     * Store new NIN verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'number_nin' => 'required|string|size:11|regex:/^[0-9]{11}$/',
        ]);

        // 1. Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Verify NIN', 'code' => '610', 'price' => 80],
        ]);

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get NIN Verification ServiceField (610)
        $serviceField = $service->fields()
            ->where('field_code', '610')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'NIN verification service is not available.'
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
                'description' => "NIN Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'processing',
                'performed_by'    => $performedBy,
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'nin' => $request->number_nin,
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
            $response = \App\Services\ArewaVerificationApi::verifyNin($request->number_nin, $requestId);

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

            // Normalize decodedData structure if nested under api_response
            if (isset($decodedData['data']['api_response']['data'])) {
                $decodedData['data'] = $decodedData['data']['api_response']['data'];
            }

            $status = $decodedData['status'] ?? 'UNKNOWN';

            if ($status === 'success') {
                // Check if NIN is suspended (contains **** in critical fields)
                $apiData = $decodedData['data'] ?? [];
                
                $isSuspended = false;
                $suspendedFields = ['firstname', 'surname', 'birthdate', 'birthstate', 'gender'];
                
                foreach ($suspendedFields as $field) {
                    $value = $apiData[$field] ?? ($apiData[str_replace('_', '', strtolower($field))] ?? '');
                    if (strpos($value, '****') !== false || strpos($value, '*****') !== false || $value === '*') {
                        $isSuspended = true;
                        break;
                    }
                }
                
                if ($isSuspended) {
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
                        'message' => 'This NIN is suspended and cannot be verified. Please contact NIMC for assistance.'
                    ]);
                }
                
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
    private function finalizeSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $ninData, $transaction, $transactionRef, $performedBy)
    {
        // Normalize ninData structure if nested under api_response
        if (isset($ninData['data']['api_response']['data'])) {
            $ninData['data'] = $ninData['data']['api_response']['data'];
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
                    'nin' => $ninData['data']['nin'] ?? 'N/A',
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'API',
                    'api_response' => $ninData
                ],
            ]);

            $apiData = $ninData['data'] ?? [];

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'number_nin' => $apiData['nin'] ?? ($apiData['number_nin'] ?? ''),
                'firstname' => $apiData['firstName'] ?? ($apiData['first_name'] ?? ''),
                'middlename' => $apiData['middleName'] ?? ($apiData['middle_name'] ?? ''),
                'surname' => $apiData['surname'] ?? ($apiData['last_name'] ?? ''),
                'birthdate' =>  $apiData['birthDate'] ?? ($apiData['dob'] ?? ($apiData['birthday'] ?? '')),
                'gender' => $apiData['gender'] ?? '',
                'telephoneno' => $apiData['telephoneNo'] ?? ($apiData['phone'] ?? ($apiData['phoneNumber'] ?? '')),
                'photo_path' => $apiData['photo'] ?? '',
                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now()
            ]);

            DB::commit();

            // Flash normalized verification data for Blade
            session()->flash('verification', $ninData);

            return redirect()->route('nin.verification.index')->with([
                'status' => 'success',
                'message' => "NIN Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
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
    private function chargeForSlip($user, $fieldCode, $nin)
    {
         // 1. Get Verification Service using ServiceManager
         $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Regular Slip', 'code' => 'V102', 'price' => 100],
            ['name' => 'standard slip', 'code' => '611', 'price' => 100],
            ['name' => 'preminum slip', 'code' => '612', 'price' => 150],
            ['name' => '1Vnin slip', 'code' => '616', 'price' => 100],
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
                     'nin' => $nin,
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
     * Download NIN slips
     */
    public function regularSlip($nin_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $nin_no)) {
                return back()->with('error', 'Invalid NIN number format.');
            }

            $record = Verification::where('number_nin', $nin_no)->latest()->first();
            if (!$record) {
                return back()->with('error', 'Verification record not found.');
            }

            $this->chargeForSlip(Auth::user(), 'V102', $nin_no); // Charge for Regular Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->regularPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                        ->with('status', 'error')
                        ->with('message', $e->getMessage());
        }
    }

    public function standardSlip($nin_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $nin_no)) {
                return back()->with('error', 'Invalid NIN number format.');
            }

            $record = Verification::where('number_nin', $nin_no)->latest()->first();
            if (!$record) {
                return back()->with('error', 'Verification record not found.');
            }

            $this->chargeForSlip(Auth::user(), '611', $nin_no); // Charge for Standard Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->standardPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                        ->with('status', 'error')
                        ->with('message', $e->getMessage());
        }
    }

    public function premiumSlip($nin_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $nin_no)) {
                return back()->with('error', 'Invalid NIN number format.');
            }

            $record = Verification::where('number_nin', $nin_no)->latest()->first();
            if (!$record) {
                return back()->with('error', 'Verification record not found.');
            }

            $this->chargeForSlip(Auth::user(), '612', $nin_no); // Charge for Premium Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->premiumPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                        ->with('status', 'error')
                        ->with('message', $e->getMessage());
        }
    }

    public function vninSlip($nin_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $nin_no)) {
                return back()->with('error', 'Invalid NIN number format.');
            }

            $record = Verification::where('number_nin', $nin_no)->latest()->first();
            if (!$record) {
                return back()->with('error', 'Verification record not found.');
            }

            $this->chargeForSlip(Auth::user(), '616', $nin_no); // Charge for VNIN Slip
            
            $repObj = new NIN_PDF_Repository();
            return $repObj->vninPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                        ->with('status', 'error')
                        ->with('message', $e->getMessage());
        }
    }
}
