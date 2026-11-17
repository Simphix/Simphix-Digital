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

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 mb-2">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    Staff Immigration Processing Desk
                </span>
                <h1 class="text-2xl font-black text-slate-900 font-display">
                    International Passport Requests
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mb-0">
                    Manage applicant submissions for new passports &amp; renewals, schedule biometric capturing, and download official A4 dossiers.
                </p>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Requests</span>
                <span class="text-2xl font-black text-slate-900">{{ number_format($stats['total']) }}</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-amber-200 bg-amber-50/20 shadow-xs">
                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Pending</span>
                <span class="text-2xl font-black text-amber-700">{{ number_format($stats['pending']) }}</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-blue-200 bg-blue-50/20 shadow-xs">
                <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Under Review</span>
                <span class="text-2xl font-black text-blue-700">{{ number_format($stats['under_review']) }}</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-purple-200 bg-purple-50/20 shadow-xs">
                <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">Capturing Sched.</span>
                <span class="text-2xl font-black text-purple-700">{{ number_format($stats['capturing_scheduled']) }}</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-emerald-200 bg-emerald-50/20 shadow-xs">
                <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Completed</span>
                <span class="text-2xl font-black text-emerald-700">{{ number_format($stats['completed']) }}</span>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-rose-200 bg-rose-50/20 shadow-xs">
                <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Rejected</span>
                <span class="text-2xl font-black text-rose-700">{{ number_format($stats['rejected']) }}</span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="dash-card p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <form action="{{ route('admin.passport-requests.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Search</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Reference, Name, NIN, Phone..."
                           class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Statuses</option>
                        @foreach (\App\Models\PassportRequest::STATUSES as $val => $lbl)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Application Type Filter -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Type</label>
                    <select name="application_type" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Types</option>
                        <option value="new" {{ request('application_type') === 'new' ? 'selected' : '' }}>New Passport</option>
                        <option value="renewal" {{ request('application_type') === 'renewal' ? 'selected' : '' }}>Renewal</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Date</label>
                    <select name="date_range" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Time</option>
                        <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7days" {{ request('date_range') === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ request('date_range') === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="w-full px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Filter</span>
                    </button>
                    @if (request()->hasAny(['search', 'status', 'application_type', 'date_range']))
                        <a href="{{ route('admin.passport-requests.index') }}"
                           class="px-3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition"
                           title="Clear Filters">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Applications Table -->
        <div class="dash-card rounded-2xl bg-white border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3.5 px-4">Reference</th>
                            <th class="py-3.5 px-4">Applicant</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">NIN</th>
                            <th class="py-3.5 px-4">Origin / LGA</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Capturing</th>
                            <th class="py-3.5 px-4">Submitted</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $req)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <a href="{{ route('admin.passport-requests.show', $req) }}" class="text-emerald-700 hover:underline">
                                        {{ $req->reference }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 uppercase">{{ $req->full_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $req->mobile_number }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $req->application_type === 'new' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                        {{ $req->application_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-700">{{ $req->nin }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $req->state_of_origin }}, {{ $req->lga }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $req->status_badge_class }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if ($req->capturing_date)
                                        <span class="font-bold text-purple-700">{{ $req->capturing_date->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-slate-400 italic">Not scheduled</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $req->created_at->format('M d, Y') }}</td>
                                <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                    <a href="{{ route('admin.passport-requests.show', $req) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Inspect</span>
                                    </a>
                                    <a href="{{ route('admin.passport-requests.pdf', $req) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-[11px] border border-emerald-200 transition"
                                       title="Download Official A4 Dossier">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span>A4 PDF</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-12 text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                        <i data-lucide="inbox" class="w-6 h-6"></i>
                                    </div>
                                    <p class="font-bold text-slate-700 mb-0">No passport requests found</p>
                                    <span class="text-xs text-slate-400">Try adjusting your filters or search keywords</span>
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
