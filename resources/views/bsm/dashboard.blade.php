<x-app-layout>
<div class="space-y-6" x-data="{
    activeTab: '{{ $activeTab ?? 'overview' }}',
    showQuickActivateModal: false,
    showAssignModal: false,
    showReassignModal: false,
    showRevertModal: false,
    selectedSimId: '',
    selectedSimNumber: '',
    currentHolderName: '',
    currentHolderRole: ''
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

    <!-- Header & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary">
                        <i data-lucide="briefcase" class="w-6 h-6"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 font-display">Business Sales Manager Console</h1>
                        <p class="text-sm text-slate-500">SIM Lifecycle Governance, Jurisdictional Sales Velocity & Customer Acceleration Desk</p>
                    </div>
                </div>
            </div>

            <!-- Global Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" @click="showQuickActivateModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold shadow-sm hover:bg-primary-dark transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Quick Customer Onboard</span>
                </button>
                <a href="{{ route('bsm.reports.jurisdictions.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold shadow-sm hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                    <span>Export Territory Report</span>
                </a>
            </div>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('bsm.dashboard') }}" class="mt-6 pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <input type="hidden" name="tab" :value="activeTab">

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

            <!-- Reset / Search Filter -->
            <div class="flex items-end gap-2">
                <a href="{{ route('bsm.dashboard', ['tab' => $activeTab]) }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition">
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

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 flex items-center gap-2 overflow-x-auto pb-px">
        <button type="button" @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-primary text-primary font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 border-b-2 text-sm whitespace-nowrap transition flex items-center gap-2">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            <span>Overview & Accelerators</span>
        </button>
        <button type="button" @click="activeTab = 'jurisdictions'" :class="activeTab === 'jurisdictions' ? 'border-primary text-primary font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 border-b-2 text-sm whitespace-nowrap transition flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4 h-4"></i>
            <span>Active Territories Reports</span>
        </button>
        <button type="button" @click="activeTab = 'lifecycle'" :class="activeTab === 'lifecycle' ? 'border-primary text-primary font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 border-b-2 text-sm whitespace-nowrap transition flex items-center gap-2">
            <i data-lucide="cpu" class="w-4 h-4"></i>
            <span>SIM Lifecycle Manager</span>
        </button>
        <button type="button" @click="activeTab = 'customers'" :class="activeTab === 'customers' ? 'border-primary text-primary font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 border-b-2 text-sm whitespace-nowrap transition flex items-center gap-2">
            <i data-lucide="user-check" class="w-4 h-4"></i>
            <span>Customer Handling Desk</span>
        </button>
        <button type="button" @click="activeTab = 'acceleration'" :class="activeTab === 'acceleration' ? 'border-primary text-primary font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700'" class="px-4 py-3 border-b-2 text-sm whitespace-nowrap transition flex items-center gap-2">
            <i data-lucide="zap" class="w-4 h-4"></i>
            <span>Interventions & Requests</span>
            @if ($pendingRequests->count() > 0 || $totalDormant > 0)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">{{ $pendingRequests->count() + min(9, $totalDormant) }}</span>
            @endif
        </button>
    </div>

    <!-- TAB 1: OVERVIEW & ACCELERATORS -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Top Jurisdictions Quick Glance & Accelerators Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Active Regions Summary -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Active Regional Performance</h2>
                        <p class="text-xs text-slate-500">Live sales, allocations, and conversion across active platform regions</p>
                    </div>
                    <button type="button" @click="activeTab = 'jurisdictions'" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                        <span>View All Territories</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-y border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">Region</th>
                                <th class="py-2.5 px-3">Manager</th>
                                <th class="py-2.5 px-3 text-right">Allocated</th>
                                <th class="py-2.5 px-3 text-right">Sales</th>
                                <th class="py-2.5 px-3 text-right">Volume (₦)</th>
                                <th class="py-2.5 px-3 text-right">Conv. %</th>
                                <th class="py-2.5 px-3 text-right">Dormant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse (array_slice($regionsBreakdown, 0, 6) as $r)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $r['name'] }}</td>
                                    <td class="py-2.5 px-3 text-xs text-slate-600">
                                        <p class="font-medium text-slate-800">{{ $r['lead_name'] }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $r['lead_phone'] }}</p>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-medium text-slate-800">{{ number_format($r['allocated']) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-600">{{ number_format($r['activated']) }}</td>
                                    <td class="py-2.5 px-3 text-right font-medium text-slate-900">₦{{ number_format($r['sales_volume'], 0) }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $r['conversion_rate'] >= 50 ? 'bg-emerald-100 text-emerald-800' : ($r['conversion_rate'] >= 20 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                            {{ $r['conversion_rate'] }}%
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        @if ($r['dormant'] > 0)
                                            <span class="text-xs font-bold text-rose-600">{{ $r['dormant'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-xs text-slate-400">No active regional records available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Urgent Sales Actions & Dormancy Alert -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-bold text-slate-900 font-display">Sales Acceleration Alerts</h2>
                        <span class="p-1 rounded-md bg-amber-50 text-amber-600"><i data-lucide="alert-triangle" class="w-4 h-4"></i></span>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">Intervene on dormant inventory or approve pending partner requests to boost conversion.</p>

                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Dormant Field Stock</p>
                                <p class="text-[11px] text-slate-500">{{ $totalDormant }} SIMs stuck ≥ 7 days</p>
                            </div>
                            <button type="button" @click="activeTab = 'acceleration'" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold">
                                Reassign
                            </button>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Pending Agent Requests</p>
                                <p class="text-[11px] text-slate-500">{{ $pendingRequests->count() }} requests awaiting stock</p>
                            </div>
                            <button type="button" @click="activeTab = 'acceleration'" class="px-2.5 py-1 rounded-lg bg-primary hover:bg-primary-dark text-white text-xs font-semibold">
                                Review
                            </button>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Failed Activation Bonus</p>
                                <p class="text-[11px] text-slate-500">{{ $failedBonusReports->count() }} stuck data top-ups</p>
                            </div>
                            <button type="button" @click="activeTab = 'acceleration'" class="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold">
                                Retry
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100">
                    <button type="button" @click="showQuickActivateModal = true" class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                        <span>Onboard & Activate Customer Now</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: ACTIVE JURISDICTIONS REPORT -->
    <div x-show="activeTab === 'jurisdictions'" class="space-y-6" style="display: none;">
        <!-- Jurisdiction Performance Table -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 font-display">Territorial Performance Breakdown</h2>
                    <p class="text-xs text-slate-500">Categorized live breakdown by active Regions and Senatorial Districts</p>
                </div>
                <a href="{{ route('bsm.reports.jurisdictions.export', request()->query()) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-700">
                    <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                    <span>Export CSV</span>
                </a>
            </div>

            <!-- Regions Section -->
            <div class="mb-8">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">1. Regional Territory Overview</h3>
                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-3">Region</th>
                                <th class="py-3 px-3">Regional Manager</th>
                                <th class="py-3 px-3">Contact</th>
                                <th class="py-3 px-3 text-right">Allocated</th>
                                <th class="py-3 px-3 text-right">Activated</th>
                                <th class="py-3 px-3 text-right">Sales Volume (₦)</th>
                                <th class="py-3 px-3 text-right">Conversion %</th>
                                <th class="py-3 px-3 text-right">Pending</th>
                                <th class="py-3 px-3 text-right">Dormant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($regionsBreakdown as $reg)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-bold text-slate-900">{{ $reg['name'] }}</td>
                                    <td class="py-3 px-3 text-slate-700 font-medium">{{ $reg['lead_name'] }}</td>
                                    <td class="py-3 px-3 text-xs text-slate-500">{{ $reg['lead_phone'] }}</td>
                                    <td class="py-3 px-3 text-right font-medium text-slate-800">{{ number_format($reg['allocated']) }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($reg['activated']) }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-slate-900">₦{{ number_format($reg['sales_volume'], 2) }}</td>
                                    <td class="py-3 px-3 text-right font-semibold text-primary">{{ $reg['conversion_rate'] }}%</td>
                                    <td class="py-3 px-3 text-right text-slate-600">{{ number_format($reg['pending']) }}</td>
                                    <td class="py-3 px-3 text-right">
                                        @if ($reg['dormant'] > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">{{ $reg['dormant'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-6 text-center text-xs text-slate-400">No active regions registered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-100/80 font-bold text-xs text-slate-900 border-t border-slate-200">
                            <tr>
                                <td class="py-3 px-3" colspan="3">TERRITORY TOTALS</td>
                                <td class="py-3 px-3 text-right">{{ number_format($jurisdictionTotals['allocated']) }}</td>
                                <td class="py-3 px-3 text-right text-emerald-700">{{ number_format($jurisdictionTotals['activated']) }}</td>
                                <td class="py-3 px-3 text-right">₦{{ number_format($jurisdictionTotals['sales_volume'], 2) }}</td>
                                <td class="py-3 px-3 text-right text-primary">{{ $jurisdictionTotals['conversion_rate'] }}%</td>
                                <td class="py-3 px-3 text-right">{{ number_format($jurisdictionTotals['pending']) }}</td>
                                <td class="py-3 px-3 text-right text-rose-700">{{ number_format($jurisdictionTotals['dormant']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Senatorial Districts Section -->
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">2. Senatorial Districts Breakdown</h3>
                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-3 px-3">District</th>
                                <th class="py-3 px-3">State</th>
                                <th class="py-3 px-3">Region</th>
                                <th class="py-3 px-3">Coordinator</th>
                                <th class="py-3 px-3">Contact</th>
                                <th class="py-3 px-3 text-right">Allocated</th>
                                <th class="py-3 px-3 text-right">Activated</th>
                                <th class="py-3 px-3 text-right">Sales Volume (₦)</th>
                                <th class="py-3 px-3 text-right">Conv. %</th>
                                <th class="py-3 px-3 text-right">Pending</th>
                                <th class="py-3 px-3 text-right">Dormant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($districtsBreakdown as $dist)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-semibold text-slate-900">{{ $dist['district'] }}</td>
                                    <td class="py-3 px-3 text-xs text-slate-600">{{ $dist['state'] }}</td>
                                    <td class="py-3 px-3 text-xs text-slate-500">{{ $dist['region'] }}</td>
                                    <td class="py-3 px-3 text-xs font-medium text-slate-800">{{ $dist['coordinator_name'] }}</td>
                                    <td class="py-3 px-3 text-xs text-slate-500">{{ $dist['coordinator_phone'] }}</td>
                                    <td class="py-3 px-3 text-right font-medium text-slate-800">{{ number_format($dist['allocated']) }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($dist['activated']) }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-slate-900">₦{{ number_format($dist['sales_volume'], 2) }}</td>
                                    <td class="py-3 px-3 text-right font-semibold text-primary">{{ $dist['conversion_rate'] }}%</td>
                                    <td class="py-3 px-3 text-right text-slate-600">{{ number_format($dist['pending']) }}</td>
                                    <td class="py-3 px-3 text-right">
                                        @if ($dist['dormant'] > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">{{ $dist['dormant'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-6 text-center text-xs text-slate-400">No active districts configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: SIM LIFECYCLE MANAGER -->
    <div x-show="activeTab === 'lifecycle'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 font-display">SIM Lifecycle Controller</h2>
                    <p class="text-xs text-slate-500">Full visibility and administrative interventions across all 9 lifecycle states</p>
                </div>
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    @php
                        $statuses = [
                            'all'                         => 'All',
                            'unassigned'                  => 'Unassigned',
                            'assigned_to_rm'              => 'In RM',
                            'assigned_to_coordinator'     => 'In Coord',
                            'assigned_to_partner'         => 'In Partner',
                            'assigned_to_business'        => 'In EB',
                            'assigned_to_business_agent'  => 'In Agent',
                            'activated'                   => 'Activated',
                            'suspended'                   => 'Suspended',
                            'deactivated'                 => 'Deactivated',
                        ];
                    @endphp
                    @foreach ($statuses as $stKey => $stLabel)
                        <a href="{{ route('bsm.dashboard', array_merge(request()->query(), ['tab' => 'lifecycle', 'lifecycle_status' => $stKey])) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition {{ $lifecycleStatus === $stKey ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $stLabel }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- SIM Search Form -->
            <form method="GET" action="{{ route('bsm.dashboard') }}" class="mb-4">
                <input type="hidden" name="tab" value="lifecycle">
                <input type="hidden" name="lifecycle_status" value="{{ $lifecycleStatus }}">
                <div class="relative">
                    <input type="text" name="sim_search" value="{{ $simSearch }}" placeholder="Search by SIM number, holder name/phone, or customer details..." class="w-full text-xs rounded-xl border-slate-200 pl-9 pr-24 py-2.5 focus:border-primary focus:ring-primary bg-slate-50">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                    <button type="submit" class="absolute right-2 top-1.5 px-3 py-1 rounded-lg bg-primary text-white text-xs font-semibold">Search</button>
                </div>
            </form>

            <!-- SIMs Lifecycle Table -->
            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-3">SIM Number</th>
                            <th class="py-3 px-3">Category</th>
                            <th class="py-3 px-3">Provider</th>
                            <th class="py-3 px-3">Lifecycle Status</th>
                            <th class="py-3 px-3">Current Holder</th>
                            <th class="py-3 px-3">Customer (if activated)</th>
                            <th class="py-3 px-3">Dormancy</th>
                            <th class="py-3 px-3 text-right">Lifecycle Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($lifecycleSims as $sim)
                            @php
                                $daysHeld = $sim->daysHeld();
                                $isDormant = $sim->status !== \App\Support\SimStatus::ACTIVATED && $sim->status !== \App\Support\SimStatus::UNASSIGNED && $daysHeld >= 7;
                                $holder = match ($sim->status) {
                                    \App\Support\SimStatus::ASSIGNED_TO_RM => $sim->regionalManager,
                                    \App\Support\SimStatus::ASSIGNED_TO_COORDINATOR => $sim->coordinator,
                                    \App\Support\SimStatus::ASSIGNED_TO_BUSINESS => $sim->business,
                                    \App\Support\SimStatus::ASSIGNED_TO_BUSINESS_AGENT => $sim->businessAgent,
                                    default => $sim->user ?? $sim->partner,
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $sim->number }}</td>
                                <td class="py-3 px-3 text-xs text-slate-700">{{ $sim->category }}</td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                        {{ $sim->provider }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    @php
                                        $badgeColor = match ($sim->status) {
                                            \App\Support\SimStatus::ACTIVATED => 'bg-emerald-100 text-emerald-800',
                                            \App\Support\SimStatus::UNASSIGNED => 'bg-slate-100 text-slate-700',
                                            \App\Support\SimStatus::SUSPENDED => 'bg-amber-100 text-amber-800',
                                            \App\Support\SimStatus::DEACTIVATED => 'bg-rose-100 text-rose-800',
                                            default => 'bg-blue-100 text-blue-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $sim->status)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-700">
                                    @if ($holder)
                                        <p class="font-semibold text-slate-900">{{ $holder->name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $holder->role)) }} &bull; {{ $holder->phone }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-700">
                                    @if ($sim->customer)
                                        <p class="font-medium text-slate-900">{{ $sim->customer->full_name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $sim->customer->phone_number }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    @if ($isDormant)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <span>{{ $daysHeld }}d idle</span>
                                        </span>
                                    @elseif ($sim->status === \App\Support\SimStatus::ACTIVATED)
                                        <span class="text-xs text-emerald-600 font-semibold">Active</span>
                                    @else
                                        <span class="text-xs text-slate-400">{{ $daysHeld }}d</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($sim->status === \App\Support\SimStatus::UNASSIGNED)
                                            <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; showAssignModal = true" class="px-2 py-1 rounded-lg bg-primary hover:bg-primary-dark text-white text-xs font-semibold">
                                                Assign
                                            </button>
                                        @elseif (in_array($sim->status, [\App\Support\SimStatus::ASSIGNED_TO_COORDINATOR, \App\Support\SimStatus::ASSIGNED_TO_PARTNER, \App\Support\SimStatus::ASSIGNED_TO_BUSINESS], true))
                                            <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; currentHolderName = '{{ $holder?->name }}'; currentHolderRole = '{{ $holder?->role }}'; showReassignModal = true" class="px-2 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">
                                                Swap
                                            </button>
                                            @if ($sim->user_id && $sim->user_id !== $sim->partner_id && $sim->user_id !== $sim->business_agent_id)
                                                <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; showRevertModal = true" class="px-2 py-1 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white text-xs font-semibold">
                                                    Revert
                                                </button>
                                            @endif
                                        @endif

                                        @if ($sim->status !== \App\Support\SimStatus::ACTIVATED)
                                            <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; showQuickActivateModal = true" class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">
                                                Activate
                                            </button>
                                        @endif

                                        @if ($sim->status === \App\Support\SimStatus::ACTIVATED)
                                            <form method="POST" action="{{ route('bsm.lifecycle.status', $sim) }}" onsubmit="return confirm('Suspend this active SIM?');">
                                                @csrf
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">Suspend</button>
                                            </form>
                                        @elseif ($sim->status === \App\Support\SimStatus::SUSPENDED)
                                            <form method="POST" action="{{ route('bsm.lifecycle.status', $sim) }}">
                                                @csrf
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="px-2 py-1 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-semibold">Reactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-xs text-slate-400">No SIM records matching selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $lifecycleSims->links() }}
            </div>
        </div>
    </div>

    <!-- TAB 4: CUSTOMER HANDLING DESK -->
    <div x-show="activeTab === 'customers'" class="space-y-6" style="display: none;">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 font-display">Customer Handling Desk</h2>
                    <p class="text-xs text-slate-500">Live search and instant access to customer onboarding records and SIM mappings</p>
                </div>
                <button type="button" @click="showQuickActivateModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Quick Onboard Customer</span>
                </button>
            </div>

            <!-- Customer Search Form -->
            <form method="GET" action="{{ route('bsm.dashboard') }}" class="mb-4">
                <input type="hidden" name="tab" value="customers">
                <div class="relative">
                    <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Search customer by name, phone number, email, or linked SIM number..." class="w-full text-xs rounded-xl border-slate-200 pl-9 pr-24 py-2.5 focus:border-primary focus:ring-primary bg-slate-50">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                    <button type="submit" class="absolute right-2 top-1.5 px-3 py-1 rounded-lg bg-primary text-white text-xs font-semibold">Search</button>
                </div>
            </form>

            <!-- Customer Table -->
            <div class="overflow-x-auto border border-slate-100 rounded-xl">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-3">Customer Name</th>
                            <th class="py-3 px-3">Phone</th>
                            <th class="py-3 px-3">Email</th>
                            <th class="py-3 px-3">Address</th>
                            <th class="py-3 px-3">Linked SIM</th>
                            <th class="py-3 px-3">Category / Provider</th>
                            <th class="py-3 px-3">Activating Partner</th>
                            <th class="py-3 px-3">Onboarded Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($customers as $cust)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-3 font-bold text-slate-900">{{ $cust->full_name }}</td>
                                <td class="py-3 px-3 text-xs font-mono font-medium text-slate-800">{{ $cust->phone_number }}</td>
                                <td class="py-3 px-3 text-xs text-slate-600">{{ $cust->email ?? '—' }}</td>
                                <td class="py-3 px-3 text-xs text-slate-500 max-w-xs truncate">{{ $cust->address ?? '—' }}</td>
                                <td class="py-3 px-3 font-mono font-semibold text-primary">
                                    {{ $cust->sim?->number ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-700">
                                    {{ $cust->sim?->category ?? '—' }} &bull; {{ strtoupper($cust->sim?->provider ?? '—') }}
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-700">
                                    <p class="font-medium text-slate-900">{{ $cust->partner?->name ?? 'BSM Desk' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $cust->partner?->phone ?? '—' }}</p>
                                </td>
                                <td class="py-3 px-3 text-xs text-slate-500">
                                    {{ $cust->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-xs text-slate-400">No customer records matching search criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        </div>
    </div>

    <!-- TAB 5: SALES ACCELERATION & INTERVENTIONS -->
    <div x-show="activeTab === 'acceleration'" class="space-y-6" style="display: none;">
        <!-- Grid of Accelerators: Dormant Stock, Pending Requests, Failed Activations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 1. Dormant Stock Alert Table -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Stalled Field Stock (≥ 7 Days)</h2>
                        <p class="text-xs text-slate-500">Unactivated SIMs sitting with partners/agents — swap or recall to accelerate turnover</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                        {{ $dormantSims->count() }} stuck
                    </span>
                </div>

                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">SIM</th>
                                <th class="py-2.5 px-3">Current Holder</th>
                                <th class="py-2.5 px-3">Idle</th>
                                <th class="py-2.5 px-3 text-right">Intervention</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($dormantSims as $dSim)
                                @php
                                    $dHolder = $dSim->user ?? $dSim->partner ?? $dSim->coordinator ?? $dSim->regionalManager;
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 font-mono font-semibold text-slate-900 text-xs">{{ $dSim->number }}</td>
                                    <td class="py-2.5 px-3 text-xs text-slate-700">
                                        <p class="font-medium text-slate-900">{{ $dHolder?->name ?? 'Unknown' }}</p>
                                        <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $dHolder?->role ?? '')) }} &bull; {{ $dHolder?->phone ?? '—' }}</p>
                                    </td>
                                    <td class="py-2.5 px-3 text-xs font-bold text-rose-600">
                                        {{ $dSim->daysHeld() }} days
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <button type="button" @click="selectedSimId = '{{ $dSim->id }}'; selectedSimNumber = '{{ $dSim->number }}'; currentHolderName = '{{ $dHolder?->name }}'; currentHolderRole = '{{ $dHolder?->role }}'; showReassignModal = true" class="px-2 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">
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
            </div>

            <!-- 2. Pending Stock Requests Table -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Incoming Partner Stock Requests</h2>
                        <p class="text-xs text-slate-500">Approve pending orders to unblock field sales and fulfill agent demand</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary">
                        {{ $pendingRequests->count() }} pending
                    </span>
                </div>

                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">Requester</th>
                                <th class="py-2.5 px-3">Category</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pendingRequests as $req)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 text-xs text-slate-700">
                                        <p class="font-medium text-slate-900">{{ $req->user?->name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $req->user?->role ?? '')) }} &bull; {{ $req->user?->phone }}</p>
                                    </td>
                                    <td class="py-2.5 px-3 text-xs text-slate-700">{{ $req->category }} ({{ strtoupper($req->provider) }})</td>
                                    <td class="py-2.5 px-3 text-xs text-center font-bold text-slate-900">{{ $req->quantity }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        <form method="POST" action="{{ route('bsm.requests.approve', $req) }}" onsubmit="return confirm('Approve and dispatch stock for this request?');">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-primary hover:bg-primary-dark text-white text-xs font-semibold">
                                                Approve
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-xs text-slate-400">All partner purchase requests are satisfied.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Failed Activation Bonus Recovery -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Activation Bonus Recovery Desk</h2>
                        <p class="text-xs text-slate-500">Resolve stuck customer data bundle deliveries to maintain customer satisfaction and sales trust</p>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="py-2.5 px-3">Customer Phone</th>
                                <th class="py-2.5 px-3">Plan / Description</th>
                                <th class="py-2.5 px-3">Failure Reason</th>
                                <th class="py-2.5 px-3">Timestamp</th>
                                <th class="py-2.5 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($failedBonusReports as $rep)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3 font-mono font-medium text-xs text-slate-900">{{ $rep->phone_number }}</td>
                                    <td class="py-2.5 px-3 text-xs text-slate-700">{{ $rep->description ?? 'Activation bonus data bundle' }}</td>
                                    <td class="py-2.5 px-3 text-xs text-rose-600 max-w-xs truncate">{{ $rep->failure_reason ?? 'Provider API timeout' }}</td>
                                    <td class="py-2.5 px-3 text-xs text-slate-400">{{ $rep->created_at->format('M d, H:i') }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        <form method="POST" action="{{ route('bsm.failed-activations.retry', $rep) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">
                                                Retry Top-Up
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-xs text-slate-400">All customer activation bonuses are successfully delivered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: Quick Customer Onboarding & Activation -->
    <div x-show="showQuickActivateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showQuickActivateModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600"><i data-lucide="zap" class="w-5 h-5"></i></span>
                        <h3 class="text-base font-bold text-slate-900 font-display">Expedited Customer Activation</h3>
                    </div>
                    <button type="button" @click="showQuickActivateModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('bsm.customers.quick-activate') }}" class="mt-4 space-y-4">
                    @csrf
                    <!-- SIM Select -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Select SIM Card to Activate *</label>
                        <select name="sim_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                            <option value="">-- Choose SIM --</option>
                            @foreach ($availableUnassignedSims as $unSim)
                                <option value="{{ $unSim->id }}" :selected="selectedSimId == '{{ $unSim->id }}'">
                                    {{ $unSim->number }} &bull; {{ $unSim->category }} ({{ strtoupper($unSim->provider) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer Full Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Full Name *</label>
                        <input type="text" name="full_name" required placeholder="e.g. Chukwuma Adeleke" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                    </div>

                    <!-- Customer Phone -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Phone Number *</label>
                        <input type="text" name="phone_number" required placeholder="08012345678" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                    </div>

                    <!-- Customer Email -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address (Optional)</label>
                        <input type="email" name="email" placeholder="customer@example.com" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                    </div>

                    <!-- Customer Address -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Residential / Business Address</label>
                        <textarea name="address" rows="2" placeholder="Street address, City, State" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showQuickActivateModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm">Activate SIM Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Assign Unassigned SIM -->
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showAssignModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 font-display">Assign SIM Card</h3>
                    <button type="button" @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('bsm.lifecycle.assign') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="sim_id" :value="selectedSimId">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Target SIM Number:</p>
                        <p class="text-sm font-mono font-bold text-slate-900" x-text="selectedSimNumber"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Assign to Lead / Agent *</label>
                        <select name="target_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                            <option value="">-- Choose Target Account --</option>
                            <optgroup label="Regional Managers">
                                @foreach ($regionalManagers as $rm)
                                    <option value="{{ $rm->id }}">{{ $rm->name }} ({{ $rm->phone }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Coordinators">
                                @foreach ($coordinators as $coord)
                                    <option value="{{ $coord->id }}">{{ $coord->name }} ({{ $coord->phone }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Partners">
                                @foreach ($partners as $part)
                                    <option value="{{ $part->id }}">{{ $part->name }} ({{ $part->phone }})</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold">Assign SIM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Lateral Swap / Reassign Stuck SIM -->
    <div x-show="showReassignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showReassignModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 font-display">Lateral Swap / Reassign SIM</h3>
                    <button type="button" @click="showReassignModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('bsm.lifecycle.reassign') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="sim_id" :value="selectedSimId">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Target SIM Number:</p>
                        <p class="text-sm font-mono font-bold text-slate-900" x-text="selectedSimNumber"></p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-500 mb-1">Current Holder:</p>
                        <p class="text-xs font-semibold text-slate-800" x-text="currentHolderName + ' (' + currentHolderRole + ')'"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Swap to New Active Holder *</label>
                        <select name="new_holder_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                            <option value="">-- Choose New Holder --</option>
                            <optgroup label="Coordinators">
                                @foreach ($coordinators as $coord)
                                    <option value="{{ $coord->id }}">{{ $coord->name }} ({{ $coord->phone }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Partners">
                                @foreach ($partners as $part)
                                    <option value="{{ $part->id }}">{{ $part->name }} ({{ $part->phone }})</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showReassignModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold">Execute Swap</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 4: Revert Stuck Delegated SIM -->
    <div x-show="showRevertModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showRevertModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 font-display">Revert Stuck Delegated SIM</h3>
                    <button type="button" @click="showRevertModal = false" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('bsm.lifecycle.revert') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="sim_id" :value="selectedSimId">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Target SIM Number:</p>
                        <p class="text-sm font-mono font-bold text-slate-900" x-text="selectedSimNumber"></p>
                    </div>
                    <p class="text-xs text-slate-500">This pulls the SIM back to its partner/agent holder stock so they can re-delegate or activate it cleanly.</p>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Reason / Note</label>
                        <input type="text" name="note" value="Reverted by Business Sales Manager to unblock stuck distribution" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2.5 px-3">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showRevertModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Revert to Holder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
