<x-app-layout>
    <title>SmartSIM - Webhook Logs</title>

    @php
        // Shared by both the super_admin console (admin.webhooks.*) and
        // the support-desk console (support-desk.webhooks.*) — support
        // gets the exact same log visibility but never the Retry action,
        // which re-runs payment-webhook processing.
        $onSupportDesk = request()->routeIs('support-desk.*');
        $webhooksIndexRoute = $onSupportDesk ? 'support-desk.webhooks.index' : 'admin.webhooks.index';
        $canRetryWebhooks = !$onSupportDesk;
    @endphp

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="webhook" class="w-5 h-5"></i>
                    </div>
                    Webhook Logs
                </h1>
                <p class="text-sm text-slate-500 mt-1">Every recorded webhook attempt across all providers — retry any that failed.</p>
            </div>
        </div>

        <!-- Header Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2] shrink-0">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $totalCount }}</p>
                </div>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100/50 flex items-center justify-center text-emerald-600 shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Success</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $successCount }}</p>
                </div>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100/50 flex items-center justify-center text-rose-600 shrink-0">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Failed</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $failedCount }}</p>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Filters -->
        <form method="GET" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3">
            <x-select-input name="status" class="rounded-xl font-medium text-sm w-auto" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach (['received', 'invalid_signature', 'success', 'failed'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </x-select-input>
            <x-select-input name="provider" class="rounded-xl font-medium text-sm w-auto" onchange="this.form.submit()">
                <option value="">All Providers</option>
                <option value="palmpay" {{ request('provider') === 'palmpay' ? 'selected' : '' }}>PalmPay</option>
            </x-select-input>
            @if (request('status') || request('provider'))
                <a href="{{ route($webhooksIndexRoute) }}" class="text-xs font-bold text-slate-400 hover:text-slate-600">Clear filters</a>
            @endif
        </form>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">Provider</th>
                            <th class="py-2.5">Event</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Attempts</th>
                            <th class="py-2.5">Received</th>
                            <th class="py-2.5">Payload</th>
                            <th class="py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($webhookLogs as $log)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors align-top">
                                <td class="py-3 font-bold text-slate-700 uppercase">{{ $log->provider }}</td>
                                <td class="py-3 text-slate-600">{{ $log->event_type ?? '—' }}</td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-xs font-bold',
                                        'bg-emerald-50 text-emerald-600' => $log->status === 'success',
                                        'bg-rose-50 text-rose-600' => $log->status === 'failed',
                                        'bg-amber-50 text-amber-600' => $log->status === 'invalid_signature',
                                        'bg-slate-100 text-slate-500' => $log->status === 'received',
                                    ])>
                                        {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                                    </span>
                                    @if ($log->status === 'failed' && $log->error_message)
                                        <p class="text-[11px] text-rose-500 mt-1 max-w-xs">{{ $log->error_message }}</p>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-600">{{ $log->attempts }}</td>
                                <td class="py-3 text-slate-500">{{ $log->created_at->format('M j, Y g:ia') }}</td>
                                <td class="py-3">
                                    <details class="max-w-xs">
                                        <summary class="cursor-pointer text-[#0056D2] font-semibold">View</summary>
                                        <pre class="mt-2 text-[10px] bg-slate-50 border border-slate-100 rounded-lg p-2 overflow-x-auto max-h-48 overflow-y-auto">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                </td>
                                <td class="py-3 text-right whitespace-nowrap">
                                    @if ($canRetryWebhooks && $log->status === 'failed')
                                        <button type="button" @click="Swal.fire({
                                            title: 'Retry Webhook #{{ $log->id }}?',
                                            text: 'This will re-run the stored payload through processing. Safe to retry — it cannot double-credit a wallet.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#0056D2',
                                            cancelButtonColor: '#64748b',
                                            confirmButtonText: 'Retry'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                let f = document.createElement('form');
                                                f.action = '{{ route('admin.webhooks.retry', $log->id) }}';
                                                f.method = 'POST';
                                                f.innerHTML = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'>';
                                                document.body.appendChild(f);
                                                f.submit();
                                            }
                                        })" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">
                                            Retry
                                        </button>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 font-semibold">No webhook attempts recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $webhookLogs->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
