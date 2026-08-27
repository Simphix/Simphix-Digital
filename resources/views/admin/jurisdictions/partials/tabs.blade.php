<div class="flex items-center gap-1.5 p-1.5 bg-slate-100 rounded-2xl w-fit">
    <a href="{{ route('admin.jurisdictions.regions.index') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('admin.jurisdictions.regions*') ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
        Regions
    </a>
    <a href="{{ route('admin.jurisdictions.states.index') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('admin.jurisdictions.states*') ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
        States
    </a>
    <a href="{{ route('admin.jurisdictions.districts.index') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 {{ request()->routeIs('admin.jurisdictions.districts*') ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
        Senatorial Districts
    </a>
</div>
