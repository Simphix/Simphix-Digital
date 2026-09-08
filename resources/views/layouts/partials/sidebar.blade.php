<!-- Sidebar Overlay (Mobile) -->
<div x-show="sidebarOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden"
     style="display: none;">
</div>

<!-- Sidebar Container -->
<aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-[76px]' : 'lg:w-64']"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-white text-slate-600 border-r border-slate-200 flex flex-col transform lg:translate-x-0 lg:static lg:h-screen transition-all duration-200 ease-in-out">

    <!-- Brand Header -->
    <div class="flex items-center justify-between gap-2 px-4 py-4 border-b border-slate-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-md">
            <img src="{{ asset('assets/images/logo/favicon.png') }}" alt="" class="w-8 h-8 rounded-md shrink-0" aria-hidden="true">
            <span x-show="!sidebarCollapsed" x-cloak class="min-w-0 truncate text-sm font-bold text-slate-900 font-display">SmartSIM</span>
        </a>
        <button type="button" @click="sidebarCollapsed = !sidebarCollapsed"
                class="hidden lg:inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <i data-lucide="chevron-left" class="w-4 h-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-grow px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden" aria-label="Main navigation">

        @include('layouts.partials.sidebar-link', [
            'href' => route('dashboard'),
            'icon' => 'layout-dashboard',
            'label' => __('Dashboard'),
            'active' => request()->routeIs('dashboard'),
        ])

        @if (auth()->user() && auth()->user()->role !== 'super_admin')
            @php
                $walletActive = request()->routeIs('wallet', 'transfer', 'withdraw');
            @endphp
            <div x-data="{ open: {{ $walletActive ? 'true' : 'false' }} }">
                <button type="button"
                        @click="if (sidebarCollapsed) { sidebarCollapsed = false; open = true } else { open = !open }"
                        :aria-expanded="open" aria-controls="sidebar-wallet-menu"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between'"
                        class="w-full flex items-center gap-3 py-2.5 px-3 rounded-md text-sm font-medium font-display transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $walletActive ? 'text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <span class="flex items-center gap-3">
                        <i data-lucide="wallet" class="w-5 h-5 shrink-0 {{ $walletActive ? 'text-slate-900' : 'text-slate-400' }}"></i>
                        <span x-show="!sidebarCollapsed" x-cloak>Wallet</span>
                    </span>
                    <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>

                <div id="sidebar-wallet-menu" x-show="open && !sidebarCollapsed"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-1 ml-[22px] space-y-0.5 border-l border-slate-200 pl-2.5" style="display: none;">

                    @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('wallet'), 'icon' => 'wallet', 'label' => __('My Wallet'), 'active' => request()->routeIs('wallet')])
                    @if (auth()->user()->role !== 'agent')
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('transfer'), 'icon' => 'send', 'label' => __('P2P Transfer'), 'active' => request()->routeIs('transfer')])
                        @if (auth()->user()->role !== 'personal')
                            @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('withdraw'), 'icon' => 'banknote', 'label' => __('Cash Out'), 'active' => request()->routeIs('withdraw')])
                        @endif
                    @endif
                </div>
            </div>
        @endif

        @if (auth()->user() && auth()->user()->role !== 'super_admin' && auth()->user()->role !== 'agent')
            @php
                $simsActive = request()->routeIs('sims.*');
            @endphp
            <div x-data="{ open: {{ $simsActive ? 'true' : 'false' }} }">
                <button type="button"
                        @click="if (sidebarCollapsed) { sidebarCollapsed = false; open = true } else { open = !open }"
                        :aria-expanded="open" aria-controls="sidebar-sims-menu"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between'"
                        class="w-full flex items-center gap-3 py-2.5 px-3 rounded-md text-sm font-medium font-display transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $simsActive ? 'text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <span class="flex items-center gap-3">
                        <i data-lucide="cpu" class="w-5 h-5 shrink-0 {{ $simsActive ? 'text-slate-900' : 'text-slate-400' }}"></i>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('SIM Services') }}</span>
                    </span>
                    <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>

                <div id="sidebar-sims-menu" x-show="open && !sidebarCollapsed"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-1 ml-[22px] space-y-0.5 border-l border-slate-200 pl-2.5" style="display: none;">

                    @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.index'), 'icon' => 'layout-grid', 'label' => __('Overview'), 'active' => request()->routeIs('sims.index')])
                    @if (\App\Support\SimAccess::canBrowseCatalog(auth()->user()))
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.pos'), 'icon' => 'credit-card', 'label' => __('POS SIM'), 'active' => request()->routeIs('sims.pos')])
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.cctv'), 'icon' => 'video', 'label' => __('CCTV SIM'), 'active' => request()->routeIs('sims.cctv')])
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.router'), 'icon' => 'router', 'label' => __('Router SIM'), 'active' => request()->routeIs('sims.router')])
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.inventory'), 'icon' => 'database', 'label' => __('Inventory'), 'active' => request()->routeIs('sims.inventory')])
                    @endif
                    @if (\App\Support\SimAccess::canViewMine(auth()->user()))
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.mine'), 'icon' => 'smartphone', 'label' => __('My SIM'), 'active' => request()->routeIs('sims.mine')])
                    @endif
                    @if (in_array(auth()->user()->role, ['partner', 'coordinator', 'regional_manager', 'business', 'business_agent'], true))
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.downline-requests'), 'icon' => 'inbox', 'label' => __('Downline Requests'), 'active' => request()->routeIs('sims.downline-requests'), 'badge' => $pendingDownlineRequestsBadge ?? 0])
                    @endif
                    @if (auth()->user()->role === 'business')
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('sims.business.commission-rates.index'), 'icon' => 'badge-percent', 'label' => __('Commission Rates'), 'active' => request()->routeIs('sims.business.commission-rates.*')])
                    @endif
                </div>
            </div>
        @endif

        @if (\App\Support\UtilityAccess::canVerify(auth()->user()))
            @php
                $verificationActive = request()->routeIs('bvn.verification.index', 'nin.verification.index', 'nin.demo.index', 'nin.phone.index');
            @endphp
            <div x-data="{ open: {{ $verificationActive ? 'true' : 'false' }} }">
                <button type="button"
                        @click="if (sidebarCollapsed) { sidebarCollapsed = false; open = true } else { open = !open }"
                        :aria-expanded="open" aria-controls="sidebar-verification-menu"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between'"
                        class="w-full flex items-center gap-3 py-2.5 px-3 rounded-md text-sm font-medium font-display transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $verificationActive ? 'text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <span class="flex items-center gap-3">
                        <i data-lucide="shield-check" class="w-5 h-5 shrink-0 {{ $verificationActive ? 'text-slate-900' : 'text-slate-400' }}"></i>
                        <span x-show="!sidebarCollapsed" x-cloak>Verification</span>
                    </span>
                    <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>

                <div id="sidebar-verification-menu" x-show="open && !sidebarCollapsed"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-1 ml-[22px] space-y-0.5 border-l border-slate-200 pl-2.5" style="display: none;">

                    @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('bvn.verification.index'), 'icon' => 'fingerprint', 'label' => 'BVN Verification', 'active' => request()->routeIs('bvn.verification.index')])
                    @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('nin.verification.index'), 'icon' => 'file-check-2', 'label' => 'NIN Verification', 'active' => request()->routeIs('nin.verification.index')])
                    @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('nin.demo.index'), 'icon' => 'users', 'label' => 'NIN Demo', 'active' => request()->routeIs('nin.demo.index')])
                    @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('nin.phone.index'), 'icon' => 'phone', 'label' => 'NIN Phone', 'active' => request()->routeIs('nin.phone.index')])
                </div>
            </div>
        @endif

        @include('layouts.partials.sidebar-link', ['href' => route('transactions'), 'icon' => 'history', 'label' => __('Transaction'), 'active' => request()->routeIs('transactions')])
        @if (auth()->user() && auth()->user()->role !== 'super_admin')
            @include('layouts.partials.sidebar-link', ['href' => route('network'), 'icon' => 'users', 'label' => __('My Network'), 'active' => request()->routeIs('network')])
        @endif
        @if (auth()->user() && (\App\Support\SimAccess::canBrowseCatalog(auth()->user()) || auth()->user()->hasRole('super_admin')))
            @include('layouts.partials.sidebar-link', ['href' => route('commissions.dashboard'), 'icon' => 'badge-percent', 'label' => __('Commissions'), 'active' => request()->routeIs('commissions.dashboard')])
        @endif
        @if (auth()->user() && \App\Support\SimAccess::canBrowseCatalog(auth()->user()))
            @include('layouts.partials.sidebar-link', ['href' => route('sales-performance.index'), 'icon' => 'trending-up', 'label' => __('Sales Performance'), 'active' => request()->routeIs('sales-performance.*')])
        @endif
        @if (auth()->user() && in_array(auth()->user()->role, ['partner', 'super_admin']))
            @include('layouts.partials.sidebar-link', ['href' => route('leaderboard'), 'icon' => 'trophy', 'label' => __('Leaderboard'), 'active' => request()->routeIs('leaderboard')])
        @endif
        @if (auth()->user() && auth()->user()->role !== 'super_admin')
            @include('layouts.partials.sidebar-link', ['href' => route('support'), 'icon' => 'help-circle', 'label' => __('Support'), 'active' => request()->routeIs('support')])
        @endif
        @include('layouts.partials.sidebar-link', ['href' => route('profile.edit'), 'icon' => 'user-cog', 'label' => __('Profile Settings'), 'active' => request()->routeIs('profile.edit')])

        @if (auth()->user() && auth()->user()->role === 'super_admin')
            <!-- Admin Management Section -->
            <div class="pt-4 mt-4 border-t border-slate-200">
                <span x-show="!sidebarCollapsed" x-cloak class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wide block mb-2">Administration</span>

                @php
                    $adminUsersActive = request()->routeIs('admin.manage.users*', 'admin.manage.upgrades*', 'admin.manage.access*');
                @endphp
                <div x-data="{ open: {{ $adminUsersActive ? 'true' : 'false' }} }">
                    <button type="button"
                            @click="if (sidebarCollapsed) { sidebarCollapsed = false; open = true } else { open = !open }"
                            :aria-expanded="open" aria-controls="sidebar-admin-users-menu"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between'"
                            class="w-full flex items-center gap-3 py-2.5 px-3 rounded-md text-sm font-medium font-display transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $adminUsersActive ? 'text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                        <span class="flex items-center gap-3">
                            <i data-lucide="users" class="w-5 h-5 shrink-0 {{ $adminUsersActive ? 'text-slate-900' : 'text-slate-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak>Users</span>
                        </span>
                        <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>

                    <div id="sidebar-admin-users-menu" x-show="open && !sidebarCollapsed"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="mt-1 ml-[22px] space-y-0.5 border-l border-slate-200 pl-2.5" style="display: none;">

                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('admin.manage.users'), 'icon' => 'users', 'label' => 'Manage Users', 'active' => request()->routeIs('admin.manage.users*')])
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('admin.manage.upgrades'), 'icon' => 'arrow-up-circle', 'label' => 'Manage Upgrades', 'active' => request()->routeIs('admin.manage.upgrades*')])
                        @include('layouts.partials.sidebar-link', ['sub' => true, 'href' => route('admin.manage.access'), 'icon' => 'shield-check', 'label' => 'Manage Access', 'active' => request()->routeIs('admin.manage.access*')])
                    </div>
                </div>

                @include('layouts.partials.sidebar-link', ['href' => route('admin.jurisdictions.regions.index'), 'icon' => 'map', 'label' => 'Jurisdictions', 'active' => request()->routeIs('admin.jurisdictions*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.reconciliation.index'), 'icon' => 'scale', 'label' => 'Reconciliation', 'active' => request()->routeIs('admin.reconciliation*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.services.index'), 'icon' => 'server', 'label' => 'Services Pricing', 'active' => request()->routeIs('admin.services*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.sme-plans.index'), 'icon' => 'wifi', 'label' => 'SME Data Plans', 'active' => request()->routeIs('admin.sme-plans*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.sim-plan.index'), 'icon' => 'settings', 'label' => 'SIM Plans', 'active' => request()->routeIs('admin.sim-plan*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.sim-data-topup.index'), 'icon' => 'battery-charging', 'label' => 'SIM Data Top-Up', 'active' => request()->routeIs('admin.sim-data-topup*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.enterprise-partners.fulfillment.index'), 'icon' => 'package-check', 'label' => 'EP Fulfillment', 'active' => request()->routeIs('admin.enterprise-partners.fulfillment*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.enterprise-partners.referral-settings.edit'), 'icon' => 'handshake', 'label' => 'EP Referral Settings', 'active' => request()->routeIs('admin.enterprise-partners.referral-settings*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.agent-upgrade-settings.edit'), 'icon' => 'trending-up', 'label' => 'Agent Upgrade Settings', 'active' => request()->routeIs('admin.agent-upgrade-settings*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.cash-out.index'), 'icon' => 'banknote', 'label' => 'Cash Out Approvals', 'active' => request()->routeIs('admin.cash-out*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.leaderboard.index'), 'icon' => 'trophy', 'label' => 'Leaderboard Settings', 'active' => request()->routeIs('admin.leaderboard*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.transactions'), 'icon' => 'receipt', 'label' => 'All Transactions', 'active' => request()->routeIs('admin.transactions*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.manage.adminwallet'), 'icon' => 'wallet', 'label' => 'Admin Wallet', 'active' => request()->routeIs('admin.manage.adminwallet')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.notifications.index'), 'icon' => 'send', 'label' => 'Push Notifications', 'active' => request()->routeIs('admin.notifications*')])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.manage.support.index'), 'icon' => 'message-square', 'label' => 'Admin Support', 'active' => request()->routeIs('admin.manage.support*'), 'badge' => $openTicketsBadge ?? 0])
                @include('layouts.partials.sidebar-link', ['href' => route('admin.webhooks.index'), 'icon' => 'webhook', 'label' => 'Webhook Logs', 'active' => request()->routeIs('admin.webhooks*'), 'badge' => $failedWebhooksBadge ?? 0])
            </div>

            <!-- Advanced Dashboard Section -->
            <div class="pt-4 mt-4 border-t border-slate-200">
                <span x-show="!sidebarCollapsed" x-cloak class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wide block mb-2">Advanced Dashboard</span>

                @include('layouts.partials.sidebar-link', ['href' => route('admin.sims-operations.index'), 'icon' => 'bar-chart-3', 'label' => 'SIMs Operations', 'active' => request()->routeIs('admin.sims-operations*')])
            </div>
        @endif
    </nav>

    <!-- Support promo card (disabled for now, kept for possible future use) -->
    <div x-show="!sidebarCollapsed" x-cloak class="hidden px-3 pb-3">
        <a href="{{ route('support') }}" class="block rounded-lg border border-slate-200 bg-slate-50 p-3.5 transition hover:border-slate-300 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
            <div class="flex h-8 w-8 items-center justify-center rounded-md bg-white border border-slate-200 text-slate-500">
                <i data-lucide="life-buoy" class="w-4 h-4"></i>
            </div>
            <p class="mt-2.5 text-sm font-semibold text-slate-800 font-display">Need help?</p>
            <p class="mt-0.5 text-xs text-slate-500 leading-relaxed">Our support team can assist with activations, wallets, and more.</p>
            <span class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary">
                Contact support
                <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </span>
        </a>
    </div>

    @if (($sidebarUpline ?? null) && auth()->user()->role !== 'business')
    <div class="border-t border-slate-200 p-3" x-data="{ open: false }">
        <button type="button"
                @click="if (sidebarCollapsed) { sidebarCollapsed = false; open = true } else { open = !open }"
                :aria-expanded="open" aria-controls="sidebar-upline-panel"
                :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between'"
                class="w-full flex items-center gap-3 py-2.5 px-3 rounded-md text-sm font-medium font-display text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
            <span class="flex items-center gap-3">
                <i data-lucide="user-check" class="w-5 h-5 shrink-0 text-slate-400"></i>
                <span x-show="!sidebarCollapsed" x-cloak>{{ __('Upline Manager') }}</span>
            </span>
            <i x-show="!sidebarCollapsed" data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
        </button>

        <div id="sidebar-upline-panel" x-show="open && !sidebarCollapsed"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs space-y-2" style="display: none;">
            <div class="flex items-start justify-between gap-3">
                <span class="text-slate-500">Name</span>
                <span class="font-semibold text-slate-800 text-right">{{ $sidebarUpline->name }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-slate-500 shrink-0">Position</span>
                <span class="font-semibold text-slate-800 capitalize text-right">{{ str_replace('_', ' ', $sidebarUpline->role) }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-slate-500 shrink-0">Phone</span>
                <span class="font-semibold text-slate-800 text-right break-all">{{ $sidebarUpline->phone }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Logout -->
    <div class="border-t border-slate-200 p-3">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    class="w-full flex items-center gap-3 py-2.5 px-3 rounded-md text-sm font-medium font-display text-rose-600 hover:bg-rose-50 transition-colors border-0 cursor-pointer bg-transparent text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <i data-lucide="log-out" class="w-5 h-5 shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-cloak>{{ __('Log Out') }}</span>
            </button>
        </form>
    </div>
</aside>
