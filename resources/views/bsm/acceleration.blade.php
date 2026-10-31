<x-app-layout>
<div class="space-y-6" x-data="{
    showReassignModal: false,
    showExportModal: false,
    selectedSimId: '',
    selectedSimNumber: '',
    currentHolderName: '',
    currentHolderRole: '',
    dateRange: '30days',
    exportFormat: 'csv',
    managerSearch: '',
    selectedManagerId: '',
    managerRole: ''
}">

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

    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                <p class="text-sm font-medium">{{ session('error') }}</p>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center p-2 rounded-lg bg-rose-50 text-rose-600">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 font-display">Sales Acceleration &amp; Interventions Desk</h1>
                    <p class="text-sm text-slate-500">Unblock bottlenecked stock, approve purchase requests, and resolve failed activation bonuses</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="showExportModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm transition">
                    <i data-lucide="download" class="w-4 h-4 text-slate-400"></i>
                    <span>Export Acceleration Report</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 1. Stalled Field Stock Alert Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Stalled Field Stock (≥ 7 Days)</h2>
                <p class="text-xs text-slate-500">Unactivated SIMs sitting with partners/agents — swap or recall to accelerate turnover</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                {{ $dormantSims->total() }} stuck
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">SIM Number</th>
                        <th class="py-2.5 px-3">Current Holder</th>
                        <th class="py-2.5 px-3">Days In Field</th>
                        <th class="py-2.5 px-3 text-right">Quick Intervention</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($dormantSims as $dSim)
                        @php
                            $dHolder = $dSim->user ?? $dSim->partner ?? $dSim->coordinator ?? $dSim->regionalManager;
                            $dDays = $dSim->daysHeld();
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-900 text-xs">{{ $dSim->number }}</td>
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                <p class="font-medium text-slate-900">{{ $dHolder?->name ?? 'Unknown' }}</p>
                                <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $dHolder?->role ?? '')) }} &bull; {{ $dHolder?->phone ?? '—' }}</p>
                            </td>
                            <td class="py-2.5 px-3 text-xs font-bold text-rose-600">
                                {{ $dDays }} days
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <button type="button" @click="selectedSimId = '{{ $dSim->id }}'; selectedSimNumber = '{{ $dSim->number }}'; currentHolderName = '{{ $dHolder?->name }}'; currentHolderRole = '{{ $dHolder?->role }}'; showReassignModal = true" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">
                                    Reassign
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-xs text-slate-400">No dormant stock detected. Velocity is healthy!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            {{ $dormantSims->links() }}
        </div>
    </div>

    <!-- 2. Pending Partner Stock Requests -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Pending Stock Requests</h2>
                <p class="text-xs text-slate-500">Fulfill incoming inventory requests to accelerate partner deployment</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                {{ $pendingRequests->total() }} pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Requester</th>
                        <th class="py-2.5 px-3">Item Requested</th>
                        <th class="py-2.5 px-3">Quantity</th>
                        <th class="py-2.5 px-3">Requested At</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendingRequests as $req)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                <p class="font-medium text-slate-900">{{ $req->user?->name ?? 'User #' . $req->user_id }}</p>
                                <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $req->user?->role ?? '')) }} &bull; {{ $req->user?->phone }}</p>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                {{ $req->category }} ({{ strtoupper($req->provider) }})
                            </td>
                            <td class="py-2.5 px-3 text-xs font-bold text-slate-900">{{ $req->quantity }}</td>
                            <td class="py-2.5 px-3 text-xs text-slate-400">{{ $req->created_at->diffForHumans() }}</td>
                            <td class="py-2.5 px-3 text-right">
                                <form method="POST" action="{{ route('bsm.requests.approve', $req) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">
                                        Approve &amp; Dispatch
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-slate-400">All inventory requests are up to date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            {{ $pendingRequests->links() }}
        </div>
    </div>

    <!-- 3. Failed Activation Bonus Retries -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Failed Activation Bonuses</h2>
                <p class="text-xs text-slate-500">Retry vendor data top-ups that stalled during SIM activation</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                {{ $failedBonusReports->total() }} stalled
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Target Number</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3">Attempt Date</th>
                        <th class="py-2.5 px-3">Vendor Response</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($failedBonusReports as $rep)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-900 text-xs">{{ $rep->phone ?? $rep->account_number ?? '—' }}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800">
                                    {{ $rep->status }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-slate-400">{{ $rep->created_at->format('M d, H:i') }}</td>
                            <td class="py-2.5 px-3 text-xs text-slate-500 max-w-xs truncate">{{ $rep->description ?? $rep->api_response ?? 'No error payload' }}</td>
                            <td class="py-2.5 px-3 text-right">
                                <form method="POST" action="{{ route('bsm.failed-activations.retry', $rep) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-[#0056D2] hover:bg-blue-700 text-white text-xs font-semibold">
                                        Retry Bonus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-slate-400">No failed activation bonus attempts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            {{ $failedBonusReports->links() }}
        </div>
    </div>

    <!-- Export Modal -->
    <div x-show="showExportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" @click.self="showExportModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Export Sales Acceleration Report</h3>
                        <p class="text-xs text-slate-400">Generate a comprehensive audit of bottlenecks, stalled inventory, and turnover velocity.</p>
                    </div>
                </div>
                <button type="button" @click="showExportModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('bsm.reports.acceleration.export') }}" target="_blank" class="space-y-4">
                <!-- Date Range Selector -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Reporting Period</label>
                    <select name="range" x-model="dateRange" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 p-2.5">
                        <option value="30days">Last 30 Days (Default)</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="all">All Time (Historical)</option>
                        <option value="custom">Custom Date Range...</option>
                    </select>
                </div>

                <!-- Custom Date Range Inputs (Shown when custom is selected) -->
                <div x-show="dateRange === 'custom'" x-cloak class="grid grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Start Date</label>
                        <input type="date" name="start_date" class="w-full text-xs rounded-lg border-slate-200 p-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">End Date</label>
                        <input type="date" name="end_date" class="w-full text-xs rounded-lg border-slate-200 p-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>
                </div>

                <!-- Manager Search & Filter -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Filter by Specific Manager (Optional)</label>
                    <div class="space-y-2">
                        <!-- Quick Search Input -->
                        <div class="relative">
                            <input type="text" name="manager_search" x-model="managerSearch"
                                   placeholder="Search manager by name, email, or phone..."
                                   class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-rose-500 placeholder-slate-400">
                            <div class="absolute left-3 top-2.5 text-slate-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>

                        <!-- Manager Dropdown Picker -->
                        <select name="manager_id" x-model="selectedManagerId" class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 p-2.5 text-slate-700">
                            <option value="">-- All Field Managers (Network Scope) --</option>
                            @if(isset($regionalManagers) && $regionalManagers->isNotEmpty())
                                <optgroup label="Regional Managers">
                                    @foreach ($regionalManagers as $rm)
                                        <option value="{{ $rm->id }}">{{ $rm->name }} (Regional Manager) — {{ $rm->phone }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if(isset($coordinators) && $coordinators->isNotEmpty())
                                <optgroup label="Coordinators">
                                    @foreach ($coordinators as $coord)
                                        <option value="{{ $coord->id }}">{{ $coord->name }} (Coordinator) — {{ $coord->phone }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if(isset($partners) && $partners->isNotEmpty())
                                <optgroup label="Partners">
                                    @foreach ($partners as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} (Partner) — {{ $p->phone }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if(isset($businesses) && $businesses->isNotEmpty())
                                <optgroup label="Enterprise Businesses">
                                    @foreach ($businesses as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }} (Enterprise Business) — {{ $b->phone }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Manager Tier Scope</label>
                    <select name="manager_role" class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 p-2.5">
                        <option value="">All Tiers</option>
                        <option value="regional_manager">Regional Managers Only</option>
                        <option value="coordinator">Coordinators Only</option>
                        <option value="partner">Partners Only</option>
                        <option value="business">Enterprise Businesses Only</option>
                        <option value="business_agent">Business Agents Only</option>
                    </select>
                </div>

                <!-- Document Format Picker (CSV vs PDF) -->
                <div class="space-y-1.5 pt-1">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Export Document Format</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer transition hover:bg-slate-50"
                               :class="exportFormat === 'csv' ? 'border-rose-500 bg-rose-50/40 ring-1 ring-rose-500' : ''">
                            <input type="radio" name="format" value="csv" x-model="exportFormat" class="text-rose-600 focus:ring-rose-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">CSV Document</span>
                                <span class="block text-[10px] text-slate-400">Raw line items &amp; Excel sheets</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer transition hover:bg-slate-50"
                               :class="exportFormat === 'pdf' ? 'border-rose-500 bg-rose-50/40 ring-1 ring-rose-500' : ''">
                            <input type="radio" name="format" value="pdf" x-model="exportFormat" class="text-rose-600 focus:ring-rose-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">PDF Report</span>
                                <span class="block text-[10px] text-slate-400">Executive presentation</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="showExportModal = false"
                            class="px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Download Acceleration Report</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
