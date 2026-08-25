<?php

namespace App\Http\Controllers;

use App\Helpers\signatureHelper;
use App\Jobs\ProcessVatCharge;
use App\Mail\PaymentNotifyMail;
use App\Models\EnterprisePartnerPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WebhookLog;
use App\Services\EnterprisePartnerActivationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PaymentWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log raw body first (helps debugging if payload parsing fails)
        Log::info('Palmpay RAW Webhook Body: '.$request->getContent());

        $payload = $request->all();
        Log::info('Palmpay webhook hit:', ['payload' => $payload]);

        $log = WebhookLog::create([
            'provider'   => 'palmpay',
            'event_type' => (($payload['transType'] ?? null) == 41) ? 'payout' : 'payin',
            'payload'    => $payload,
            'status'     => 'received',
        ]);

        // Verify the signature
        if (!$this->verifySignature($payload)) {
            Log::warning('Invalid webhook signature received', ['payload' => $payload]);
            $log->update(['status' => 'invalid_signature']);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $this->processAndLog($payload, $log);

        return $log->status === 'success'
            ? response('success', 200)->header('Content-Type', 'text/plain')
            : response()->json(['error' => 'Server error'], 500);
    }

    /**
     * Shared processing path for both the live webhook route and the admin
     * "Retry" action, so there's exactly one place this logic lives.
     */
    public function processAndLog(array $payload, WebhookLog $log): void
    {
        try {
            $this->processReservedAccountTransaction($payload);
            $log->update(['status' => 'success', 'processed_at' => now(), 'attempts' => $log->attempts + 1]);
        } catch (\Throwable $e) {
            Log::error('Error processing webhook: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'attempts' => $log->attempts + 1]);
        }
    }

    private function verifySignature($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }

        $sign = $data['sign'];
        $publicKey = config('keys.public');

        if (!$publicKey) {
            Log::error('PalmPay public key not configured in config/keys.php');
            return false;
        }

        return signatureHelper::verify_callback_signature($data, $sign, $publicKey);
    }

    private function processReservedAccountTransaction($payload)
    {
        DB::transaction(function () use ($payload) {
            // transType 41 is Payout (Disbursement), others are Payin (Collections)
            if (($payload['transType'] ?? null) == 41) {
                Log::info('[PAYOUT Webhook]:', ['payload' => $payload]);
                $this->handlePayout($payload);
            } else {
                Log::info('[PAYIN Webhook]:', ['payload' => $payload]);

                $virtualAccountNo = $payload['virtualAccountNo'] ?? null;
                $orderNo          = $payload['merchantOrderNo'] ?? $payload['orderNo'] ?? null;
                $amountPaid       = isset($payload['orderAmount']) ? $payload['orderAmount'] / 100 : 0;
                $payerBankName    = $payload['payerBankName'] ?? '';
                $payerAccountName = $payload['payerAccountName'] ?? '';
                $service_description = 'Your wallet has been credited with ₦' . number_format($amountPaid, 2);
                $orderStatus      = $payload['orderStatus'] ?? null;

                if (!$virtualAccountNo || !$orderNo) {
                    Log::warning('Webhook missing accountNo or orderNo', ['payload' => $payload]);
                    return;
                }

                $virtualAccount = VirtualAccount::where('account_number', $virtualAccountNo)->first();

                if ($virtualAccount) {
                    $this->createTransactionForReservedAccount(
                        $virtualAccount->user_id,
                        $orderNo,
                        $amountPaid,
                        $payerBankName,
                        $payerAccountName,
                        $service_description,
                        $orderStatus,
                        $payload
                    );
                } else {
                    $this->handleEnterprisePartnerPayment($virtualAccountNo, $amountPaid, $orderStatus, $payload);
                }
            }
        });
    }

    /**
     * Purely additive fallback for a payin whose account_number doesn't
     * match any VirtualAccount (the general wallet-topup flow) — checks
     * whether it instead matches a pending EnterprisePartnerPayment (the
     * eligibility-purchase flow) before falling through to the existing
     * "not found" warning log.
     */
    private function handleEnterprisePartnerPayment(?string $virtualAccountNo, float $amountPaid, $orderStatus, array $payload): void
    {
        $payment = $virtualAccountNo
            ? EnterprisePartnerPayment::where('account_number', $virtualAccountNo)->where('status', 'pending')->first()
            : null;

        if (!$payment) {
            Log::warning('Virtual account not found for account_number: '.$virtualAccountNo, ['payload' => $payload]);

            return;
        }

        if ($orderStatus != 1 || $amountPaid < (float) $payment->expected_amount) {
            Log::info('Enterprise Partner payment received but not yet sufficient/successful, skipping activation', [
                'account_number' => $virtualAccountNo,
                'order_status'   => $orderStatus,
                'amount_paid'    => $amountPaid,
                'expected'       => (float) $payment->expected_amount,
            ]);

            return;
        }

        app(EnterprisePartnerActivationService::class)->activate($payment);
    }

    private function handlePayout($payload)
    {
        $orderNo = $payload['merchantOrderNo'] ?? $payload['orderNo'] ?? null;
        $status = $payload['orderStatus'] ?? null; // usually 1 for success

        if (!$orderNo) return;

        $transaction = Transaction::where('transaction_ref', $orderNo)->first();
        if ($transaction) {
            $newStatus = ($status == 1) ? 'completed' : (($status == 2) ? 'failed' : 'pending');
            $transaction->update([
                'status' => $newStatus,
                'metadata' => array_merge($transaction->metadata ?? [], ['webhook_update' => $payload])
            ]);
            Log::info("Payout transaction {$orderNo} updated to {$newStatus}");
        }
    }

    private function createTransactionForReservedAccount($userId, $orderNo, $amountPaid, $payerBankName, $payerAccountName, $service_description, $orderStatus, $payload)
    {
        $transaction = Transaction::where('transaction_ref', $orderNo)->lockForUpdate()->first();

        if ($transaction) {
            if ($transaction->status === 'completed') {
                Log::info("Transaction {$orderNo} already completed. Skipping.");
                return;
            }

            $this->updateTransaction($orderNo, $amountPaid, $payerBankName, $payerAccountName, $service_description, $orderStatus, $userId, $payload);
            
            if ($orderStatus == 1) {
                $this->processSuccessFunding($userId, $amountPaid, $orderNo, $payerBankName, $payload);
            }
        } else {
            // Only credit wallet if status is successful ( PalmsPay usually 1 for success)
            if ($orderStatus == 1 || $orderStatus === null) {
                $this->insertTransaction($userId, $orderNo, $amountPaid, $payerAccountName, $payerBankName, $service_description, $payload);
                $this->processSuccessFunding($userId, $amountPaid, $orderNo, $payerBankName, $payload);
            } else {
                Log::info("Transaction {$orderNo} skipped due to status: {$orderStatus}");
            }
        }
    }

    private function processSuccessFunding($userId, $amountPaid, $orderNo, $payerBankName, $payload)
    {
        $this->updateWalletBalance($userId, $amountPaid);

        // Check for 10,000 threshold logic
        if ($amountPaid >= 10000) {
            $chargeAmount = 50;
            $chargeDesc = 'transaction lavy charge';
            $chargeRef = 'CHG-' . strtoupper(Str::random(10));
            
            $this->debitWallet($userId, $chargeAmount, $chargeDesc, $chargeRef, $orderNo);

            // Schedule VAT Charge (15 Naira) after 1 minute. Non-blocking: a
            // dispatch failure here must never roll back the wallet credit
            // this whole method runs inside a DB transaction for.
            try {
                ProcessVatCharge::dispatch($userId, $orderNo)->delay(now()->addMinute());
            } catch (\Throwable $e) {
                Log::error('Non-blocking error dispatching ProcessVatCharge: '.$e->getMessage());
            }
        }

        $this->sendNotificationAndEmail($userId, $amountPaid, $orderNo, $payerBankName, 'Topup');
    }

    private function updateTransaction($orderNo, $amountPaid, $payerBankName, $payerAccountName, $service_description, $orderStatus, $userId, $payload)
    {
        $status = ($orderStatus == 1) ? 'completed' : 'pending';

        $user = User::find($userId);
        $performedBy = $user ? $user->first_name . ' ' . $user->last_name : 'System';

        Transaction::where('transaction_ref', $orderNo)
            ->update([
                'description'         => $service_description,
                'amount'              => $amountPaid,
                'fee'                 => 0.00,
                'net_amount'          => $amountPaid,
                'payer_name'          => $payerAccountName,
                'status'              => $status,
                'performed_by'        => $performedBy,
                'metadata'            => $payload,
                'updated_at'          => Carbon::now(),
            ]);

        Log::info('Transaction updated for '.$orderNo);
    }

    private function insertTransaction($userId, $orderNo, $amountPaid, $payerAccountName, $payerBankName, $service_description, $payload)
    {
        $user = User::find($userId);
        $performedBy = $user ? $user->first_name . ' ' . $user->last_name : 'System';

        Transaction::create([
            'user_id'        => $userId,
            'payer_name'     => $payerAccountName,
            'transaction_ref'=> $orderNo,
            'type'           => 'credit',
            'description'    => $service_description,
            'amount'         => $amountPaid,
            'fee'            => 0.00,
            'net_amount'     => $amountPaid,
            'status'         => 'completed',
            'performed_by'   => $performedBy,
            'metadata'       => $payload,
        ]);

        Log::info('New transaction inserted for '.$orderNo);
    }

    private function updateWalletBalance($userId, $amountPaid)
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if ($wallet) {
            $wallet->credit($amountPaid);
            Log::info('Wallet updated for user '.$userId.' with amount '.$amountPaid);
        } else {
            Log::warning('Wallet not found for user ID: '.$userId);
        }
    }

    private function debitWallet($userId, $amount, $desc, $ref, $relatedRef)
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if ($wallet) {
            $wallet->debit($amount);

            Transaction::create([
                'user_id' => $userId,
                'transaction_ref' => $ref,
                'type' => 'debit',
                'amount' => $amount,
                'fee' => 0.00,
                'net_amount' => $amount,
                'description' => $desc,
                'status' => 'completed',
                'performed_by' => 'System',
                'metadata' => ['related_transaction' => $relatedRef, 'type' => 'levy_charge'],
            ]);
        }
    }

    private function sendNotificationAndEmail($userId, $amountPaid, $orderNo, $bankName, $type)
    {
        try {
            $user = User::query()->find($userId);
            if (!$user || !$user->email) {
                Log::warning('Skip email: No user or email found for ID '.$userId);
                return;
            }

            $mail_data = [
                'type'     => $type,
                'amount'   => number_format($amountPaid, 2),
                'ref'      => $orderNo,
                'bankName' => $bankName,
            ];

            // Use queue() instead of send() to avoid blocking or failing the webhook response
            Mail::to($user->email)->queue(new PaymentNotifyMail($mail_data));
            
            Log::info('Payment notification queued for '.$user->email);
        } catch (\Throwable $e) {
            // Log the error but do not throw, so the transaction remains "completed" in the database
            Log::error('Non-blocking error in sendNotificationAndEmail: '.$e->getMessage());
        }
    }
}
