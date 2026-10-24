<x-app-layout>
    <title>SimPhix - Sales Performance</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                Sales Performance
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $isPersonal ? 'Your own SIM activation conversion and commission trend.' : 'How your downline is converting, and where to follow up.' }}
            </p>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-card padding="p-4">
                <p class="text-xs text-slate-400 mb-0.5">{{ $isPersonal ? 'My SIM Conversion' : 'Downline SIM Conversion' }}</p>
                <p class="text-lg font-bold font-display text-slate-800 truncate">
                    {{ $activatedSims }} / {{ $totalSims }}
                    <span class="text-sm font-semibold text-primary">· {{ $conversionRate }}%</span>
                </p>
                <p class="text-xs text-slate-400 mt-1">{{ $activatedSims }} activated of {{ $totalSims }} {{ $isPersonal ? 'held' : 'distributed' }}</p>
            </x-card>

            <x-card padding="p-4">
                <p class="text-xs text-slate-400 mb-0.5">Commission This Week</p>
                <p class="text-lg font-bold font-display text-slate-800 truncate">₦{{ number_format($commissionThisWeek, 2) }}</p>
                <p class="text-xs mt-1 font-semibold {{ $commissionTrendPct > 0 ? 'text-emerald-600' : ($commissionTrendPct < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                    @if ($commissionTrendPct > 0)
                        <i data-lucide="arrow-up-right" class="w-3 h-3 inline"></i> {{ $commissionTrendPct }}% vs last week
                    @elseif ($commissionTrendPct < 0)
                        <i data-lucide="arrow-down-right" class="w-3 h-3 inline"></i> {{ $commissionTrendPct }}% vs last week
                    @else
                        No change vs last week
                    @endif
                </p>
            </x-card>

            <x-card padding="p-4">
                <p class="text-xs text-slate-400 mb-0.5">Dormant {{ $isPersonal ? 'SIMs' : 'Downline SIMs' }}</p>
                <p class="text-lg font-bold font-display {{ $dormantCount > 0 ? 'text-rose-600' : 'text-slate-800' }} truncate">{{ $dormantCount }}</p>
                <p class="text-xs text-slate-400 mt-1">Held {{ \App\Http\Controllers\SalesPerformanceController::DORMANT_DAYS ?? 7 }}+ days unactivated</p>
            </x-card>
        </div>

        <!-- Commission Trend Chart -->
        <x-card padding="p-6">
            <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4 flex items-center gap-2">
                <i data-lucide="bar-chart-2" class="text-primary w-5 h-5"></i>
                Commission — Last 6 Weeks
            </h3>

            @php
                $maxVal = 100;
                foreach ($weeklyTrend as $week) {
                    if ($week['amount'] > $maxVal) $maxVal = $week['amount'];
                }
            @endphp

            <div class="bg-slate-50 border border-slate-200/60 rounded-lg p-3">
                <div class="position-relative w-100" style="height: 140px;">
                    <svg class="w-full h-full" viewBox="0 0 350 140" preserveAspectRatio="none">
                        <line x1="0" y1="35" x2="350" y2="35" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="0" y1="70" x2="350" y2="70" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="0" y1="105" x2="350" y2="105" stroke="#e2e8f0" stroke-width="1" />

                        @foreach ($weeklyTrend as $index => $week)
                            @php
                                $colWidth = 58;
                                $startX = ($index * $colWidth) + 12;
                                $barHeight = ($week['amount'] / $maxVal) * 105;
                                if ($week['amount'] > 0 && $barHeight < 4) $barHeight = 4;
                                $barY = 115 - $barHeight;
                            @endphp
                            <rect x="{{ $startX }}" y="{{ $barY }}" width="30" height="{{ $barHeight }}" rx="3" fill="#0056D2" />
                            <text x="{{ $startX + 15 }}" y="130" text-anchor="middle" font-size="9" fill="#94a3b8">{{ $week['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Unactivated SIMs -->
        <x-card padding="p-6">
            <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">
                {{ $isPersonal ? 'My Unactivated SIMs' : 'Unactivated SIMs' }}
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">SIM Number</th>
                            @unless ($isPersonal)
                                <th class="py-2.5">Held By</th>
                            @endunless
                            <th class="py-2.5">Days Held</th>
                            <th class="py-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unactivatedSims as $row)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                <td class="py-3 font-semibold text-slate-700">{{ $row['sim']->number }}</td>
                                @unless ($isPersonal)
                                    <td class="py-3 text-slate-600">
                                        {{ $row['holder'] ? ($row['holder']->first_name . ' ' . $row['holder']->last_name) : '—' }}
                                    </td>
                                @endunless
                                <td class="py-3 text-slate-400 font-medium">{{ $row['days_held'] }} days</td>
                                <td class="py-3 text-right">
                                    @if ($row['is_dormant'])
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase bg-rose-50 text-rose-600 border border-rose-100">Dormant</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase bg-primary/10 text-primary">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isPersonal ? 3 : 4 }}" class="py-12 text-center text-slate-400">
                                    <div class="bg-slate-50 rounded-2xl w-14 h-14 flex items-center justify-center mb-3 mx-auto border border-slate-100">
                                        <i data-lucide="check-circle-2" class="w-6 h-6 text-slate-400"></i>
                                    </div>
                                    <h6 class="font-bold text-slate-800 text-sm">Nothing pending</h6>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[280px] mx-auto">
                                        {{ $isPersonal ? 'All your SIMs are activated.' : 'Every SIM in your downline is activated.' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
