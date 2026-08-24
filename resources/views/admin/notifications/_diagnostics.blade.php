@if (!$vapidConfigured)
    <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl flex items-start gap-3 shadow-sm">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
        <div class="text-xs leading-relaxed">
            <strong class="text-sm block mb-0.5">Push notifications aren't configured on this server yet</strong>
            No VAPID keys are set, so every subscribe attempt fails silently in the browser and nothing can ever be delivered. Run
            <code class="px-1 py-0.5 bg-rose-100 rounded font-mono">php artisan notifications:generate-vapid-keys</code>
            on the server and add the printed <code class="px-1 py-0.5 bg-rose-100 rounded font-mono">VAPID_PUBLIC_KEY</code>/<code class="px-1 py-0.5 bg-rose-100 rounded font-mono">VAPID_PRIVATE_KEY</code> to <code class="px-1 py-0.5 bg-rose-100 rounded font-mono">.env</code>, then reload this page.
        </div>
    </div>
@elseif ($subscriberCount === 0)
    <div class="p-4 bg-amber-50/70 border border-amber-100 text-amber-800 rounded-2xl flex items-start gap-3 shadow-sm">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
        <div class="text-xs leading-relaxed">
            <strong class="text-sm block mb-0.5">No devices are subscribed yet</strong>
            Push notifications are configured correctly, but 0 of {{ $totalUsers }} users have enabled them so far. Each person (including you, to test) needs to click the bell-plus icon in the header and grant permission on their own device before they can receive anything — a notification can never reach someone who hasn't done that, the same way it can't on any app.
        </div>
    </div>
@else
    <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center gap-2.5 text-xs text-slate-500 font-semibold">
        <i data-lucide="info" class="w-4 h-4 text-slate-400 shrink-0"></i>
        <span>{{ $subscriberCount }} of {{ $totalUsers }} users have enabled push notifications on at least one device. A notification can only reach people in that {{ $subscriberCount }} — "All Users" means all of them, not everyone in the system.</span>
    </div>
@endif
