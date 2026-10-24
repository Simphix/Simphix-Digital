<x-app-layout>
    <title>SimPhix Support Desk - SIM {{ $sim->number }}</title>

    @php
        $holders = [
            'Owner' => $sim->user,
            'Partner' => $sim->partner,
            'Coordinator' => $sim->coordinator,
            'Regional Manager' => $sim->regionalManager,
            'Enterprise Partner' => $sim->business,
            'Business Agent' => $sim->businessAgent,
        ];
    @endphp

    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Page Header -->
        <div class="flex items-center gap-3">
            <a href="{{ route('support-desk.sims.index') }}"
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Holder chain -->
            <div class="lg:col-span-1 bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
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
                    <p class="text-xs text-slate-400">This SIM has no holder recorded yet.</p>
                @endif
            </div>

            <!-- History timeline -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50 mb-4">Assignment & Activation History</h3>

                <div class="space-y-6 relative pl-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-100">
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
    </div>
</x-app-layout>
