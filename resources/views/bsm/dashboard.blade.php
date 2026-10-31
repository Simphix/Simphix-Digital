<x-app-layout>
<div class="space-y-6">

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

    <!-- Header & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary">
                        <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 font-display">Business Sales Manager Console</h1>
                        <p class="text-sm text-slate-500">Executive Overview: SIM lifecycle velocity, territorial sales, and acceleration metrics</p>
                    </div>
                </div>
            </div>

            <!-- Global Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.manage.users') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold shadow-sm hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="users" class="w-4 h-4 text-slate-500"></i>
                    <span>Users Management</span>
                    @if (($pendingUpgradesCount ?? 0) > 0)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white">
                            {{ $pendingUpgradesCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('bsm.customers') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold shadow-sm hover:bg-primary-dark transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Customer Desk</span>
                </a>
                <a href="{{ route('bsm.reports.jurisdictions.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold shadow-sm hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                    <span>Export Territory Report</span>
                </a>
            </div>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('bsm.dashboard') }}" class="mt-6 pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <!-- Range Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Time Range</label>
                <select name="range" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3 bg-slate-50">
                    <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="7days" {{ $range === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ $range === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="this_month" {{ $range === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="all" {{ $range === 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">SIM Category</label>
                <select name="category" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3 bg-slate-50">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Provider Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Network Provider</label>
                <select name="provider" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3 bg-slate-50">
                    <option value="">All Providers</option>
                    @foreach ($providers as $prov)
                        <option value="{{ $prov }}" {{ $selectedProvider === $prov ? 'selected' : '' }}>{{ strtoupper($prov) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Reset Filter -->
            <div class="flex items-end gap-2">
                <a href="{{ route('bsm.dashboard') }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset Filters</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Executive KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        <!-- Total Platform SIMs -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total SIMs</span>
                <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600"><i data-lucide="cpu" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-2">
                <p class="text-2xl font-bold text-slate-900 font-display">{{ number_format($totalSims) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ number_format($totalAllocated) }} in field, {{ number_format($totalUnassigned) }} ready</p>
            </div>
        </div>

        <!-- Total Activated Sales -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Activated</span>
                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="check-circle" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-2">
                <p class="text-2xl font-bold text-emerald-600 font-display">{{ number_format($totalActivated) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ number_format($activatedSalesCount) }} sold in period</p>
            </div>
        </div>

        <!-- Sales Volume ₦ -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sales Volume</span>
                <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="trending-up" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-2">
                <p class="text-2xl font-bold text-slate-900 font-display">₦{{ number_format($totalSalesVolume, 0) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $rangeLabel }}</p>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Conversion Rate</span>
                <span class="p-1.5 rounded-lg bg-violet-50 text-violet-600"><i data-lucide="pie-chart" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-2">
                <p class="text-2xl font-bold text-slate-900 font-display">{{ $conversionRate }}%</p>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                    <div class="bg-violet-600 h-1.5 rounded-full" style="width: {{ min(100, $conversionRate) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Dormant Stock Alert -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dormant Stock</span>
                <span class="p-1.5 rounded-lg bg-rose-50 text-rose-600"><i data-lucide="clock-alert" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-2">
                <p class="text-2xl font-bold {{ $totalDormant > 0 ? 'text-rose-600' : 'text-slate-900' }} font-display">{{ number_format($totalDormant) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Idle ≥ 7 days in field</p>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Customers Onboarded</span>
                <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600"><i data-lucide="users" class="w-4 h-4"></i></span>
            </div>
            <div class="mt-2">
                <p class="text-2xl font-bold text-slate-900 font-display">{{ number_format($totalCustomers) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Active end-customers</p>
            </div>
        </div>
    </div>

    <!-- Failed Activation SIM Section -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-rose-50 text-rose-600 shrink-0">
                    <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900 font-display">Failed Activation SIM</h2>
                        @if ($failedBonusCount > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                {{ $failedBonusCount }} Failed
                            </span>
                        @endif
                        @if ($retryingBonusCount > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                {{ $retryingBonusCount }} Retrying
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Activation bonus data top-ups that stalled or encountered vendor failure during activation. Review and retry grants directly.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('bsm.acceleration') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-semibold transition">
                    <span>Acceleration Hub</span>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400"></i>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 px-4">SIM Number</th>
                        <th class="py-3 px-4">Network</th>
                        <th class="py-3 px-4">Holder / Agent</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Failure Reason</th>
                        <th class="py-3 px-4">Failed At</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($failedActivations as $failed)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-slate-900 text-xs">{{ $failed->phone_number ?? $failed->account_number ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $net = strtoupper($failed->network ?? 'UNKNOWN');
                                    $netBadgeClasses = match($net) {
                                        'MTN' => 'bg-amber-50 text-amber-700 border border-amber-200/60',
                                        'AIRTEL' => 'bg-red-50 text-red-700 border border-red-200/60',
                                        'GLO' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/60',
                                        '9MOBILE' => 'bg-teal-50 text-teal-700 border border-teal-200/60',
                                        default => 'bg-slate-100 text-slate-700 border border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $netBadgeClasses }}">
                                    {{ $net }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if ($failed->user)
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $failed->user->name }}</p>
                                        <p class="text-[10px] text-slate-400 capitalize">{{ str_replace('_', ' ', $failed->user->role) }}</p>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if ($failed->status === 'retrying')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Retrying…
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 max-w-xs truncate" title="{{ $failed->description ?? $failed->api_response ?? 'No error payload' }}">
                                {{ $failed->description ?? $failed->api_response ?? 'No error payload' }}
                            </td>
                            <td class="py-3 px-4 text-slate-400 font-medium whitespace-nowrap">
                                <div>{{ $failed->created_at->format('M d, Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $failed->created_at->format('H:i') }} ({{ $failed->created_at->diffForHumans() }})</div>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                @if ($failed->status === 'failed')
                                    <form method="POST" action="{{ route('bsm.failed-activations.retry', $failed) }}" class="inline-block" onsubmit="this.querySelector('button').disabled = true">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#0056D2] hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition">
                                            <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                                            <span>Retry Bonus</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium">
                                        <i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>
                                        <span>In Flight</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700">No Failed Activation SIMs</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">All SIM activation bonus top-ups are successfully delivered or clear.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($failedActivations->hasPages())
            <div class="mt-3 pt-3 border-t border-slate-100">
                {{ $failedActivations->links() }}
            </div>
        @endif
    </div>

    <!-- Management Desks Hub (Multi-Page Navigation Grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- 1. Territories & Downline Desk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between hover:border-primary/40 transition">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Territories Report</h3>
                        <p class="text-xs text-slate-500">Regional &amp; District breakdown</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">
                    Analyze sales performance across active geopolitical regions and senatorial districts under your hierarchy. Export full CSV audit files.
                </p>
                <div class="p-3 bg-slate-50 rounded-xl space-y-2 mb-4 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Regional Volume</span>
                        <span class="font-bold text-slate-900">₦{{ number_format($jurisdictionTotals['sales_volume'], 0) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Field Allocation Ratio</span>
                        <span class="font-bold text-slate-900">{{ number_format($jurisdictionTotals['allocated']) }} SIMs</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('bsm.territories') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                <span>View Territories Report</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <!-- 2. SIM Lifecycle Governance Desk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between hover:border-primary/40 transition">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="p-2.5 rounded-xl bg-violet-50 text-violet-600">
                        <i data-lucide="cpu" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">SIM Lifecycle Desk</h3>
                        <p class="text-xs text-slate-500">Govern holdings &amp; status</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">
                    Inspect every individual SIM across all field tiers. Assign unassigned inventory, swap slow holders, recall stock, or update status.
                </p>
                <div class="p-3 bg-slate-50 rounded-xl space-y-2 mb-4 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Active in Field</span>
                        <span class="font-bold text-blue-600">{{ number_format($totalAllocated) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Ready in Depot</span>
                        <span class="font-bold text-emerald-600">{{ number_format($totalUnassigned) }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('bsm.lifecycle') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                <span>Open Lifecycle Desk</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <!-- 3. Customer Handling Desk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between hover:border-primary/40 transition">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Customer Handling Desk</h3>
                        <p class="text-xs text-slate-500">Customer directory &amp; onboarding</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">
                    Access verified customer profiles, view linked SIM details, and execute instant customer KYC registration and SIM activation.
                </p>
                <div class="p-3 bg-slate-50 rounded-xl space-y-2 mb-4 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Registered Customers</span>
                        <span class="font-bold text-slate-900">{{ number_format($totalCustomers) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Available for Activation</span>
                        <span class="font-bold text-emerald-600">{{ number_format($totalUnassigned) }} SIMs</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('bsm.customers') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                <span>Open Customer Desk</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <!-- 4. Sales Acceleration & Interventions Desk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between hover:border-primary/40 transition">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="p-2.5 rounded-xl bg-rose-50 text-rose-600">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Sales Acceleration</h3>
                        <p class="text-xs text-slate-500">Resolve stalled distribution</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">
                    Identify and intervene on bottlenecked stock: swap dormant inventory (≥ 7 days), approve agent SIM requests, and retry failed bonuses.
                </p>
                <div class="p-3 bg-slate-50 rounded-xl space-y-2 mb-4 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Dormant SIMs</span>
                        <span class="font-bold {{ $totalDormant > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $totalDormant }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Pending Stock Requests</span>
                        <span class="font-bold {{ $pendingRequestsCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $pendingRequestsCount }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('bsm.acceleration') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition">
                <span>Open Acceleration Desk</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <!-- 5. SIM Cards & Plans Management Desk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between hover:border-primary/40 transition">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="p-2.5 rounded-xl bg-indigo-50 text-indigo-600">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">SIM Cards &amp; Plans</h3>
                        <p class="text-xs text-slate-500">Platform inventory &amp; operations</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">
                    Full administrative control over direct assignments, bulk collect, bulk assign, requests, swap requests, and category activation switches.
                </p>
                <div class="p-3 bg-slate-50 rounded-xl space-y-2 mb-4 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>New SIM Uploads</span>
                        <span class="font-bold text-slate-400">Admin Only</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Management Access</span>
                        <span class="font-bold text-emerald-600">Enabled</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.sim-plan.index') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition">
                <span>Manage SIMs &amp; Plans</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <!-- 6. Users Management Desk -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between hover:border-primary/40 transition">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <span class="p-2.5 rounded-xl bg-teal-50 text-teal-600">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Users Management</h3>
                        <p class="text-xs text-slate-500">Accounts, roles &amp; upgrades</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed mb-4">
                    Full user directory governance. View profiles, manage role access, review pending role upgrade requests, and monitor field hierarchy.
                </p>
                <div class="p-3 bg-slate-50 rounded-xl space-y-2 mb-4 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Registered Users</span>
                        <span class="font-bold text-slate-900">{{ number_format($totalUsersCount ?? \App\Models\User::count()) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Pending Upgrades</span>
                        <span class="font-bold {{ ($pendingUpgradesCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                            {{ number_format($pendingUpgradesCount ?? \App\Models\User::where('upgrade_status', 'pending')->count()) }}
                        </span>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.manage.users') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                <span>Manage Users &amp; Upgrades</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Top Regional Performance Snapshot Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Top Performing Territories Snapshot</h2>
                <p class="text-xs text-slate-500">Quick summary of high-velocity regional sales</p>
            </div>
            <a href="{{ route('bsm.territories') }}" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                <span>View Full Territories Breakdown</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Region Name</th>
                        <th class="py-2.5 px-3">Regional Manager</th>
                        <th class="py-2.5 px-3 text-right">Allocated</th>
                        <th class="py-2.5 px-3 text-right">Activated</th>
                        <th class="py-2.5 px-3 text-right">Sales Volume</th>
                        <th class="py-2.5 px-3 text-right">Conversion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($topRegions as $tr)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-3 font-bold text-slate-900">{{ $tr['name'] }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800">{{ $tr['lead_name'] }}</td>
                            <td class="py-3 px-3 text-right font-semibold">{{ number_format($tr['allocated']) }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($tr['activated']) }}</td>
                            <td class="py-3 px-3 text-right font-semibold">₦{{ number_format($tr['sales_volume'], 0) }}</td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $tr['conversion_rate'] >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $tr['conversion_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400 text-xs">No territorial sales data recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>
