<x-app-layout>
    <title>SimPhix Support Desk - Manage SIM {{ $sim->number }}</title>

    @php
        $holders = [
            'Owner' => $sim->user,
            'Partner' => $sim->partner,
            'Coordinator' => $sim->coordinator,
            'Regional Manager' => $sim->regionalManager,
            'Enterprise Business' => $sim->business,
            'Business Agent' => $sim->businessAgent,
        ];
        $roleLabels = [
            'personal' => 'personal user',
            'coordinator' => 'coordinator',
            'business' => 'Enterprise Business',
        ];
    @endphp

    <div class="space-y-6 max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="flex items-center gap-3">
            <a href="{{ route('support-desk.sim-inventory.index') }}"
               class="w-10 h-10 rounded-2xl bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-600 transition-all shadow-sm">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-2xl font-bold font-display text-slate-800 font-mono">{{ $sim->number ?? 'Unnumbered SIM' }}</h1>
                <p class="text-xs text-slate-500 mt-0.5 capitalize">{{ $sim->category }} • {{ $sim->provider }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 text-xs font-extrabold border rounded-full uppercase tracking-wider {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">
                {{ \App\Support\SimStatus::label($sim->status) }}
            </span>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl flex items-center gap-2 text-xs font-semibold">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl flex items-center gap-2 text-xs font-semibold">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 flex-shrink-0"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Holder chain + history -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Holder Chain</h3>

                    @foreach ($holders as $label => $holder)
                        @if ($holder)
                            <a href="{{ route('support-desk.users.show', $holder) }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-[#0056D2] flex items-center justify-center font-extrabold text-xs uppercase shrink-0">
                                    {{ substr($holder->first_name, 0, 1) }}{{ substr($holder->last_name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $label }}</span>
                                    <span class="block text-sm font-bold text-slate-800 truncate">{{ $holder->first_name }} {{ $holder->last_name }}</span>
                                </div>
                            </a>
                        @endif
                    @endforeach

                    @if (collect($holders)->filter()->isEmpty())
                        <p class="text-xs text-slate-400">This SIM has no holder recorded — it's available in the general pool.</p>
                    @endif
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50 mb-4">History</h3>
                    <div class="space-y-5 relative pl-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100 max-h-96 overflow-y-auto">
                        @forelse ($sim->history as $event)
                            <div class="relative">
                                <span class="absolute -left-6 top-0.5 w-6 h-6 rounded-full bg-white border-4 border-white flex items-center justify-center shadow-sm {{ $event->to_status === \App\Support\SimStatus::ACTIVATED ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                <span class="block text-xs font-bold text-slate-700">
                                    {{ $event->from_status ? \App\Support\SimStatus::label($event->from_status) . ' → ' : '' }}{{ \App\Support\SimStatus::label($event->to_status) }}
                                </span>
                                <span class="text-xs text-slate-400 font-medium mt-0.5 block">
                                    {{ $event->created_at->format('M d, Y h:i A') }}
                                    @if ($event->actor)
                                        • by {{ $event->actor->first_name }} {{ $event->actor->last_name }}
                                    @endif
                                </span>
                                @if ($event->note)
                                    <span class="text-xs text-slate-500 italic mt-0.5 block">{{ $event->note }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No history recorded for this SIM yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="lg:col-span-2 space-y-6">
                @if ($sim->status === \App\Support\SimStatus::ACTIVATED)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-start gap-3">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                            <p class="text-sm text-slate-600">This SIM is <strong>activated</strong> and in use by a live customer — it cannot be collected, reassigned, or unassigned from here.</p>
                        </div>
                    </div>
                @else
                    @if ($sim->status === \App\Support\SimStatus::UNASSIGNED)
                        <!-- Direct Assign -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
                             x-data="simUserPicker('{{ route('support-desk.sim-inventory.users.search') }}', null)">
                            <div>
                                <h3 class="font-bold text-slate-800 font-display">Assign This SIM</h3>
                                <p class="text-xs text-slate-400 mt-1">This SIM is unassigned — hand it directly to any active user on the platform.</p>
                            </div>

                            <form method="POST" action="{{ route('support-desk.sim-inventory.assign', $sim) }}" class="space-y-3" @submit="if(!userId) { $event.preventDefault(); }">
                                @csrf
                                <input type="hidden" name="user_id" :value="userId">

                                <div class="relative">
                                    <x-text-input type="text" x-model="query" @input.debounce.400ms="search()"
                                                  placeholder="Search by name, email, or phone…" autocomplete="off" class="rounded-xl font-medium w-full" />
                                    <div x-show="results.length > 0" x-cloak
                                         class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                                        <template x-for="u in results" :key="u.id">
                                            <button type="button" @click="pick(u)"
                                                    class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-50 last:border-0">
                                                <span class="block text-xs font-bold text-slate-700" x-text="u.name"></span>
                                                <span class="block text-[11px] text-slate-400" x-text="u.email + ' · ' + u.role"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div x-show="userId" x-cloak class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                    <span x-text="'Selected: ' + selectedLabel"></span>
                                </div>

                                <x-primary-button type="submit" class="!text-xs">
                                    Assign SIM
                                </x-primary-button>
                            </form>
                        </div>
                    @endif

                    @if ($swapTargetRole)
                        <!-- Collect & Reassign in same hierarchy -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
                             x-data="simUserPicker('{{ route('support-desk.sim-inventory.users.search') }}', '{{ $swapTargetRole }}')">
                            <div>
                                <h3 class="font-bold text-slate-800 font-display">Collect &amp; Reassign — Same Hierarchy</h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Collects this SIM from its current holder and hands it straight to another active
                                    {{ $roleLabels[$swapTargetRole] ?? $swapTargetRole }} — every other link in the chain
                                    (partner/coordinator/regional manager) stays exactly as it is.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('support-desk.sim-inventory.reassign', $sim) }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="user_id" :value="userId">

                                <div class="relative">
                                    <x-text-input type="text" x-model="query" @input.debounce.400ms="search()"
                                                  placeholder="Search by name, email, or phone…" autocomplete="off" class="rounded-xl font-medium w-full" />
                                    <div x-show="results.length > 0" x-cloak
                                         class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                                        <template x-for="u in results" :key="u.id">
                                            <button type="button" @click="pick(u)"
                                                    class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-50 last:border-0">
                                                <span class="block text-xs font-bold text-slate-700" x-text="u.name"></span>
                                                <span class="block text-[11px] text-slate-400" x-text="u.email + ' · ' + u.role"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div x-show="userId" x-cloak class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                    <span x-text="'Selected: ' + selectedLabel"></span>
                                </div>

                                <x-primary-button type="submit" class="!text-xs">
                                    Collect &amp; Reassign
                                </x-primary-button>
                            </form>
                        </div>
                    @endif

                    <!-- Unassign completely -->
                    @if ($sim->status !== \App\Support\SimStatus::UNASSIGNED)
                        <div class="bg-white rounded-xl border border-rose-200 shadow-sm p-6 space-y-4">
                            <div>
                                <h3 class="font-bold text-rose-700 font-display">Unassign Completely</h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Clears every holder on this SIM — partner, coordinator, regional manager, everything —
                                    and returns it to the general inventory as <strong>Unassigned</strong>, ready to be
                                    assigned to anyone directly.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('support-desk.sim-inventory.unassign', $sim) }}"
                                  onsubmit="return confirm('Unassign SIM {{ $sim->number }} completely? This clears every holder and cannot be undone automatically.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-rose-50 border border-rose-100 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl transition-all">
                                    <i data-lucide="unlink" class="w-4 h-4"></i>
                                    Unassign This SIM
                                </button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function simUserPicker(searchUrl, role) {
            return {
                query: '',
                results: [],
                userId: null,
                selectedLabel: '',
                async search() {
                    if (this.query.trim().length < 2) { this.results = []; return; }
                    this.userId = null;
                    let url = searchUrl + '?q=' + encodeURIComponent(this.query);
                    if (role) { url += '&role=' + encodeURIComponent(role); }
                    const res = await fetch(url);
                    this.results = await res.json();
                },
                pick(u) {
                    this.userId = u.id;
                    this.selectedLabel = u.name + ' (' + u.role + ')';
                    this.query = u.name;
                    this.results = [];
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
