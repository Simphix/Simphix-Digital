<?php

use App\Models\AdminNotification;
use App\Models\AdminNotificationDelivery;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\VapidKeyGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function pushResetEnvFile(array $lines): string
{
    $path = tempnam(sys_get_temp_dir(), 'push-reset-');
    file_put_contents($path, implode("\n", $lines) . "\n");

    return $path;
}

test('reset rotates VAPID config, clears subscriptions and preserves delivery history', function () {
    app()->instance(VapidKeyGenerator::class, new class extends VapidKeyGenerator {
        public function generate(): array
        {
            return ['publicKey' => 'new-public-key', 'privateKey' => 'new-private-key'];
        }
    });

    $user = User::factory()->create();
    $endpoint = 'https://push.example.test/old-endpoint';
    $subscription = PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'public_key' => 'old-public-key',
        'auth_token' => 'old-auth-token',
    ]);
    $notification = AdminNotification::create([
        'title' => 'Existing notification',
        'body' => 'History remains available',
        'target_type' => AdminNotification::TARGET_GENERAL,
        'status' => 'sent',
    ]);
    $delivery = AdminNotificationDelivery::create([
        'admin_notification_id' => $notification->id,
        'push_subscription_id' => $subscription->id,
        'status' => 'sent',
        'sent_at' => now(),
    ]);
    $envFile = pushResetEnvFile(['APP_URL=https://new.example.test', 'EXISTING_SETTING=keep-me']);

    try {
        $this->artisan('notifications:reset', [
            '--env-file' => $envFile,
            '--force' => true,
        ])->assertSuccessful();

        $contents = file_get_contents($envFile);
        expect(PushSubscription::count())->toBe(0)
            ->and($delivery->fresh()->push_subscription_id)->toBeNull()
            ->and($notification->fresh()->status)->toBe('sent')
            ->and($contents)->toContain('VAPID_SUBJECT=https://new.example.test')
            ->and($contents)->toContain('VAPID_PUBLIC_KEY=new-public-key')
            ->and($contents)->toContain('VAPID_PRIVATE_KEY=new-private-key')
            ->and($contents)->toContain('EXISTING_SETTING=keep-me');
    } finally {
        @unlink($envFile);
    }
});

test('dry run does not modify environment or subscriptions', function () {
    $user = User::factory()->create();
    $endpoint = 'https://push.example.test/keep-endpoint';
    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'public_key' => 'old-public-key',
        'auth_token' => 'old-auth-token',
    ]);
    $envFile = pushResetEnvFile(['APP_URL=https://new.example.test', 'EXISTING_SETTING=keep-me']);
    $original = file_get_contents($envFile);

    try {
        $this->artisan('notifications:reset', [
            '--env-file' => $envFile,
            '--dry-run' => true,
        ])->assertSuccessful();

        expect(PushSubscription::count())->toBe(1)
            ->and(file_get_contents($envFile))->toBe($original);
    } finally {
        @unlink($envFile);
    }
});
