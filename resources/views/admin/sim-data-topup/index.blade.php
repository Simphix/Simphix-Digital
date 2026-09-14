<x-app-layout>
    <title>SmartSIM - SIM Data Top-Up</title>

    @php
        // Shared by both the super_admin console (admin.sim-data-topup.*)
        // and the support-desk console (support-desk.sim-data-topup.*) —
        // identical eligibility (only ACTIVATED SIMs) and identical Report
        // rows either way, only the route/URL base differs.
        $onSupportDesk = request()->routeIs('support-desk.*');
        $topupIndexRoute = $onSupportDesk ? 'support-desk.sim-data-topup.index' : 'admin.sim-data-topup.index';
        $topupSearchSimsRoute = $onSupportDesk ? 'support-desk.sim-data-topup.search-sims' : 'admin.sim-data-topup.search-sims';
        $topupBase = $onSupportDesk ? '/support-desk/sim-data-topup' : '/admin/sim-data-topup';
    @endphp

    <div class="space-y-6 max-w-7xl mx-auto"
         x-data="{
            simQuery: '',
            simResults: [],
            submitting: false,

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

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">SIM Data Top-Up</h1>
                <p class="text-xs text-slate-400 mt-1">Grant a free data top-up to any activated SIM via the active vendor — no monthly limit.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Top Up a SIM -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
            <h3 class="text-sm font-bold text-slate-800 font-display">Top Up a SIM</h3>
            <div class="space-y-1.5 relative max-w-md">
                <x-input-label value="SIM Number" />
                <x-text-input type="text" x-model="simQuery" @input.debounce.400ms="searchSims()"
                              placeholder="Search by SIM number…" autocomplete="off" class="rounded-xl font-medium w-full" />
                <div x-show="simResults.length > 0" x-cloak
                     class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                    <template x-for="sim in simResults" :key="sim.id">
                        <button type="button" @click="pickSim(sim)"
                                class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-50 last:border-0">
                            <span class="block text-xs font-bold text-slate-700" x-text="sim.number"></span>
                            <span class="block text-[11px] text-slate-400" x-text="sim.category + ' · ' + sim.provider.toUpperCase()"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- This Month's Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Top-Ups This Month</span>
                <div class="mt-2 space-y-1.5">
                    @forelse ($countByType as $type => $count)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">{{ ucwords(str_replace('_', ' ', $type)) }}</span>
                            <span class="text-slate-400">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No top-ups this month.</p>
                    @endforelse
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Data Volume by Network (Completed)</span>
                <div class="mt-2 space-y-1.5">
                    @forelse ($volumeByNetwork as $network => $mb)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">{{ $network }}</span>
                            <span class="text-slate-400">{{ number_format($mb / 1024, 2) }} GB</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No completed top-ups this month.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <form method="GET" action="{{ route($topupIndexRoute) }}" class="flex flex-wrap items-end gap-3">
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
                <button type="submit" class="text-xs font-bold px-4 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors">Apply Filters</button>
            </form>
        </div>

        <!-- Recent Top-Ups -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-md font-bold text-slate-800 font-display">Recent Top-Ups</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Date</th>
                            <th class="py-4 px-6">Phone Number</th>
                            <th class="py-4 px-6">Network</th>
                            <th class="py-4 px-6">Type</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6 text-slate-400 text-xs">{{ $row->created_at->format('M d, Y H:i') }}</td>
                                <td class="py-4 px-6 font-bold text-slate-800">{{ $row->phone_number }}</td>
                                <td class="py-4 px-6">{{ $row->network }}</td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                        {{ ucwords(str_replace('_', ' ', $row->type)) }}
                                    </span>
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
                                <td class="py-4 px-6 text-slate-500 max-w-xs truncate">{{ $row->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-sm">No top-ups for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->withQueryString()->links('vendor.pagination.custom') }}
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
