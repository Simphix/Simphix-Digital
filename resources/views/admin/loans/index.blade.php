<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-7xl mx-auto space-y-6">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        <!-- Prominent Disclaimer in Admin View -->
        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-slate-800 text-xs flex items-center gap-3">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0"></i>
            <span><strong>Underwriting Rule:</strong> Business loans are strictly for Moniepoint business account holders. Ensure each applicant's Moniepoint account number turnover is verified before approval.</span>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-[#00266B] flex items-center justify-center p-2 shrink-0">
                        <img src="{{ asset('assets/images/moniepoint-logo.png') }}" alt="Moniepoint" class="w-full h-auto object-contain brightness-0 invert">
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display tracking-tight">
                            Moniepoint Business Loans Desk
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('loans.index') }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>View Customer Loan Portal</span>
                    </a>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 mt-6 pt-6 border-t border-slate-100">
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Applications</span>
                    <span class="text-xl font-bold font-display text-slate-900 mt-1 block">{{ number_format($stats['total']) }}</span>
                </div>
                <div class="bg-amber-50/60 rounded-xl p-3.5 border border-amber-200/60">
                    <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Processing</span>
                    <span class="text-xl font-bold font-display text-amber-900 mt-1 block">{{ number_format($stats['pending']) }}</span>
                </div>
                <div class="bg-blue-50/60 rounded-xl p-3.5 border border-blue-200/60">
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">In Review / Contacted</span>
                    <span class="text-xl font-bold font-display text-blue-900 mt-1 block">{{ number_format($stats['in_review']) }}</span>
                </div>
                <div class="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200/60">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Approved</span>
                    <span class="text-xl font-bold font-display text-emerald-900 mt-1 block">{{ number_format($stats['approved']) }}</span>
                </div>
                <div class="bg-purple-50/60 rounded-xl p-3.5 border border-purple-200/60">
                    <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">Disbursed Volume</span>
                    <span class="text-lg font-bold font-display text-purple-900 mt-1 block">&#8358;{{ number_format($stats['total_volume'], 0) }}</span>
                </div>
                <div class="bg-rose-50/60 rounded-xl p-3.5 border border-rose-200/60">
                    <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Declined</span>
                    <span class="text-xl font-bold font-display text-rose-900 mt-1 block">{{ number_format($stats['declined']) }}</span>
                </div>
            </div>

            <!-- Filters Form -->
            <form method="GET" action="{{ route('admin.loan-requests.index') }}" class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search ref, name, phone, account #..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="">All Statuses</option>
                        @foreach (\App\Models\LoanRequest::STATUSES as $stKey => $stLabel)
                            <option value="{{ $stKey }}" {{ request('status') === $stKey ? 'selected' : '' }}>
                                {{ $stLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div>
                    <select name="date_range" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="">All Time</option>
                        <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7days" {{ request('date_range') === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ request('date_range') === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 py-2 px-3 text-xs font-bold rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white shadow-xs transition">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'status', 'date_range']))
                        <a href="{{ route('admin.loan-requests.index') }}"
                           class="py-2 px-3 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Requests Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Reference</th>
                            <th class="py-3.5 px-4">Merchant Name</th>
                            <th class="py-3.5 px-4">Moniepoint Account</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Tenure</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Applied</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $item)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-slate-900 block">{{ $item->reference }}</span>
                                    @if ($item->user)
                                        <span class="text-[10px] text-slate-400">User #{{ $item->user->id }}</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 block">{{ $item->name }}</span>
                                    <span class="text-slate-500 font-mono text-[11px] block">{{ $item->phone }}</span>
                                    @if ($item->business_name)
                                        <span class="text-[10px] text-slate-400 truncate max-w-[160px] block">{{ $item->business_name }}</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-mono font-bold text-[#00266B] px-2 py-0.5 rounded bg-blue-50 border border-blue-200">
                                        {{ $item->moniepoint_account_number }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-emerald-700 block text-xs">&#8358;{{ number_format($item->amount_requested, 2) }}</span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $item->tenure_label }}
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $item->status_badge_class }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-slate-500">
                                    <span class="block">{{ $item->created_at->format('M d, Y') }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $item->created_at->format('h:i A') }}</span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('admin.loan-requests.show', $item) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#00266B] hover:bg-[#001F5C] text-white text-[11px] font-bold transition shadow-xs">
                                        <span>Assess</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                                    <p class="font-medium text-xs">No business loan applications found matching the current filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
