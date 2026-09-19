<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashOutRequest;
use App\Models\Report;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PalmPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashOutController extends Controller
{
    protected $palmPay;

    public function __construct(PalmPayService $palmPay)
    {
        $this->palmPay = $palmPay;
    }

    /**
     * List pending and resolved cash-out requests.
     */
    public function index()
    {
        $pending = CashOutRequest::with('user')->where('status', 'pending')->latest()->get();

        $resolved = CashOutRequest::with('user', 'approvedBy')
            ->where('status', '!=', 'pending')
            ->latest()
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        $platformPendingCount = \App\Models\PlatformCashOutRequest::where('status', 'pending')->count();

        return view('admin.cash-out.index', compact('pending', 'resolved', 'platformPendingCount'));
    }

    /**
     * Approve a cash-out request — this is what actually triggers the
     * PalmPay payout (relocated from the old instant-withdraw flow).
     */
    public function approve(CashOutRequest $cashOutRequest)
    {
        if ($cashOutRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $stillPending = DB::transaction(function () use ($cashOutRequest) {
            $locked = CashOutRequest::where('id', $cashOutRequest->id)->lockForUpdate()->first();

            return $locked && $locked->status === 'pending';
        });

        if (!$stillPending) {
            return back()->with('error', 'This request has already been processed.');
        }

        $transaction = Transaction::where('transaction_ref', $cashOutRequest->transaction_ref)->first();
        $report = Report::where('ref', $cashOutRequest->transaction_ref)->first();
        $user = $cashOutRequest->user;

        try {
            $payoutResponse = $this->palmPay->transfer([
                'orderId' => $cashOutRequest->transaction_ref,
                'payeeName' => $cashOutRequest->account_name,
                'payeeBankCode' => $cashOutRequest->bank_code,
                'payeeBankAccNo' => $cashOutRequest->account_no,
                'payeePhoneNo' => $user->phone_number ?? '0000000000',
                'amount' => (int) ($cashOutRequest->amount * 100), // kobo
                'currency' => 'NGN',
                'notifyUrl' => url('/api/palmpay/webhook'),
                'remark' => "Cash out approval for {$cashOutRequest->transaction_ref}",
            ]);

            if (isset($payoutResponse['respCode']) && $payoutResponse['respCode'] === '00000000') {
                // Success — finalize
                DB::transaction(function () use ($cashOutRequest, $transaction, $report, $payoutResponse) {
                    $cashOutRequest->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'resolved_at' => now(),
                    ]);

                    if ($transaction) {
                        $meta = $transaction->metadata ?? [];
                        $meta['api_response'] = $payoutResponse;
                        $transaction->metadata = $meta;
                        $transaction->status = 'completed';
                        $transaction->save();
                    }

                    if ($report) {
                        $report->status = 'completed';
                        $report->save();
                    }
                });

                // Tax charge, if applicable — mirrors the original withdrawal
                // flow's tax sub-block, only now runs on admin approval.
                if ($cashOutRequest->tax > 0) {
                    try {
                        $service = Service::where('name', 'Withdrawal')->first();

                        DB::transaction(function () use ($cashOutRequest, $service, $user) {
                            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                            if ($wallet && $wallet->balance >= $cashOutRequest->tax) {
                                $oldBal = $wallet->balance;
                                $newBal = $oldBal - $cashOutRequest->tax;
                                $wallet->balance = $newBal;
                                $wallet->save();

                                $taxRef = 'TAX' . strtoupper(Str::random(12));
                                Transaction::create([
                                    'transaction_ref' => $taxRef,
                                    'user_id' => $user->id,
                                    'amount' => $cashOutRequest->tax,
                                    'fee' => 0,
                                    'net_amount' => $cashOutRequest->tax,
                                    'description' => "Withdrawal Tax for Ref #{$cashOutRequest->transaction_ref}",
                                    'type' => 'debit',
                                    'status' => 'completed',
                                    'performed_by' => 'System',
                                    'metadata' => [
                                        'service' => 'withdrawal_tax',
                                        'withdrawal_ref' => $cashOutRequest->transaction_ref,
                                    ],
                                ]);

                                Report::create([
                                    'user_id' => $user->id,
                                    'phone_number' => $user->phone_number ?? 'N/A',
                                    'account_number' => $wallet->wallet_no ?? 'N/A',
                                    'account_name' => trim($user->first_name . ' ' . $user->last_name),
                                    'network' => 'System Tax',
                                    'ref' => $taxRef,
                                    'amount' => $cashOutRequest->tax,
                                    'status' => 'completed',
                                    'type' => 'withdrawal_tax',
                                    'description' => "Withdrawal Tax for Ref #{$cashOutRequest->transaction_ref}",
                                    'old_balance' => $oldBal,
                                    'new_balance' => $newBal,
                                    'service_id' => $service?->id,
                                ]);
                            }
                        });
                    } catch (\Exception $taxEx) {
                        Log::error('Cash Out tax charging failed: ' . $taxEx->getMessage());
                    }
                }

                return back()->with('success', "Cash out approved and paid to {$cashOutRequest->account_name}.");
            }

            $errorMsg = $payoutResponse['respMsg'] ?? 'PalmPay rejected the transaction.';

            if ($this->isSystemError($errorMsg)) {
                // A signature/auth/config failure, not a genuine vendor
                // decline — every approval will fail identically until this
                // is fixed, so don't refund-and-close (which would force the
                // user to resubmit) or mislabel it as "recipient rejected".
                // Leave pending for a retry once the underlying issue is fixed.
                Log::critical('Cash Out approval blocked by a PalmPay system/signature error: ' . $errorMsg);

                return back()->with('error', "PalmPay could not process this approval due to a system/authentication error, not a recipient rejection: {$errorMsg}. The request remains pending — retry after this is fixed.");
            }

            // Explicit API rejection — refund the user
            DB::transaction(function () use ($cashOutRequest, $transaction, $report, $payoutResponse) {
                $cashOutRequest->update([
                    'status' => 'rejected',
                    'admin_notes' => $payoutResponse['respMsg'] ?? 'PalmPay rejected the transaction.',
                    'approved_by' => Auth::id(),
                    'resolved_at' => now(),
                ]);

                if ($transaction) {
                    $transaction->status = 'failed';
                    $transaction->save();
                }

                if ($report) {
                    $report->status = 'failed';
                    $report->save();
                }

                $wallet = Wallet::where('user_id', $cashOutRequest->user_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->increment('balance', $cashOutRequest->total_charge);
                }
            });

            Log::error('Cash Out approval rejected by PalmPay: ' . $errorMsg);

            return back()->with('error', 'PalmPay rejected the transfer: ' . $errorMsg . ' (Funds refunded).');
        } catch (\Exception $e) {
            // TIMEOUT / NETWORK ERROR / AMBIGUOUS FAILURE.
            // Do NOT refund and do NOT mark resolved — PalmPay might still be
            // processing it. Leave the request exactly as 'pending' so it
            // shows up for another approval attempt or manual reconciliation.
            Log::error('Cash Out approval API exception (not refunded automatically): ' . $e->getMessage());

            return back()->with('success', 'Approval sent — awaiting final confirmation from the bank. This request remains pending until resolved.');
        }
    }

    /**
     * Whether a PalmPay error message indicates a system/config failure
     * (bad signature, expired token, bad certificate) rather than a
     * genuine business decline of the recipient/transaction.
     */
    private function isSystemError(string $respMsg): bool
    {
        $needles = ['sign', 'signature', 'authoriz', 'token', 'certificate'];
        $haystack = strtolower($respMsg);

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reject a pending cash-out request and refund the user's wallet.
     */
    public function reject(Request $request, CashOutRequest $cashOutRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        if ($cashOutRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        try {
            DB::transaction(function () use ($cashOutRequest, $request) {
                $locked = CashOutRequest::where('id', $cashOutRequest->id)->lockForUpdate()->first();
                if (!$locked || $locked->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $locked->update([
                    'status' => 'rejected',
                    'admin_notes' => $request->admin_notes ?: 'Rejected.',
                    'approved_by' => Auth::id(),
                    'resolved_at' => now(),
                ]);

                $transaction = Transaction::where('transaction_ref', $locked->transaction_ref)->first();
                if ($transaction) {
                    $transaction->status = 'failed';
                    $transaction->save();
                }

                $report = Report::where('ref', $locked->transaction_ref)->first();
                if ($report) {
                    $report->status = 'failed';
                    $report->save();
                }

                $wallet = Wallet::where('user_id', $locked->user_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->increment('balance', $locked->total_charge);
                }
            });

            return back()->with('success', 'Cash out request rejected and ₦' . number_format($cashOutRequest->total_charge, 2) . ' refunded to the user.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error rejecting request: ' . $e->getMessage());
        }
    }
}
