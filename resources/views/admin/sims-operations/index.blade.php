<x-app-layout>
    <div class="space-y-6 max-w-7xl mx-auto" x-data="{
        showCustom: {{ $range === 'custom' ? 'true' : 'false' }},
        selectedRange: '{{ $range }}',
        activeSection: '{{ request('section') === 'activation-bonus' || request()->hasAny(['bonus_date_start', 'bonus_date_end', 'bonus_number', 'bonus_status', 'bonus_network', 'bonus_page']) ? 'activation-bonus' : 'activations' }}',
        selectedRegion: '{{ $filters['region_id'] ?? '' }}',
        selectedState: '{{ $filters['state_id'] ?? '' }}'
    }">

        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">SIMs Operations</h1>
            <p class="text-xs text-slate-400 mt-1">Activation metrics filterable by date, network provider, category, region, state, senatorial district, and user.</p>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl flex items-center gap-2 text-xs font-semibold">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Section Tabs -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 flex gap-1">
            <button type="button" @click="activeSection = 'activations'; const u = new URL(window.location); u.searchParams.set('section', 'activations'); window.history.replaceState({}, '', u);" :class="activeSection === 'activations' ? 'bg-[#0056D2] text-white shadow-xs' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="activity" class="w-4 h-4"></i> Activations
            </button>
            <button type="button" @click="activeSection = 'activation-bonus'; const u = new URL(window.location); u.searchParams.set('section', 'activation-bonus'); window.history.replaceState({}, '', u);" :class="activeSection === 'activation-bonus' ? 'bg-[#0056D2] text-white shadow-xs' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="gift" class="w-4 h-4"></i> Activation Bonus
            </button>
        </div>

        <div x-show="activeSection === 'activations'" class="space-y-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-4">
            <form method="GET" action="{{ route('admin.sims-operations.index') }}" class="space-y-4">
                <input type="hidden" name="section" value="activations">
                <input type="hidden" name="range" :value="selectedRange">

                <!-- Time Range Controls -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-semibold text-slate-400 mr-1 flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            Period:
                        </span>
                        <button type="submit" @click="selectedRange = 'today'; showCustom = false"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors shadow-2xs {{ $range === 'today' ? 'bg-[#0056D2] text-white border-[#0056D2]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Today</button>
                        <button type="submit" @click="selectedRange = '3days'; showCustom = false"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors shadow-2xs {{ $range === '3days' ? 'bg-[#0056D2] text-white border-[#0056D2]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">3 Days</button>
                        <button type="submit" @click="selectedRange = '7days'; showCustom = false"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors shadow-2xs {{ $range === '7days' ? 'bg-[#0056D2] text-white border-[#0056D2]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">7 Days</button>
                        <button type="submit" @click="selectedRange = '30days'; showCustom = false"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors shadow-2xs {{ $range === '30days' ? 'bg-[#0056D2] text-white border-[#0056D2]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">30 Days</button>
                        <button type="submit" @click="selectedRange = 'all'; showCustom = false"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors shadow-2xs {{ $range === 'all' ? 'bg-[#0056D2] text-white border-[#0056D2]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">All Time</button>
                        <button type="button" @click="showCustom = !showCustom; selectedRange = 'custom'"
                                class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors shadow-2xs {{ $range === 'custom' ? 'bg-[#0056D2] text-white border-[#0056D2]' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Custom Range</button>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.sims-operations.export', request()->query()) }}" class="text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-2xs">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Export Filtered CSV
                        </a>
                        <a href="{{ route('admin.sims-operations.activated-numbers.export') }}" class="text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5 shadow-2xs">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Export All Numbers
                        </a>
                    </div>
                </div>

                <!-- Custom Range Picker Inputs -->
                <div x-show="showCustom" x-cloak class="p-3 bg-slate-50/70 border border-slate-100 rounded-xl flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500 font-semibold">From:</span>
                    <input type="date" name="period_start" value="{{ $periodStart }}" max="{{ now()->format('Y-m-d') }}"
                           class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20 bg-white">
                    <span class="text-xs text-slate-400 font-medium">to</span>
                    <input type="date" name="period_end" value="{{ $periodEnd }}" max="{{ now()->format('Y-m-d') }}"
                           class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20 bg-white">
                    <button type="submit" @click="selectedRange = 'custom'" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#0056D2] text-white hover:bg-[#0049b8] transition-colors shadow-xs">Apply Date Range</button>
                </div>

                <!-- Detailed Filters Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2.5 pt-3 border-t border-slate-100">
                    <!-- Search Input -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Search SIM / User</label>
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="SIM number, ICCID, holder…"
                                   class="w-full text-xs rounded-lg border border-slate-200 pl-8 pr-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20 bg-white">
                        </div>
                    </div>

                    <!-- Provider Filter -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Provider</label>
                        <select name="provider" class="w-full text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                            <option value="">All providers</option>
                            @foreach ($providers as $prov)
                                <option value="{{ $prov }}" @selected(($filters['provider'] ?? '') === $prov)>{{ strtoupper($prov) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Category</label>
                        <select name="category" class="w-full text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                            <option value="">All categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Region Filter -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Region</label>
                        <select name="region_id" x-model="selectedRegion" class="w-full text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                            <option value="">All regions</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}" @selected($filters['region_id'] == $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">State</label>
                        <select name="state_id" x-model="selectedState" class="w-full text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                            <option value="">All states</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" data-region="{{ $state->region_id }}"
                                        x-show="!selectedRegion || selectedRegion == '{{ $state->region_id }}'"
                                        @selected($filters['state_id'] == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- User Filter & Apply -->
                    <div class="flex items-end gap-1.5">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Holder</label>
                            <select name="user_id" class="w-full text-xs rounded-lg border border-slate-200 px-2 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                                <option value="">All holders</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected($filters['user_id'] == $user->id)>{{ trim($user->first_name . ' ' . $user->last_name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#0056D2] text-white hover:bg-[#0049b8] transition-colors shadow-xs h-[32px] flex items-center justify-center shrink-0">
                            Filter
                        </button>
                    </div>
                </div>

                @php
                    $hasActiveFilters = $filters['region_id'] || $filters['state_id'] || $filters['district_id'] || $filters['user_id'] || ($filters['provider'] ?? null) || ($filters['category'] ?? null) || ($filters['search'] ?? null) || $range !== '7days';
                @endphp
                @if ($hasActiveFilters)
                    <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-xs">
                        <span class="text-slate-400 font-semibold flex items-center gap-1 text-[11px]">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-[#0056D2]"></i>
                            Filtered By:
                        </span>
                        @if ($range !== '7days')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                Period: {{ $range === 'custom' ? ($periodStart . ' to ' . $periodEnd) : ucfirst($range) }}
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except(['range', 'period_start', 'period_end']), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        @if ($filters['provider'] ?? null)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                Provider: {{ strtoupper($filters['provider']) }}
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('provider'), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        @if ($filters['category'] ?? null)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                Category: {{ $filters['category'] }}
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('category'), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        @if ($filters['search'] ?? null)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                Search: "{{ $filters['search'] }}"
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('search'), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        @if ($filters['region_id'])
                            @php $reg = $regions->firstWhere('id', $filters['region_id']); @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                Region: {{ $reg?->name ?? $filters['region_id'] }}
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('region_id'), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        @if ($filters['state_id'])
                            @php $st = $states->firstWhere('id', $filters['state_id']); @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                State: {{ $st?->name ?? $filters['state_id'] }}
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('state_id'), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        @if ($filters['user_id'])
                            @php $u = $users->firstWhere('id', $filters['user_id']); @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                Holder: {{ $u ? trim($u->first_name . ' ' . $u->last_name) : $filters['user_id'] }}
                                <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('user_id'), ['section' => 'activations'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                            </span>
                        @endif
                        <a href="{{ route('admin.sims-operations.index', ['section' => 'activations']) }}"
                           class="text-[11px] text-slate-500 hover:text-rose-600 font-semibold underline ml-1"
                           title="Reset all filters">
                            Clear all
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Nightly Activated-SIMs Export Email & On-Demand Dispatch -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                        <i data-lucide="mail-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 font-display">Nightly Activated-SIMs Automated Export</h2>
                        <span class="text-xs text-slate-500">Automatically scheduled for <strong>12:00 AM WAT (Midnight)</strong> every night via <code class="text-[11px] bg-slate-100 px-1 py-0.5 rounded font-mono">sims:export-activated</code>.</span>
                    </div>
                </div>
                @if ($exportSettings->is_active && $exportSettings->recipient_email)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active &amp; Scheduled
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        Export Suspended / Inactive
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.sims-operations.export-settings.update') }}" class="flex flex-col md:flex-row md:items-end gap-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-3 shrink-0">
                    <x-toggle-switch name="is_active" :checked="old('is_active', $exportSettings->is_active)" />
                    <div>
                        <span class="block text-xs font-bold text-slate-700">Nightly Automation</span>
                        <span class="block text-[11px] text-slate-400">Toggle automatic daily dispatch</span>
                    </div>
                </div>

                <div class="flex-grow">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Recipient Email(s) <span class="text-[10px] text-slate-400 font-normal">(Separate multiple addresses with commas)</span></label>
                    <input type="text" name="recipient_email" required
                           value="{{ old('recipient_email', $exportSettings->recipient_email) }}"
                           placeholder="ops@simphix.com, management@simphix.com"
                           class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('recipient_email')
                        <span class="text-[11px] text-rose-600 font-semibold block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="text-xs font-bold px-4 py-2 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors shrink-0 shadow-xs">
                        Save Settings
                    </button>
                </div>
            </form>

            <!-- Execution Status & Delivery Health -->
            <div class="border-t border-slate-100 pt-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-xl {{ $exportSettings->last_export_status === 'failed' ? 'bg-rose-50/70 border border-rose-100' : ($exportSettings->last_export_status === 'success' ? 'bg-emerald-50/50 border border-emerald-100' : 'bg-slate-50/70 border border-slate-100') }}">
                    <div class="flex items-start gap-2.5">
                        <div class="mt-0.5">
                            @if ($exportSettings->last_export_status === 'success')
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            @elseif ($exportSettings->last_export_status === 'failed')
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block animate-ping"></span>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 inline-block"></span>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-800">Latest Export Run:</span>
                                @if ($exportSettings->last_attempted_at)
                                    <span class="text-xs font-semibold text-slate-600">{{ $exportSettings->last_attempted_at->timezone('Africa/Lagos')->format('M d, Y h:i A') }} WAT</span>
                                    @if ($exportSettings->last_export_status === 'success')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Delivered ({{ $exportSettings->last_export_count }} SIMs)</span>
                                    @elseif ($exportSettings->last_export_status === 'failed')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Delivery Failed</span>
                                    @elseif ($exportSettings->last_export_status === 'skipped')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Skipped</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-500 italic">No runs recorded yet (will execute at 12:00 AM WAT or upon manual test).</span>
                                @endif
                            </div>
                            @if ($exportSettings->last_export_message)
                                <p class="text-[11px] text-slate-600 mt-1 font-mono break-all">{{ $exportSettings->last_export_message }}</p>
                            @endif
                        </div>
                    </div>
                    @if ($exportSettings->last_export_recipient)
                        <div class="text-right sm:text-right text-[11px] text-slate-500 shrink-0">
                            Sent to: <span class="font-semibold text-slate-700">{{ $exportSettings->last_export_recipient }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- On-Demand Dispatch / Test Delivery -->
            <div class="border-t border-slate-100 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/70 p-3 rounded-xl">
                <div>
                    <span class="block text-xs font-bold text-slate-700">Test Delivery or Resend Missed Export</span>
                    <span class="block text-[11px] text-slate-400">Trigger immediate delivery of the export CSV to verify SMTP or recover missing days.</span>
                </div>
                <form method="POST" action="{{ route('admin.sims-operations.export-settings.send-now') }}" class="flex items-center gap-2 shrink-0">
                    @csrf
                    <select name="period" class="text-xs rounded-lg border border-slate-200 py-1.5 px-2.5 bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="yesterday">Yesterday (Standard Full Day)</option>
                        <option value="today">Today so far</option>
                        <option value="24h">Past 24 Hours (Rolling)</option>
                        <option value="all">All Activated SIMs (Full)</option>
                    </select>
                    <button type="submit" onclick="return confirm('Send export email now?')" class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                        <i data-lucide="send" class="w-3.5 h-3.5 text-primary"></i>
                        <span>Send Export Now</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Activations</span>
                <p class="text-3xl font-extrabold text-slate-800 font-display mt-1">{{ number_format($totalActivations) }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $periodStart }} — {{ $periodEnd }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 md:col-span-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">By Territory</span>
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
                            <th class="py-4 px-6">Territory</th>
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
                <form method="GET" action="{{ route('admin.sims-operations.index') }}" class="space-y-3">
                    <input type="hidden" name="section" value="activation-bonus">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">From</label>
                            <input type="date" name="bonus_date_start" value="{{ $bonusFilters['date_start']?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                                   class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">To</label>
                            <input type="date" name="bonus_date_end" value="{{ $bonusFilters['date_end']?->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                                   class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20 bg-white">
                        </div>
                        <div class="flex-grow max-w-xs">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Phone Number</label>
                            <div class="relative">
                                <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" name="bonus_number" value="{{ $bonusFilters['number'] }}" placeholder="Search phone number…"
                                       class="w-full text-xs rounded-lg border border-slate-200 pl-8 pr-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20 bg-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Network</label>
                            <select name="bonus_network" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[130px] bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                                <option value="">All networks</option>
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}" @selected(($bonusFilters['network'] ?? '') === $prov)>{{ strtoupper($prov) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                            <select name="bonus_status" class="text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 min-w-[130px] bg-white focus:outline-none focus:ring-2 focus:ring-[#0056D2]/20">
                                <option value="">All statuses</option>
                                <option value="completed" @selected($bonusFilters['status'] === 'completed')>Completed</option>
                                <option value="failed" @selected($bonusFilters['status'] === 'failed')>Failed</option>
                                <option value="retrying" @selected($bonusFilters['status'] === 'retrying')>Retrying</option>
                            </select>
                        </div>
                        <button type="submit" class="text-xs font-bold px-4 py-1.5 rounded-lg bg-[#0056D2] text-white hover:bg-[#0049b8] transition-colors shadow-xs h-[32px]">Apply Filters</button>

                        @php
                            $hasBonusFilters = $bonusFilters['date_start'] || $bonusFilters['date_end'] || $bonusFilters['number'] || $bonusFilters['status'] || ($bonusFilters['network'] ?? null);
                        @endphp
                        @if ($hasBonusFilters)
                            <a href="{{ route('admin.sims-operations.index', ['section' => 'activation-bonus']) }}"
                               class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-1.5 h-[32px]"
                               title="Clear activation bonus filters">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>Clear Filters</span>
                            </a>
                        @endif

                        <a href="{{ route('admin.sims-operations.activation-bonus.export', request()->query()) }}" class="text-xs font-bold px-3.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-1.5 ml-auto h-[32px] shadow-2xs">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            Export CSV
                        </a>
                    </div>

                    @if ($hasBonusFilters)
                        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-xs">
                            <span class="text-slate-400 font-semibold flex items-center gap-1 text-[11px]">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-[#0056D2]"></i>
                                Active Bonus Filters:
                            </span>
                            @if ($bonusFilters['date_start'] || $bonusFilters['date_end'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                    Dates: {{ $bonusFilters['date_start']?->format('M d') ?? 'Start' }} – {{ $bonusFilters['date_end']?->format('M d') ?? 'Now' }}
                                    <a href="{{ route('admin.sims-operations.index', array_merge(request()->except(['bonus_date_start', 'bonus_date_end']), ['section' => 'activation-bonus'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                                </span>
                            @endif
                            @if ($bonusFilters['network'] ?? null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                    Network: {{ strtoupper($bonusFilters['network']) }}
                                    <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('bonus_network'), ['section' => 'activation-bonus'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                                </span>
                            @endif
                            @if ($bonusFilters['number'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                    Number: {{ $bonusFilters['number'] }}
                                    <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('bonus_number'), ['section' => 'activation-bonus'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                                </span>
                            @endif
                            @if ($bonusFilters['status'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-[#0056D2] font-semibold border border-blue-100 text-[11px]">
                                    Status: {{ ucfirst($bonusFilters['status']) }}
                                    <a href="{{ route('admin.sims-operations.index', array_merge(request()->except('bonus_status'), ['section' => 'activation-bonus'])) }}" class="hover:text-blue-800 ml-0.5 font-bold">&times;</a>
                                </span>
                            @endif
                            <a href="{{ route('admin.sims-operations.index', ['section' => 'activation-bonus']) }}"
                               class="text-[11px] text-slate-500 hover:text-rose-600 font-semibold underline ml-1">
                                Clear all
                            </a>
                        </div>
                    @endif
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
