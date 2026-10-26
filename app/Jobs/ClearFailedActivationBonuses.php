<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to clear failed activation bonus data reports from the system.
 * Can be dispatched to a background queue or executed synchronously
 * via the Artisan command `php artisan sim:clear-failed-activation-bonuses`.
 */
class ClearFailedActivationBonuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int|null $olderThanDays Only clear records created older than N days ago.
     * @param bool $asResolved If true, marks as resolved instead of deleting rows.
     * @param string|null $network Filter by network (e.g. mtn, airtel, glo, 9mobile).
     * @param string|null $number Filter by specific phone number.
     */
    public function __construct(
        public ?int $olderThanDays = null,
        public bool $asResolved = false,
        public ?string $network = null,
        public ?string $number = null,
    ) {
    }

    /**
     * Execute the job.
     *
     * @return int Number of records cleared.
     */
    public function handle(): int
    {
        $query = Report::where('type', 'activation_bonus_data')
            ->whereIn('status', ['failed', 'retrying']);

        if ($this->olderThanDays !== null && $this->olderThanDays > 0) {
            $query->where('created_at', '<=', now()->subDays($this->olderThanDays));
        }

        if (!empty($this->network)) {
            $query->where('network', strtolower($this->network));
        }

        if (!empty($this->number)) {
            $query->where('phone_number', $this->number);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            Log::info('ClearFailedActivationBonuses: No matching failed activation bonus reports found.');
            return 0;
        }

        if ($this->asResolved) {
            $query->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
                'description' => DB::raw("CONCAT(COALESCE(description, ''), ' [Cleared by Admin]')"),
            ]);

            Log::info("ClearFailedActivationBonuses: Marked {$count} failed activation bonus report(s) as resolved.");
        } else {
            $query->delete();

            Log::info("ClearFailedActivationBonuses: Deleted {$count} failed activation bonus report(s).");
        }

        return $count;
    }
}
