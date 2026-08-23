<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Delayed ₦15 VAT charge on wallet top-ups of ₦10,000+, dispatched a
 * minute after the levy charge in PaymentWebhookController::processSuccessFunding().
 */
class ProcessVatCharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];

    public function __construct(public int $userId, public ?string $relatedRef = null)
    {
    }

    public function handle(): void
    {
        $wallet = Wallet::where('user_id', $this->userId)->first();
        if (!$wallet) {
            Log::warning("ProcessVatCharge: no wallet for user {$this->userId}");
            return;
        }

        $wallet->debit(15.00);

        Transaction::create([
            'user_id'         => $this->userId,
            'transaction_ref' => 'VAT-' . strtoupper(Str::random(10)),
            'type'            => 'debit',
            'amount'          => 15.00,
            'fee'             => 0.00,
            'net_amount'      => 15.00,
            'description'     => 'VAT charge',
            'status'          => 'completed',
            'performed_by'    => 'System',
            'metadata'        => ['related_transaction' => $this->relatedRef, 'type' => 'vat_charge'],
        ]);
    }
}
