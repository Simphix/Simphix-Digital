<x-app-layout>
    @php
        // Shared by both the super_admin console (admin.manage.support.*)
        // and the support-desk console (support-desk.tickets.*) — this
        // page has identical ticket-triage powers either way, only the
        // route names differ.
        $onSupportDesk = request()->routeIs('support-desk.*');
        $ticketsIndexRoute = $onSupportDesk ? 'support-desk.tickets.index' : 'admin.manage.support.index';
        $ticketsShowRoute = $onSupportDesk ? 'support-desk.tickets.show' : 'admin.manage.support.show';
    @endphp
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Customer Support Dashboard</h1>
            <p class="text-xs text-slate-400 mt-1">Review user support requests, respond to tickets, and manage customer service status.</p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Support Tickets Statistics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Tickets -->
            <div class="bg-gradient-to-br from-indigo-50/50 to-slate-50/50 p-5 rounded-xl border border-indigo-100/60 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-[#0056D2] border border-indigo-100/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Tickets</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $totalTickets }}</span>
                </div>
            </div>

            <!-- Open Tickets -->
            <div class="bg-gradient-to-br from-amber-50/50 to-orange-50/50 p-5 rounded-xl border border-amber-100/60 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="clock" class="w-5 h-5 animate-pulse"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Open Tickets</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $openTickets }}</span>
                </div>
            </div>

            <!-- Responded Tickets -->
            <div class="bg-gradient-to-br from-blue-50/50 to-sky-50/50 p-5 rounded-xl border border-blue-100/60 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="reply" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Responded</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $respondedTickets }}</span>
                </div>
            </div>

            <!-- Closed Tickets -->
            <div class="bg-gradient-to-br from-slate-50/50 to-slate-100/50 p-5 rounded-xl border border-slate-200/50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="archive" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Closed Tickets</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $closedTickets }}</span>
                </div>
            </div>
        </div>

        <!-- Filters Block -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="GET" action="{{ route($ticketsIndexRoute) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <x-input-label for="search" value="Search Query" />
                    <x-text-input type="text" id="search" name="search" :value="request('search')"
                           class="rounded-xl !text-xs font-semibold"
                           placeholder="Subject, email, or name..." />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <x-select-input id="status" name="status" class="rounded-xl !text-xs font-semibold">
                        <option value="">All Statuses</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="responded" {{ request('status') == 'responded' ? 'selected' : '' }}>Responded</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </x-select-input>
                </div>

                <div>
                    <x-input-label for="priority" value="Priority" />
                    <x-select-input id="priority" name="priority" class="rounded-xl !text-xs font-semibold">
                        <option value="">All Priorities</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                    </x-select-input>
                </div>

                <div class="flex gap-2">
                    <x-primary-button type="submit" class="flex-1 !text-xs">
                        Apply Filters
                    </x-primary-button>
                    @if(request()->filled('search') || request()->filled('status') || request()->filled('priority'))
                        <a href="{{ route($ticketsIndexRoute) }}"
                           class="py-2.5 px-4 bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-xl transition-all duration-200 text-center flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tickets List Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Ticket Details</th>
                            <th class="py-4 px-6">Customer Details</th>
                            <th class="py-4 px-6">Category</th>
                            <th class="py-4 px-6">Priority</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse($tickets as $ticket)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    <span class="block text-slate-800 font-bold font-display truncate max-w-[220px]">
                                        {{ $ticket->subject }}
                                    </span>
                                    <span class="block text-xs text-slate-400 mt-0.5">
                                        ID: #{{ $ticket->id }} • Created: {{ $ticket->created_at->format('M d, Y h:i A') }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="block text-slate-800 font-bold font-display">
                                        {{ $ticket->user->first_name }} {{ $ticket->user->last_name }}
                                    </span>
                                    <span class="block text-xs text-slate-400 mt-0.5">
                                        {{ $ticket->user->email }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-600 font-bold capitalize">
                                    {{ $ticket->category }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold border {{ $ticket->priority_badge }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold border rounded-full uppercase tracking-wider {{ $ticket->status_badge }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current {{ $ticket->status === 'open' ? 'animate-ping' : '' }}"></span>
                                        {{ $ticket->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route($ticketsShowRoute, $ticket) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0056D2]/5 hover:bg-[#0056D2]/10 text-[#0056D2] font-bold text-xs rounded-xl transition-all">
                                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                        Respond
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-slate-400 text-sm">
                                    <div class="bg-slate-50 rounded-2xl w-14 h-14 flex items-center justify-center mb-3 mx-auto border border-slate-100">
                                        <i data-lucide="message-square-off" class="w-6 h-6 text-slate-400"></i>
                                    </div>
                                    <h6 class="font-bold text-slate-800 text-sm">No Support Tickets Found</h6>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto">
                                        There are currently no active or historical support tickets matching these criteria.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tickets->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
