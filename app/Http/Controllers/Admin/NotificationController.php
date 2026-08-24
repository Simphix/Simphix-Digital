<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminNotificationJob;
use App\Models\AdminNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotification::with('createdBy')->latest()->paginate(15);
        $vapidConfigured = $this->vapidConfigured();
        $subscriberCount = PushSubscription::distinct('user_id')->count('user_id');
        $totalUsers = User::count();

        return view('admin.notifications.index', compact('notifications', 'vapidConfigured', 'subscriberCount', 'totalUsers'));
    }

    public function create()
    {
        $roles = AdminNotification::ROLES;
        $vapidConfigured = $this->vapidConfigured();
        $subscriberCount = PushSubscription::distinct('user_id')->count('user_id');
        $totalUsers = User::count();

        // How many distinct users of each role currently have at least one
        // subscribed device — shown next to the role checkboxes so an admin
        // sees the real reach *before* sending, not after.
        $subscribersByRole = User::query()
            ->whereHas('pushSubscriptions')
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $usersByRole = User::query()
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.notifications.create', compact('roles', 'vapidConfigured', 'subscriberCount', 'totalUsers', 'subscribersByRole', 'usersByRole'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:500',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'target_type' => 'required|in:general,roles',
            'target_roles' => 'required_if:target_type,roles|array',
            'target_roles.*' => 'in:' . implode(',', AdminNotification::ROLES),
            'send_option' => 'required|in:now,schedule',
            'scheduled_at' => 'required_if:send_option,schedule|nullable|date|after:now',
        ]);

        $notification = AdminNotification::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'url' => $validated['url'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'target_type' => $validated['target_type'],
            'target_roles' => $validated['target_type'] === AdminNotification::TARGET_ROLES ? $validated['target_roles'] : null,
            'status' => $validated['send_option'] === 'now' ? 'sending' : 'scheduled',
            'scheduled_at' => $validated['send_option'] === 'schedule' ? $validated['scheduled_at'] : null,
            'created_by' => Auth::id(),
        ]);

        if ($validated['send_option'] === 'now') {
            SendAdminNotificationJob::dispatch($notification);
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Notification ' . ($validated['send_option'] === 'now' ? 'queued for sending.' : 'scheduled.'));
    }

    public function show(AdminNotification $notification)
    {
        $deliveries = $notification->deliveries()->with('subscription.user')->paginate(20);
        $vapidConfigured = $this->vapidConfigured();
        $subscriberCount = PushSubscription::distinct('user_id')->count('user_id');
        $totalUsers = User::count();

        return view('admin.notifications.show', compact('notification', 'deliveries', 'vapidConfigured', 'subscriberCount', 'totalUsers'));
    }

    private function vapidConfigured(): bool
    {
        return filled(config('services.webpush.vapid.public_key')) && filled(config('services.webpush.vapid.private_key'));
    }

    public function cancel(AdminNotification $notification)
    {
        if ($notification->status !== 'scheduled') {
            return back()->with('error', 'Only a not-yet-sent scheduled notification can be cancelled.');
        }

        $notification->update(['status' => 'cancelled']);

        return back()->with('success', 'Notification cancelled.');
    }
}
