<?php

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Models\AdminNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('subscribing twice with the same endpoint updates rather than duplicates', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $payload = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-1',
        'keys' => ['p256dh' => 'pubkey1', 'auth' => 'authtoken1'],
    ];

    (new PushSubscriptionController())->store(Request::create('/push/subscribe', 'POST', $payload));
    (new PushSubscriptionController())->store(Request::create('/push/subscribe', 'POST', array_merge($payload, [
        'keys' => ['p256dh' => 'pubkey1-updated', 'auth' => 'authtoken1'],
    ])));

    expect(PushSubscription::count())->toBe(1);
    expect(PushSubscription::first()->public_key)->toBe('pubkey1-updated');

    Auth::logout();
});

test('unsubscribing removes only the authenticated users own subscription', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    PushSubscription::create([
        'user_id' => $owner->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/owner-endpoint',
        'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/owner-endpoint'),
        'public_key' => 'pk', 'auth_token' => 'at',
    ]);
    PushSubscription::create([
        'user_id' => $other->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/other-endpoint',
        'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/other-endpoint'),
        'public_key' => 'pk', 'auth_token' => 'at',
    ]);

    Auth::login($owner);
    (new PushSubscriptionController())->destroy(Request::create('/push/unsubscribe', 'DELETE', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/owner-endpoint',
    ]));
    Auth::logout();

    expect(PushSubscription::count())->toBe(1);
    expect(PushSubscription::first()->user_id)->toBe($other->id);
});

test('admin notification due scope only returns scheduled notifications past their time', function () {
    AdminNotification::create([
        'title' => 'Due', 'body' => 'x', 'target_type' => 'general',
        'status' => 'scheduled', 'scheduled_at' => now()->subMinute(),
    ]);
    AdminNotification::create([
        'title' => 'Not due yet', 'body' => 'x', 'target_type' => 'general',
        'status' => 'scheduled', 'scheduled_at' => now()->addHour(),
    ]);
    AdminNotification::create([
        'title' => 'Already sent', 'body' => 'x', 'target_type' => 'general',
        'status' => 'sent', 'scheduled_at' => now()->subMinute(),
    ]);

    $due = AdminNotification::due()->get();

    expect($due)->toHaveCount(1);
    expect($due->first()->title)->toBe('Due');
});

test('composing a notification for specific roles validates and stores the role list', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);
    Auth::login($admin);

    $request = Request::create('/admin/notifications', 'POST', [
        'title' => 'Maintenance notice',
        'body' => 'We will be down briefly.',
        'target_type' => 'roles',
        'target_roles' => ['partner', 'agent'],
        'send_option' => 'schedule',
        'scheduled_at' => now()->addHour()->format('Y-m-d\TH:i'),
    ]);

    (new NotificationController())->store($request);

    $notification = AdminNotification::first();
    expect($notification->status)->toBe('scheduled');
    expect($notification->target_roles)->toBe(['partner', 'agent']);
    expect($notification->created_by)->toBe($admin->id);

    Auth::logout();
});

test('composing a notification with send now dispatches immediately with sending status', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $admin = User::factory()->create(['role' => 'super_admin']);
    Auth::login($admin);

    $request = Request::create('/admin/notifications', 'POST', [
        'title' => 'General announcement',
        'body' => 'Hello everyone.',
        'target_type' => 'general',
        'send_option' => 'now',
    ]);

    (new NotificationController())->store($request);

    $notification = AdminNotification::first();
    expect($notification->status)->toBe('sending');
    expect($notification->scheduled_at)->toBeNull();

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendAdminNotificationJob::class);

    Auth::logout();
});
