<x-app-layout>
    <title>SmartSIM Support Desk - SIM Inventory</title>

    <div class="space-y-6 max-w-7xl mx-auto">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">SIM Inventory</h1>
            <p class="text-xs text-slate-400 mt-1">Browse every SIM in the pool. Collect one back from its holder to reassign it within the same hierarchy, or unassign it completely.</p>
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

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total SIMs</span>
                <p class="text-2xl font-extrabold text-slate-800 font-display mt-1">{{ number_format($totalUploaded) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Available (Unassigned)</span>
                <p class="text-2xl font-extrabold text-slate-800 font-display mt-1">{{ number_format($totalAvailable) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">In the Cascade</span>
                <p class="text-2xl font-extrabold text-slate-800 font-display mt-1">{{ number_format($totalAssigned) }}</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Activated</span>
                <p class="text-2xl font-extrabold text-slate-800 font-display mt-1">{{ number_format($totalActivated) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Filters -->
            <div class="p-6 border-b border-slate-100 bg-slate-50/30">
                <form method="GET" action="{{ route('support-desk.sim-inventory.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                    <div class="relative flex-grow">
                        <x-text-input type="text" name="search" :value="request('search')"
                               class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                               placeholder="Search by SIM number..." />
                        <div class="absolute left-4 top-3.5 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>

                    <div class="w-full md:w-48">
                        <x-select-input name="status" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                            <option value="">All Statuses</option>
                            @foreach (\App\Support\SimStatus::all() as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ \App\Support\SimStatus::label($status) }}</option>
                            @endforeach
                        </x-select-input>
                    </div>

                    <div class="w-full md:w-44">
                        <x-select-input name="category" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ ucfirst($category) }}</option>
                            @endforeach
                        </x-select-input>
                    </div>

                    <div class="w-full md:w-44">
                        <x-select-input name="provider" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                            <option value="">All Providers</option>
                            @foreach ($providers as $provider)
                                <option value="{{ $provider }}" {{ request('provider') === $provider ? 'selected' : '' }}>{{ $provider }}</option>
                            @endforeach
                        </x-select-input>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-primary-button type="submit" class="flex-1 md:flex-none !text-xs">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            Filter
                        </x-primary-button>
                        @if(request('search') || request('status') || request('category') || request('provider'))
                            <a href="{{ route('support-desk.sim-inventory.index') }}" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Number</th>
                            <th class="py-4 px-6">Category / Provider</th>
                            <th class="py-4 px-6">Current Holder</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($sims as $sim)
                            @php
                                $holder = $sim->user ?? $sim->businessAgent ?? $sim->business ?? $sim->partner ?? $sim->coordinator ?? $sim->regionalManager;
                            @endphp
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6 font-mono font-bold text-slate-800">{{ $sim->number ?? '—' }}</td>
                                <td class="py-4 px-6 text-xs">
                                    <span class="block font-bold capitalize">{{ $sim->category }}</span>
                                    <span class="block text-slate-400">{{ $sim->provider }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    @if ($holder)
                                        <span class="block font-bold text-slate-800">{{ $holder->first_name }} {{ $holder->last_name }}</span>
                                        <span class="block text-xs text-slate-400">{{ str_replace('_', ' ', $holder->role) }}</span>
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
                                    <a href="{{ route('support-desk.sim-inventory.show', $sim) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0056D2]/5 hover:bg-[#0056D2]/10 text-[#0056D2] font-bold text-xs rounded-xl transition-all">
                                        <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-16 text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-4 max-w-sm mx-auto">
                                        <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100 shadow-inner">
                                            <i data-lucide="package" class="w-8 h-8"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-extrabold text-slate-800 font-display">No SIMs found</h6>
                                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Try clearing filters or refining your search query.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sims->hasPages())
                <div class="p-6 border-t border-slate-100 bg-slate-50/30">
                    {{ $sims->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Floating bulk-actions button -->
    <div class="fixed bottom-6 right-6 z-40" x-data="{ open: false }">
        <div x-show="open" @click.away="open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="absolute bottom-16 right-0 w-56 bg-white rounded-xl border border-slate-200 shadow-xl p-2 space-y-1" style="display: none;" x-cloak>
            <a href="{{ route('support-desk.sim-inventory.bulk-assign') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4 text-emerald-600"></i> Bulk Assign SIM
            </a>
            <a href="{{ route('support-desk.sim-inventory.bulk-collect') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                <i data-lucide="user-minus" class="w-4 h-4 text-rose-600"></i> Bulk Collect SIM
            </a>
        </div>
        <button type="button" @click="open = !open" aria-label="Bulk actions"
                class="w-14 h-14 rounded-full bg-[#0056D2] hover:bg-[#0049b8] text-white shadow-xl flex items-center justify-center transition-transform"
                :class="open ? 'rotate-45' : ''">
            <i data-lucide="plus" class="w-6 h-6"></i>
        </button>
    </div>
</x-app-layout>
