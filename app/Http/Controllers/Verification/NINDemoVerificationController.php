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

class NINDemoVerificationController extends Controller
{
    /**
     * Show Demographic verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service using ServiceManager
        // Service code V100, and slip codes V101, V102
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Demo Verification', 'code' => 'V100', 'price' => 100],
            ['name' => 'Free Slip', 'code' => 'V101', 'price' => 0],
            ['name' => 'Regular Slip', 'code' => 'V102', 'price' => 100],
            ['name' => 'standard slip', 'code' => '611', 'price' => 100],
            ['name' => 'preminum slip', 'code' => '612', 'price' => 150],
        ]);
        
        // Get Prices
        $demoPrice = 0;
        $freeSlipPrice = 0;
        $regularSlipPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;

        if ($service) {
            $demoField = $service->fields()->where('field_code', 'V100')->first();
            $freeField = $service->fields()->where('field_code', 'V101')->first();
            $regularField = $service->fields()->where('field_code', 'V102')->first();
            $standardField = $service->fields()->where('field_code', '611')->first();
            $premiumField = $service->fields()->where('field_code', '612')->first();

            $demoPrice = $demoField ? $demoField->getPriceForUserType($user->role) : 0;
            $freeSlipPrice = $freeField ? $freeField->getPriceForUserType($user->role) : 0;
            $regularSlipPrice = $regularField ? $regularField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardField ? $standardField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumField ? $premiumField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-demo-verification', [
            'wallet' => $wallet,
            'demoPrice' => $demoPrice,
            'freeSlipPrice' => $freeSlipPrice,
            'regularSlipPrice' => $regularSlipPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
        ]);
    }

    /**
     * Store new Demographic verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'gender' => 'required|string|in:M,F',
            'dateOfBirth' => 'required|string', // Should match format in docs
        ]);

        // 1. Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Demo Verification', 'code' => 'V100', 'price' => 100],
        ]);

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get ServiceField (V100)
        $serviceField = $service->fields()
            ->where('field_code', 'V100')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'Demographic verification service is not available.'
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

            $transactionRef = 'D2' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'transaction_ref' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'description' => "NIN Demographic Verification - {$serviceField->field_name}",
                'type' => 'debit',
                'status' => 'processing',
                'performed_by'    => $performedBy,
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
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
            $response = \App\Services\ArewaVerificationApi::verifyNinDemo(
                $request->firstName,
                $request->lastName,
                $request->gender,
                $request->dateOfBirth,
                $requestId
            );

            $data = $response->json();

            // Check for successful response
            if ($response->successful() && isset($data['status']) && $data['status'] === true) {
                if (isset($data['api_response']['status']) && $data['api_response']['status'] === true) {
                     return $this->finalizeSuccessTransaction(
                        $wallet,
                        $servicePrice,
                        $user,
                        $serviceField,
                        $service,
                        $data,
                        $transaction,
                        $transactionRef,
                        $performedBy
                    );
                }
            }

            // Refund
            DB::transaction(function () use ($wallet, $servicePrice, $transaction) {
                $w = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
                if ($w) {
                    $w->increment('balance', $servicePrice);
                }
                $transaction->update(['status' => 'failed']);
            });

            // Handle different error scenarios
            $errorMessage = $data['message'] ?? 'Verification failed. Please check your details and try again.';
            
            // Check if it's an upstream provider error
            if (isset($data['message']) && (
                str_contains(strtolower($data['message']), 'upstream') ||
                str_contains(strtolower($data['message']), 'nimc') ||
                str_contains(strtolower($data['message']), 'unavailable') ||
                str_contains(strtolower($data['message']), 'service is currently')
            )) {
                \Log::warning('NIMC Service Unavailable', [
                    'firstName' => $request->firstName,
                    'lastName' => $request->lastName,
                    'user_id' => $user->id,
                    'response' => $data
                ]);
                
                return back()->with([
                    'status' => 'warning',
                    'message' => $errorMessage . ' This is a temporary issue with the verification service provider.'
                ]);
            }

            // Log API errors for debugging
            \Log::error('NIN Demo Verification API Error', [
                'firstName' => $request->firstName,
                'lastName' => $request->lastName,
                'user_id' => $user->id,
                'status_code' => $response->status(),
                'response' => $data
            ]);

            return back()->with([
                'status' => 'error',
                'message' => $errorMessage
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
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
                Log::error('Refund failed after verification connection error: ' . $refException->getMessage());
            }

            \Log::error('NIN Demo Verification Connection Error', [
                'firstName' => $request->firstName ?? 'N/A',
                'lastName' => $request->lastName ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with([
                'status' => 'error',
                'message' => 'Unable to connect to verification service. Please check your internet connection and try again.'
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
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
                Log::error('Refund failed after verification request error: ' . $refException->getMessage());
            }

            \Log::error('NIN Demo Verification Request Error', [
                'firstName' => $request->firstName ?? 'N/A',
                'lastName' => $request->lastName ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with([
                'status' => 'error',
                'message' => 'Verification request failed. Please try again later.'
            ]);
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
                Log::error('Refund failed after verification system error: ' . $refException->getMessage());
            }

            \Log::error('NIN Demo Verification System Error', [
                'firstName' => $request->firstName ?? 'N/A',
                'lastName' => $request->lastName ?? 'N/A',
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with([
                'status' => 'error',
                'message' => 'A system error occurred. Please contact support if this persists.'
            ]);
        }
    }

    /**
     * Process successful transaction (Finalize transaction & Verification Record)
     */
    private function finalizeSuccessTransaction($wallet, $servicePrice, $user, $serviceField, $service, $apiResponse, $transaction, $transactionRef, $performedBy)
    {
        DB::beginTransaction();

        try {
            // Extract data from API response - handle both array and single object
            $apiData = $apiResponse['api_response']['data'] ?? [];
            $dataArray = is_array($apiData) ? ($apiData['data'] ?? $apiData) : [];
            
            // Get the first record if it's an array, otherwise use empty array
            $ninData = [];
            if (is_array($dataArray) && !empty($dataArray)) {
                $ninData = isset($dataArray[0]) ? $dataArray[0] : $dataArray;
            }
            
            // Log if no data received but continue with transaction
            if (empty($ninData)) {
                \Log::warning('NIN Demo Verification - No data in response', [
                    'user_id' => $user->id,
                    'response' => $apiResponse
                ]);
            }
            
            // Check for masked/suspended NIN data (indicated by ****)
            $isSuspended = false;
            $suspendedFields = [];
            
            // Check critical fields for masking
            $criticalFields = ['firstname', 'surname', 'nin', 'telephoneno'];
            foreach ($criticalFields as $field) {
                if (isset($ninData[$field]) && str_contains($ninData[$field], '****')) {
                    $isSuspended = true;
                    $suspendedFields[] = $field;
                }
            }
            
            // Log if suspended NIN is detected
            if ($isSuspended) {
                \Log::warning('NIN Demo Verification - Suspended NIN Detected', [
                    'user_id' => $user->id,
                    'suspended_fields' => $suspendedFields,
                    'nin_data' => $ninData
                ]);
            }
            
            $transaction->update([
                'transaction_ref' => $transactionRef,
                'status' => "completed",
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'nin' => $ninData['nin'] ?? 'N/A',
                    'user_role' => $user->role,
                    'is_suspended' => $isSuspended,
                    'suspended_fields' => $suspendedFields,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'Arewa API',
                    'api_response' => $apiResponse
                ],
            ]);

            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'number_nin' => $ninData['nin'] ?? null,
                'firstname' => $ninData['firstname'] ?? null,
                'middlename' => $ninData['middlename'] ?? null,
                'surname' => $ninData['surname'] ?? null,
                'birthdate' =>  $ninData['birthdate'] ?? null,
                'gender' => $ninData['gender'] ?? null,
                'telephoneno' => $ninData['telephoneno'] ?? null,
                'photo_path' => $ninData['photo'] ?? null,
                'signature_path' => $ninData['signature'] ?? null,
                'residence_state' => $ninData['residence_state'] ?? null,
                'residence_lga' => $ninData['residence_lga'] ?? null,
                'residence_town' => $ninData['residence_town'] ?? null,
                'residence_address' => $ninData['residence_AdressLine1'] ?? null,
                'self_origin_state' => $ninData['self_origin_state'] ?? null,
                'trackingId' => $ninData['trackingId'] ?? null,
                'performed_by'    => $performedBy,
                'submission_date' => Carbon::now(),
                'status' => 'pending',
                'response_data' => $apiResponse
            ]);

            DB::commit();

            // Flash normalized verification data for Blade
            session()->flash('verification', [
                'data' => [
                    'nin' => $ninData['nin'] ?? 'N/A',
                    'firstName' => $ninData['firstname'] ?? 'N/A',
                    'surname' => $ninData['surname'] ?? 'N/A',
                    'middleName' => $ninData['middlename'] ?? 'N/A',
                    'birthDate' => $ninData['birthdate'] ?? 'N/A',
                    'gender' => $ninData['gender'] ?? 'N/A',
                    'telephoneNo' => $ninData['telephoneno'] ?? 'N/A',
                    'photo' => $ninData['photo'] ?? null,
                ]
            ]);

            // Prepare success message with warning if suspended
            $message = "NIN Demographic Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2);
            
            if ($isSuspended) {
                $message .= " | WARNING: This NIN appears to be suspended or restricted. The verification service returned masked data (****). Please contact NIMC for assistance.";
            }

            return redirect()->route('nin.demo.index')->with([
                'status' => $isSuspended ? 'warning' : 'success',
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

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
         $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Free Slip', 'code' => 'V101', 'price' => 0],
            ['name' => 'Regular Slip', 'code' => 'V102', 'price' => 100],
            ['name' => 'standard slip', 'code' => '611', 'price' => 100],
            ['name' => 'preminum slip', 'code' => '612', 'price' => 150],
         ]);

        if (!$service) {
            throw new \Exception('Verification service not available.');
        }

        $serviceField = $service->fields()
            ->where('field_code', $fieldCode)
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
             throw new \Exception('Slip service not available.');
        }

        $servicePrice = $serviceField->getPriceForUserType($user->role);
        
        DB::beginTransaction();
        try {
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

             $transactionRef = 'D-' . (time() % 1000000000) . '-' . mt_rand(100, 999);
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
  
             $wallet->decrement('balance', $servicePrice);
             
             DB::commit();
             return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function freeSlip($nin_no)
    {
        try {
            if (!preg_match('/^[0-9]{11}$/', $nin_no)) {
                return back()->with('error', 'Invalid NIN number format.');
            }

            $record = Verification::where('number_nin', $nin_no)->latest()->first();
            if (!$record) {
                return back()->with('error', 'Verification record not found.');
            }

            $this->chargeForSlip(Auth::user(), 'V101', $nin_no);
            $repObj = new NIN_PDF_Repository();
            return $repObj->regularPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                        ->with('status', 'error')
                        ->with('message', $e->getMessage());
        }
    }

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

            $this->chargeForSlip(Auth::user(), 'V102', $nin_no);
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

            $this->chargeForSlip(Auth::user(), '611', $nin_no);
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

            $this->chargeForSlip(Auth::user(), '612', $nin_no);
            $repObj = new NIN_PDF_Repository();
            return $repObj->premiumPDF($nin_no);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                        ->with('status', 'error')
                        ->with('message', $e->getMessage());
        }
    }
}
