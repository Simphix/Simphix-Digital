<x-app-layout>
    <div class="space-y-6 max-w-6xl mx-auto">

        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Reconciliation</h1>
            <p class="text-xs text-slate-400 mt-1">Read-only detection of mismatches across the platform's money-moving flows. Nothing here is auto-corrected — every finding is meant to be investigated and resolved through the normal admin tools.</p>
        </div>

        @include('admin.reconciliation.partials.tabs')

        <div class="bg-white border border-slate-200 rounded-2xl p-3.5" x-data="{ showCustom: {{ $range === 'custom' ? 'true' : 'false' }} }">
            <form method="GET" action="{{ route('admin.reconciliation.index', $tab) }}" class="flex flex-wrap items-center gap-2">
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

                @if ($tab === 'cashouts')
                    <div class="flex items-center gap-2 ml-1 pl-3 border-l border-slate-200">
                        <label class="text-xs font-semibold text-slate-400">Stuck after (minutes)</label>
                        <input type="number" name="stuck_threshold_minutes" value="{{ $stuckThresholdMinutes }}" min="1"
                               class="w-20 text-xs rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <input type="hidden" name="range" value="{{ $range }}">
                        <button type="submit" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-800 text-white hover:bg-slate-700 transition-colors">Apply</button>
                    </div>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-md font-bold text-slate-800 font-display">{{ $findings->count() }} finding{{ $findings->count() === 1 ? '' : 's' }}</h3>
                    <p class="text-xs text-slate-400 mt-1">{{ $periodStart }} — {{ $periodEnd }}</p>
                </div>
            </div>

            <div class="overflow-x-auto" x-data="{ open: null }">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Severity</th>
                            <th class="py-4 px-6">Summary</th>
                            <th class="py-4 px-6">Related IDs</th>
                            <th class="py-4 px-6 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($findings as $index => $finding)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    @php
                                        $severityClasses = match ($finding->severity) {
                                            'critical' => 'bg-rose-50 text-rose-600 border-rose-100',
                                            'warning' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full border uppercase tracking-wider {{ $severityClasses }}">{{ $finding->severity }}</span>
                                </td>
                                <td class="py-4 px-6">{{ $finding->summary }}</td>
                                <td class="py-4 px-6 text-slate-400 text-xs">{{ implode(', ', $finding->related_ids) }}</td>
                                <td class="py-4 px-6 text-right">
                                    <button type="button" @click="open = open === {{ $index }} ? null : {{ $index }}" class="text-xs font-bold text-primary hover:underline">
                                        <span x-show="open !== {{ $index }}">Show</span>
                                        <span x-show="open === {{ $index }}" x-cloak>Hide</span>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="open === {{ $index }}" x-cloak>
                                <td colspan="4" class="px-6 pb-4">
                                    <pre class="text-xs bg-slate-50 border border-slate-100 rounded-xl p-4 overflow-x-auto">{{ json_encode($finding->details, JSON_PRETTY_PRINT) }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400 text-sm">No findings for this range — everything reconciles cleanly.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
