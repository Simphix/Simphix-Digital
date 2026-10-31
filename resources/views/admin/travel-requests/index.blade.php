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
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="plane" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display tracking-tight">
                            Travel &amp; Tours Requests Desk
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('travel.index') }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>View Customer Catalog</span>
                    </a>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-6 pt-6 border-t border-slate-100">
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Inquiries</span>
                    <span class="text-xl font-bold font-display text-slate-900 mt-1 block">{{ number_format($stats['total']) }}</span>
                </div>
                <div class="bg-amber-50/60 rounded-xl p-3.5 border border-amber-200/60">
                    <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Pending Triage</span>
                    <span class="text-xl font-bold font-display text-amber-900 mt-1 block">{{ number_format($stats['pending']) }}</span>
                </div>
                <div class="bg-blue-50/60 rounded-xl p-3.5 border border-blue-200/60">
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">In Review / Contacted</span>
                    <span class="text-xl font-bold font-display text-blue-900 mt-1 block">{{ number_format($stats['active']) }}</span>
                </div>
                <div class="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200/60">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Approved / Ready</span>
                    <span class="text-xl font-bold font-display text-emerald-900 mt-1 block">{{ number_format($stats['approved']) }}</span>
                </div>
                <div class="bg-rose-50/60 rounded-xl p-3.5 border border-rose-200/60">
                    <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Declined / Cancelled</span>
                    <span class="text-xl font-bold font-display text-rose-900 mt-1 block">{{ number_format($stats['cancelled']) }}</span>
                </div>
            </div>

            <!-- Filters Form -->
            <form method="GET" action="{{ route('admin.travel-requests.index') }}" class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search name, phone, ref, or package..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="">All Statuses</option>
                        @foreach (\App\Models\TravelRequest::STATUSES as $stKey => $stLabel)
                            <option value="{{ $stKey }}" {{ request('status') === $stKey ? 'selected' : '' }}>
                                {{ $stLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category / Package Type Filter -->
                <div>
                    <select name="package_type" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="">All Categories</option>
                        @foreach (\App\Models\TravelRequest::PACKAGE_TYPES as $ptKey => $ptLabel)
                            <option value="{{ $ptKey }}" {{ request('package_type') === $ptKey ? 'selected' : '' }}>
                                {{ $ptLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range & Submit -->
                <div class="flex items-center gap-2">
                    <select name="date_range" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="">All Dates</option>
                        <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7days" {{ request('date_range') === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ request('date_range') === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-[#0056D2] text-white text-xs font-bold transition shrink-0">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'status', 'package_type', 'date_range']))
                        <a href="{{ route('admin.travel-requests.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition shrink-0" title="Clear Filters">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                            <th class="py-3 px-3">Reference</th>
                            <th class="py-3 px-3">Customer / Traveler</th>
                            <th class="py-3 px-3">Package Requested</th>
                            <th class="py-3 px-3">Travelers</th>
                            <th class="py-3 px-3">Preferred Date</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3">Submitted</th>
                            <th class="py-3 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $req)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                    <a href="{{ route('admin.travel-requests.show', $req) }}" class="text-[#0056D2] hover:underline">
                                        {{ $req->reference }}
                                    </a>
                                </td>
                                <td class="py-3 px-3">
                                    <p class="font-bold text-slate-900">{{ $req->name }}</p>
                                    <p class="text-[11px] text-slate-500 font-mono">{{ $req->phone }}</p>
                                    @if ($req->email)
                                        <p class="text-[10px] text-slate-400 truncate max-w-[160px]">{{ $req->email }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <p class="font-medium text-slate-800">{{ $req->package_name }}</p>
                                    <span class="inline-block text-[10px] text-slate-400 uppercase font-semibold">
                                        {{ $req->package_type_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-800">
                                    {{ $req->number_of_travelers }}
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    {{ $req->departure_date ?: '—' }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $req->status_badge_class }}">
                                        {{ $req->status_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-500">
                                    {{ $req->created_at->format('M d, H:i') }}
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="{{ route('admin.travel-requests.show', $req) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-[#0056D2] text-white font-semibold text-xs transition">
                                        <span>Manage</span>
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    No travel requests match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100">
                {{ $requests->links() }}
            </div>
        </div>

    </div>
</x-app-layout>
