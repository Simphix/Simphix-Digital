<div class="flex flex-wrap items-center gap-1.5 p-1.5 bg-slate-100 rounded-2xl w-fit">
    @php
        $labels = [
            'p2p' => 'P2P Transfers',
            'deposits' => 'Deposits',
            'cashouts' => 'Cashouts',
            'commissions' => 'Commissions',
            'purchases' => 'Purchases',
            'sim_bonus' => 'SIM Bonus',
            'balance_integrity' => 'Wallet Balances',
            'platform_wallet' => 'Platform Wallets',
        ];
    @endphp
    @foreach ($labels as $key => $label)
        <a href="{{ route('admin.reconciliation.index', array_filter([$key, 'range' => request('range'), 'period_start' => request('period_start'), 'period_end' => request('period_end')])) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-1.5 {{ $tab === $key ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            {{ $label }}
            @if (($summary[$key] ?? 0) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-extrabold {{ $tab === $key ? 'bg-rose-100 text-rose-600' : 'bg-slate-200 text-slate-500' }}">
                    {{ $summary[$key] }}
                </span>
            @endif
        </a>
    @endforeach
</div>
