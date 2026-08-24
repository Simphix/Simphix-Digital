<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Models\AdminNotificationDelivery;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAdminNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public AdminNotification $notification)
    {
    }

    public function handle(WebPushService $webPush): void
    {
        $subscriptions = $this->recipients()->get();

        $payload = [
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'url' => $this->notification->url ?: '/',
            'icon' => $this->notification->icon,
        ];

        $delivered = 0;
        $failed = 0;

        // Sent one at a time and isolated in its own try/catch — a single
        // corrupt/invalid subscription must never block delivery to
        // everyone else in the batch (see WebPushService::sendOne()).
        foreach ($subscriptions as $subscription) {
            try {
                $report = $webPush->sendOne($subscription, $payload);
            } catch (\Throwable $e) {
                $failed++;
                AdminNotificationDelivery::create([
                    'admin_notification_id' => $this->notification->id,
                    'push_subscription_id' => $subscription->id,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
                Log::warning('Push send failed for subscription #' . $subscription->id . ': ' . $e->getMessage());
                continue;
            }

            if ($report->isSuccess()) {
                $delivered++;
                AdminNotificationDelivery::create([
                    'admin_notification_id' => $this->notification->id,
                    'push_subscription_id' => $subscription->id,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $subscription->update(['last_used_at' => now()]);
            } elseif ($report->isSubscriptionExpired()) {
                $failed++;
                AdminNotificationDelivery::create([
                    'admin_notification_id' => $this->notification->id,
                    'push_subscription_id' => $subscription->id,
                    'status' => 'expired',
                    'error' => $report->getReason(),
                ]);
                PushSubscription::where('id', $subscription->id)->delete();
            } else {
                $failed++;
                AdminNotificationDelivery::create([
                    'admin_notification_id' => $this->notification->id,
                    'push_subscription_id' => $subscription->id,
                    'status' => 'failed',
                    'error' => $report->getReason(),
                ]);
            }
        }

        $this->notification->update([
            'status' => 'sent',
            'sent_at' => now(),
            'total_recipients' => $subscriptions->count(),
            'total_delivered' => $delivered,
            'total_failed' => $failed,
        ]);
    }

    /**
     * Even if every send throws, the notification must still be marked
     * resolved (not left stuck on "sending" forever) — record whatever
     * we know and re-throw so the queue's own failure tracking still sees it.
     */
    public function failed(\Throwable $e): void
    {
        $this->notification->update([
            'status' => 'failed',
            'sent_at' => now(),
        ]);
        Log::error('SendAdminNotificationJob permanently failed for notification #' . $this->notification->id . ': ' . $e->getMessage());
    }

    private function recipients()
    {
        $query = PushSubscription::query();

        if ($this->notification->target_type === AdminNotification::TARGET_ROLES) {
            $roles = $this->notification->target_roles ?? [];
            $query->whereHas('user', fn ($q) => $q->whereIn('role', $roles));
        }

        return $query;
    }
}
