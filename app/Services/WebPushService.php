<?php

namespace App\Services;

use App\Models\PushSubscription;
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
     * Send to one subscription, isolated from every other subscription.
     * Deliberately not queueNotification()+flush() — the encryption step
     * for a single subscription with a corrupt/invalid key throws out of
     * flush()'s generator entirely, aborting delivery to every OTHER
     * subscription still queued in that batch. Sending one at a time
     * trades flush()'s connection-pooling for the guarantee that one bad
     * subscription can never block delivery to the rest.
     *
     * @throws \Throwable on any failure — encryption or transport
     */
    public function sendOne(PushSubscription $subscription, array $payload): MessageSentReport
    {
        return $this->webPush->sendOneNotification(
            Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
            ]),
            json_encode($payload)
        );
    }
}
