<?php

namespace App\Http\Controllers;

use App\Exceptions\UnmatchedFintavaWebhook;
use App\Helpers\FintavaSignatureHelper;
use App\Jobs\ProcessVatCharge;
use App\Mail\PaymentNotifyMail;
use App\Models\CashoutSettings;
use App\Models\EnterprisePartnerPayment;
use App\Models\FintavaWalletTransfer;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WebhookLog;
use App\Services\EnterprisePartnerPaymentService;
use App\Services\FintavaService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Fintava (BaaS provider — replacing PalmPay) inbound webhook handling.
 * Events:
 * - account_funded (payin deposit into a user's Loma Bank account)
 * - customer_bank_transfer (payout status update for approved cashouts)
 * - debit_transfer_reversal (reversed payout refunded back to user's wallet)
 */
class FintavaWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $rawBody = $request->getContent();
        Log::info('Fintava RAW Webhook Body: ' . $rawBody);

        $payload = $request->all();
        $event = $payload['event'] ?? null;
        Log::info('Fintava webhook hit:', ['event' => $event, 'payload' => $payload]);

        $hash = hash('sha256', $rawBody);

        if (!FintavaSignatureHelper::verify($rawBody, $request->header('x-fintava-signature'), config('services.fintava.webhook_secret'))) {
            $reason = empty(config('services.fintava.webhook_secret'))
                ? 'FINTAVA_WEBHOOK_SECRET is not configured'
                : (empty($request->header('x-fintava-signature'))
                    ? 'x-fintava-signature header is missing'
                    : 'signature does not match the body');

            Log::warning('Invalid Fintava webhook signature received', ['event' => $event, 'reason' => $reason]);

            WebhookLog::firstOrCreate(
                ['provider' => 'fintava', 'payload_hash' => $hash, 'status' => 'invalid_signature'],
                ['event_type' => $event ?? 'unknown', 'payload' => $payload]
            );

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        try {
            return Cache::lock('fintava-webhook:' . $hash, 60)
                ->block(15, fn () => $this->receive($payload, $event, $hash));
        } catch (LockTimeoutException) {
            return response()->json(['error' => 'Busy, retry later'], 503);
        }
    }

    /** Record (once) and process a signature-verified delivery. */
    private function receive(array $payload, ?string $event, string $hash)
    {
        $log = WebhookLog::where('provider', 'fintava')
            ->where('payload_hash', $hash)
            ->where('status', '!=', 'invalid_signature')
            ->latest('id')
            ->first();

        if ($log && in_array($log->status, ['success', 'ignored'], true)) {
            Log::info("Duplicate Fintava webhook delivery matches log #{$log->id} ({$log->status}); not reprocessing.");

            return response('success', 200)->header('Content-Type', 'text/plain');
        }

        $log ??= WebhookLog::create([
            'provider'     => 'fintava',
            'event_type'   => $event ?? 'unknown',
            'payload'      => $payload,
            'payload_hash' => $hash,
            'status'       => 'received',
        ]);

        $this->processAndLog($payload, $log);

        return in_array($log->status, ['success', 'ignored', 'unmatched'], true)
            ? response('success', 200)->header('Content-Type', 'text/plain')
            : response()->json(['error' => 'Server error'], 500);
    }

    /**
     * Shared processing path for both the live webhook route and the admin "Retry" action.
     */
    public function processAndLog(array $payload, WebhookLog $log): void
    {
        try {
            $handled = $this->dispatchEvent($payload);
            $log->update([
                'status'       => $handled ? 'success' : 'ignored',
                'processed_at' => now(),
                'attempts'     => $log->attempts + 1,
            ]);
        } catch (UnmatchedFintavaWebhook $e) {
            $log->update([
                'status'        => 'unmatched',
                'error_message' => $e->getMessage(),
                'attempts'      => $log->attempts + 1,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error processing Fintava webhook: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'attempts'      => $log->attempts + 1,
            ]);
        }
    }

    private function dispatchEvent(array $payload): bool
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        match ($event) {
            'account_funded'          => $this->handleAccountFunded($data),
            'customer_bank_transfer'  => $this->handleBankTransfer($data),
            'debit_transfer_reversal' => $this->handleReversal($data),
            default                   => Log::info("Fintava webhook event '{$event}' is not handled — acknowledged.", ['data' => $data]),
        };

        return in_array($event, ['account_funded', 'customer_bank_transfer', 'debit_transfer_reversal'], true);
    }

    private function handleAccountFunded(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            $this->creditDeposit($data);

            return;
        }

        Cache::lock('fintava-deposit:' . $reference, 60)->block(15, fn () => $this->creditDeposit($data));
    }

    private function findFundedAccount(array $data): ?VirtualAccount
    {
        $beneficiary = $data['beneficiaryAccountNumber'] ?? null;
        $customerId = $data['userId'] ?? null;

        if (!$beneficiary && !$customerId) {
            $accountNumber = $data['accountNumber'] ?? null;

            return $accountNumber ? VirtualAccount::where('account_number', $accountNumber)->first() : null;
        }

        $account = $beneficiary ? VirtualAccount::where('account_number', $beneficiary)->first() : null;

        if (!$account && $customerId) {
            $account = VirtualAccount::where('provider_customer_id', $customerId)
                ->orWhere('account_reference', $customerId)
                ->first();
        }

        return $account;
    }

    private function creditDeposit(array $data): void
    {
        $virtualAccount = null;
        $amountPaid = 0.0;
        $reference = null;
        $shouldSweep = false;

        DB::transaction(function () use ($data, &$virtualAccount, &$amountPaid, &$reference, &$shouldSweep) {
            $reference = $data['reference'] ?? null;
            $amountPaid = isset($data['amount']) ? (float) $data['amount'] : 0.0;
            $senderName = $data['accountName'] ?? '';
            $status = $data['status'] ?? null;
            $description = 'Your wallet has been credited with ₦' . number_format($amountPaid, 2);

            if (!$reference || !($data['beneficiaryAccountNumber'] ?? $data['userId'] ?? $data['accountNumber'] ?? null)) {
                Log::warning('Fintava account_funded webhook missing reference or account identifier', ['data' => $data]);

                return;
            }

            $virtualAccount = $this->findFundedAccount($data);

            if (!$virtualAccount) {
                // Check if this deposit belongs to an Enterprise Partner application payment
                $beneficiaryNo = $data['beneficiaryAccountNumber'] ?? $data['accountNumber'] ?? null;
                $payment = $beneficiaryNo
                    ? EnterprisePartnerPayment::where('account_number', $beneficiaryNo)->where('status', 'pending')->first()
                    : null;

                if ($payment) {
                    if (strtolower((string) $status) === 'success' && $amountPaid >= (float) $payment->expected_amount) {
                        app(EnterprisePartnerPaymentService::class)->confirmPayment($payment);
                    }

                    return;
                }

                Log::error('Fintava deposit received but no funding account matches it — nobody was credited.', ['data' => $data]);

                throw new UnmatchedFintavaWebhook(
                    'No funding account matches this deposit (beneficiary account '
                    . ($data['beneficiaryAccountNumber'] ?? $data['accountNumber'] ?? 'unknown')
                    . ', Fintava customer ' . ($data['userId'] ?? 'unknown') . '). Nobody was credited.'
                );
            }

            $userId = $virtualAccount->user_id;
            $transaction = Transaction::where('provider_reference', $reference)
                ->orWhere('transaction_ref', $reference)
                ->lockForUpdate()
                ->first();

            if ($transaction) {
                if ($transaction->status === 'completed') {
                    Log::info("Fintava deposit {$reference} already completed. Skipping.");

                    return;
                }

                $this->updateDepositTransaction($transaction, $amountPaid, $senderName, $description, $status, $userId, $data);

                if (strtolower((string) $status) === 'success') {
                    $this->processSuccessFunding($userId, $amountPaid, $reference, $senderName, $data);
                    $shouldSweep = true;
                }

                return;
            }

            if (strtolower((string) $status) === 'success' || $status === null) {
                $this->insertDepositTransaction($userId, $reference, $amountPaid, $senderName, $description, $data);
                $this->processSuccessFunding($userId, $amountPaid, $reference, $senderName, $data);
                $shouldSweep = true;
            } else {
                Log::info("Fintava deposit {$reference} skipped due to status: {$status}");
            }
        });

        if ($shouldSweep && $virtualAccount && $reference) {
            $this->sweepToMerchantWallet($virtualAccount, $amountPaid, $reference, $data);
        }
    }

    private function handleBankTransfer(array $data): void
    {
        $reference = $data['reference'] ?? $data['customerReference'] ?? null;
        $status = $data['status'] ?? null;

        if (!$reference) {
            Log::warning('Fintava customer_bank_transfer webhook missing a reference', ['data' => $data]);

            return;
        }

        $transaction = Transaction::where('provider_reference', $reference)
            ->orWhere('transaction_ref', $reference)
            ->first();

        if (!$transaction) {
            Log::warning("No transaction found matching Fintava payout reference {$reference}", ['data' => $data]);

            return;
        }

        $newStatus = strtoupper((string) $status) === 'SUCCESS' ? 'completed' : 'failed';

        $transaction->update([
            'status'   => $newStatus,
            'metadata' => array_merge($transaction->metadata ?? [], ['webhook_update' => $data]),
        ]);

        Log::info("Fintava payout transaction {$transaction->transaction_ref} updated to {$newStatus}");
    }

    private function handleReversal(array $data): void
    {
        $reference = $data['transactionReference'] ?? $data['customerReference'] ?? $data['reference'] ?? null;

        if (!$reference) {
            Log::warning('Fintava debit_transfer_reversal webhook missing a reference', ['data' => $data]);

            return;
        }

        DB::transaction(function () use ($reference, $data) {
            $transaction = Transaction::where('provider_reference', $reference)
                ->orWhere('transaction_ref', $reference)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                Log::warning("No transaction found matching Fintava reversal reference {$reference}", ['data' => $data]);

                return;
            }

            if ($transaction->status === 'reversed') {
                Log::info("Transaction {$transaction->transaction_ref} already reversed. Skipping.");

                return;
            }

            $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();
            if ($wallet) {
                $wallet->credit((float) $transaction->amount);
            }

            $transaction->update([
                'status'   => 'reversed',
                'metadata' => array_merge($transaction->metadata ?? [], ['reversal' => $data]),
            ]);

            Log::alert("Fintava reversed a completed transfer — transaction {$transaction->transaction_ref} marked 'reversed' and ₦" . number_format((float) $transaction->amount, 2) . ' refunded to the wallet.');
        });
    }

    private function processSuccessFunding($userId, $amountPaid, $reference, $senderName, $payload): void
    {
        $this->updateWalletBalance($userId, $amountPaid);

        // Calculate dynamic Inflow Charges & Threshold Deductions
        $inflowCharges = CashoutSettings::current()->calculateInflowCharges((float) $amountPaid);

        // 1. Threshold Charge (e.g. if amount exceeds ₦9,999.99, deduct ₦50)
        if ($inflowCharges['threshold_met'] && $inflowCharges['threshold_fee'] > 0) {
            $chargeAmount = $inflowCharges['threshold_fee'];
            $chargeDesc = $inflowCharges['threshold_description'];
            $chargeRef = 'CHG-' . strtoupper(Str::random(10));

            $this->debitWallet($userId, $chargeAmount, $chargeDesc, $chargeRef, $reference, 'levy_charge');

            try {
                ProcessVatCharge::dispatch($userId, $reference)->delay(now()->addMinute());
            } catch (\Throwable $e) {
                Log::error('Non-blocking error dispatching ProcessVatCharge: ' . $e->getMessage());
            }
        }

        // 2. Base Inflow Fee (if configured, e.g. 0.3% capped at ₦300)
        if ($inflowCharges['has_base_fee'] && $inflowCharges['base_fee'] > 0) {
            $baseChargeRef = 'CHG-FEE-' . strtoupper(Str::random(8));
            $this->debitWallet($userId, $inflowCharges['base_fee'], $inflowCharges['base_fee_description'], $baseChargeRef, $reference, 'inflow_fee');
        }

        $this->sendNotificationAndEmail($userId, $amountPaid, $reference, 'Loma Bank', 'Topup');
    }

    private function updateDepositTransaction(Transaction $transaction, $amountPaid, $senderName, $description, $status, $userId, $payload): void
    {
        $newStatus = strtolower((string) $status) === 'success' ? 'completed' : 'pending';
        $user = User::find($userId);
        $performedBy = $user ? $user->first_name . ' ' . $user->last_name : 'System';

        $transaction->update([
            'description'  => $description,
            'amount'       => $amountPaid,
            'fee'          => 0.00,
            'net_amount'   => $amountPaid,
            'status'       => $newStatus,
            'performed_by' => $performedBy,
            'metadata'     => $payload,
            'updated_at'   => Carbon::now(),
        ]);

        Log::info('Fintava deposit transaction updated for ' . $transaction->transaction_ref);
    }

    private function insertDepositTransaction($userId, $reference, $amountPaid, $senderName, $description, $payload): void
    {
        $user = User::find($userId);
        $performedBy = $user ? $user->first_name . ' ' . $user->last_name : 'System';

        Transaction::create([
            'user_id'            => $userId,
            'transaction_ref'    => $reference,
            'provider_reference' => $reference,
            'type'               => 'credit',
            'description'        => $description,
            'amount'             => $amountPaid,
            'fee'                => 0.00,
            'net_amount'         => $amountPaid,
            'status'             => 'completed',
            'performed_by'       => $performedBy,
            'metadata'           => $payload,
        ]);

        Log::info('New Fintava deposit transaction inserted for ' . $reference);
    }

    private function updateWalletBalance($userId, $amountPaid): void
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if ($wallet) {
            $wallet->credit($amountPaid);
            Log::info('Wallet updated for user ' . $userId . ' with amount ' . $amountPaid);
        } else {
            Log::warning('Wallet not found for user ID: ' . $userId);
        }
    }

    private function debitWallet($userId, $amount, $desc, $ref, $relatedRef, string $chargeType = 'levy_charge'): void
    {
        $wallet = Wallet::where('user_id', $userId)->first();
        if ($wallet) {
            $wallet->debit($amount);

            Transaction::create([
                'user_id'         => $userId,
                'transaction_ref' => $ref,
                'type'            => 'debit',
                'amount'          => $amount,
                'fee'             => 0.00,
                'net_amount'      => $amount,
                'description'     => $desc,
                'status'          => 'completed',
                'performed_by'    => 'System',
                'metadata'        => ['related_transaction' => $relatedRef, 'type' => $chargeType],
            ]);
        }
    }

    /**
     * Move deposited funds from the customer's Loma Bank virtual account to the merchant's Fintava wallet.
     * Note: The customer is NEVER charged for this wallet-to-wallet transfer on their platform balance.
     */
    private function sweepToMerchantWallet(VirtualAccount $virtualAccount, float $amount, string $reference, array $data): ?FintavaWalletTransfer
    {
        $senderAccount = $virtualAccount->account_number ?? $data['beneficiaryAccountNumber'] ?? $data['accountNumber'] ?? null;
        $merchantAccount = config('services.fintava.merchant_account') ?: config('services.fintava.master_source_id');

        if (!$senderAccount || !$merchantAccount) {
            Log::info('Fintava wallet sweep skipped: missing sender account or merchant account is not configured.', [
                'sender'   => $senderAccount,
                'merchant' => $merchantAccount,
                'deposit'  => $reference,
            ]);

            return null;
        }

        $customerReference = 'W2W-' . $reference;

        $existing = FintavaWalletTransfer::where('customer_reference', $customerReference)->first();
        if ($existing && $existing->status === 'completed') {
            Log::info("Fintava wallet sweep {$customerReference} already completed. Skipping.");

            return $existing;
        }

        $transfer = $existing ?? FintavaWalletTransfer::create([
            'user_id'            => $virtualAccount->user_id,
            'virtual_account_id' => $virtualAccount->id,
            'deposit_reference'  => $reference,
            'customer_reference' => $customerReference,
            'sender_account'     => $senderAccount,
            'receiver_account'   => $merchantAccount,
            'amount'             => $amount,
            'fee_charged'        => 0.00,
            'net_amount'         => $amount,
            'status'             => 'pending',
        ]);

        try {
            $fintava = app(FintavaService::class);
            $params = [
                'senderAccount'     => $senderAccount,
                'receiverAccount'   => $merchantAccount,
                'amount'            => (float) $amount,
                'CustomerReference' => $customerReference,
                'customerReference' => $customerReference,
                'narration'         => "Wallet sweep for deposit {$reference}",
            ];

            $response = $fintava->walletTransfer($params);

            $isSuccess = FintavaService::isSuccessStatus($response);
            $providerRef = $response['data']['reference'] ?? $response['data']['transactionReference'] ?? null;
            $errorMsg = !$isSuccess
                ? (is_array($response['message'] ?? null) ? implode('; ', $response['message']) : ($response['message'] ?? 'Transfer failed'))
                : null;

            $transfer->update([
                'status'             => $isSuccess ? 'completed' : 'failed',
                'provider_reference' => $providerRef,
                'request_payload'    => $params,
                'response_payload'   => $response,
                'error_message'      => $errorMsg,
            ]);

            // Update deposit transaction metadata with sweep audit info
            $depositTx = Transaction::where('provider_reference', $reference)
                ->orWhere('transaction_ref', $reference)
                ->first();

            if ($depositTx) {
                $meta = $depositTx->metadata ?? [];
                $meta['fintava_sweep'] = [
                    'transfer_id'        => $transfer->id,
                    'customer_reference' => $customerReference,
                    'status'             => $transfer->status,
                    'sender_account'     => $senderAccount,
                    'receiver_account'   => $merchantAccount,
                    'amount'             => $amount,
                    'provider_reference' => $providerRef,
                    'error'              => $errorMsg,
                ];
                $depositTx->update(['metadata' => $meta]);
            }

            if ($isSuccess) {
                Log::info("Fintava wallet sweep completed: {$amount} moved from {$senderAccount} to {$merchantAccount} (ref {$customerReference})");
            } else {
                Log::error("Fintava wallet sweep failed for {$customerReference}: {$errorMsg}", ['response' => $response]);
            }

            return $transfer;
        } catch (\Throwable $e) {
            Log::error("Fintava wallet sweep exception for {$customerReference}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $transfer->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $transfer;
        }
    }

    private function sendNotificationAndEmail($userId, $amountPaid, $reference, $bankName, $type): void
    {
        try {
            $user = User::query()->find($userId);
            if (!$user || !$user->email) {
                return;
            }

            $mail_data = [
                'type'     => $type,
                'amount'   => number_format($amountPaid, 2),
                'ref'      => $reference,
                'bankName' => $bankName,
            ];

            Mail::to($user->email)->queue(new PaymentNotifyMail($mail_data));
        } catch (\Throwable $e) {
            Log::error('Non-blocking error in sendNotificationAndEmail: ' . $e->getMessage());
        }
    }
}
