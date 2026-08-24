<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminNotificationJob;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotification::with('createdBy')->latest()->paginate(15);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        $roles = AdminNotification::ROLES;

        return view('admin.notifications.create', compact('roles'));
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

        return view('admin.notifications.show', compact('notification', 'deliveries'));
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
