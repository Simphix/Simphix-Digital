{{-- Enterprise Partner dashboard: activated SIMs + SIMs held by downline agents, date-filterable. --}}
<div class="mb-4 bg-white border border-slate-200 rounded-xl shadow-sm p-4 md:p-6 space-y-4"
     x-data="{ tab: '{{ request()->has('held_page') && !request()->has('activated_page') ? 'held' : 'activated' }}' }">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-800 font-display mb-0">My SIMs</h3>
            <p class="text-xs text-slate-400 mt-1 mb-0">Activated SIMs and SIMs currently held by your downline agents.</p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="sims_from" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">From</label>
                <input type="date" id="sims_from" name="sims_from" value="{{ $epSims['from'] }}"
                       class="text-xs rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">
            </div>
            <div>
                <label for="sims_to" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">To</label>
                <input type="date" id="sims_to" name="sims_to" value="{{ $epSims['to'] }}"
                       class="text-xs rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5">
            </div>
            <button type="submit" class="text-xs font-semibold px-3 py-2 rounded-lg bg-primary text-white">Filter</button>
            @if ($epSims['from'] || $epSims['to'])
                <a href="{{ route('dashboard') }}" class="text-xs font-semibold px-3 py-2 text-slate-500 hover:underline">Clear</a>
            @endif
        </form>
    </div>
    @error('sims_to') <p class="text-xs text-rose-600 mb-0">{{ $message }}</p> @enderror
    @error('sims_from') <p class="text-xs text-rose-600 mb-0">{{ $message }}</p> @enderror

    <div class="flex gap-1 bg-slate-50 rounded-lg p-1 w-fit">
        <button type="button" @click="tab = 'activated'"
                :class="tab === 'activated' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500'"
                class="px-3 py-1.5 text-xs font-semibold rounded-md">
            Activated SIMs ({{ $epSims['activated']->total() }})
        </button>
        <button type="button" @click="tab = 'held'"
                :class="tab === 'held' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500'"
                class="px-3 py-1.5 text-xs font-semibold rounded-md">
            Downline Held SIMs ({{ $epSims['held']->total() }})
        </button>
    </div>

    @foreach (['activated' => ['Activated', 'No activated SIMs for this period.'], 'held' => ['Held since', 'No SIMs held by your downline for this period.']] as $key => [$dateLabel, $emptyText])
        <div x-show="tab === '{{ $key }}'" @if ($key !== 'activated') style="display: none;" @endif>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">SIM Number</th>
                            <th class="py-2.5">Provider</th>
                            <th class="py-2.5">Category</th>
                            <th class="py-2.5">Downline Agent</th>
                            <th class="py-2.5">{{ $dateLabel }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($epSims[$key] as $sim)
                            <tr>
                                <td class="py-2.5 font-semibold text-slate-700">{{ $sim->number }}</td>
                                <td class="py-2.5 uppercase text-slate-500">{{ $sim->provider }}</td>
                                <td class="py-2.5 text-slate-500">{{ $sim->category }}</td>
                                <td class="py-2.5 text-slate-500">
                                    {{ $sim->businessAgent ? trim($sim->businessAgent->first_name . ' ' . $sim->businessAgent->last_name) : '—' }}
                                </td>
                                <td class="py-2.5 text-slate-500">
                                    {{ $sim->event_at ? \Illuminate\Support\Carbon::parse($sim->event_at)->format('M d, Y h:i A') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">{{ $emptyText }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $epSims[$key]->links() }}</div>
        </div>
    @endforeach
</div>
