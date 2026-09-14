<x-app-layout>
    <title>SmartSIM Support Desk - Overview</title>

    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Support Desk</h1>
            <p class="text-xs text-slate-400 mt-1">Triage tickets and look up transactions, SIMs, and users to resolve escalations.</p>
        </div>

        <!-- Ticket Statistics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-amber-50/50 to-orange-50/50 p-5 rounded-xl border border-amber-100/60 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="clock" class="w-5 h-5 animate-pulse"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Open Tickets</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $openTickets }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50/50 to-sky-50/50 p-5 rounded-xl border border-blue-100/60 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="reply" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Responded</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $respondedTickets }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-50/50 to-slate-100/50 p-5 rounded-xl border border-slate-200/50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-600 border border-slate-200/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="archive" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Closed Tickets</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $closedTickets }}</span>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-50/50 to-teal-50/50 p-5 rounded-xl border border-emerald-100/60 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100/50 flex items-center justify-center shadow-sm">
                    <i data-lucide="cpu" class="w-5 h-5"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Activations Today</span>
                    <span class="text-xl font-black text-slate-800 font-display mt-0.5 block">{{ $todayActivations }}</span>
                </div>
            </div>
        </div>

        <!-- Today's transaction snapshot -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Transactions Today</span>
                <span class="text-2xl font-extrabold text-slate-800 font-display mt-1 block">{{ number_format($todayTransactionsCount) }}</span>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Completed Volume Today</span>
                <span class="text-2xl font-extrabold text-emerald-600 font-display mt-1 block">₦{{ number_format($todayTransactionsVolume, 2) }}</span>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Failed Today</span>
                <span class="text-2xl font-extrabold text-rose-600 font-display mt-1 block">{{ number_format($failedTransactionsToday) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Needs attention -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 font-display flex items-center gap-2">
                        <i data-lucide="message-square" class="w-4.5 h-4.5 text-[#0056D2]"></i>
                        Needs Attention — Open Tickets
                    </h3>
                    <a href="{{ route('support-desk.tickets.index', ['status' => 'open']) }}" class="text-xs font-bold text-[#0056D2] hover:underline">View all</a>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse ($latestOpenTickets as $ticket)
                        <a href="{{ route('support-desk.tickets.show', $ticket) }}" class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50/50 transition-all">
                            <div class="min-w-0">
                                <span class="block text-sm font-bold text-slate-800 truncate">{{ $ticket->subject }}</span>
                                <span class="block text-xs text-slate-400 mt-0.5">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }} • {{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold border {{ $ticket->priority_badge }} shrink-0">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </a>
                    @empty
                        <div class="text-center py-12 text-slate-400">
                            <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-400"></i>
                            <p class="text-sm font-semibold text-slate-600 mt-2">No open tickets right now.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick lookup -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Quick Lookup</h3>

                <a href="{{ route('support-desk.transactions.index') }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-[#0056D2] flex items-center justify-center border border-indigo-100/50"><i data-lucide="receipt" class="w-4 h-4"></i></div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Verify a Transaction</span>
                        <span class="block text-xs text-slate-400">Search by ref, amount, or customer</span>
                    </div>
                </a>

                <a href="{{ route('support-desk.sims.index') }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100/50"><i data-lucide="cpu" class="w-4 h-4"></i></div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Check a SIM</span>
                        <span class="block text-xs text-slate-400">Status, holder, and history</span>
                    </div>
                </a>

                <a href="{{ route('support-desk.users.index') }}" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center border border-slate-200/50"><i data-lucide="user" class="w-4 h-4"></i></div>
                    <div>
                        <span class="block text-sm font-bold text-slate-800">Look up a User</span>
                        <span class="block text-xs text-slate-400">Profile, wallet, SIMs, tickets</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
