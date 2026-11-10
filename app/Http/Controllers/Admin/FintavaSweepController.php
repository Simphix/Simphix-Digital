<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FintavaWalletTransfer;
use App\Services\FintavaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FintavaSweepController extends Controller
{
    public function index(Request $request): View
    {
        $query = FintavaWalletTransfer::with(['user', 'virtualAccount']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('deposit_reference', 'like', "%{$search}%")
                  ->orWhere('customer_reference', 'like', "%{$search}%")
                  ->orWhere('sender_account', 'like', "%{$search}%")
                  ->orWhere('receiver_account', 'like', "%{$search}%")
                  ->orWhere('provider_reference', 'like', "%{$search}%");
            });
        }

        $sweeps = $query->latest()->paginate(15)->appends($request->query());

        $totalCount = FintavaWalletTransfer::count();
        $totalSweptAmount = FintavaWalletTransfer::where('status', 'completed')->sum('amount');
        $successCount = FintavaWalletTransfer::where('status', 'completed')->count();
        $failedCount = FintavaWalletTransfer::where('status', 'failed')->count();

        return view('admin.fintava-sweeps.index', compact(
            'sweeps',
            'totalCount',
            'totalSweptAmount',
            'successCount',
            'failedCount'
        ));
    }

    public function retry(FintavaWalletTransfer $sweep): RedirectResponse
    {
        if ($sweep->status === 'completed') {
            return back()->with('info', "Sweep {$sweep->customer_reference} has already been completed.");
        }

        try {
            $fintava = app(FintavaService::class);
            $params = [
                'senderAccount'     => $sweep->sender_account,
                'receiverAccount'   => $sweep->receiver_account,
                'amount'            => (float) $sweep->amount,
                'CustomerReference' => $sweep->customer_reference,
                'customerReference' => $sweep->customer_reference,
                'narration'         => "Retry wallet sweep for deposit {$sweep->deposit_reference}",
            ];

            $response = $fintava->walletTransfer($params);
            $isSuccess = FintavaService::isSuccessStatus($response);
            $providerRef = $response['data']['reference'] ?? $response['data']['transactionReference'] ?? null;
            $errorMsg = !$isSuccess
                ? (is_array($response['message'] ?? null) ? implode('; ', $response['message']) : ($response['message'] ?? 'Transfer failed'))
                : null;

            $sweep->update([
                'status'             => $isSuccess ? 'completed' : 'failed',
                'provider_reference' => $providerRef ?? $sweep->provider_reference,
                'request_payload'    => $params,
                'response_payload'   => $response,
                'error_message'      => $errorMsg,
            ]);

            return $isSuccess
                ? back()->with('success', "Sweep {$sweep->customer_reference} completed successfully.")
                : back()->with('error', "Sweep retry failed: {$errorMsg}");
        } catch (\Throwable $e) {
            Log::error("Fintava sweep retry error for {$sweep->customer_reference}: " . $e->getMessage());

            $sweep->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->with('error', "Exception during retry: " . $e->getMessage());
        }
    }
}
