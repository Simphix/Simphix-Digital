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

class NINPhoneVerificationController extends Controller
{
    /**
     * Show Phone verification page
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Phone NIN Verification', 'code' => 'V105', 'price' => 100],
            ['name' => 'Regular Slip', 'code' => 'V102', 'price' => 100],
            ['name' => 'standard slip', 'code' => '611', 'price' => 100],
            ['name' => 'preminum slip', 'code' => '612', 'price' => 150],
        ]);
        
        // Get Prices
        $phonePrice = 0;
        $regularSlipPrice = 0;
        $standardSlipPrice = 0;
        $premiumSlipPrice = 0;

        if ($service) {
            $phoneField = $service->fields()->where('field_code', 'V105')->first();
            $regularField = $service->fields()->where('field_code', 'V102')->first();
            $standardField = $service->fields()->where('field_code', '611')->first();
            $premiumField = $service->fields()->where('field_code', '612')->first();

            $phonePrice = $phoneField ? $phoneField->getPriceForUserType($user->role) : 0;
            $regularSlipPrice = $regularField ? $regularField->getPriceForUserType($user->role) : 0;
            $standardSlipPrice = $standardField ? $standardField->getPriceForUserType($user->role) : 0;
            $premiumSlipPrice = $premiumField ? $premiumField->getPriceForUserType($user->role) : 0;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('verification.nin-phone-verification', [
            'wallet' => $wallet,
            'phonePrice' => $phonePrice,
            'regularSlipPrice' => $regularSlipPrice,
            'standardSlipPrice' => $standardSlipPrice,
            'premiumSlipPrice' => $premiumSlipPrice,
        ]);
    }

    /**
     * Store new Phone verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'phone_number' => 'required|string|size:11|regex:/^[0-9]{11}$/',
        ]);

        // 1. Get Verification Service using ServiceManager
        $service = ServiceManager::getServiceWithFields('Verification', [
            ['name' => 'Phone NIN Verification', 'code' => 'V105', 'price' => 100],
        ]);

        if (!$service) {
            return back()->with([
                'status' => 'error',
                'message' => 'Verification service not available.'
            ]);
        }

        // 2. Get ServiceField (V105)
        $serviceField = $service->fields()
            ->where('field_code', 'V105')
            ->where('is_active', true)
            ->first();

        if (!$serviceField) {
            return back()->with([
                'status' => 'error',
                'message' => 'Phone verification service is not available.'
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

            $transactionRef = 'P' . (time() % 1000000000) . '-' . mt_rand(100, 999);
            $performedBy = $user->first_name . ' ' . $user->last_name;

            $transaction = Transaction::create([
                'transaction_ref' => $transactionRef,
                'user_id' => $user->id,
                'amount' => $servicePrice,
                'description' => "NIN Phone Verification - {$serviceField->field_name}",
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

        // 5. Call API outside transaction
        try {
            $requestId = RequestIdHelper::generateRequestId();
            $response = \App\Services\ArewaVerificationApi::verifyNinByPhone($request->phone_number, $requestId);

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
            $errorMessage = $data['message'] ?? 'Verification failed. Please try again.';
            
            // Check if it's an upstream provider error
            if (isset($data['message']) && (
                str_contains(strtolower($data['message']), 'upstream') ||
                str_contains(strtolower($data['message']), 'nimc') ||
                str_contains(strtolower($data['message']), 'unavailable') ||
                str_contains(strtolower($data['message']), 'service is currently')
            )) {
                \Log::warning('NIMC Service Unavailable', [
                    'phone' => $request->phone_number,
                    'user_id' => $user->id,
                    'response' => $data
                ]);
                
                return back()->with([
                    'status' => 'warning',
                    'message' => $errorMessage . ' This is a temporary issue with the verification service provider.'
                ]);
            }

            // Log API errors for debugging
            \Log::error('NIN Phone Verification API Error', [
                'phone' => $request->phone_number,
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

            \Log::error('NIN Phone Verification Connection Error', [
                'phone' => $request->phone_number ?? 'N/A',
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

            \Log::error('NIN Phone Verification Request Error', [
                'phone' => $request->phone_number ?? 'N/A',
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

            \Log::error('NIN Phone Verification System Error', [
                'phone' => $request->phone_number ?? 'N/A',
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
                \Log::warning('NIN Phone Verification - No data in response', [
                    'user_id' => $user->id,
                    'response' => $apiResponse
                ]);
            }
            
            $transaction->update([
                'status' => 'completed',
                'metadata' => [
                    'service' => 'verification',
                    'service_field' => $serviceField->field_name,
                    'field_code' => $serviceField->field_code,
                    'phone' => $apiResponse['value'] ?? 'N/A',
                    'nin' => $ninData['nin'] ?? 'N/A',
                    'user_role' => $user->role,
                    'price_details' => [
                        'base_price' => $serviceField->base_price,
                        'user_price' => $servicePrice,
                    ],
                    'source' => 'Arewa API',
                    'api_response' => $apiResponse
                ],
            ]);

            // Map all fields from API response to verification table
            Verification::create([
                'user_id' => $user->id,
                'service_field_id' => $serviceField->id,
                'service_id' => $service->id,
                'transaction_id' => $transaction->id,
                'reference' => $transactionRef,
                'field_code' => $serviceField->field_code ?? null,
                'field_name' => $serviceField->field_name ?? null,
                'service_name' => $service->service_name ?? null,
                'service_type' => $service->service_type ?? null,
                'amount' => $servicePrice,
                
                // Personal Information
                'firstname' => $ninData['firstname'] ?? null,
                'middlename' => $ninData['middlename'] ?? null,
                'surname' => $ninData['surname'] ?? null,
                'gender' => $ninData['gender'] ?? null,
                'birthdate' => $ninData['birthdate'] ?? null,
                'birthstate' => $ninData['birthstate'] ?? null,
                'birthlga' => $ninData['birthlga'] ?? null,
                'birthcountry' => $ninData['birthcountry'] ?? null,
                'maritalstatus' => $ninData['maritalstatus'] ?? null,
                'email' => $ninData['email'] ?? null,
                'telephoneno' => $ninData['telephoneno'] ?? null,
                
                // Residence Information
                'residence_address' => $ninData['residence_AdressLine1'] ?? null,
                'residence_state' => $ninData['residence_state'] ?? null,
                'residence_lga' => $ninData['residence_lga'] ?? null,
                'residence_town' => $ninData['residence_Town'] ?? null,
                
                // Additional Information
                'religion' => $ninData['religion'] ?? null,
                'employmentstatus' => $ninData['emplymentstatus'] ?? null,
                'educationallevel' => $ninData['educationallevel'] ?? null,
                'profession' => $ninData['profession'] ?? null,
                'height' => $ninData['heigth'] ?? null,
                'title' => $ninData['title'] ?? null,
                
                // NIN and Identification
                'nin' => $ninData['nin'] ?? null,
                'number_nin' => $ninData['nin'] ?? null,
                'userid' => $ninData['centralID'] ?? null,
                'photo_path' => $ninData['photo'] ?? null,
                'signature_path' => $ninData['signature'] ?? null,
                'trackingId' => $ninData['trackingId'] ?? null,
                
                // Next of Kin Information
                'nok_firstname' => $ninData['nok_firstname'] ?? null,
                'nok_middlename' => $ninData['nok_middlename'] ?? null,
                'nok_surname' => $ninData['nok_surname'] ?? null,
                'nok_address1' => $ninData['nok_address1'] ?? null,
                'nok_address2' => $ninData['nok_address2'] ?? null,
                'nok_lga' => $ninData['nok_lga'] ?? null,
                'nok_state' => $ninData['nok_state'] ?? null,
                'nok_town' => $ninData['nok_town'] ?? null,
                'nok_postalcode' => $ninData['nok_postalcode'] ?? null,
                
                // Self Origin Information
                'self_origin_state' => $ninData['self_origin_state'] ?? null,
                'self_origin_lga' => $ninData['self_origin_lga'] ?? null,
                'self_origin_place' => $ninData['self_origin_place'] ?? null,
                
                // Transaction Information
                'performed_by' => $performedBy,
                'submission_date' => Carbon::now(),
                'status' => 'pending',
                'response_data' => $apiResponse
            ]);

            DB::commit();

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

            return redirect()->route('nin.phone.index')->with([
                'status' => 'success',
                'message' => "NIN Phone Verification successful. Reference: {$transactionRef}. Charged: NGN " . number_format($servicePrice, 2),
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

             $transactionRef = 'S2' . (time() % 1000000000) . '-' . mt_rand(100, 999);
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
