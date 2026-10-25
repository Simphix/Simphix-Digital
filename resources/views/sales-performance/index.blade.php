<x-app-layout>
    <title>SimPhix - Sales Performance & Territory Breakdown</title>

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Page Header & Action Toolbar -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="trending-up" class="w-5 h-5"></i>
                    </div>
                    Sales Performance
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $isPersonal ? 'Your direct SIM sales, activations, and category summary.' : 'Comprehensive summary and territory breakdown of sales by you and your downline.' }}
                </p>
            </div>

            <!-- Export Report Button -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('sales-performance.export', request()->all()) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm hover:shadow">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Export CSV Report
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <form action="{{ route('sales-performance.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <!-- Date Range -->
                <div>
                    <label for="filter_range" class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[11px]">Period</label>
                    <select id="filter_range" name="range" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="7days" {{ $range === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="30days" {{ $range === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ $range === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="all" {{ $range === 'all' ? 'selected' : '' }}>All Time</option>
                    </select>
                </div>

                <!-- SIM Category -->
                <div>
                    <label for="filter_cat" class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[11px]">Category</label>
                    <select id="filter_cat" name="category" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Provider -->
                <div>
                    <label for="filter_prov" class="block font-bold text-slate-600 uppercase tracking-wider mb-1 text-[11px]">Network</label>
                    <select id="filter_prov" name="provider" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="">All Networks</option>
                        @foreach ($providers as $prov)
                            <option value="{{ $prov }}" {{ $selectedProvider === $prov ? 'selected' : '' }}>{{ strtoupper($prov) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-2 px-4 rounded-xl bg-primary text-white font-bold hover:bg-primary/90 transition shadow-sm text-center">
                        Apply Filters
                    </button>
                    @if (request()->hasAny(['range', 'category', 'provider', 'period_start']))
                        <a href="{{ route('sales-performance.index') }}" class="py-2 px-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 font-semibold transition text-center" title="Reset filters">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- SECTION 1: OVERVIEW SUMMARY -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold font-display text-slate-800 flex items-center gap-2">
                    <i data-lucide="layout-grid" class="w-5 h-5 text-primary"></i>
                    Executive Overview
                </h2>
                <span class="text-xs font-semibold text-slate-400 bg-white px-3 py-1 rounded-full border border-slate-200">
                    {{ $rangeLabel }}
                </span>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Sales (Activations) -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Activated Sales</span>
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold text-slate-800 font-display">{{ $activatedSalesCount }}</span>
                        <span class="text-xs font-semibold text-slate-400">/ {{ $totalAllocatedSims }} allocated</span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Conversion Rate</span>
                        <span class="font-bold text-primary">{{ $overallConversionRate }}%</span>
                    </div>
                </div>

                <!-- Card 2: Total Sales Volume -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sales Volume</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="banknote" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <span class="text-2xl font-extrabold text-slate-800 font-display">₦{{ number_format($totalSalesVolume, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Average / Sale</span>
                        <span class="font-bold text-slate-700">
                            ₦{{ $activatedSalesCount > 0 ? number_format($totalSalesVolume / $activatedSalesCount, 2) : '0.00' }}
                        </span>
                    </div>
                </div>

                <!-- Card 3: Direct vs Downline Breakdown -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sales Distribution</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Direct Sales:</span>
                            <span class="font-bold text-slate-800">{{ $directSalesCount }} <span class="text-[11px] font-normal text-slate-400">(₦{{ number_format($directSalesVolume, 0) }})</span></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Downline Sales:</span>
                            <span class="font-bold text-indigo-700">{{ $downlineSalesCount }} <span class="text-[11px] font-normal text-slate-400">(₦{{ number_format($downlineSalesVolume, 0) }})</span></span>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden flex">
                        @php
                            $directShare = $activatedSalesCount > 0 ? round(($directSalesCount / $activatedSalesCount) * 100) : 0;
                        @endphp
                        <div class="bg-primary h-full" style="width: {{ $directShare }}%"></div>
                        <div class="bg-indigo-500 h-full" style="width: {{ 100 - $directShare }}%"></div>
                    </div>
                </div>

                <!-- Card 4: Commissions & Dormancy -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Commissions Earned</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i data-lucide="badge-percent" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <span class="text-2xl font-extrabold text-slate-800 font-display">₦{{ number_format($commissionThisPeriod, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                        <span class="text-slate-500 font-medium">Dormant Stock (7+ days):</span>
                        <span class="font-bold {{ $dormantCount > 0 ? 'text-rose-600' : 'text-slate-700' }}">{{ $dormantCount }} SIMs</span>
                    </div>
                </div>
            </div>

            <!-- Category & Provider Breakdown Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Category Breakdown -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm lg:col-span-2 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i data-lucide="cpu" class="w-4 h-4 text-primary"></i>
                        Sales Breakdown by Category
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @forelse ($categoryBreakdown as $cat)
                            <div class="bg-slate-50/80 rounded-xl p-3 border border-slate-100 space-y-1">
                                <span class="text-[11px] font-bold text-slate-700 block truncate">{{ $cat['category'] }}</span>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-base font-extrabold text-slate-800 font-display">{{ $cat['activated'] }}</span>
                                    <span class="text-[11px] font-semibold text-primary">{{ $cat['conversion_rate'] }}%</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block">₦{{ $cat['formatted_volume'] }}</span>
                            </div>
                        @empty
                            <div class="col-span-full py-4 text-center text-slate-400 text-xs font-medium">
                                No category sales recorded in this period.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Network Provider Share -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i data-lucide="radio" class="w-4 h-4 text-primary"></i>
                        Network Provider Sales
                    </h3>
                    <div class="space-y-2 text-xs">
                        @forelse ($providerBreakdown as $prov)
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="font-bold text-slate-700">{{ $prov['provider'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-slate-800 font-display">{{ $prov['activated'] }} sales</span>
                                    <span class="text-[11px] text-slate-400 font-normal">({{ $prov['allocated'] }} alloc)</span>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-slate-400 text-xs font-medium">
                                No network data in this period.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Weekly Commission Trend Chart -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 text-primary"></i>
                        Weekly Commission Trend (Last 6 Weeks)
                    </h3>
                    <span class="text-xs font-bold text-emerald-600">
                        @if ($commissionTrendPct > 0)
                            +{{ $commissionTrendPct }}% vs prior week
                        @elseif ($commissionTrendPct < 0)
                            {{ $commissionTrendPct }}% vs prior week
                        @else
                            Stable vs prior week
                        @endif
                    </span>
                </div>

                @php
                    $maxVal = 100;
                    foreach ($weeklyTrend as $week) {
                        if ($week['amount'] > $maxVal) $maxVal = $week['amount'];
                    }
                @endphp

                <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-3">
                    <div class="relative w-full" style="height: 120px;">
                        <svg class="w-full h-full" viewBox="0 0 350 120" preserveAspectRatio="none">
                            <line x1="0" y1="30" x2="350" y2="30" stroke="#e2e8f0" stroke-width="1" />
                            <line x1="0" y1="60" x2="350" y2="60" stroke="#e2e8f0" stroke-width="1" />
                            <line x1="0" y1="90" x2="350" y2="90" stroke="#e2e8f0" stroke-width="1" />

                            @foreach ($weeklyTrend as $index => $week)
                                @php
                                    $colWidth = 58;
                                    $startX = ($index * $colWidth) + 12;
                                    $barHeight = ($week['amount'] / $maxVal) * 85;
                                    if ($week['amount'] > 0 && $barHeight < 4) $barHeight = 4;
                                    $barY = 95 - $barHeight;
                                @endphp
                                <rect x="{{ $startX }}" y="{{ $barY }}" width="30" height="{{ $barHeight }}" rx="4" fill="#0056D2" />
                                <text x="{{ $startX + 15 }}" y="112" text-anchor="middle" font-size="9" fill="#94a3b8" font-weight="600">{{ $week['label'] }}</text>
                            @endforeach
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: BREAKDOWN BY TERRITORIES TABLE -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold font-display text-slate-800 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-5 h-5 text-primary"></i>
                        Sales Breakdown by Territory & Downline
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Performance and sales metrics for geographic territories and downline personnel under your administration
                    </p>
                </div>
                <div class="text-xs text-slate-500 font-semibold bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200/60 w-fit">
                    {{ count($jurisdictionRows) }} Territories
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-2">Territory</th>
                            <th class="py-3 px-2">Level / Role</th>
                            <th class="py-3 px-2">Assigned Officer</th>
                            <th class="py-3 px-2 text-center">Allocated</th>
                            <th class="py-3 px-2 text-center">Activated Sales</th>
                            <th class="py-3 px-2 text-right">Sales Volume</th>
                            <th class="py-3 px-2 text-center">Conversion</th>
                            <th class="py-3 px-2 text-center">Pending</th>
                            <th class="py-3 px-2 text-center">Dormant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($jurisdictionRows as $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-2">
                                    <div class="font-bold text-slate-800 text-xs">{{ $row['jurisdiction'] }}</div>
                                </td>
                                <td class="py-3.5 px-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $row['type'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-2">
                                    <span class="font-semibold text-slate-700 block">{{ $row['lead_name'] }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $row['lead_phone'] }}</span>
                                </td>
                                <td class="py-3.5 px-2 text-center font-semibold text-slate-600">
                                    {{ $row['allocated'] }}
                                </td>
                                <td class="py-3.5 px-2 text-center font-extrabold text-slate-800">
                                    <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full font-bold">
                                        {{ $row['activated'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-2 text-right font-bold text-slate-800 font-display">
                                    ₦{{ number_format($row['sales_volume'], 2) }}
                                </td>
                                <td class="py-3.5 px-2 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-12 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-primary h-full rounded-full" style="width: {{ min(100, $row['conversion_rate']) }}%"></div>
                                        </div>
                                        <span class="font-bold text-[11px] text-slate-700">{{ $row['conversion_rate'] }}%</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-2 text-center text-slate-500 font-medium">
                                    {{ $row['pending'] }}
                                </td>
                                <td class="py-3.5 px-2 text-center">
                                    @if ($row['dormant'] > 0)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                            {{ $row['dormant'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-medium">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400 font-medium">
                                    No territory records found under your scope.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!-- Totals Footer Row -->
                    @if (count($jurisdictionRows) > 0)
                        <tfoot>
                            <tr class="bg-slate-50/90 border-t-2 border-slate-200 font-bold text-slate-800">
                                <td class="py-3 px-2 uppercase tracking-wider text-[11px]">Total Summary</td>
                                <td class="py-3 px-2 text-slate-400 font-normal">—</td>
                                <td class="py-3 px-2 text-slate-400 font-normal">—</td>
                                <td class="py-3 px-2 text-center font-extrabold text-slate-800">{{ $jurisdictionTotals['allocated'] }}</td>
                                <td class="py-3 px-2 text-center font-extrabold text-emerald-700">{{ $jurisdictionTotals['activated'] }}</td>
                                <td class="py-3 px-2 text-right font-extrabold font-display text-slate-900">₦{{ number_format($jurisdictionTotals['sales_volume'], 2) }}</td>
                                <td class="py-3 px-2 text-center font-extrabold text-primary">{{ $jurisdictionTotals['conversion_rate'] }}%</td>
                                <td class="py-3 px-2 text-center text-slate-600">{{ $jurisdictionTotals['pending'] }}</td>
                                <td class="py-3 px-2 text-center {{ $jurisdictionTotals['dormant'] > 0 ? 'text-rose-600 font-extrabold' : 'text-slate-500' }}">
                                    {{ $jurisdictionTotals['dormant'] }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- SECTION 3: DETAILED SALES LOG & UNACTIVATED STOCK -->
        <div x-data="{ tab: 'sales' }" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
            <!-- Tabs Navigation -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="tab = 'sales'"
                            :class="tab === 'sales' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        Activated Sales Log ({{ $activatedSalesCount }})
                    </button>
                    <button type="button"
                            @click="tab = 'unactivated'"
                            :class="tab === 'unactivated' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        Pending / Dormant Stock ({{ $pendingCount }})
                    </button>
                </div>

                <a href="{{ route('sales-performance.export', request()->all()) }}"
                   class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export full list
                </a>
            </div>

            <!-- Tab 1: Activated Sales Log -->
            <div x-show="tab === 'sales'" class="space-y-3">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[11px]">
                                <th class="py-2.5 px-2">SIM Number</th>
                                <th class="py-2.5 px-2">Category</th>
                                <th class="py-2.5 px-2">Network</th>
                                <th class="py-2.5 px-2">Customer</th>
                                <th class="py-2.5 px-2">Activated By</th>
                                <th class="py-2.5 px-2">Territory</th>
                                <th class="py-2.5 px-2">Date</th>
                                <th class="py-2.5 px-2 text-right">Sale Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($activatedSimsList as $sim)
                                @php
                                    $price = $priceMap[$sim->category] ?? 1000.00;
                                    $custName = $sim->customer?->full_name ?? ($sim->user && $sim->user_id !== $sim->partner_id ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : '—');
                                    $custPhone = $sim->customer?->phone_number ?? ($sim->user && $sim->user_id !== $sim->partner_id ? $sim->user->phone : '');
                                    $sellerName = $sim->user ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : 'Direct';
                                    $sellerRole = $sim->user ? ucfirst(str_replace('_', ' ', $sim->user->role)) : ucfirst(str_replace('_', ' ', $user->role));
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-2 font-bold font-mono text-slate-800">{{ $sim->number }}</td>
                                    <td class="py-3 px-2 font-semibold text-slate-700">{{ $sim->category }}</td>
                                    <td class="py-3 px-2 uppercase font-bold text-slate-500">{{ $sim->provider }}</td>
                                    <td class="py-3 px-2">
                                        <span class="font-semibold text-slate-800 block">{{ $custName }}</span>
                                        @if ($custPhone)
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $custPhone }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2">
                                        <span class="font-medium text-slate-700 block">{{ $sellerName }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $sellerRole }}</span>
                                    </td>
                                    <td class="py-3 px-2 text-slate-600">
                                        @if ($sim->coordinator && $sim->coordinator->districtAsCoordinator)
                                            {{ $sim->coordinator->districtAsCoordinator->name }}
                                        @elseif ($sim->regionalManager && $sim->regionalManager->regionAsManager)
                                            {{ $sim->regionalManager->regionAsManager->name }}
                                        @elseif ($sim->partner && $sim->partner->lga)
                                            {{ $sim->partner->lga }}
                                        @else
                                            HQ / Direct
                                        @endif
                                    </td>
                                    <td class="py-3 px-2 text-slate-500">
                                        {{ $sim->customer?->created_at?->format('M d, Y') ?? $sim->updated_at?->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-2 text-right font-bold text-slate-800 font-display">
                                        ₦{{ number_format($price, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-400 font-semibold">
                                        No activated sales in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Pending / Dormant Stock -->
            <div x-show="tab === 'unactivated'" class="space-y-3" style="display: none;">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase text-[11px]">
                                <th class="py-2.5 px-2">SIM Number</th>
                                <th class="py-2.5 px-2">Category</th>
                                <th class="py-2.5 px-2">Network</th>
                                @unless ($isPersonal)
                                    <th class="py-2.5 px-2">Currently Held By</th>
                                @endunless
                                <th class="py-2.5 px-2">Days Held</th>
                                <th class="py-2.5 px-2 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($unactivatedSims as $row)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-2 font-bold font-mono text-slate-800">{{ $row['sim']->number }}</td>
                                    <td class="py-3 px-2 font-semibold text-slate-700">{{ $row['sim']->category }}</td>
                                    <td class="py-3 px-2 uppercase font-bold text-slate-500">{{ $row['sim']->provider }}</td>
                                    @unless ($isPersonal)
                                        <td class="py-3 px-2 text-slate-700">
                                            {{ $row['holder'] ? trim($row['holder']->first_name . ' ' . $row['holder']->last_name) : 'In Hand' }}
                                        </td>
                                    @endunless
                                    <td class="py-3 px-2 text-slate-500 font-medium">{{ $row['days_held'] }} days</td>
                                    <td class="py-3 px-2 text-right">
                                        @if ($row['is_dormant'])
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-600 border border-rose-100">
                                                Dormant (7+ days)
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-primary/10 text-primary">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isPersonal ? 5 : 6 }}" class="py-8 text-center text-slate-400 font-semibold">
                                        All SIMs in your stock and downline are activated.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
