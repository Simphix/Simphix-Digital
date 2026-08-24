<x-app-layout>
    <title>SmartSIM - {{ $notification->title }}</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800">{{ $notification->title }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ $notification->body }}</p>
            </div>

            <div class="flex items-center gap-3">
                @if ($notification->status === 'scheduled')
                    <form method="POST" action="{{ route('admin.notifications.cancel', $notification) }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-rose-50 text-rose-600 border border-rose-100 rounded-xl text-xs font-semibold hover:bg-rose-100 transition-colors">
                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                            Cancel
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-400"></i>
                    Back
                </a>
            </div>
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

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                @if ($notification->status === 'sent' && $notification->total_recipients === 0)
                    <p class="text-sm font-bold text-amber-600 mt-1">Sent &middot; no recipients</p>
                @else
                    <p class="text-sm font-bold text-slate-800 mt-1 capitalize">{{ $notification->status }}</p>
                @endif
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase">Recipients</p>
                <p class="text-sm font-bold text-slate-800 mt-1">{{ $notification->total_recipients }}</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase">Delivered</p>
                <p class="text-sm font-bold text-emerald-600 mt-1">{{ $notification->total_delivered }}</p>
            </div>
            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm text-center">
                <p class="text-xs font-semibold text-slate-400 uppercase">Failed</p>
                <p class="text-sm font-bold text-rose-600 mt-1">{{ $notification->total_failed }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 font-display">Delivery Log</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase bg-slate-50/50">
                            <th class="py-3 px-6">User</th>
                            <th class="py-3 px-6">Status</th>
                            <th class="py-3 px-6">Error</th>
                            <th class="py-3 px-6">Sent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($deliveries as $d)
                            <tr>
                                <td class="py-3 px-6 font-semibold text-slate-700">{{ $d->subscription->user->first_name ?? 'Unknown' }} {{ $d->subscription->user->last_name ?? '' }}</td>
                                <td class="py-3 px-6">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-xs font-bold uppercase',
                                        'bg-emerald-50 text-emerald-600' => $d->status === 'sent',
                                        'bg-rose-50 text-rose-600' => in_array($d->status, ['failed', 'expired']),
                                        'bg-slate-100 text-slate-500' => $d->status === 'pending',
                                    ])>{{ $d->status }}</span>
                                </td>
                                <td class="py-3 px-6 text-slate-400">{{ $d->error ?? '—' }}</td>
                                <td class="py-3 px-6 text-slate-400">{{ $d->sent_at?->format('M d, Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-400 font-semibold">No deliveries recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $deliveries->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
</x-app-layout>
