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

        // Keyed by endpoint so flush()'s reports can be matched back to a
        // subscription/delivery row.
        $byEndpoint = $subscriptions->keyBy('endpoint');

        $webPush->queue($subscriptions, [
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'url' => $this->notification->url ?: '/',
            'icon' => $this->notification->icon,
        ]);

        $delivered = 0;
        $failed = 0;

        foreach ($webPush->flush() as $report) {
            $subscription = $byEndpoint->get($report->getEndpoint());
            if (!$subscription) {
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
