<?php

namespace App\Services;

use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Thin wrapper around minishlink/web-push, configured from config('services.webpush.vapid').
 * See docs/push-notification-scheduling.md for the VAPID key setup this needs.
 */
class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.vapid.subject'),
                'publicKey' => config('services.webpush.vapid.public_key'),
                'privateKey' => config('services.webpush.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Queue a notification for every subscription in $subscriptions.
     */
    public function queue(iterable $subscriptions, array $payload): void
    {
        $body = json_encode($payload);

        foreach ($subscriptions as $subscription) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                ]),
                $body
            );
        }
    }

    /**
     * Send everything queued via queue(). Yields MessageSentReport, each
     * carrying ->getEndpoint() so callers can map back to a PushSubscription.
     *
     * @return \Generator<MessageSentReport>
     */
    public function flush(): \Generator
    {
        return $this->webPush->flush();
    }
}
