<x-app-layout>
    <title>SimPhix - SIM Data Top-Up &amp; 28-Day Activation Bonuses</title>

    @php
        $onSupportDesk = request()->routeIs('support-desk.*');
        $topupIndexRoute = $onSupportDesk ? 'support-desk.sim-data-topup.index' : 'admin.sim-data-topup.index';
        $topupSearchSimsRoute = $onSupportDesk ? 'support-desk.sim-data-topup.search-sims' : 'admin.sim-data-topup.search-sims';
        $topupBase = $onSupportDesk ? '/support-desk/sim-data-topup' : '/admin/sim-data-topup';

        $topupTriggeredCompleteRoute = $onSupportDesk ? 'support-desk.sim-data-topup.triggered.complete' : 'admin.sim-data-topup.triggered.complete';
        $topupTriggeredResolveSilentlyRoute = $onSupportDesk ? 'support-desk.sim-data-topup.triggered.resolve-silently' : 'admin.sim-data-topup.triggered.resolve-silently';
        $topupTriggeredRunCheckRoute = $onSupportDesk ? 'support-desk.sim-data-topup.triggered.run-check' : 'admin.sim-data-topup.triggered.run-check';
        $topupTriggeredBulkActionRoute = $onSupportDesk ? 'support-desk.sim-data-topup.triggered.bulk-action' : 'admin.sim-data-topup.triggered.bulk-action';

        $currentTab = request('tab', $activeTab ?? 'triggered');
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto"
         x-data="{
            activeTab: '{{ $currentTab }}',
            simQuery: '',
            simResults: [],
            submitting: false,
            selectedGrants: [],
            selectAll: false,

            toggleSelectAll() {
                if (this.selectAll) {
                    this.selectedGrants = Array.from(document.querySelectorAll('.grant-checkbox')).map(el => el.value);
                } else {
                    this.selectedGrants = [];
                }
            },

            async searchSims() {
                if (this.simQuery.trim().length < 2) { this.simResults = []; return; }
                const res = await fetch('{{ route($topupSearchSimsRoute) }}?q=' + encodeURIComponent(this.simQuery));
                this.simResults = await res.json();
            },

            async pickSim(sim) {
                this.simResults = [];
                this.simQuery = sim.number;
                if (this.submitting) return;
                this.submitting = true;

                const [reviewRes, plansRes] = await Promise.all([
                    fetch(`{{ $topupBase }}/sims/${sim.id}/usage-review`),
                    fetch(`{{ $topupBase }}/sims/${sim.id}/plans`),
                ]);
                const review = await reviewRes.json();
                const plans = await plansRes.json();
                this.submitting = false;

                openTopUpReviewModal(sim, review, plans, '{{ csrf_token() }}');
            }
         }">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">SIM Data Top-Up Desk</h1>
                <p class="text-xs text-slate-500 mt-1">Review 28-day triggered activation bonuses, complete vendor top-ups, resolve silently, or grant manual data bundles.</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route($topupTriggeredRunCheckRoute) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-primary"></i>
                        <span>Run 28-Day Check Now</span>
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50/80 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-xs">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <span class="text-xs md:text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50/80 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 shadow-xs">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                <span class="text-xs md:text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-px">
            <a href="{{ route($topupIndexRoute, ['tab' => 'triggered']) }}"
               @click.prevent="activeTab = 'triggered'; history.pushState(null, '', '{{ route($topupIndexRoute, ['tab' => 'triggered']) }}')"
               :class="activeTab === 'triggered' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-semibold'"
               class="inline-flex items-center gap-2 py-3 px-4 border-b-2 text-xs md:text-sm transition-colors cursor-pointer">
                <i data-lucide="bell-ring" class="w-4 h-4"></i>
                <span>Triggered 28-Day Bonuses</span>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $pendingTriggeredCount > 0 ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-slate-100 text-slate-600' }}">
                    {{ number_format($pendingTriggeredCount) }}
                </span>
            </a>

            <a href="{{ route($topupIndexRoute, ['tab' => 'manual']) }}"
               @click.prevent="activeTab = 'manual'; history.pushState(null, '', '{{ route($topupIndexRoute, ['tab' => 'manual']) }}')"
               :class="activeTab === 'manual' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-semibold'"
               class="inline-flex items-center gap-2 py-3 px-4 border-b-2 text-xs md:text-sm transition-colors cursor-pointer">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span>Direct SIM Top-Up</span>
            </a>

            <a href="{{ route($topupIndexRoute, ['tab' => 'history']) }}"
               @click.prevent="activeTab = 'history'; history.pushState(null, '', '{{ route($topupIndexRoute, ['tab' => 'history']) }}')"
               :class="activeTab === 'history' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-semibold'"
               class="inline-flex items-center gap-2 py-3 px-4 border-b-2 text-xs md:text-sm transition-colors cursor-pointer">
                <i data-lucide="history" class="w-4 h-4"></i>
                <span>Top-Up History &amp; Metrics</span>
            </a>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: TRIGGERED 28-DAY ACTIVATION BONUSES -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'triggered'" x-cloak class="space-y-4">
            <!-- Filter & Bulk Actions Bar -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <form method="GET" action="{{ route($topupIndexRoute) }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="triggered">
                    <div class="relative">
                        <input type="text" name="triggered_search" value="{{ $triggeredSearch }}" placeholder="Filter by SIM number…"
                               class="text-xs rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 w-52">
                    </div>
                    <div>
                        <select name="triggered_network" class="text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700">
                            <option value="">All Networks</option>
                            @foreach ($networks as $network)
                                <option value="{{ $network }}" @selected(strtoupper($triggeredNetwork ?? '') === strtoupper($network))>{{ strtoupper($network) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="text-xs font-bold px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Filter
                    </button>
                    @if ($triggeredSearch || $triggeredNetwork)
                        <a href="{{ route($topupIndexRoute, ['tab' => 'triggered']) }}" class="text-xs text-slate-400 hover:text-slate-600 underline">Reset</a>
                    @endif
                </form>

                <!-- Bulk Actions when checkboxes are checked -->
                <div x-show="selectedGrants.length > 0" x-cloak class="flex items-center gap-2 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl">
                    <span class="text-xs font-bold text-amber-900" x-text="selectedGrants.length + ' selected'"></span>
                    <form method="POST" action="{{ route($topupTriggeredBulkActionRoute) }}" class="inline-flex gap-1.5"
                          @submit="return confirm('Execute bulk action on ' + selectedGrants.length + ' selected SIM(s)?')">
                        @csrf
                        <template x-for="id in selectedGrants" :key="id">
                            <input type="hidden" name="grant_ids[]" :value="id">
                        </template>
                        <button type="submit" name="action" value="complete"
                                class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition">
                            Bulk Complete (Send Data)
                        </button>
                        <button type="submit" name="action" value="resolve_silent"
                                class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300 transition">
                            Bulk Resolve Silently
                        </button>
                    </form>
                </div>
            </div>

            <!-- Triggered SIMs Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 font-display">SIMs Due for 28-Day Activation Bonus Top-Up</h2>
                        <span class="text-xs text-slate-400">Triggered automatically every 28 days by <code class="font-mono text-[11px] bg-slate-100 px-1 py-0.5 rounded">sim:grant-bulk-activation-bonus</code>.</span>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">{{ $triggeredGrants->total() }} pending action(s)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/60">
                                <th class="py-3.5 px-4 w-10">
                                    <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-slate-300 text-primary focus:ring-primary">
                                </th>
                                <th class="py-3.5 px-4">SIM Number &amp; Provider</th>
                                <th class="py-3.5 px-4">Assigned Holder</th>
                                <th class="py-3.5 px-4">Last Top-Up / Activation</th>
                                <th class="py-3.5 px-4">Activation Bonus Count</th>
                                <th class="py-3.5 px-4">Bonus Plan</th>
                                <th class="py-3.5 px-4">Triggered At</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            @forelse ($triggeredGrants as $grant)
                                @php
                                    $sim = $grant->sim;
                                    $provider = strtolower($sim->provider ?? '');
                                    $plan = $bonusSettings[$provider]->plan ?? null;
                                    $holder = $sim->user ?? $sim->partner ?? $sim->business;
                                    $lastTopUpDate = \App\Support\SimBonusPeriod::lastTopUpDate($sim);
                                    $daysElapsed = \App\Support\SimBonusPeriod::daysSinceLastTopUp($sim);
                                    $completedCount = $sim->activationBonusCount();
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3.5 px-4">
                                        <input type="checkbox" :value="{{ $grant->id }}" x-model="selectedGrants" class="grant-checkbox rounded border-slate-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-slate-900 block font-mono text-sm">{{ $sim->number }}</span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                                {{ strtoupper($sim->provider) }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">{{ $sim->category }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($holder)
                                            <span class="font-semibold text-slate-800 block">{{ $holder->first_name }} {{ $holder->last_name }}</span>
                                            <span class="text-[10px] text-slate-400 block">{{ $holder->email ?? $holder->phone ?? '—' }}</span>
                                        @else
                                            <span class="text-slate-400 italic">No assigned holder</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($lastTopUpDate)
                                            <span class="font-medium text-slate-700 block">{{ $lastTopUpDate->format('M d, Y') }}</span>
                                            <span class="text-[11px] font-bold text-amber-700 mt-0.5 block">{{ $daysElapsed }} days elapsed</span>
                                        @else
                                            <span class="text-slate-400 italic">Activation date: {{ $sim->activated_at?->format('M d, Y') ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <!-- Activation Bonus Progress Count (e.g. 1/12 immediately after activation, now pending 2/12) -->
                                        <div class="inline-flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-lg text-xs font-black font-mono bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $grant->cycleLabel() }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                Pending
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 block mt-1">
                                            {{ $completedCount }}/12 previously granted
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($plan)
                                            <span class="font-bold text-slate-800 block">{{ $plan->size }} {{ $plan->plan_type }}</span>
                                            <span class="text-[10px] text-slate-400">Vendor: {{ strtoupper($plan->provider) }}</span>
                                        @else
                                            <span class="text-rose-600 font-semibold text-[11px]">No bonus plan set</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                        {{ ($grant->triggered_at ?? $grant->created_at)->format('M d, Y H:i') }}
                                    </td>
                                    <td class="py-3.5 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Complete Action (Send Data) -->
                                            <form method="POST" action="{{ route($topupTriggeredCompleteRoute, $grant->id) }}"
                                                  onsubmit="return confirm('Dispatch {{ $plan?->size ?? 'activation bonus' }} data to {{ $sim->number }} (Cycle {{ $grant->cycleLabel() }}) via active vendor?')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 text-[11px] font-bold px-3 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition shadow-xs">
                                                    <i data-lucide="send" class="w-3 h-3"></i>
                                                    <span>Send Data</span>
                                                </button>
                                            </form>

                                            <!-- Resolve Silently -->
                                            <form method="POST" action="{{ route($topupTriggeredResolveSilentlyRoute, $grant->id) }}"
                                                  onsubmit="return confirm('Resolve silently without dispatching data? This marks Cycle {{ $grant->cycleLabel() }} as fulfilled without calling vendor API.')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition">
                                                    <i data-lucide="check" class="w-3 h-3 text-slate-400"></i>
                                                    <span>Resolve Silently</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 px-4">
                                        <div class="max-w-sm mx-auto text-center space-y-2">
                                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
                                                <i data-lucide="check-check" class="w-5 h-5"></i>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-800">All 28-Day Bonuses are Up to Date!</h3>
                                            <p class="text-xs text-slate-400">No SIMs are currently waiting for 28-day activation bonus approval. The automated check runs nightly at 1:00 AM WAT, or you can trigger a manual check anytime.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($triggeredGrants->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $triggeredGrants->appends(['tab' => 'triggered'])->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: DIRECT MANUAL SIM TOP-UP            -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'manual'" x-cloak class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 font-display">Direct SIM Search &amp; Manual Top-Up</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Search for any active SIM number to inspect its top-up history and grant a manual data plan.</p>
                </div>
                <div class="space-y-1.5 relative max-w-md">
                    <x-input-label value="SIM Number" />
                    <x-text-input type="text" x-model="simQuery" @input.debounce.400ms="searchSims()"
                                  placeholder="Type SIM number (e.g. 080...)" autocomplete="off" class="rounded-xl font-medium w-full text-sm" />
                    <div x-show="simResults.length > 0" x-cloak
                         class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                        <template x-for="sim in simResults" :key="sim.id">
                            <button type="button" @click="pickSim(sim)"
                                    class="w-full text-left px-3 py-2.5 hover:bg-slate-50 border-b border-slate-100 last:border-0 transition-colors">
                                <span class="block text-xs font-bold text-slate-800 font-mono" x-text="sim.number"></span>
                                <span class="block text-[11px] text-slate-400" x-text="sim.category + ' · ' + sim.provider.toUpperCase()"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: TOP-UP HISTORY & METRICS            -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'history'" x-cloak class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Top-Ups This Month</span>
                    <div class="mt-3 space-y-1.5">
                        @forelse ($countByType as $type => $count)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600 font-semibold">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                                <span class="font-bold text-slate-800 font-mono">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No top-ups recorded this month.</p>
                        @endforelse
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Data Volume by Network</span>
                    <div class="mt-3 space-y-1.5">
                        @forelse ($volumeByNetwork as $network => $mb)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600 font-semibold">{{ $network }}</span>
                                <span class="font-bold text-slate-800 font-mono">{{ number_format($mb / 1024, 2) }} GB</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No completed volume this month.</p>
                        @endforelse
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Bonus Grants Fulfilled</span>
                    <p class="text-2xl font-black text-slate-800 font-display mt-2">{{ number_format($completedBonusesThisMonth) }}</p>
                    <span class="text-[11px] text-slate-400 mt-1 block">Completed or resolved this month</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
                <form method="GET" action="{{ route($topupIndexRoute) }}" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="tab" value="history">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">From</label>
                        <input type="date" name="date_start" value="{{ $filters['date_start']?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">To</label>
                        <input type="date" name="date_end" value="{{ $filters['date_end']?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Network</label>
                        <select name="network" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[140px]">
                            <option value="">All networks</option>
                            @foreach ($networks as $network)
                                <option value="{{ $network }}" @selected($filters['network'] === $network)>{{ $network }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Type</label>
                        <select name="type" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[160px]">
                            <option value="">All types</option>
                            <option value="admin_data_topup" @selected($filters['type'] === 'admin_data_topup')>Admin Top-Up</option>
                            <option value="data" @selected($filters['type'] === 'data')>Paid Purchase</option>
                            <option value="activation_bonus_data" @selected($filters['type'] === 'activation_bonus_data')>Activation Bonus</option>
                        </select>
                    </div>
                    <button type="submit" class="text-xs font-bold px-4 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors shadow-2xs">
                        Apply Filters
                    </button>
                    @if ($filters['date_start'] || $filters['date_end'] || $filters['network'] || $filters['type'])
                        <a href="{{ route($topupIndexRoute, ['tab' => 'history']) }}" class="text-xs text-slate-400 hover:text-slate-600 underline self-center">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Recent Top-Ups Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800 font-display">Recent Top-Up Transactions</h3>
                    <span class="text-xs text-slate-400">{{ $rows->total() }} transaction(s) recorded</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6">Date</th>
                                <th class="py-4 px-6">Phone Number</th>
                                <th class="py-4 px-6">Network</th>
                                <th class="py-4 px-6">Type</th>
                                <th class="py-4 px-6">Bonus Count</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            @forelse ($rows as $row)
                                @php
                                    $rowSim = \App\Models\Sim::where('number', $row->phone_number)->first();
                                @endphp
                                <tr class="hover:bg-slate-50/30 transition-colors">
                                    <td class="py-4 px-6 text-slate-400">{{ $row->created_at->format('M d, Y H:i') }}</td>
                                    <td class="py-4 px-6 font-bold text-slate-800 font-mono text-xs">{{ $row->phone_number }}</td>
                                    <td class="py-4 px-6 font-semibold">{{ $row->network }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                            {{ ucwords(str_replace('_', ' ', $row->type)) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if ($rowSim)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                                {{ $rowSim->activationBonusProgress() }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        @if ($row->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">Completed</span>
                                        @elseif ($row->status === 'processing')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-700">Processing</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-700">Failed</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-slate-500 max-w-xs truncate" title="{{ $row->description }}">{{ $row->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-400 text-xs">No top-ups recorded matching this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($rows->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $rows->appends(['tab' => 'history'])->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openTopUpReviewModal(sim, review, plans, csrfToken) {
                if (plans.length === 0) {
                    Swal.fire('No Plans Available', `No enabled data plans found for ${sim.provider.toUpperCase()} on the currently active vendor.`, 'warning');
                    return;
                }

                const historyHtml = review.count > 0
                    ? review.history.map(h => `
                        <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100">
                            <span>${h.date} · ${h.type.replace(/_/g, ' ')}</span>
                            <span class="text-slate-400">${h.description}</span>
                        </div>
                    `).join('')
                    : '<p class="text-xs text-slate-400">No prior top-ups in the last 30 days.</p>';

                const plansOptionsHtml = plans.map(p => `
                    <option value="${p.data_id}">${p.size} ${p.plan_type} (${p.validity})</option>
                `).join('');

                Swal.fire({
                    title: `Top Up ${sim.number}`,
                    html: `
                        <div class="text-left text-sm">
                            <p class="font-bold mb-1">Last 30 days: ${review.count} top-up(s), ${(review.total_mb / 1024).toFixed(2)} GB total</p>
                            <div class="max-h-32 overflow-y-auto mb-3">${historyHtml}</div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Choose a plan</label>
                            <select id="topup-plan-select" class="w-full text-xs border border-slate-200 rounded-lg p-2">
                                ${plansOptionsHtml}
                            </select>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#0056D2',
                    confirmButtonText: 'Confirm Top-Up',
                    preConfirm: () => {
                        const plan = document.getElementById('topup-plan-select').value;
                        if (!plan) {
                            Swal.showValidationMessage('Please select a plan.');
                            return false;
                        }
                        return plan;
                    },
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = `{{ $topupBase }}/sims/${sim.id}/topup`;
                    f.innerHTML = `
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="plan" value="${result.value}">
                    `;
                    document.body.appendChild(f);
                    f.submit();
                });
            }
        </script>
    @endpush
</x-app-layout>
