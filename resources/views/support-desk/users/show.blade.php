<x-app-layout>
    <title>SimPhix Support Desk - {{ $user->first_name }} {{ $user->last_name }}</title>

    @php
        $mask = fn (?string $value) => $value ? str_repeat('•', max(strlen($value) - 4, 0)) . substr($value, -4) : null;
    @endphp

    <div class="space-y-6 max-w-6xl mx-auto">
        <!-- Back Link -->
        <a href="{{ route('support-desk.users.index') }}"
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-[#0056D2] transition-colors duration-150">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to User Lookup
        </a>

        <!-- Hero Profile -->
        <div class="relative overflow-hidden bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="w-20 h-20 rounded-2xl overflow-hidden flex items-center justify-center font-extrabold text-2xl uppercase shadow-md border-2
                    @if ($user->status === 'active') border-emerald-500 ring-4 ring-emerald-500/10
                    @elseif ($user->status === 'suspended') border-amber-500 ring-4 ring-amber-500/10
                    @else border-rose-500 ring-4 ring-rose-500/10 @endif">
                    @if ($user->profile_photo)
                        <img src="{{ asset($user->profile_photo) }}" alt="Profile Photo" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-[#0056D2] to-[#0049b8] text-white flex items-center justify-center">
                            {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div>
                    <h1 class="text-xl font-bold font-display text-slate-800">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $user->email }} • {{ $user->phone ?? 'No phone on file' }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs font-extrabold uppercase border border-slate-200">{{ str_replace('_', ' ', $user->role) }}</span>
                        @if ($user->status === 'active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full uppercase">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Active
                            </span>
                        @elseif ($user->status === 'suspended')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-100 rounded-full uppercase">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Suspended
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-extrabold bg-rose-50 text-rose-700 border border-rose-100 rounded-full uppercase">
                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span> {{ ucfirst($user->status) }}
                            </span>
                        @endif
                    </div>
                    @if ($user->status === 'suspended' && $user->suspension_reason)
                        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-2 max-w-md">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 inline mr-1"></i>
                            {{ $user->suspension_reason }}
                            @if ($user->suspended_at) — {{ $user->suspended_at->format('M d, Y') }} @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="text-xs text-slate-500 space-y-1 shrink-0">
                <div>Joined {{ $user->created_at->format('M d, Y') }}</div>
                <div>Last login {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'never' }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Left column: KYC + Wallet + Virtual accounts -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">KYC Details</h3>
                    <div class="text-xs space-y-2.5">
                        <div class="flex justify-between gap-3"><span class="font-semibold text-slate-400">State / LGA</span><span class="font-bold text-slate-700 text-right">{{ $user->state ?? '—' }} / {{ $user->lga ?? '—' }}</span></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold text-slate-400">Address</span><span class="font-bold text-slate-700 text-right">{{ $user->address ?? '—' }}</span></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold text-slate-400">BVN</span><span class="font-bold text-slate-700 font-mono">{{ $mask($user->bvn) ?? 'Not on file' }}</span></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold text-slate-400">NIN</span><span class="font-bold text-slate-700 font-mono">{{ $mask($user->nin) ?? 'Not on file' }}</span></div>
                        @if ($user->business_name)
                            <div class="flex justify-between gap-3"><span class="font-semibold text-slate-400">Business</span><span class="font-bold text-slate-700 text-right">{{ $user->business_name }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Wallet (view only)</h3>
                    <div class="text-xs space-y-2.5">
                        <div class="flex justify-between"><span class="font-semibold text-slate-400">Balance</span><span class="font-bold text-emerald-600">₦{{ number_format($user->wallet->balance ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-semibold text-slate-400">Bonus</span><span class="font-bold text-slate-700">₦{{ number_format($user->wallet->bonus ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-semibold text-slate-400">Wallet Status</span><span class="font-bold text-slate-700 capitalize">{{ $user->wallet->status ?? 'inactive' }}</span></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50">Virtual Accounts</h3>
                    <div class="space-y-2">
                        @forelse ($user->virtualAccounts as $account)
                            <div class="p-3 bg-slate-50/50 rounded-xl border border-slate-100 text-xs">
                                <span class="block font-bold text-slate-800">{{ $account->bank_name }}</span>
                                <span class="block text-slate-500 font-mono">{{ $account->account_number }} • {{ $account->account_name }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No virtual accounts linked.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right column: SIMs, transactions, tickets -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50 mb-3">SIMs</h3>
                    <div class="space-y-2">
                        @forelse ($sims as $sim)
                            <a href="{{ route('support-desk.sims.show', $sim) }}" class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <div>
                                    <span class="block text-sm font-bold text-slate-800 font-mono">{{ $sim->number ?? '—' }}</span>
                                    <span class="block text-xs text-slate-400 capitalize">{{ $sim->category }} • {{ $sim->provider }}</span>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold border rounded-full uppercase tracking-wider {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">
                                    {{ \App\Support\SimStatus::label($sim->status) }}
                                </span>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">No SIMs assigned to this user.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-50 mb-3">
                        <h3 class="font-bold text-slate-800 font-display">Recent Transactions</h3>
                        <a href="{{ route('support-desk.transactions.index', ['search' => $user->email]) }}" class="text-xs font-bold text-[#0056D2] hover:underline">Search all</a>
                    </div>
                    <div class="space-y-2">
                        @forelse ($recentTransactions as $tx)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100">
                                <div class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-800 truncate">{{ $tx->description }}</span>
                                    <span class="block text-xs text-slate-400 font-mono">{{ $tx->transaction_ref }} • {{ $tx->created_at->format('M d, Y h:i A') }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="block text-sm font-extrabold {{ in_array($tx->type, ['credit', 'refund', 'bonus', 'manual_credit']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ in_array($tx->type, ['credit', 'refund', 'bonus', 'manual_credit']) ? '+' : '-' }}₦{{ number_format($tx->amount, 2) }}
                                    </span>
                                    <span class="block text-xs text-slate-400 uppercase">{{ $tx->status }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No transactions yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-50 mb-3">Recent Support Tickets</h3>
                    <div class="space-y-2">
                        @forelse ($recentTickets as $ticket)
                            <a href="{{ route('support-desk.tickets.show', $ticket) }}" class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-all">
                                <span class="text-sm font-bold text-slate-800 truncate">{{ $ticket->subject }}</span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold border rounded-full uppercase tracking-wider {{ $ticket->status_badge }} shrink-0">
                                    {{ $ticket->status }}
                                </span>
                            </a>
                        @empty
                            <p class="text-xs text-slate-400">No support tickets from this user.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
