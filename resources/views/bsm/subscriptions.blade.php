<x-app-layout>
<div class="space-y-6"
     x-data="{
         reactivateModalOpen: false,
         selectedSim: null,
         activeTab: 'wallet',
         walletSource: 'main',
         isGeneratingAccount: false,
         generatedAccount: @js(session('generated_funding_account')),
         copiedField: null,

         openReactivate(sim) {
             this.selectedSim = sim;
             this.activeTab = 'wallet';
             this.walletSource = 'main';
             this.reactivateModalOpen = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
         },
         openFunding(sim) {
             this.selectedSim = sim;
             this.activeTab = 'funding';
             this.reactivateModalOpen = true;
             this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
         },
         async generateAccount() {
             if (!this.selectedSim) return;
             this.isGeneratingAccount = true;
             try {
                 const res = await fetch('{{ url('bsm/subscriptions') }}/' + this.selectedSim.id + '/generate-funding-account', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}'
                     }
                 });
                 const data = await res.json();
                 if (!res.ok || !data.success) {
                     alert(data.message || 'Failed to generate account.');
                     return;
                 }
                 this.generatedAccount = data;
             } catch (e) {
                 alert(e.message || 'Network error.');
             } finally {
                 this.isGeneratingAccount = false;
                 this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
             }
         },
         copy(val, key) {
             if (!val) return;
             navigator.clipboard.writeText(val);
             this.copiedField = key;
             setTimeout(() => this.copiedField = null, 2000);
         }
     }"
     x-init="if (generatedAccount) { reactivateModalOpen = true; activeTab = 'funding'; }">

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

    <!-- Header & Action Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center p-2 rounded-lg bg-primary/10 text-primary">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 font-display">SIM Subscriptions & Lifetimes</h1>
                        <p class="text-sm text-slate-500">Track 365-day subscription lifecycles, monitor countdowns, and execute renewals via partner wallet or one-time funding accounts</p>
                    </div>
                </div>
            </div>

            <!-- Global Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('bsm.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold shadow-sm hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-slate-500"></i>
                    <span>BSM Dashboard</span>
                </a>
                <a href="{{ route('bsm.lifecycle') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-sm font-semibold shadow-sm hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="cpu" class="w-4 h-4 text-slate-500"></i>
                    <span>Lifecycle Desk</span>
                </a>
                <a href="{{ route('bsm.subscriptions.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0056D2] hover:bg-[#0047B3] text-white text-sm font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Filter Controls Form -->
        <form method="GET" action="{{ route('bsm.subscriptions') }}" class="mt-6 pt-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <!-- Search -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Search</label>
                <div class="relative">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="SIM, Partner, Customer..."
                           class="w-full text-xs rounded-xl border-slate-200 pl-8 pr-3 py-2 bg-slate-50 focus:border-primary focus:ring-primary">
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Subscription Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3 bg-slate-50">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active (> 30 days left)</option>
                    <option value="expiring_soon" {{ $selectedStatus === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (≤ 30 days)</option>
                    <option value="expired" {{ $selectedStatus === 'expired' ? 'selected' : '' }}>Expired (0 days left)</option>
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

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition flex items-center justify-center gap-1">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('bsm.subscriptions') }}" class="py-2 px-3 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 transition flex items-center justify-center" title="Reset Filters">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Subscriptions -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Subscriptions</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0056D2] flex items-center justify-center">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-display">{{ number_format($totalSubscriptions) }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">All activated SIM subscriptions</p>
        </div>

        <!-- Active Subscriptions -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 font-display">{{ number_format($activeCount) }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">> 30 days remaining</p>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Expiring Soon</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 font-display">{{ number_format($expiringSoonCount) }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Expires within 30 days</p>
        </div>

        <!-- Expired Subscriptions -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Expired</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 font-display">{{ number_format($expiredCount) }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Requires reactivation</p>
        </div>
    </div>

    <!-- Comprehensive Subscriptions Directory Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-sm text-slate-900 font-display">SIM Subscriptions Directory</h3>
                <p class="text-xs text-slate-500 mt-0.5">Showing {{ $sims->firstItem() ?? 0 }} to {{ $sims->lastItem() ?? 0 }} of {{ $sims->total() }} subscriptions</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">SIM Number</th>
                        <th class="py-3.5 px-4">Network / Category</th>
                        <th class="py-3.5 px-4">Partner (Owner)</th>
                        <th class="py-3.5 px-4">Customer</th>
                        <th class="py-3.5 px-4">Activation Date</th>
                        <th class="py-3.5 px-4">Expiry Date</th>
                        <th class="py-3.5 px-4">Days Left</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sims as $sim)
                        @php
                            $partner = $sim->partner ?? $sim->user;
                            $partnerName = $partner ? trim($partner->first_name . ' ' . $partner->last_name) : 'Unassigned';
                            $customerName = $sim->customer?->full_name ?? '—';
                            $customerPhone = $sim->customer?->phone_number ?? '—';
                            $daysRemaining = $sim->daysRemaining();
                            $isExpired = $sim->isSubscriptionExpired();
                            $subStatus = $sim->subscriptionStatus();
                            $fee = app(\App\Services\SimSubscriptionService::class)->getReactivationFee($sim, $partner);
                            $partnerWallet = $partner?->wallet;
                            $mainBal = $partnerWallet ? max(0, (float)$partnerWallet->balance - (float)$partnerWallet->lien_amount) : 0;
                            $bonusBal = $partnerWallet ? (float)$partnerWallet->bonus : 0;

                            $simData = [
                                'id' => $sim->id,
                                'number' => $sim->number,
                                'category' => $sim->category,
                                'provider' => strtoupper($sim->provider),
                                'partnerName' => $partnerName,
                                'partnerRole' => $partner ? str_replace('_', ' ', $partner->role) : '—',
                                'partnerEmail' => $partner?->email ?? '—',
                                'partnerId' => $partner?->id,
                                'mainBal' => $mainBal,
                                'bonusBal' => $bonusBal,
                                'fee' => $fee,
                                'formattedFee' => number_format($fee, 2),
                                'daysRemaining' => $daysRemaining,
                                'isExpired' => $isExpired,
                                'reactivateUrl' => route('bsm.subscriptions.reactivate', $sim),
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Number -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 text-sm">
                                {{ $sim->number }}
                            </td>

                            <!-- Network / Category -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase
                                        {{ $sim->provider === 'mtn' ? 'bg-amber-100 text-amber-900' : ($sim->provider === 'airtel' ? 'bg-rose-100 text-rose-900' : 'bg-emerald-100 text-emerald-900') }}">
                                        {{ $sim->provider }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-700">{{ $sim->category }}</span>
                                </div>
                            </td>

                            <!-- Partner -->
                            <td class="py-3.5 px-4">
                                @if ($partner)
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0056D2] font-bold text-[11px] flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($partner->first_name ?? 'P', 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 block leading-tight">{{ $partnerName }}</span>
                                            <span class="text-[10px] text-slate-400 capitalize block">{{ str_replace('_', ' ', $partner->role) }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Customer -->
                            <td class="py-3.5 px-4">
                                @if ($customerName !== '—')
                                    <span class="font-semibold text-slate-800 block">{{ $customerName }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono block">{{ $customerPhone }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Activated At -->
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                {{ $sim->activated_at ? $sim->activated_at->format('M d, Y') : '—' }}
                            </td>

                            <!-- Expiry Date -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-medium {{ $isExpired ? 'text-rose-600 font-bold' : 'text-slate-700' }}">
                                {{ $sim->subscription_expires_at ? $sim->subscription_expires_at->format('M d, Y') : '—' }}
                            </td>

                            <!-- Days Left (with blip) -->
                            <td class="py-3.5 px-4">
                                @if ($daysRemaining !== null)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition-all
                                        {{ $isExpired ? 'bg-rose-50 text-rose-700 border-rose-200' : ($subStatus === 'expiring_soon' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200') }}">
                                        <span class="relative flex h-2 w-2">
                                            @if (! $isExpired)
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $subStatus === 'expiring_soon' ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                                            @endif
                                            <span class="relative inline-flex rounded-full h-2 w-2 {{ $isExpired ? 'bg-rose-500' : ($subStatus === 'expiring_soon' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                        </span>
                                        <span class="whitespace-nowrap">
                                            @if ($isExpired)
                                                Expired (0d)
                                            @elseif ($daysRemaining === 0)
                                                Expires Today
                                            @elseif ($daysRemaining === 1)
                                                1 day left
                                            @else
                                                {{ $daysRemaining }} days left
                                            @endif
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $sim->subscriptionBadgeClasses() }}">
                                    {{ $sim->subscriptionStatusLabel() }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($isExpired)
                                        <button type="button"
                                                @click="openReactivate(@js($simData))"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold shadow-sm shadow-amber-500/20 transition active:scale-95 animate-pulse">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                            <span>Reactivate</span>
                                        </button>
                                    @else
                                        <button type="button"
                                                @click="openReactivate(@js($simData))"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                            <i data-lucide="refresh-cw" class="w-3 h-3 text-slate-400"></i>
                                            <span>Reactivate</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 font-semibold">
                                <i data-lucide="clock" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                                No SIM subscriptions found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($sims->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $sims->links('vendor.pagination.custom') }}
            </div>
        @endif
    </div>

    <!-- Reactivation Modal (With 2 Options) -->
    <div x-show="reactivateModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         style="display: none;"
         x-cloak>
        <div @click.away="reactivateModalOpen = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <template x-if="selectedSim">
                <div>
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-blue-500/10 via-blue-500/5 to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-600/10 text-[#0056D2] flex items-center justify-center shrink-0">
                                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 font-display text-sm">Reactivate SIM Subscription</h3>
                                <p class="text-xs text-slate-500">SIM <span class="font-mono font-bold text-slate-800" x-text="selectedSim.number"></span> (365-Day Renewal)</p>
                            </div>
                        </div>
                        <button type="button" @click="reactivateModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- SIM Overview Pill Box -->
                    <div class="px-6 pt-4 pb-2">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] text-slate-500 block">SIM Details & Owner</span>
                                <span class="text-xs font-bold text-slate-800" x-text="selectedSim.provider + ' · ' + selectedSim.category + ' (' + selectedSim.partnerName + ')'"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[11px] text-slate-500 block">Reactivation Fee</span>
                                <span class="text-sm font-extrabold text-[#0056D2]" x-text="'₦' + selectedSim.formattedFee"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs: Option 1 (Charge Partner) vs Option 2 (One-Time Account) -->
                    <div class="px-6 pt-2">
                        <div class="flex rounded-xl bg-slate-100 p-1">
                            <button type="button"
                                    @click="activeTab = 'wallet'"
                                    :class="activeTab === 'wallet' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-500 font-medium hover:text-slate-700'"
                                    class="flex-1 py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5">
                                <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                Option 1: Charge Partner
                            </button>
                            <button type="button"
                                    @click="activeTab = 'funding'"
                                    :class="activeTab === 'funding' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-500 font-medium hover:text-slate-700'"
                                    class="flex-1 py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5">
                                <i data-lucide="landmark" class="w-3.5 h-3.5"></i>
                                Option 2: One-Time Account
                            </button>
                        </div>
                    </div>

                    <!-- TAB 1: Charge Partner Wallet -->
                    <div x-show="activeTab === 'wallet'" class="p-6 space-y-4">
                        <form :action="selectedSim.reactivateUrl" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="payment_option" value="partner_wallet">

                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Choose Partner Wallet to Debit</label>
                                <div class="grid grid-cols-1 gap-2.5">
                                    <!-- Main Balance -->
                                    <label class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all"
                                           :class="walletSource === 'main' ? 'border-[#0056D2] bg-blue-50/50 ring-1 ring-[#0056D2]' : 'border-slate-200 hover:bg-slate-50'">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="wallet_source" value="main" x-model="walletSource" class="text-primary focus:ring-primary h-4 w-4">
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Partner Main Balance</span>
                                                <span class="text-[11px] text-slate-500" x-text="'Available: ₦' + Number(selectedSim.mainBal).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700" x-text="'₦' + Number(selectedSim.mainBal).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </label>

                                    <!-- Commission Balance -->
                                    <label class="relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all"
                                           :class="walletSource === 'bonus' ? 'border-[#0056D2] bg-blue-50/50 ring-1 ring-[#0056D2]' : 'border-slate-200 hover:bg-slate-50'">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="wallet_source" value="bonus" x-model="walletSource" class="text-primary focus:ring-primary h-4 w-4">
                                            <div>
                                                <span class="block text-xs font-bold text-slate-800">Partner Commission Balance</span>
                                                <span class="text-[11px] text-slate-500" x-text="'Available: ₦' + Number(selectedSim.bonusBal).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700" x-text="'₦' + Number(selectedSim.bonusBal).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Optional Note</label>
                                <input type="text" name="notes" placeholder="e.g. Authorized by BSM for field reactivation"
                                       class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-primary focus:ring-primary">
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" @click="reactivateModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-100 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0056D2] hover:bg-[#0047B3] text-white text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Debit Partner & Reactivate
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: Option 2: Provide One-Time Account for Funding Reactivation -->
                    <div x-show="activeTab === 'funding'" class="p-6 space-y-4">
                        <template x-if="!generatedAccount">
                            <div class="space-y-4 text-center py-4">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0056D2] flex items-center justify-center mx-auto">
                                    <i data-lucide="landmark" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Generate Dedicated Loma Bank Funding Account</h4>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                        Generates a one-time virtual account for SIM <strong x-text="selectedSim.number"></strong>. Give this account to the customer or partner to transfer the reactivation fee.
                                    </p>
                                </div>
                                <div class="pt-2">
                                    <button type="button"
                                            @click="generateAccount()"
                                            :disabled="isGeneratingAccount"
                                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#0056D2] to-[#003d99] hover:from-[#0047B3] hover:to-[#002e7a] text-white text-xs font-bold shadow-lg shadow-blue-500/20 transition disabled:opacity-50">
                                        <i data-lucide="plus-circle" class="w-4 h-4" :class="isGeneratingAccount ? 'animate-spin' : ''"></i>
                                        <span x-text="isGeneratingAccount ? 'Generating Account...' : 'Generate One-Time Funding Account'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="generatedAccount">
                            <div class="space-y-4 animate-in fade-in duration-200">
                                <!-- Dedicated Account Details Card -->
                                <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white shadow-xl space-y-3 relative overflow-hidden">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-blue-400">One-Time Funding Account</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">Pending Payment</span>
                                    </div>

                                    <div class="pt-1">
                                        <span class="text-xs text-slate-400 block">Bank Name</span>
                                        <span class="text-sm font-bold text-white block" x-text="generatedAccount.bank_name || 'Loma Bank'"></span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-xs text-slate-400 block">Account Number</span>
                                            <span class="text-xl sm:text-2xl font-mono font-extrabold text-white tracking-wider block" x-text="generatedAccount.account_number"></span>
                                        </div>
                                        <button type="button"
                                                @click="copy(generatedAccount.account_number, 'acc')"
                                                class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-1">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            <span x-text="copiedField === 'acc' ? 'Copied!' : 'Copy'"></span>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-between pt-2 border-t border-slate-700/60">
                                        <div>
                                            <span class="text-[11px] text-slate-400 block">Account Name</span>
                                            <span class="text-xs font-semibold text-slate-200" x-text="generatedAccount.account_name"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[11px] text-slate-400 block">Expected Amount</span>
                                            <span class="text-sm font-bold text-amber-300" x-text="'₦' + (generatedAccount.formatted_fee || Number(generatedAccount.amount).toLocaleString('en-US', {minimumFractionDigits: 2}))"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900 flex items-start gap-2">
                                    <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                                    <span>Once a bank transfer of the reactivation fee is received into this account, the SIM will be <strong>automatically reactivated</strong> for 365 days.</span>
                                </div>

                                <!-- Manual Confirmation Override -->
                                <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                                    <button type="button"
                                            @click="copy('Bank: ' + (generatedAccount.bank_name || 'Loma Bank') + '\nAccount Number: ' + generatedAccount.account_number + '\nAccount Name: ' + generatedAccount.account_name + '\nAmount: ₦' + (generatedAccount.formatted_fee || generatedAccount.amount), 'all')"
                                            class="px-3 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition flex items-center gap-1">
                                        <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                        <span x-text="copiedField === 'all' ? 'Copied Details!' : 'Copy Full Details'"></span>
                                    </button>

                                    <form :action="'{{ url('bsm/subscriptions/confirm-payment') }}/' + generatedAccount.subscription_id" method="POST">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                                            <i data-lucide="check-check" class="w-4 h-4"></i>
                                            Mark Paid Manually
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
</x-app-layout>
