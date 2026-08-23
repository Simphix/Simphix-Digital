@push('styles')
    <!-- Bootstrap 5 GRID ONLY for premium responsiveness without Reboot/Reset overrides -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css" rel="stylesheet" />
    <style>
        .dash-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            transition: border-color 0.2s ease;
        }
        .dash-card:hover {
            border-color: rgba(0, 86, 210, 0.3);
        }
        .custom-badge {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .admin-btn {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            text-decoration: none;
            background: #ffffff;
        }
        .admin-btn:hover {
            background: #f8fafc;
            border-color: rgba(0, 86, 210, 0.3);
        }
    </style>
@endpush

<x-app-layout>
    <div class="container-fluid py-2 px-0">

        <!-- Header -->
        <div class="row align-items-center mb-4 gy-3">
            <div class="col-12 col-md-8">
                <h1 class="text-2xl font-bold font-display text-slate-800 mb-1 flex flex-wrap items-center gap-2">
                    Super Admin Console
                    <span class="text-xs font-semibold text-primary bg-primary/10 border border-primary/20 rounded-md py-1 px-2.5">
                        System Admin
                    </span>
                </h1>
                <p class="text-slate-500 text-sm mb-0">Real-time system health, pending operations, transaction metrics, and user management.</p>
            </div>

            <div class="col-12 col-md-4 flex justify-start justify-content-md-end">
                <div class="flex items-center gap-2 text-emerald-600 py-2 px-3 bg-white border border-slate-200 rounded-lg text-sm">
                    <i data-lucide="shield-check" class="text-emerald-500 w-4 h-4"></i>
                    <span>Operations Active</span>
                </div>
            </div>
        </div>

        <!-- Time-Range Filter -->
        <div class="dash-card p-3.5 mb-4" x-data="{ showCustom: {{ $range === 'custom' ? 'true' : 'false' }} }">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-400 mr-1">Showing:</span>
                <button type="submit" name="range" value="today"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors {{ $range === 'today' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}"
                        @click="showCustom = false">Today</button>
                <button type="submit" name="range" value="3days"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors {{ $range === '3days' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}"
                        @click="showCustom = false">3 Days</button>
                <button type="submit" name="range" value="7days"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors {{ $range === '7days' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}"
                        @click="showCustom = false">7 Days</button>
                <button type="button" @click="showCustom = !showCustom"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors {{ $range === 'custom' ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Custom</button>

                <div x-show="showCustom" x-cloak class="flex flex-wrap items-center gap-2 ml-1">
                    <input type="date" name="period_start" value="{{ $periodStart }}" max="{{ now()->format('Y-m-d') }}"
                           class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" name="period_end" value="{{ $periodEnd }}" max="{{ now()->format('Y-m-d') }}"
                           class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <button type="submit" name="range" value="custom" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors">Apply</button>
                </div>
            </form>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="p-4 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3" role="alert">
                <i data-lucide="check-circle" class="text-emerald-500 shrink-0 w-5 h-5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3" role="alert">
                <i data-lucide="alert-circle" class="text-rose-500 shrink-0 w-5 h-5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @php
            $rangeLabel = match ($range) {
                'today' => 'Today',
                '3days' => 'Last 3 days',
                'custom' => \Carbon\Carbon::parse($periodStart)->format('M j') . ' – ' . \Carbon\Carbon::parse($periodEnd)->format('M j'),
                default => 'Last 7 days',
            };
        @endphp

        <!-- ADMIN KEY PERFORMANCE INDICATORS GRID (KPIs) -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">

            <!-- KPI 1: System Users -->
            <div class="col">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">System Users</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">{{ number_format($totalUsers) }}</p>
                        <p class="text-xs text-slate-400 mb-0 truncate">{{ number_format($activeUsers) }} active &middot; {{ number_format($totalUsers - $activeUsers) }} suspended</p>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Circulating Capital -->
            <div class="col">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="wallet" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">Circulating Funds</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">₦{{ number_format($totalWalletBalance, 2) }}</p>
                        <p class="text-xs text-slate-400 mb-0 truncate">Bonus pool: ₦{{ number_format($totalWalletBonus, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- KPI 3: System Transactions (ranged) -->
            <div class="col">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">Transaction Volume</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">₦{{ number_format($totalTransactionVolume, 2) }}</p>
                        <p class="text-xs text-slate-400 mb-0 truncate">{{ number_format($totalTransactionsCount) }} completed &middot; {{ $rangeLabel }}</p>
                    </div>
                </div>
            </div>

            <!-- KPI 4: SIM Activations (ranged) -->
            <div class="col">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="smartphone" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">SIM Activations</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">{{ number_format($simActivationsCount) }}</p>
                        <p class="text-xs text-slate-400 mb-0 truncate">{{ $rangeLabel }}</p>
                    </div>
                </div>
            </div>

            <!-- KPI 5: Pending Operations -->
            <div class="col">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">Pending Requests</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">
                            {{ $pendingUpgradesCount + $pendingSimRequestsCount + $openTicketsCount }}
                        </p>
                        <p class="text-xs text-slate-400 mb-0 truncate">{{ $pendingUpgradesCount }} upgrades &middot; {{ $pendingSimRequestsCount }} SIMs &middot; {{ $openTicketsCount }} tickets</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- QUICK ADMIN ACTIONS PANEL -->
        <div class="dash-card p-4 mb-4">
            <h3 class="text-sm font-semibold text-slate-800 font-display mb-3 flex items-center gap-2">
                <i data-lucide="sliders" class="text-primary w-5 h-5"></i>
                Administrative Quick Actions
            </h3>

            <div class="row row-cols-2 row-cols-md-4 row-cols-lg-8 g-3">
                <!-- Action 1: Manage Users -->
                <div class="col">
                    <a href="{{ route('admin.manage.users') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="users" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">Manage Users</span>
                    </a>
                </div>

                <!-- Action 2: Manage Access -->
                <div class="col">
                    <a href="{{ route('admin.manage.access') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="shield" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">Access Control</span>
                    </a>
                </div>

                <!-- Action 3: Upgrade Requests -->
                <div class="col">
                    <a href="{{ route('admin.manage.upgrades') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="arrow-up-circle" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">Role Upgrades</span>
                    </a>
                </div>

                <!-- Action 4: SIM Requests -->
                <div class="col">
                    <a href="{{ route('admin.sim-plan.index') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="settings" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">SIM Inventory</span>
                    </a>
                </div>

                <!-- Action 5: SME Data Plans -->
                <div class="col">
                    <a href="{{ route('admin.sme-plans.index') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="wifi" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">SME Data Plans</span>
                    </a>
                </div>

                <!-- Action 6: Services pricing -->
                <div class="col">
                    <a href="{{ route('admin.services.index') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="server" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">Pricing & Fees</span>
                    </a>
                </div>

                <!-- Action 7: System Transactions -->
                <div class="col">
                    <a href="{{ route('admin.transactions') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="receipt" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">All Transactions</span>
                    </a>
                </div>

                <!-- Action 8: Support Tickets -->
                <div class="col">
                    <a href="{{ route('admin.manage.support.index') }}" class="admin-btn p-3 text-center flex flex-col items-center gap-2 h-100">
                        <i data-lucide="message-square" class="text-slate-600 w-5 h-5"></i>
                        <span class="font-semibold text-slate-700 text-[11px]">Support Desk</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- SPLIT LAYOUT: OPERATIONS LISTS -->
        <div class="row g-4">

            <!-- Left Side: Transactions & Upgrade Requests (7 cols out of 12) -->
            <div class="col-12 col-lg-7 flex flex-col">

                <!-- Recent System Transactions -->
                <div class="dash-card p-4 mb-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-slate-800 font-display mb-0 flex items-center gap-2">
                            <i data-lucide="history" class="text-primary w-5 h-5"></i>
                            Recent System Transactions
                        </h3>
                        <a href="{{ route('admin.transactions') }}" class="no-underline px-3 py-1.5 bg-primary/10 hover:bg-primary/15 text-primary text-xs font-semibold rounded-md transition">
                            View All
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse($recentTransactions as $tx)
                            <div class="p-3 bg-slate-50 border border-slate-200/60 rounded-lg flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0 flex-grow">
                                    <div class="p-2 rounded-md {{ $tx->type === 'credit' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center shrink-0 w-8 h-8">
                                        <i data-lucide="{{ $tx->type === 'credit' ? 'arrow-down-left' : 'arrow-up-right' }}" class="w-4 h-4"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <h4 class="font-semibold text-slate-800 mb-0 truncate block font-display text-xs" title="{{ $tx->description }}">
                                            {{ $tx->description }}
                                        </h4>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-slate-400 mt-0.5 text-xs">
                                            <span class="text-slate-500 font-medium truncate max-w-[120px] sm:max-w-none">{{ $tx->user->email ?? 'N/A' }}</span>
                                            <span class="hidden xs:inline">•</span>
                                            <span class="text-nowrap">{{ $tx->created_at->format('M d, h:i A') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end shrink-0 ml-3">
                                    <span class="block font-display font-semibold text-slate-800 text-xs text-nowrap">
                                        ₦{{ number_format($tx->amount, 2) }}
                                    </span>
                                    <span class="text-slate-500 text-xs font-medium capitalize">
                                        {{ $tx->status }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-slate-400 text-xs">No transactions recorded in the system yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Side: Support Tickets & SIM Requests (5 cols out of 12) -->
            <div class="col-12 col-lg-5 flex flex-col">

                <!-- Open Support Tickets -->
                <div class="dash-card p-4 mb-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-slate-800 font-display mb-0 flex items-center gap-2">
                            <i data-lucide="message-square" class="text-primary w-5 h-5"></i>
                            Open Support Desk Tickets
                        </h3>
                        <a href="{{ route('admin.manage.support.index') }}" class="no-underline px-3 py-1.5 bg-primary/10 hover:bg-primary/15 text-primary text-xs font-semibold rounded-md transition">
                            View All
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse($openTickets as $ticket)
                            <div class="p-3 bg-slate-50 border border-slate-200/60 rounded-lg space-y-2 hover:bg-slate-100/70 transition">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="min-w-0">
                                        <h4 class="font-semibold text-slate-800 mb-0 text-truncate font-display text-xs" style="max-width: 180px;">
                                            {{ $ticket->subject }}
                                        </h4>
                                        <p class="text-slate-400 mb-0 mt-0.5 text-xs">
                                            User: {{ $ticket->user->email ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-semibold capitalize {{ $ticket->priority === 'high' ? 'text-rose-600' : ($ticket->priority === 'medium' ? 'text-amber-600' : 'text-slate-500') }}">
                                        {{ $ticket->priority }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center pt-2 border-t border-slate-100 text-xs font-medium">
                                    <span class="text-slate-500 capitalize">{{ $ticket->category }}</span>
                                    <a href="{{ route('admin.manage.support.show', $ticket) }}" class="text-primary no-underline flex items-center gap-0.5 hover:underline">
                                        View & Reply <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-slate-400 text-xs">No open support tickets. Excellent!</div>
                        @endforelse
                    </div>
                </div>

                <!-- Pending SIM Requests -->
                <div class="dash-card p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-slate-800 font-display mb-0 flex items-center gap-2">
                            <i data-lucide="cpu" class="text-primary w-5 h-5"></i>
                            Pending SIM Orders
                        </h3>
                        <a href="{{ route('admin.sim-plan.index') }}" class="no-underline px-3 py-1.5 bg-primary/10 hover:bg-primary/15 text-primary text-xs font-semibold rounded-md transition">
                            Manage
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse($pendingSimRequests as $simReq)
                            <div class="p-3 bg-slate-50 border border-slate-200/60 rounded-lg flex items-center justify-between">
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-slate-800 mb-0 font-display text-xs">
                                        {{ $simReq->provider }} SIM Request
                                    </h4>
                                    <span class="block text-slate-400 mt-0.5 text-xs">
                                        Num: {{ $simReq->number ?? 'N/A' }} • User: {{ $simReq->user->email ?? 'N/A' }}
                                    </span>
                                </div>
                                <div class="text-end">
                                    <span class="block font-display font-semibold text-slate-800 text-xs">
                                        ₦{{ number_format($simReq->amount, 2) }}
                                    </span>
                                    <span class="text-amber-600 text-xs font-medium capitalize">
                                        {{ $simReq->status }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-slate-400 text-xs">No pending SIM request orders found.</div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
