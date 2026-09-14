<x-app-layout>
    <title>SmartSIM Support Desk - SIM Lookup</title>

    <div class="space-y-8 max-w-4xl mx-auto">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">SIM Lookup</h1>
            <p class="text-xs text-slate-400 mt-1">Look up one SIM by its exact number, or its holder's exact email/phone. There is no general SIM listing here.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="GET" action="{{ route('support-desk.sims.index') }}" class="flex flex-col sm:flex-row items-stretch gap-3">
                <div class="relative flex-grow">
                    <x-text-input type="text" name="search" :value="$search"
                           class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                           placeholder="SIM number, or holder's email/phone" />
                    <div class="absolute left-4 top-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                </div>
                <x-primary-button type="submit" class="!text-xs shrink-0">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    Look Up
                </x-primary-button>
                @if ($search !== '')
                    <a href="{{ route('support-desk.sims.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150 shrink-0">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        @if ($search === '')
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm text-center py-16 text-slate-400">
                <i data-lucide="cpu" class="w-10 h-10 mx-auto text-slate-300"></i>
                <p class="text-sm font-semibold text-slate-600 mt-3">Enter a SIM number or holder's email/phone to look it up.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6">Number</th>
                                <th class="py-4 px-6">Category / Provider</th>
                                <th class="py-4 px-6">Owner</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                            @forelse ($sims as $sim)
                                <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                    <td class="py-4 px-6 font-mono font-bold text-slate-800">{{ $sim->number ?? '—' }}</td>
                                    <td class="py-4 px-6 text-xs">
                                        <span class="block font-bold capitalize">{{ $sim->category }}</span>
                                        <span class="block text-slate-400">{{ $sim->provider }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if ($sim->user)
                                            <span class="block font-bold text-slate-800">{{ $sim->user->first_name }} {{ $sim->user->last_name }}</span>
                                            <span class="block text-xs text-slate-400">{{ $sim->user->email }}</span>
                                        @else
                                            <span class="text-slate-400 text-xs">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold border rounded-full uppercase tracking-wider {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">
                                            {{ \App\Support\SimStatus::label($sim->status) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a href="{{ route('support-desk.sims.show', $sim) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0056D2]/5 hover:bg-[#0056D2]/10 text-[#0056D2] font-bold text-xs rounded-xl transition-all">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-16 text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-4 max-w-sm mx-auto">
                                            <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100 shadow-inner">
                                                <i data-lucide="search-x" class="w-8 h-8"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-extrabold text-slate-800 font-display">No match found</h6>
                                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">No SIM matches that exact number, and no holder matches that exact email/phone.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
