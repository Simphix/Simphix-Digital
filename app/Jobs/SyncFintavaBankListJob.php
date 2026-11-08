<?php

namespace App\Jobs;

use App\Models\Bank;
use App\Services\FintavaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fintava bank-list sync — read-only and naturally idempotent (each run
 * just upserts the current list). Still queueable (ShouldQueue) for a
 * future scheduled refresh, but the actual work lives in sync() so
 * WithdrawController::syncBanks() can also call it synchronously: this is
 * an infrequent, rate-limited, admin-only action, and running it inline
 * means the "Sync Banks" button works immediately without depending on a
 * queue worker actually being alive to pick the job up.
 */
class SyncFintavaBankListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function middleware(): array
    {
        return [new RateLimited('fintava-vendor')];
    }

    public function handle(FintavaService $fintava): void
    {
        $this->sync($fintava);
    }

    /**
     * @return array{success: bool, count: int, message: string}
     */
    public function sync(FintavaService $fintava): array
    {
        $response = $fintava->getBanks();

        if (($response['status'] ?? null) != 200 || !isset($response['data']) || !is_array($response['data'])) {
            Log::error('Fintava bank list sync failed', ['response' => $response]);

            return [
                'success' => false,
                'count' => 0,
                'message' => $response['message'] ?? 'Fintava did not return a bank list.',
            ];
        }

        $count = 0;
        foreach ($response['data'] as $bank) {
            if (empty($bank['code']) || empty($bank['name'])) {
                continue;
            }

            Bank::updateOrCreate(
                ['bank_code' => $bank['code']],
                [
                    'bank_name' => $bank['name'],
                    'is_active' => true,
                ]
            );
            $count++;
        }

        Log::info('Fintava bank list synced', ['count' => $count]);

        return ['success' => true, 'count' => $count, 'message' => 'Bank list synced.'];
    }
}
