<x-app-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                        <i data-lucide="badge-percent" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold text-slate-900 font-display">BSM Central Commission Wallet</h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                View-Only
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Real-time ledger of central BSM commission allocations accrued from SIM activations across the platform.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('bsm.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl text-xs font-semibold shadow-xs transition">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-500"></i>
                        BSM Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- View-Only Notice Banner -->
        <div class="p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border border-indigo-100 rounded-2xl flex items-start gap-3 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-indigo-100/80 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div class="text-xs text-slate-700 space-y-0.5">
                <span class="font-bold text-slate-900 text-sm">Read-Only Commission Monitor</span>
                <p class="text-slate-600 leading-relaxed">
                    As a Business Sales Manager, you have comprehensive read-only visibility into this central commission pool. Every eligible SIM activation automatically allocates commission here according to company policy. Debits, disbursements, and commission percentages are managed centrally by Platform Administration.
                </p>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Current Balance -->
            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Current Balance</span>
                    <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="wallet" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900 font-display">₦{{ number_format($wallet->balance, 2) }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Available accrued commission balance</p>
                </div>
            </div>

            <!-- Credited This Month -->
            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">This Month</span>
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl font-extrabold text-emerald-600 font-display">₦{{ number_format($thisMonthCredited, 2) }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Accrued in {{ now()->format('F Y') }}</p>
                </div>
            </div>

            <!-- All-Time Credited -->
            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Credited</span>
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900 font-display">₦{{ number_format($wallet->total_credited, 2) }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Cumulative platform commission earned</p>
                </div>
            </div>

            <!-- All-Time Debited -->
            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Disbursed</span>
                    <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                        <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900 font-display">₦{{ number_format($wallet->total_debited, 2) }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Settlements / payouts processed</p>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <form method="GET" action="{{ route('bsm.commission-wallet') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label for="type" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Type</label>
                    <select name="type" id="type" class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition">
                        <option value="">All Types</option>
                        <option value="credit" @selected(request('type') === 'credit')>Credit (+)</option>
                        <option value="debit" @selected(request('type') === 'debit')>Debit (-)</option>
                    </select>
                </div>
                <div>
                    <label for="source" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Source</label>
                    <input type="text" name="source" id="source" value="{{ request('source') }}" placeholder="e.g. sim_activation"
                           class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition" />
                </div>
                <div>
                    <label for="from_date" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                           class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition" />
                </div>
                <div>
                    <label for="to_date" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                           class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition" />
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#0056D2] hover:bg-[#0047B0] text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        Filter
                    </button>
                    @if (request()->hasAny(['type', 'source', 'from_date', 'to_date']))
                        <a href="{{ route('bsm.commission-wallet') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition" title="Clear Filters">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Transactions Ledger Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 font-display">Commission Transactions Ledger</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Showing paginated transaction history for the central commission wallet</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">
                    Total Records: {{ number_format($transactions->total()) }}
                </span>
            </div>

            @if ($transactions->isEmpty())
                <div class="py-16 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                        <i data-lucide="receipt-text" class="w-6 h-6"></i>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-bold text-slate-700">No Commission Transactions Found</p>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">Commission entries will appear here in real time as eligible retail SIMs are activated across the platform.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-3.5 px-4">Date / Time</th>
                                <th class="py-3.5 px-4">Reference</th>
                                <th class="py-3.5 px-4">Type</th>
                                <th class="py-3.5 px-4">Source</th>
                                <th class="py-3.5 px-4 text-right">Amount</th>
                                <th class="py-3.5 px-4 text-right">Balance After</th>
                                <th class="py-3.5 px-4">Narration</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            @foreach ($transactions as $tx)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                                        {{ $tx->created_at->format('M d, Y') }}
                                        <span class="text-[11px] text-slate-400 block">{{ $tx->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap font-mono text-[11px] text-slate-600">
                                        {{ $tx->transaction_ref }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if ($tx->type === 'credit')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                <i data-lucide="plus" class="w-3 h-3"></i>
                                                Credit
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                                <i data-lucide="minus" class="w-3 h-3"></i>
                                                Debit
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-600 font-mono text-[11px]">
                                            {{ $tx->source }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-right font-bold {{ $tx->type === 'credit' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $tx->type === 'credit' ? '+' : '-' }}₦{{ number_format($tx->amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-right font-semibold text-slate-800">
                                        ₦{{ number_format($tx->new_balance, 2) }}
                                    </td>
                                    <td class="py-3 px-4 max-w-xs truncate text-slate-600" title="{{ $tx->description }}">
                                        {{ $tx->description ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $transactions->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
