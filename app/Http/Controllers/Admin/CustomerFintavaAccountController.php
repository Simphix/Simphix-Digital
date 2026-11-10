<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FintavaWalletTransfer;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Services\FintavaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerFintavaAccountController extends Controller
{
    public function __construct(
        protected FintavaService $fintava
    ) {}

    /**
     * Search or redirect to a customer's Fintava virtual account.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $account = VirtualAccount::where('account_number', $search)
                ->orWhere('provider_customer_id', $search)
                ->orWhere('provider_wallet_id', $search)
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('email', $search)
                      ->orWhere('phone', $search)
                      ->orWhere('id', $search);
                })
                ->first();

            if ($account) {
                return redirect()->route('admin.fintava-accounts.show', $account);
            }
        }

        $accounts = VirtualAccount::with('user')
            ->where('provider', 'fintava')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('account_number', 'like', "%{$search}%")
                      ->orWhere('account_name', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            })
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        return view('admin.fintava-accounts.index', compact('accounts', 'search'));
    }

    /**
     * Inspect a customer's live Fintava virtual account details, balance, transactions, and transfer history.
     */
    public function show(VirtualAccount $virtualAccount): View
    {
        $virtualAccount->loadMissing('user');

        $liveBalance = null;
        $ledgerBalance = null;
        $balanceError = null;

        if ($virtualAccount->provider_wallet_id) {
            try {
                $balanceResponse = $this->fintava->getCustomerWalletBalance($virtualAccount->provider_wallet_id);
                if (FintavaService::isSuccessStatus($balanceResponse)) {
                    $data = $balanceResponse['data'] ?? [];
                    if (is_numeric($data)) {
                        $liveBalance = (float) $data;
                        $ledgerBalance = (float) $data;
                    } elseif (is_array($data)) {
                        $liveBalance = isset($data['balance']) ? (float) $data['balance'] : (isset($data['availableBalance']) ? (float) $data['availableBalance'] : (isset($data['walletBalance']) ? (float) $data['walletBalance'] : 0.0));
                        $ledgerBalance = isset($data['ledgerBalance']) ? (float) $data['ledgerBalance'] : $liveBalance;
                    }
                } else {
                    $balanceError = is_array($balanceResponse['message'] ?? null)
                        ? implode('; ', $balanceResponse['message'])
                        : ($balanceResponse['message'] ?? 'Could not fetch balance from Fintava.');
                }
            } catch (\Throwable $e) {
                Log::error("Failed fetching live Fintava balance for wallet {$virtualAccount->provider_wallet_id}: " . $e->getMessage());
                $balanceError = 'Fintava service communication error: ' . $e->getMessage();
            }
        } else {
            $balanceError = 'No provider wallet ID recorded for this virtual account.';
        }

        $transactions = [];
        $txnError = null;

        if ($virtualAccount->provider_customer_id) {
            try {
                $txnResponse = $this->fintava->getCustomerTransactions([
                    'customerId' => $virtualAccount->provider_customer_id,
                    'take'       => 50,
                    'page'       => 1,
                    'order'      => 'DESC',
                ]);

                if (FintavaService::isSuccessStatus($txnResponse)) {
                    $transactions = $this->normalizeTransactions($txnResponse);
                } else {
                    $txnError = is_array($txnResponse['message'] ?? null)
                        ? implode('; ', $txnResponse['message'])
                        : ($txnResponse['message'] ?? 'Unable to fetch transactions.');
                }
            } catch (\Throwable $e) {
                Log::error("Failed fetching live Fintava transactions for customer {$virtualAccount->provider_customer_id}: " . $e->getMessage());
                $txnError = 'Fintava service communication error: ' . $e->getMessage();
            }
        }

        // Local wallet-to-wallet transfer audits for this account
        $sweeps = FintavaWalletTransfer::where('virtual_account_id', $virtualAccount->id)
            ->orWhere('sender_account', $virtualAccount->account_number)
            ->latest()
            ->take(20)
            ->get();

        $merchantAccount = config('services.fintava.merchant_account', '');

        return view('admin.fintava-accounts.show', compact(
            'virtualAccount',
            'liveBalance',
            'ledgerBalance',
            'balanceError',
            'transactions',
            'txnError',
            'sweeps',
            'merchantAccount'
        ));
    }

    /**
     * Transfer funds from this customer's Fintava virtual account (e.g. to the merchant wallet).
     * Platform level balance of the customer is NOT debited.
     */
    public function transfer(Request $request, VirtualAccount $virtualAccount): RedirectResponse
    {
        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:1'],
            'receiver_account' => ['nullable', 'string', 'max:50'],
            'narration'        => ['nullable', 'string', 'max:255'],
        ]);

        $receiverAccount = trim($validated['receiver_account'] ?? '') ?: config('services.fintava.merchant_account');

        if (empty($receiverAccount)) {
            return back()->with('error', 'Merchant wallet account is not configured in services.fintava.merchant_account.');
        }

        $amount = (float) $validated['amount'];
        $ref = 'ADMIN-SWEEP-' . Str::upper(Str::random(12));
        $narration = $validated['narration'] ?: "Admin manual balance transfer for {$virtualAccount->account_number}";

        $params = [
            'senderAccount'     => $virtualAccount->account_number,
            'receiverAccount'   => $receiverAccount,
            'amount'            => $amount,
            'CustomerReference' => $ref,
            'customerReference' => $ref,
            'narration'         => $narration,
        ];

        try {
            $response = $this->fintava->walletTransfer($params);
            $isSuccess = FintavaService::isSuccessStatus($response);
            $providerRef = $response['data']['reference'] ?? $response['data']['transactionReference'] ?? null;
            $errorMsg = ! $isSuccess
                ? (is_array($response['message'] ?? null) ? implode('; ', $response['message']) : ($response['message'] ?? 'Transfer rejected by Fintava'))
                : null;

            FintavaWalletTransfer::create([
                'user_id'            => $virtualAccount->user_id,
                'virtual_account_id' => $virtualAccount->id,
                'deposit_reference'  => 'MANUAL-' . Str::upper(Str::random(10)),
                'customer_reference' => $ref,
                'sender_account'     => $virtualAccount->account_number,
                'receiver_account'   => $receiverAccount,
                'amount'             => $amount,
                'fee_charged'        => 0.00,
                'net_amount'         => $amount,
                'status'             => $isSuccess ? 'completed' : 'failed',
                'provider_reference' => $providerRef,
                'request_payload'    => $params,
                'response_payload'   => $response,
                'error_message'      => $errorMsg,
            ]);

            if ($isSuccess) {
                return back()->with('success', "Transferred ₦" . number_format($amount, 2) . " from {$virtualAccount->account_number} to {$receiverAccount}. Ref: {$ref}");
            }

            return back()->with('error', "Fintava transfer error: {$errorMsg}");
        } catch (\Throwable $e) {
            Log::error("Admin Fintava transfer exception: " . $e->getMessage());

            FintavaWalletTransfer::create([
                'user_id'            => $virtualAccount->user_id,
                'virtual_account_id' => $virtualAccount->id,
                'deposit_reference'  => 'MANUAL-' . Str::upper(Str::random(10)),
                'customer_reference' => $ref,
                'sender_account'     => $virtualAccount->account_number,
                'receiver_account'   => $receiverAccount,
                'amount'             => $amount,
                'fee_charged'        => 0.00,
                'net_amount'         => $amount,
                'status'             => 'failed',
                'provider_reference' => null,
                'request_payload'    => $params,
                'response_payload'   => ['error' => $e->getMessage()],
                'error_message'      => $e->getMessage(),
            ]);

            return back()->with('error', "Transfer failed due to exception: " . $e->getMessage());
        }
    }

    /**
     * Download/export customer's Fintava transactions as CSV.
     */
    public function exportTransactions(Request $request, VirtualAccount $virtualAccount): StreamedResponse
    {
        $transactions = [];

        if ($virtualAccount->provider_customer_id) {
            try {
                $limit = min(500, max(1, (int) $request->input('limit', 200)));
                $txnResponse = $this->fintava->getCustomerTransactions([
                    'customerId' => $virtualAccount->provider_customer_id,
                    'take'       => $limit,
                    'page'       => 1,
                    'order'      => 'DESC',
                ]);

                if (FintavaService::isSuccessStatus($txnResponse)) {
                    $transactions = $this->normalizeTransactions($txnResponse);
                }
            } catch (\Throwable $e) {
                Log::error("Failed fetching live Fintava transactions for export: " . $e->getMessage());
            }
        }

        $filename = "fintava_transactions_{$virtualAccount->account_number}_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($transactions, $virtualAccount) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens it with proper encoding
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Date',
                'Reference',
                'Type',
                'Amount (NGN)',
                'Status',
                'Sender',
                'Receiver',
                'Narration',
            ]);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx['created_at'] ?? 'N/A',
                    $tx['reference'] ?? 'N/A',
                    strtoupper((string) ($tx['type'] ?? 'N/A')),
                    number_format((float) ($tx['amount'] ?? 0), 2, '.', ''),
                    strtoupper((string) ($tx['status'] ?? 'N/A')),
                    $tx['sender'] ?? $virtualAccount->account_number,
                    $tx['receiver'] ?? 'N/A',
                    $tx['narration'] ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Normalize transaction records returned by Fintava BaaS into a uniform array structure.
     */
    protected function normalizeTransactions(array $txnResponse): array
    {
        $raw = [];
        $data = $txnResponse['data'] ?? [];

        if (is_array($data)) {
            if (array_is_list($data)) {
                $raw = $data;
            } elseif (isset($data['data']) && is_array($data['data'])) {
                $raw = $data['data'];
            } elseif (isset($data['transactions']) && is_array($data['transactions'])) {
                $raw = $data['transactions'];
            }
        }

        $normalized = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $dateRaw = $item['createdAt'] ?? $item['created_at'] ?? $item['date'] ?? null;
            $formattedDate = 'N/A';
            if ($dateRaw) {
                try {
                    $formattedDate = Carbon::parse($dateRaw)->format('M d, Y h:i A');
                } catch (\Throwable) {
                    $formattedDate = (string) $dateRaw;
                }
            }

            $normalized[] = [
                'reference'  => $item['reference'] ?? $item['transactionReference'] ?? $item['id'] ?? 'N/A',
                'type'       => $item['type'] ?? $item['transactionType'] ?? 'N/A',
                'amount'     => (float) ($item['amount'] ?? 0),
                'status'     => $item['status'] ?? 'N/A',
                'narration'  => $item['narration'] ?? $item['description'] ?? $item['remark'] ?? '—',
                'sender'     => $item['senderAccount'] ?? $item['sender'] ?? null,
                'receiver'   => $item['receiverAccount'] ?? $item['receiver'] ?? null,
                'created_at' => $formattedDate,
                'raw'        => $item,
            ];
        }

        return $normalized;
    }
}
