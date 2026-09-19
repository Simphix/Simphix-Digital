@php $onPlatform = request()->routeIs('admin.platform-wallets.approvals.*'); @endphp
<div class="inline-flex bg-white rounded-xl border border-slate-200 shadow-sm p-1 gap-1">
    <a href="{{ route('admin.cash-out.index') }}"
       class="px-4 py-2 text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5 {{ $onPlatform ? 'text-slate-500 hover:bg-slate-50' : 'bg-[#0056D2] text-white' }}">
        <i data-lucide="users" class="w-3.5 h-3.5"></i> User Cash Outs
        @if (($userPendingCount ?? 0) > 0)
            <span class="px-1.5 py-0.5 text-[10px] rounded-full leading-none {{ $onPlatform ? 'bg-rose-500 text-white' : 'bg-white/20 text-white' }}">{{ $userPendingCount }}</span>
        @endif
    </a>
    <a href="{{ route('admin.platform-wallets.approvals.index') }}"
       class="px-4 py-2 text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5 {{ $onPlatform ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50' }}">
        <i data-lucide="landmark" class="w-3.5 h-3.5"></i> Platform Wallet Cash Outs
        @if (($platformPendingCount ?? 0) > 0)
            <span class="px-1.5 py-0.5 text-[10px] rounded-full leading-none {{ $onPlatform ? 'bg-white/20 text-white' : 'bg-rose-500 text-white' }}">{{ $platformPendingCount }}</span>
        @endif
    </a>
</div>
