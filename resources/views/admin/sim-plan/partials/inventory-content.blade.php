<!-- Bulk Assign Action Bar -->
<div x-show="selectedSims.length > 0" 
     style="display: none;" 
     class="flex items-center justify-between bg-indigo-50/80 border border-indigo-100 rounded-2xl p-3.5 mb-4 animate-in slide-in-from-top duration-200">
    <span class="text-xs font-semibold text-slate-700">
        Selected: <span class="font-extrabold text-indigo-700" x-text="selectedSims.length"></span> / 50 SIMs
    </span>
    <button type="button" @click="openBulkAssignModal()"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center gap-1.5 transition-colors shadow-sm">
        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
        Assign Checked
    </button>
</div>

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse text-xs">
        <thead>
            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                <th class="py-2.5 pl-3 w-8">
                    <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded text-[#0056D2] focus:ring-[#0056D2]">
                </th>
                <th class="py-2.5">Number</th>
                <th class="py-2.5">Category/Network</th>
                <th class="py-2.5">Status</th>
                <th class="py-2.5">Assigned To</th>
                <th class="py-2.5 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sims as $sim)
                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                    <td class="py-3 pl-3">
                        @if ($sim->status === 'UNASSIGNED')
                            <input type="checkbox" id="sim_cb_{{ $sim->id }}" value="{{ $sim->id }}"
                                   :checked="selectedSims.includes({{ $sim->id }})"
                                   @change="toggleSim({{ $sim->id }}, $event.target.checked)"
                                   class="sim-checkbox rounded text-[#0056D2] focus:ring-[#0056D2]">
                        @else
                            <input type="checkbox" disabled class="rounded bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed">
                        @endif
                    </td>
                    <td class="py-3 font-bold text-slate-800">{{ $sim->number }}</td>
                    <td class="py-3">
                        <span class="font-semibold text-slate-700 block">{{ $sim->category }}</span>
                        <span class="text-xs text-slate-400 block uppercase">{{ $sim->provider }}</span>
                    </td>
                    <td class="py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase
                            {{ $sim->status === 'ACTIVATED' ? 'bg-emerald-50 text-emerald-600' : (str_starts_with($sim->status, 'ASSIGNED_TO') ? 'bg-blue-50 text-blue-600' : ($sim->status === 'UNASSIGNED' ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600')) }}">
                            @if($sim->status === 'UNASSIGNED')
                                NOT ASSIGNED
                            @else
                                {{ str_replace('_', ' ', $sim->status) }}
                            @endif
                        </span>
                    </td>
                    <td class="py-3">
                        @if ($sim->regionalManager || $sim->coordinator || $sim->user)
                            @php $holder = $sim->regionalManager ?? $sim->coordinator ?? $sim->user; @endphp
                            <span class="font-semibold text-slate-700">{{ $holder->first_name }} {{ $holder->last_name }}</span>
                            <span class="block text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', $holder->role) }}</span>
                        @else
                            <span class="text-slate-400 font-semibold italic">None</span>
                        @endif
                    </td>
                    <td class="py-3 text-right space-x-1 whitespace-nowrap">
                        @if (in_array($sim->status, ['ASSIGNED_TO_RM', 'ASSIGNED_TO_COORDINATOR', 'ASSIGNED_TO_PARTNER']))
                            <form action="{{ route('admin.sim-plan.activate', $sim->id) }}" method="POST" class="inline-block" @submit="loading = true">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-all duration-150">
                                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                    Activate
                                </button>
                            </form>
                        @endif
                        @if ($sim->status === 'UNASSIGNED')
                            <button type="button" @click="openAssignSimModal({{ $sim->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-[#0056D2] hover:bg-[#354062] text-white rounded-xl transition-all duration-150 shadow-sm">
                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                Assign
                            </button>
                        @else
                            <form action="{{ route('admin.sim-plan.unassign', $sim->id) }}" method="POST" class="inline-block" @submit="loading = true">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                    <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                    Unassign
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                <i data-lucide="search-x" class="w-5 h-5"></i>
                            </div>
                            <span class="font-semibold text-xs">No SIM records found matching the current filters.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pt-4 border-t border-slate-100">
    {{ $sims->withQueryString()->links('vendor.pagination.custom') }}
</div>
