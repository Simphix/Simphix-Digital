<x-app-layout>
    <title>SimPhix - Fintava Wallet Sweeps</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header & Tab Navigation -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="arrow-left-right" class="w-5 h-5"></i>
                    </div>
                    Fintava Wallet Sweeps (Audits)
                </h1>
                <p class="text-sm text-slate-500 mt-1">Audit log of all automated customer-to-merchant wallet-to-wallet transfers on Fintava (Loma Bank).</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.webhooks.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition shadow-xs">
                    <i data-lucide="webhook" class="w-4 h-4 text-slate-400"></i>
                    Webhook Logs
                </a>
            </div>
        </div>

        <!-- Header Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2] shrink-0">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Sweeps</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ number_format($totalCount) }}</p>
                </div>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100/50 flex items-center justify-center text-emerald-600 shrink-0">
                    <i data-lucide="coins" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Amount Swept</p>
                    <p class="text-lg font-bold font-display text-emerald-600 truncate">₦{{ number_format((float) $totalSweptAmount, 2) }}</p>
                </div>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100/50 flex items-center justify-center text-emerald-600 shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Successful</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ number_format($successCount) }}</p>
                </div>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100/50 flex items-center justify-center text-rose-600 shrink-0">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Failed</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ number_format($failedCount) }}</p>
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
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search reference, account..."
                   class="px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs w-64" />

            <x-select-input name="status" class="rounded-xl font-medium text-sm w-auto" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </x-select-input>

            <button type="submit" class="px-3.5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition shadow-xs">
                Filter
            </button>

            @if (request('status') || request('search'))
                <a href="{{ route('admin.fintava-sweeps.index') }}" class="text-xs font-bold text-slate-400 hover:text-slate-600">Clear filters</a>
            @endif
        </form>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">Deposit Ref</th>
                            <th class="py-2.5">Sweep Reference</th>
                            <th class="py-2.5">User</th>
                            <th class="py-2.5">Sender Account</th>
                            <th class="py-2.5">Receiver (Merchant)</th>
                            <th class="py-2.5">Amount</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Date</th>
                            <th class="py-2.5">Payload</th>
                            <th class="py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sweeps as $sweep)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors align-top">
                                <td class="py-3 font-mono font-bold text-slate-700">{{ $sweep->deposit_reference }}</td>
                                <td class="py-3 font-mono text-slate-600">{{ $sweep->customer_reference }}</td>
                                <td class="py-3 text-slate-700">
                                    @if ($sweep->user)
                                        <span class="font-semibold">{{ $sweep->user->first_name }} {{ $sweep->user->last_name }}</span>
                                        <p class="text-[10px] text-slate-400">{{ $sweep->user->email }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 font-mono text-slate-600">{{ $sweep->sender_account }}</td>
                                <td class="py-3 font-mono text-slate-600">{{ $sweep->receiver_account }}</td>
                                <td class="py-3 font-bold text-slate-800">₦{{ number_format((float) $sweep->amount, 2) }}</td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-xs font-bold',
                                        'bg-emerald-50 text-emerald-600' => $sweep->status === 'completed',
                                        'bg-rose-50 text-rose-600' => $sweep->status === 'failed',
                                        'bg-amber-50 text-amber-600' => $sweep->status === 'pending',
                                    ])>
                                        {{ ucfirst($sweep->status) }}
                                    </span>
                                    @if ($sweep->status === 'failed' && $sweep->error_message)
                                        <p class="text-[11px] text-rose-500 mt-1 max-w-xs">{{ $sweep->error_message }}</p>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-500 whitespace-nowrap">{{ $sweep->created_at->format('M j, Y g:ia') }}</td>
                                <td class="py-3">
                                    <details class="max-w-xs">
                                        <summary class="cursor-pointer text-[#0056D2] font-semibold">Details</summary>
                                        <div class="mt-2 space-y-2">
                                            @if ($sweep->request_payload)
                                                <div>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Request</p>
                                                    <pre class="text-[10px] bg-slate-50 border border-slate-100 rounded-lg p-2 overflow-x-auto max-h-36 overflow-y-auto">{{ json_encode($sweep->request_payload, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if ($sweep->response_payload)
                                                <div>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Response</p>
                                                    <pre class="text-[10px] bg-slate-50 border border-slate-100 rounded-lg p-2 overflow-x-auto max-h-36 overflow-y-auto">{{ json_encode($sweep->response_payload, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                </td>
                                <td class="py-3 text-right whitespace-nowrap">
                                    @if ($sweep->status === 'failed')
                                        <form method="POST" action="{{ route('admin.fintava-sweeps.retry', $sweep->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold text-[#0056D2] hover:bg-blue-50 rounded-lg transition border border-blue-200">
                                                Retry Sweep
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    No Fintava wallet sweeps recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sweeps->hasPages())
                <div class="pt-4 border-t border-slate-100">
                    {{ $sweeps->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
