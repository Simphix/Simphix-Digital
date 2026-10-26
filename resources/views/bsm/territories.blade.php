<x-app-layout>
<div class="space-y-6" x-data="{
    regionSearch: '',
    districtSearch: '',
    drilldownSearch: '',

    // Deep drilldown state
    activeDrilldown: false,
    currentLevel: null,
    currentEntity: null,
    items: [],
    breadcrumbs: [],
    loading: false,
    error: '',

    async drilldown(level, id, label) {
        if (!id) return;
        this.loading = true;
        this.error = '';
        this.activeDrilldown = true;
        this.drilldownSearch = '';

        // Manage breadcrumb trail
        const existingIdx = this.breadcrumbs.findIndex(b => b.level === level && b.id === id);
        if (existingIdx !== -1) {
            this.breadcrumbs = this.breadcrumbs.slice(0, existingIdx + 1);
        } else {
            if (level === 'region') {
                this.breadcrumbs = [{ level: 'region', id: id, label: label }];
            } else if (level === 'coordinator' || level === 'district') {
                const regCrumb = this.breadcrumbs.find(b => b.level === 'region');
                this.breadcrumbs = regCrumb 
                    ? [regCrumb, { level: 'coordinator', id: id, label: label }]
                    : [{ level: 'coordinator', id: id, label: label }];
            } else if (level === 'partner') {
                const regCrumb = this.breadcrumbs.find(b => b.level === 'region');
                const coordCrumb = this.breadcrumbs.find(b => b.level === 'coordinator');
                this.breadcrumbs = [
                    ...(regCrumb ? [regCrumb] : []),
                    ...(coordCrumb ? [coordCrumb] : []),
                    { level: 'partner', id: id, label: label }
                ];
            } else if (level === 'agent') {
                const regCrumb = this.breadcrumbs.find(b => b.level === 'region');
                const coordCrumb = this.breadcrumbs.find(b => b.level === 'coordinator');
                const partnerCrumb = this.breadcrumbs.find(b => b.level === 'partner');
                this.breadcrumbs = [
                    ...(regCrumb ? [regCrumb] : []),
                    ...(coordCrumb ? [coordCrumb] : []),
                    ...(partnerCrumb ? [partnerCrumb] : []),
                    { level: 'agent', id: id, label: label }
                ];
            }
        }

        try {
            const params = new URLSearchParams({
                level: level,
                id: id,
                range: '{{ $range }}',
                category: '{{ $selectedCategory }}',
                provider: '{{ $selectedProvider }}'
            });

            const res = await fetch('{{ route('bsm.territories.drilldown') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                this.currentLevel = data.level;
                this.currentEntity = data.entity;
                this.items = data.items;
            } else {
                this.error = data.message || 'Could not load downline breakdown.';
            }
        } catch (e) {
            this.error = 'Network error loading downline details.';
        } finally {
            this.loading = false;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
                const el = document.getElementById('deep-drilldown-desk');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    },

    closeDrilldown() {
        this.activeDrilldown = false;
        this.currentLevel = null;
        this.currentEntity = null;
        this.items = [];
        this.breadcrumbs = [];
    }
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

    <!-- Top Shared Navigation -->
    @include('bsm.partials.nav')

    <!-- Header & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary">
                        <i data-lucide="map-pin" class="w-6 h-6"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 font-display">Territories & Downline Report</h1>
                        <p class="text-sm text-slate-500">Regional & Senatorial District Sales Performance, Stock Allocation & Conversion Metrics</p>
                    </div>
                </div>
            </div>

            <!-- Global Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('bsm.reports.jurisdictions.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold shadow-sm hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                    <span>Export Territory CSV</span>
                </a>
            </div>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('bsm.territories') }}" class="mt-6 pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
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
                <a href="{{ route('bsm.territories') }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset Filters</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Overview Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-3.5 bg-white rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Allocated SIMs</span>
            <p class="text-xl font-bold text-slate-900 mt-1">{{ number_format($jurisdictionTotals['allocated']) }}</p>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Activated Sales</span>
            <p class="text-xl font-bold text-emerald-600 mt-1">{{ number_format($jurisdictionTotals['activated']) }}</p>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Sales Volume</span>
            <p class="text-xl font-bold text-slate-900 mt-1">₦{{ number_format($jurisdictionTotals['sales_volume'], 0) }}</p>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Conversion</span>
            <p class="text-xl font-bold text-violet-600 mt-1">{{ $jurisdictionTotals['conversion_rate'] }}%</p>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Pending Stock</span>
            <p class="text-xl font-bold text-slate-700 mt-1">{{ number_format($jurisdictionTotals['pending']) }}</p>
        </div>
        <div class="p-3.5 bg-white rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase">Dormant Stock</span>
            <p class="text-xl font-bold {{ $jurisdictionTotals['dormant'] > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">{{ number_format($jurisdictionTotals['dormant']) }}</p>
        </div>
    </div>

    <!-- Deep Territory Drilldown & Downline Explorer Panel -->
    <div id="deep-drilldown-desk" x-show="activeDrilldown" x-cloak class="bg-white rounded-2xl border-2 border-primary/20 p-6 shadow-md space-y-5 transition-all">
        <!-- Explorer Header & Breadcrumbs -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="p-1.5 rounded-lg bg-primary/10 text-primary">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </span>
                    <h2 class="text-lg font-bold text-slate-900 font-display">Deep Downline &amp; Hierarchy Explorer</h2>
                    <span class="text-[11px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-primary/10 text-primary" x-text="currentLevel ? currentLevel.toUpperCase() + ' LEVEL' : ''"></span>
                </div>

                <!-- Breadcrumb navigation -->
                <nav class="flex flex-wrap items-center gap-1.5 text-xs">
                    <button type="button" @click="closeDrilldown()" class="font-medium text-slate-500 hover:text-primary transition flex items-center gap-1">
                        <i data-lucide="home" class="w-3.5 h-3.5"></i>
                        <span>Territories</span>
                    </button>

                    <template x-for="(b, idx) in breadcrumbs" :key="idx">
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                            <button type="button" @click="drilldown(b.level, b.id, b.label)"
                                    :class="idx === breadcrumbs.length - 1 ? 'font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-lg' : 'font-medium text-slate-600 hover:text-primary hover:underline'"
                                    x-text="b.label">
                            </button>
                        </div>
                    </template>
                </nav>
            </div>

            <!-- Controls: Search & Close -->
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <input type="text" x-model="drilldownSearch" placeholder="Filter this downline level..." class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary pl-8 pr-3 py-1.5 bg-slate-50">
                    <div class="absolute left-2.5 top-2 text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </div>
                </div>
                <button type="button" @click="closeDrilldown()" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 hover:text-slate-700 transition" title="Close Explorer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Entity Snapshot Banner -->
        <template x-if="currentEntity">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div>
                    <span class="text-slate-400 uppercase font-semibold text-[10px] tracking-wider" x-text="currentLevel === 'region' ? 'Selected Geopolitical Region' : (currentLevel === 'coordinator' ? 'Selected Senatorial District & Coordinator' : (currentLevel === 'partner' ? 'Selected Partner / Enterprise Business' : 'Selected Field Agent'))"></span>
                    <h3 class="text-base font-bold text-slate-900" x-text="currentEntity.name"></h3>
                    <p class="text-slate-500 mt-0.5" x-text="currentEntity.lead_name ? ('Manager: ' + currentEntity.lead_name + ' · ' + (currentEntity.lead_phone || '—')) : (currentEntity.district_name ? currentEntity.district_name + ' · ' + (currentEntity.phone || '—') : (currentEntity.role_label ? currentEntity.role_label + ' · ' + (currentEntity.phone || '—') : (currentEntity.phone || '—')) )"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 font-bold" x-text="items.length + ' item(s) found'"></span>
                </div>
            </div>
        </template>

        <!-- Loading Spinner -->
        <div x-show="loading" class="py-12 flex flex-col items-center justify-center gap-3 text-slate-400">
            <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-primary"></i>
            <p class="text-xs font-semibold">Tracing downline hierarchy &amp; querying sales records...</p>
        </div>

        <!-- Error Message -->
        <div x-show="error" x-text="error" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium"></div>

        <!-- LEVEL 1: Region -> Senatorial Districts -->
        <div x-show="!loading && currentLevel === 'region'" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Senatorial District</th>
                        <th class="py-2.5 px-3">State</th>
                        <th class="py-2.5 px-3">Coordinator</th>
                        <th class="py-2.5 px-3 text-right">Allocated</th>
                        <th class="py-2.5 px-3 text-right">Activated</th>
                        <th class="py-2.5 px-3 text-right">Sales Volume</th>
                        <th class="py-2.5 px-3 text-right">Conversion</th>
                        <th class="py-2.5 px-3 text-right">Dormant</th>
                        <th class="py-2.5 px-3 text-right">Downline Orbit</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="item in items.filter(i => !drilldownSearch || i.district.toLowerCase().includes(drilldownSearch.toLowerCase()) || i.coordinator_name.toLowerCase().includes(drilldownSearch.toLowerCase()) || i.state.toLowerCase().includes(drilldownSearch.toLowerCase()))" :key="item.id">
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-3 font-bold text-slate-900" x-text="item.district"></td>
                            <td class="py-3 px-3 font-semibold text-slate-600" x-text="item.state"></td>
                            <td class="py-3 px-3">
                                <p class="font-bold text-slate-800" x-text="item.coordinator_name"></p>
                                <p class="text-[10px] text-slate-400" x-text="item.coordinator_phone"></p>
                            </td>
                            <td class="py-3 px-3 text-right font-semibold" x-text="item.allocated"></td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600" x-text="item.activated"></td>
                            <td class="py-3 px-3 text-right font-semibold" x-text="'₦' + Number(item.sales_volume).toLocaleString()"></td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold"
                                      :class="item.conversion_rate >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'"
                                      x-text="item.conversion_rate + '%'"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span :class="item.dormant > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="item.dormant"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 font-bold text-[11px]" x-text="item.partner_count + ' Partner(s)'"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button type="button" x-show="item.coordinator_id" @click="drilldown('coordinator', item.coordinator_id, item.district + ' (' + item.coordinator_name + ')')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-white hover:bg-primary-dark font-bold text-xs transition shadow-sm">
                                    <span>Explore Downline</span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </button>
                                <span x-show="!item.coordinator_id" class="text-slate-400 italic text-[11px]">No coordinator</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- LEVEL 2: Coordinator -> Partners & Enterprise Businesses -->
        <div x-show="!loading && currentLevel === 'coordinator'" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Partner / Enterprise Business</th>
                        <th class="py-2.5 px-3">Tier Role</th>
                        <th class="py-2.5 px-3">Contact Details</th>
                        <th class="py-2.5 px-3 text-right">Allocated</th>
                        <th class="py-2.5 px-3 text-right">Activated</th>
                        <th class="py-2.5 px-3 text-right">Sales Volume</th>
                        <th class="py-2.5 px-3 text-right">Conversion</th>
                        <th class="py-2.5 px-3 text-right">Dormant</th>
                        <th class="py-2.5 px-3 text-right">Field Agents</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="item in items.filter(i => !drilldownSearch || i.name.toLowerCase().includes(drilldownSearch.toLowerCase()) || i.phone.toLowerCase().includes(drilldownSearch.toLowerCase()))" :key="item.id">
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-3 font-bold text-slate-900" x-text="item.name"></td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                                      :class="item.role === 'business' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                      x-text="item.role_label"></span>
                            </td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-slate-800" x-text="item.phone"></p>
                                <p class="text-[10px] text-slate-400" x-text="item.email"></p>
                            </td>
                            <td class="py-3 px-3 text-right font-semibold" x-text="item.allocated"></td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600" x-text="item.activated"></td>
                            <td class="py-3 px-3 text-right font-semibold" x-text="'₦' + Number(item.sales_volume).toLocaleString()"></td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold"
                                      :class="item.conversion_rate >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'"
                                      x-text="item.conversion_rate + '%'"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span :class="item.dormant > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="item.dormant"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-purple-50 text-purple-700 font-bold text-[11px]" x-text="item.agent_count + ' Agent(s)'"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button type="button" @click="drilldown('partner', item.id, item.name + ' (' + item.role_label + ')')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-white hover:bg-primary-dark font-bold text-xs transition shadow-sm">
                                    <span>Explore Downline</span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="10" class="py-8 text-center text-slate-400 text-xs">No partners or enterprise businesses mapped under this coordinator yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- LEVEL 3: Partner -> Field Agents & Sub-Users -->
        <div x-show="!loading && currentLevel === 'partner'" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Field Agent / Downline User</th>
                        <th class="py-2.5 px-3">Role</th>
                        <th class="py-2.5 px-3">Contact</th>
                        <th class="py-2.5 px-3 text-right">Allocated</th>
                        <th class="py-2.5 px-3 text-right">Activated</th>
                        <th class="py-2.5 px-3 text-right">Sales Volume</th>
                        <th class="py-2.5 px-3 text-right">Conversion</th>
                        <th class="py-2.5 px-3 text-right">Dormant</th>
                        <th class="py-2.5 px-3 text-right">Customers</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="item in items.filter(i => !drilldownSearch || i.name.toLowerCase().includes(drilldownSearch.toLowerCase()) || i.phone.toLowerCase().includes(drilldownSearch.toLowerCase()))" :key="item.id">
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-3 font-bold text-slate-900" x-text="item.name"></td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-700" x-text="item.role_label"></span>
                            </td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-slate-800" x-text="item.phone"></p>
                                <p class="text-[10px] text-slate-400" x-text="item.email"></p>
                            </td>
                            <td class="py-3 px-3 text-right font-semibold" x-text="item.allocated"></td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600" x-text="item.activated"></td>
                            <td class="py-3 px-3 text-right font-semibold" x-text="'₦' + Number(item.sales_volume).toLocaleString()"></td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold"
                                      :class="item.conversion_rate >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'"
                                      x-text="item.conversion_rate + '%'"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span :class="item.dormant > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'" x-text="item.dormant"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-[11px]" x-text="item.customer_count + ' Customer(s)'"></span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <button type="button" @click="drilldown('agent', item.id, item.name)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 font-bold text-xs transition shadow-sm">
                                    <span>SIMs &amp; Customers</span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="10" class="py-8 text-center text-slate-400 text-xs">No downline field agents or users recorded under this partner yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- LEVEL 4: Field Agent -> SIMs & Customers Record -->
        <div x-show="!loading && currentLevel === 'agent'" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">SIM Number</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Network</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3">Holding Time</th>
                        <th class="py-2.5 px-3">Customer Full Name</th>
                        <th class="py-2.5 px-3">Customer Phone</th>
                        <th class="py-2.5 px-3">Activation Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="item in items.filter(i => !drilldownSearch || i.number.toLowerCase().includes(drilldownSearch.toLowerCase()) || i.customer_name.toLowerCase().includes(drilldownSearch.toLowerCase()))" :key="item.id">
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-3 font-mono font-bold text-slate-900" x-text="item.number"></td>
                            <td class="py-3 px-3 font-medium text-slate-700" x-text="item.category"></td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700" x-text="item.provider"></span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="item.status === 'ACTIVATED' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'"
                                      x-text="item.status"></span>
                            </td>
                            <td class="py-3 px-3 font-medium text-slate-600" x-text="item.days_held + ' days'"></td>
                            <td class="py-3 px-3 font-bold text-slate-900" x-text="item.customer_name"></td>
                            <td class="py-3 px-3 font-semibold text-slate-700" x-text="item.customer_phone"></td>
                            <td class="py-3 px-3 text-slate-500" x-text="item.activated_at"></td>
                        </tr>
                    </template>
                    <tr x-show="items.length === 0">
                        <td colspan="8" class="py-8 text-center text-slate-400 text-xs">No SIM cards assigned or activated by this agent yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. Regional Breakdown Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Regional Sales Performance</h2>
                <p class="text-xs text-slate-500">Tier 1: Geopolitical Regions and Assigned Regional Managers. Click any region to explore its deep downline breakdown.</p>
            </div>
            <div class="w-full sm:w-64">
                <input type="text" x-model="regionSearch" placeholder="Filter regions..." class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-1.5 px-3">
            </div>
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
                        <th class="py-2.5 px-3 text-right">Pending</th>
                        <th class="py-2.5 px-3 text-right">Dormant (7+ d)</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($regionsBreakdown as $r)
                        <tr class="hover:bg-slate-50/70 transition cursor-pointer"
                            @click="drilldown('region', {{ $r['id'] }}, '{{ addslashes($r['name']) }}')"
                            x-show="!regionSearch || '{{ strtolower($r['name']) }}'.includes(regionSearch.toLowerCase()) || '{{ strtolower($r['lead_name']) }}'.includes(regionSearch.toLowerCase())">
                            <td class="py-3 px-3 font-bold text-slate-900 hover:text-primary transition flex items-center gap-1.5">
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>{{ $r['name'] }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-slate-800">{{ $r['lead_name'] }}</p>
                                <p class="text-[10px] text-slate-400">{{ $r['lead_phone'] }}</p>
                            </td>
                            <td class="py-3 px-3 text-right font-semibold">{{ number_format($r['allocated']) }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($r['activated']) }}</td>
                            <td class="py-3 px-3 text-right font-semibold">₦{{ number_format($r['sales_volume'], 0) }}</td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $r['conversion_rate'] >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $r['conversion_rate'] }}%
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-medium text-slate-500">{{ number_format($r['pending']) }}</td>
                            <td class="py-3 px-3 text-right">
                                <span class="{{ $r['dormant'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">{{ number_format($r['dormant']) }}</span>
                            </td>
                            <td class="py-3 px-3 text-right" @click.stop>
                                <button type="button" @click="drilldown('region', {{ $r['id'] }}, '{{ addslashes($r['name']) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-white hover:bg-primary-dark font-bold text-xs transition shadow-sm">
                                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                    <span>Breakdown</span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-slate-400 text-xs">No regional territories configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Senatorial Districts Breakdown Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Senatorial District Breakdown</h2>
                <p class="text-xs text-slate-500">Tier 2: Senatorial Districts, States and Designated Coordinators. Click View Downline to trace its partner network.</p>
            </div>
            <div class="w-full sm:w-64">
                <input type="text" x-model="districtSearch" placeholder="Filter districts or states..." class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-1.5 px-3">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">District</th>
                        <th class="py-2.5 px-3">State & Region</th>
                        <th class="py-2.5 px-3">Coordinator</th>
                        <th class="py-2.5 px-3 text-right">Allocated</th>
                        <th class="py-2.5 px-3 text-right">Activated</th>
                        <th class="py-2.5 px-3 text-right">Sales Volume</th>
                        <th class="py-2.5 px-3 text-right">Conversion</th>
                        <th class="py-2.5 px-3 text-right">Pending</th>
                        <th class="py-2.5 px-3 text-right">Dormant (7+ d)</th>
                        <th class="py-2.5 px-3 text-right">Downline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($districtsBreakdown as $d)
                        <tr class="hover:bg-slate-50/70 transition" x-show="!districtSearch || '{{ strtolower($d['district']) }}'.includes(districtSearch.toLowerCase()) || '{{ strtolower($d['state']) }}'.includes(districtSearch.toLowerCase()) || '{{ strtolower($d['coordinator_name']) }}'.includes(districtSearch.toLowerCase())">
                            <td class="py-3 px-3 font-bold text-slate-900">{{ $d['district'] }}</td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-slate-800">{{ $d['state'] }}</p>
                                <p class="text-[10px] text-slate-400">{{ $d['region'] }}</p>
                            </td>
                            <td class="py-3 px-3">
                                <p class="font-semibold text-slate-800">{{ $d['coordinator_name'] }}</p>
                                <p class="text-[10px] text-slate-400">{{ $d['coordinator_phone'] }}</p>
                            </td>
                            <td class="py-3 px-3 text-right font-semibold">{{ number_format($d['allocated']) }}</td>
                            <td class="py-3 px-3 text-right font-bold text-emerald-600">{{ number_format($d['activated']) }}</td>
                            <td class="py-3 px-3 text-right font-semibold">₦{{ number_format($d['sales_volume'], 0) }}</td>
                            <td class="py-3 px-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $d['conversion_rate'] >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $d['conversion_rate'] }}%
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-medium text-slate-500">{{ number_format($d['pending']) }}</td>
                            <td class="py-3 px-3 text-right">
                                <span class="{{ $d['dormant'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">{{ number_format($d['dormant']) }}</span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                @if (!empty($d['coordinator_id']))
                                    <button type="button" @click="drilldown('coordinator', {{ $d['coordinator_id'] }}, '{{ addslashes($d['district']) }} ({{ addslashes($d['coordinator_name']) }})')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white font-bold text-xs transition border border-blue-200 shadow-sm">
                                        <i data-lucide="network" class="w-3.5 h-3.5"></i>
                                        <span>Downline</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Unassigned</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-6 text-center text-slate-400 text-xs">No senatorial districts registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>
