<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformCashOutRequest;
use App\Services\PalmPayService;
use App\Services\PlatformWalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformCashOutApprovalController extends Controller
{
    protected $palmPay;

    public function __construct(PalmPayService $palmPay)
    {
        $this->palmPay = $palmPay;
    }

    public function index()
    {
        $pending = PlatformCashOutRequest::with('wallet', 'requestedBy')->where('status', 'pending')->latest()->get();

        $resolved = PlatformCashOutRequest::with('wallet', 'requestedBy', 'approvedBy')
            ->where('status', '!=', 'pending')
            ->latest()
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        return view('admin.platform-wallets.cash-out-approvals', compact('pending', 'resolved'));
    }

    /**
     * Approve a platform cash-out request — mirrors Admin\CashOutController::approve()
     * but debits/refunds a PlatformWallet instead of a per-user Wallet, and
     * has no tax sub-block (no tax concept applies to platform cashouts).
     */
    public function approve(PlatformCashOutRequest $platformCashOutRequest)
    {
        if ($platformCashOutRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        // Both requester and approver can be super_admin here — block
        // self-approval since this moves real company money.
        if ($platformCashOutRequest->requested_by === Auth::id()) {
            return back()->with('error', 'You cannot approve a platform cash-out request you submitted yourself.');
        }

        $stillPending = DB::transaction(function () use ($platformCashOutRequest) {
            $locked = PlatformCashOutRequest::where('id', $platformCashOutRequest->id)->lockForUpdate()->first();

            return $locked && $locked->status === 'pending';
        });

        if (!$stillPending) {
            return back()->with('error', 'This request has already been processed.');
        }

        try {
            $payoutResponse = $this->palmPay->transfer([
                'orderId' => 'PCOR-' . $platformCashOutRequest->id . '-' . time(),
                'payeeName' => $platformCashOutRequest->account_name,
                'payeeBankCode' => $platformCashOutRequest->bank_code,
                'payeeBankAccNo' => $platformCashOutRequest->account_no,
                'payeePhoneNo' => '0000000000',
                'amount' => (int) ($platformCashOutRequest->amount * 100), // kobo
                'currency' => 'NGN',
                'notifyUrl' => url('/api/palmpay/webhook'),
                'remark' => "Platform cash out approval #{$platformCashOutRequest->id}",
            ]);

            if (isset($payoutResponse['respCode']) && $payoutResponse['respCode'] === '00000000') {
                $platformCashOutRequest->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'resolved_at' => now(),
                ]);

                return back()->with('success', "Cash out approved and paid to {$platformCashOutRequest->account_name}.");
            }

            $errorMsg = $payoutResponse['respMsg'] ?? 'PalmPay rejected the transaction.';

            if ($this->isSystemError($errorMsg)) {
                Log::critical('Platform Cash Out approval blocked by a PalmPay system/signature error: ' . $errorMsg);

                return back()->with('error', "PalmPay could not process this approval due to a system/authentication error, not a recipient rejection: {$errorMsg}. The request remains pending — retry after this is fixed.");
            }

            // Explicit API rejection — refund the platform wallet
            DB::transaction(function () use ($platformCashOutRequest, $payoutResponse) {
                $platformCashOutRequest->update([
                    'status' => 'rejected',
                    'admin_notes' => $payoutResponse['respMsg'] ?? 'PalmPay rejected the transaction.',
                    'approved_by' => Auth::id(),
                    'resolved_at' => now(),
                ]);

                app(PlatformWalletLedger::class)->credit(
                    $platformCashOutRequest->wallet->type,
                    $platformCashOutRequest->wallet->name,
                    (float) $platformCashOutRequest->amount,
                    'cashout_refund',
                    "Refund for rejected cash-out request #{$platformCashOutRequest->id}",
                    $platformCashOutRequest
                );
            });

            Log::error('Platform Cash Out approval rejected by PalmPay: ' . $errorMsg);

            return back()->with('error', 'PalmPay rejected the transfer: ' . $errorMsg . ' (Funds refunded to the platform wallet).');
        } catch (\Exception $e) {
            // Timeout/network/ambiguous failure — do not refund, leave pending
            // for a retry or manual reconciliation, same reasoning as the
            // personal cash-out flow.
            Log::error('Platform Cash Out approval API exception (not refunded automatically): ' . $e->getMessage());

            return back()->with('success', 'Approval sent — awaiting final confirmation from the bank. This request remains pending until resolved.');
        }
    }

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

    public function reject(Request $request, PlatformCashOutRequest $platformCashOutRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        if ($platformCashOutRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        if ($platformCashOutRequest->requested_by === Auth::id()) {
            return back()->with('error', 'You cannot reject a platform cash-out request you submitted yourself.');
        }

        try {
            DB::transaction(function () use ($platformCashOutRequest, $request) {
                $locked = PlatformCashOutRequest::where('id', $platformCashOutRequest->id)->lockForUpdate()->first();
                if (!$locked || $locked->status !== 'pending') {
                    throw new \Exception('This request has already been processed.');
                }

                $locked->update([
                    'status' => 'rejected',
                    'admin_notes' => $request->admin_notes ?: 'Rejected.',
                    'approved_by' => Auth::id(),
                    'resolved_at' => now(),
                ]);

                app(PlatformWalletLedger::class)->credit(
                    $locked->wallet->type,
                    $locked->wallet->name,
                    (float) $locked->amount,
                    'cashout_refund',
                    "Refund for rejected cash-out request #{$locked->id}",
                    $locked
                );
            });

            return back()->with('success', 'Cash out request rejected and ₦' . number_format($platformCashOutRequest->amount, 2) . ' refunded to the platform wallet.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error rejecting request: ' . $e->getMessage());
        }
    }
}
