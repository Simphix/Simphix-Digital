@php
    $sub = $sub ?? false;
    $badge = $badge ?? null;

    $faSolidMap = [
        'layout-dashboard' => 'fa-solid fa-table-columns',
        'wallet'           => 'fa-solid fa-wallet',
        'banknote'         => 'fa-solid fa-money-bill-wave',
        'cpu'              => 'fa-solid fa-sim-card',
        'layout-grid'      => 'fa-solid fa-border-all',
        'zap'              => 'fa-solid fa-bolt',
        'database'         => 'fa-solid fa-database',
        'smartphone'       => 'fa-solid fa-mobile-screen-button',
        'inbox'            => 'fa-solid fa-inbox',
        'badge-percent'    => 'fa-solid fa-percent',
        'shield-check'     => 'fa-solid fa-shield-halved',
        'fingerprint'      => 'fa-solid fa-fingerprint',
        'file-check-2'     => 'fa-solid fa-id-card',
        'users'            => 'fa-solid fa-users',
        'phone'            => 'fa-solid fa-phone',
        'history'          => 'fa-solid fa-clock-rotate-left',
        'trending-up'      => 'fa-solid fa-chart-line',
        'trophy'           => 'fa-solid fa-trophy',
        'file-badge'       => 'fa-solid fa-certificate',
        'help-circle'      => 'fa-solid fa-circle-question',
        'user-cog'         => 'fa-solid fa-user-gear',
        'map-pin'          => 'fa-solid fa-location-dot',
        'user-check'       => 'fa-solid fa-user-check',
        'settings'         => 'fa-solid fa-gear',
        'message-square'   => 'fa-solid fa-comments',
        'receipt'          => 'fa-solid fa-receipt',
        'package'          => 'fa-solid fa-box',
        'battery-charging' => 'fa-solid fa-bolt-lightning',
        'webhook'          => 'fa-solid fa-network-wired',
        'alert-octagon'    => 'fa-solid fa-circle-exclamation',
        'arrow-up-circle'  => 'fa-solid fa-circle-arrow-up',
        'repeat'           => 'fa-solid fa-arrows-rotate',
        'map'              => 'fa-solid fa-map',
        'scale'            => 'fa-solid fa-scale-balanced',
        'server'           => 'fa-solid fa-server',
        'wifi'             => 'fa-solid fa-wifi',
        'package-check'    => 'fa-solid fa-box-open',
        'handshake'        => 'fa-solid fa-handshake',
        'toggle-right'     => 'fa-solid fa-toggle-on',
        'send'             => 'fa-solid fa-paper-plane',
        'briefcase'        => 'fa-solid fa-briefcase',
        'bar-chart-3'      => 'fa-solid fa-chart-simple',
        'log-out'          => 'fa-solid fa-right-from-bracket',
    ];

    $isFa = str_starts_with($icon, 'fa-') || isset($faSolidMap[$icon]);
    $solidClass = str_starts_with($icon, 'fa-') ? $icon : ($faSolidMap[$icon] ?? null);
@endphp
<a href="{{ $href }}"
   title="{{ $label }}"
   @if($active) aria-current="page" @endif
   :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
   class="flex items-center {{ $sub ? 'gap-2.5 py-2 pl-3 pr-3 text-xs' : 'gap-3 py-2.5 px-3 text-sm' }} rounded-md font-medium font-display transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $active ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
    <span class="relative shrink-0 flex items-center justify-center {{ $sub ? 'w-4 h-4' : 'w-5 h-5' }}">
        @if ($isFa && $solidClass)
            <i class="{{ $solidClass }} {{ $sub ? 'text-xs' : 'text-base' }} leading-none shrink-0 {{ $active ? 'text-slate-900' : 'text-slate-400' }}"></i>
        @else
            <i data-lucide="{{ $icon }}" class="{{ $sub ? 'w-3.5 h-3.5' : 'w-5 h-5' }} shrink-0 {{ $active ? 'text-slate-900' : 'text-slate-400' }}"></i>
        @endif
        @if ($badge)
            <span x-show="sidebarCollapsed" x-cloak class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-rose-500"></span>
        @endif
    </span>
    <span x-show="!sidebarCollapsed" x-cloak class="flex-1">{{ $label }}</span>
    @if ($badge)
        <span x-show="!sidebarCollapsed" x-cloak class="ml-auto px-1.5 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full font-display leading-none">{{ $badge }}</span>
    @endif
</a>
