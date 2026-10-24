<x-app-layout>
    <title>SimPhix - Push Notifications</title>

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </div>
                    Push Notifications
                </h1>
                <p class="text-sm text-slate-500 mt-1">Compose, schedule, and track push notifications sent to users.</p>
            </div>

            <a href="{{ route('admin.notifications.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl text-xs font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                New Notification
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        @include('admin.notifications._diagnostics')

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase bg-slate-50/50">
                            <th class="py-3 px-6">Title</th>
                            <th class="py-3 px-6">Target</th>
                            <th class="py-3 px-6">Status</th>
                            <th class="py-3 px-6">Delivered / Failed</th>
                            <th class="py-3 px-6">Scheduled / Sent</th>
                            <th class="py-3 px-6">Created By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($notifications as $n)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-6">
                                    <a href="{{ route('admin.notifications.show', $n) }}" class="font-bold text-slate-800 hover:text-primary hover:underline">{{ $n->title }}</a>
                                </td>
                                <td class="py-3 px-6 text-slate-500 font-semibold">
                                    {{ $n->target_type === 'general' ? 'All Users' : implode(', ', $n->target_roles ?? []) }}
                                </td>
                                <td class="py-3 px-6">
                                    @if ($n->status === 'sent' && $n->total_recipients === 0)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase bg-amber-50 text-amber-600">Sent &middot; no recipients</span>
                                    @else
                                        <span @class([
                                            'px-2 py-0.5 rounded-full text-xs font-bold uppercase',
                                            'bg-slate-100 text-slate-500' => $n->status === 'draft',
                                            'bg-amber-50 text-amber-600' => in_array($n->status, ['scheduled', 'sending']),
                                            'bg-emerald-50 text-emerald-600' => $n->status === 'sent',
                                            'bg-rose-50 text-rose-600' => in_array($n->status, ['failed', 'cancelled']),
                                        ])>{{ $n->status }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 font-semibold {{ $n->total_recipients === 0 ? 'text-slate-400' : 'text-slate-600' }}">
                                    {{ $n->total_delivered }} / {{ $n->total_failed }}
                                    <span class="text-slate-400 font-normal">of {{ $n->total_recipients }}</span>
                                </td>
                                <td class="py-3 px-6 text-slate-400 font-medium">
                                    {{ ($n->scheduled_at ?? $n->sent_at)?->format('M d, Y H:i') ?? '—' }}
                                </td>
                                <td class="py-3 px-6 text-slate-500 font-semibold">
                                    {{ $n->createdBy->first_name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400 font-semibold">No notifications sent yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $notifications->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
</x-app-layout>
