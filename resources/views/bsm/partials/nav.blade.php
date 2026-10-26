@php
    $currentRoute = request()->route()->getName();
@endphp

<div class="bg-white rounded-2xl border border-slate-200 p-2 shadow-sm mb-6">
    <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0 scrollbar-none text-xs font-bold">
        <a href="{{ route('bsm.dashboard') }}"
           class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap {{ $currentRoute === 'bsm.dashboard' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            <span>Overview</span>
        </a>

        <a href="{{ route('bsm.territories') }}"
           class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap {{ $currentRoute === 'bsm.territories' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i data-lucide="map-pin" class="w-4 h-4"></i>
            <span>Territories Report</span>
        </a>

        <a href="{{ route('bsm.lifecycle') }}"
           class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap {{ $currentRoute === 'bsm.lifecycle' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i data-lucide="cpu" class="w-4 h-4"></i>
            <span>SIM Lifecycle</span>
        </a>

        <a href="{{ route('bsm.customers') }}"
           class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap {{ $currentRoute === 'bsm.customers' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i data-lucide="user-check" class="w-4 h-4"></i>
            <span>Customer Desk</span>
        </a>

        <a href="{{ route('bsm.acceleration') }}"
           class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap {{ $currentRoute === 'bsm.acceleration' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i data-lucide="zap" class="w-4 h-4"></i>
            <span>Sales Acceleration</span>
        </a>

        <div class="h-5 w-px bg-slate-200 mx-1 hidden sm:block"></div>

        <a href="{{ route('admin.sim-plan.index') }}"
           class="flex items-center gap-2 px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap {{ request()->routeIs('admin.sim-plan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/50' }}">
            <i data-lucide="settings" class="w-4 h-4"></i>
            <span>SIM Cards & Plans Mgmt</span>
        </a>
    </div>
</div>
