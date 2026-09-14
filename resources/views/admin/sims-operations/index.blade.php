<x-app-layout>
    <div class="space-y-6 max-w-7xl mx-auto" x-data="{ showCustom: {{ $range === 'custom' ? 'true' : 'false' }}, activeSection: 'activations' }">

        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">SIMs Operations</h1>
            <p class="text-xs text-slate-400 mt-1">Activation metrics filterable by date, region, state, senatorial district, and user.</p>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl flex items-center gap-2 text-xs font-semibold">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Section Tabs -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 flex gap-1">
            <button type="button" @click="activeSection = 'activations'" :class="activeSection === 'activations' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="activity" class="w-4 h-4"></i> Activations
            </button>
            <button type="button" @click="activeSection = 'activation-bonus'" :class="activeSection === 'activation-bonus' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="gift" class="w-4 h-4"></i> Activation Bonus
            </button>
        </div>

        <div x-show="activeSection === 'activations'" class="space-y-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <form method="GET" action="{{ route('admin.sims-operations.index') }}" class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
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
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-3 pt-3 border-t border-slate-100">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Region</label>
                        <select name="region_id" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[160px]">
                            <option value="">All regions</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}" @selected($filters['region_id'] == $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">State</label>
                        <select name="state_id" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[160px]">
                            <option value="">All states</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected($filters['state_id'] == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Senatorial District</label>
                        <select name="district_id" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[160px]">
                            <option value="">All districts</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district->id }}" @selected($filters['district_id'] == $district->id)>{{ $district->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">User</label>
                        <select name="user_id" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[180px]">
                            <option value="">All users</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected($filters['user_id'] == $user->id)>{{ trim($user->first_name . ' ' . $user->last_name) }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="text-xs font-bold px-4 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors">Apply Filters</button>
                    <a href="{{ route('admin.sims-operations.export', request()->query()) }}" class="text-xs font-bold px-4 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        Export Filtered Activations
                    </a>
                    <a href="{{ route('admin.sims-operations.activated-numbers.export') }}" class="text-xs font-bold px-4 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                        <i data-lucide="download" class="w-3.5 h-3.5 shrink-0"></i>
                        Export All Activated Numbers
                    </a>
                </div>
            </form>
        </div>

        <!-- Nightly Activated-SIMs Export Email -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <form method="POST" action="{{ route('admin.sims-operations.export-settings.update') }}" class="flex flex-col md:flex-row md:items-end gap-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-3 shrink-0">
                    <x-toggle-switch name="is_active" :checked="old('is_active', $exportSettings->is_active)" />
                    <div>
                        <span class="block text-xs font-bold text-slate-700">Nightly Export Email</span>
                        <span class="block text-[11px] text-slate-400">Emails every activated SIM number each night (<code class="text-[10px]">sims:export-activated</code>).</span>
                    </div>
                </div>

                <div class="flex-grow">
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Recipient Email</label>
                    <input type="email" name="recipient_email" required
                           value="{{ old('recipient_email', $exportSettings->recipient_email) }}"
                           placeholder="ops@smartsimsub.com"
                           class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('recipient_email')
                        <span class="text-[11px] text-rose-600 font-semibold">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="text-xs font-bold px-4 py-2 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors shrink-0">Save</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Activations</span>
                <p class="text-3xl font-extrabold text-slate-800 font-display mt-1">{{ number_format($totalActivations) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $periodStart }} — {{ $periodEnd }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:col-span-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">By Jurisdiction</span>
                <div class="mt-2 space-y-1.5">
                    @forelse ($byJurisdiction as $label => $count)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">{{ $label }}</span>
                            <span class="text-slate-400">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No activations in this range.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 lg:col-span-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Activation Trend</span>
                <canvas id="activationTrendChart" class="mt-3" height="90"></canvas>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">By Category</span>
                <canvas id="byCategoryChart" class="mt-3" height="120"></canvas>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">By Provider</span>
            <canvas id="byProviderChart" class="mt-3" height="60"></canvas>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-md font-bold text-slate-800 font-display">Activation Events</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Date</th>
                            <th class="py-4 px-6">Number</th>
                            <th class="py-4 px-6">Category</th>
                            <th class="py-4 px-6">Provider</th>
                            <th class="py-4 px-6">Activated For</th>
                            <th class="py-4 px-6">Jurisdiction</th>
                            <th class="py-4 px-6">Activated By</th>
                            <th class="py-4 px-6 text-right">SIM Record</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($events as $history)
                            @php $sim = $history->sim; @endphp
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6 text-slate-400 text-xs">{{ $history->created_at->format('M d, Y H:i') }}</td>
                                <td class="py-4 px-6 font-bold text-slate-800">{{ $sim?->number ?? '—' }}</td>
                                <td class="py-4 px-6">{{ $sim?->category ?? '—' }}</td>
                                <td class="py-4 px-6">{{ strtoupper($sim?->provider ?? '—') }}</td>
                                <td class="py-4 px-6">{{ $sim?->user ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : '—' }}</td>
                                <td class="py-4 px-6 text-slate-500">
                                    @php
                                        $jurisdiction = $sim?->coordinator?->districtAsCoordinator?->state?->region
                                            ?? $sim?->regionalManager?->regionAsManager;
                                    @endphp
                                    {{ $jurisdiction?->name ?? '—' }}
                                </td>
                                <td class="py-4 px-6">{{ $history->actor ? trim($history->actor->first_name . ' ' . $history->actor->last_name) : '—' }}</td>
                                <td class="py-4 px-6 text-right">
                                    @if ($sim)
                                        <a href="{{ route('admin.sim-plan.index', ['search' => $sim->number]) }}" class="text-xs font-bold text-primary hover:underline">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400 text-sm">No activation events for this range/filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $events->withQueryString()->links('vendor.pagination.custom') }}
        </div>

        </div>

        <!-- Section: Activation Bonus Operations -->
        <div x-show="activeSection === 'activation-bonus'" x-cloak class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <form method="GET" action="{{ route('admin.sims-operations.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">From</label>
                        <input type="date" name="bonus_date_start" value="{{ $bonusFilters['date_start']?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">To</label>
                        <input type="date" name="bonus_date_end" value="{{ $bonusFilters['date_end']?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                               class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Phone Number</label>
                        <input type="text" name="bonus_number" value="{{ $bonusFilters['number'] }}" placeholder="Search phone number…"
                               class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[180px] focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Status</label>
                        <select name="bonus_status" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[140px]">
                            <option value="">All statuses</option>
                            <option value="completed" @selected($bonusFilters['status'] === 'completed')>Completed</option>
                            <option value="failed" @selected($bonusFilters['status'] === 'failed')>Failed</option>
                            <option value="retrying" @selected($bonusFilters['status'] === 'retrying')>Retrying</option>
                        </select>
                    </div>
                    <button type="submit" class="text-xs font-bold px-4 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors">Apply Filters</button>
                    <a href="{{ route('admin.sims-operations.activation-bonus.export', request()->query()) }}" class="text-xs font-bold px-4 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        Export CSV
                    </a>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-md font-bold text-slate-800 font-display">Activation Bonus Operations</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6">Date</th>
                                <th class="py-4 px-6">Phone Number</th>
                                <th class="py-4 px-6">Network</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6">Amount</th>
                                <th class="py-4 px-6">Reason</th>
                                <th class="py-4 px-6">Owner</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                            @forelse ($bonusReports as $report)
                                <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                    <td class="py-4 px-6 text-slate-400 text-xs">{{ $report->created_at->format('M d, Y H:i') }}</td>
                                    <td class="py-4 px-6 font-bold text-slate-800">{{ $report->phone_number }}</td>
                                    <td class="py-4 px-6 uppercase">{{ $report->network }}</td>
                                    <td class="py-4 px-6">
                                        @if ($report->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">Completed</span>
                                        @elseif ($report->status === 'retrying')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-700">Retrying…</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-700">Failed</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">₦{{ number_format($report->amount, 2) }}</td>
                                    <td class="py-4 px-6 text-slate-500 max-w-xs">{{ $report->description }}</td>
                                    <td class="py-4 px-6">{{ $report->user ? trim($report->user->first_name . ' ' . $report->user->last_name) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-400 text-sm">No activation bonus operations for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $bonusReports->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const trendData = {{ Illuminate\Support\Js::from($trend->pluck('count', 'day')) }};
                const categoryData = {{ Illuminate\Support\Js::from($byCategory->pluck('count', 'label')) }};
                const providerData = {{ Illuminate\Support\Js::from($byProvider->pluck('count', 'label')) }};

                new Chart(document.getElementById('activationTrendChart'), {
                    type: 'line',
                    data: {
                        labels: Object.keys(trendData),
                        datasets: [{ label: 'Activations', data: Object.values(trendData), borderColor: '#0056D2', backgroundColor: 'rgba(0,86,210,0.08)', fill: true, tension: 0.3 }],
                    },
                    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                });

                new Chart(document.getElementById('byCategoryChart'), {
                    type: 'doughnut',
                    data: { labels: Object.keys(categoryData), datasets: [{ data: Object.values(categoryData), backgroundColor: ['#0056D2', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6'] }] },
                    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } },
                });

                new Chart(document.getElementById('byProviderChart'), {
                    type: 'bar',
                    data: { labels: Object.keys(providerData), datasets: [{ label: 'Activations', data: Object.values(providerData), backgroundColor: '#0056D2' }] },
                    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                });
            });
        </script>
    @endpush
</x-app-layout>
