<x-app-layout>
    <title>SimPhix Support Desk - Transaction Lookup</title>

    <div class="space-y-8 max-w-5xl mx-auto">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Transaction Lookup</h1>
            <p class="text-xs text-slate-400 mt-1">Look up a transaction by its exact reference/ID, or a customer's exact email/phone for their transaction history. There are no platform-wide totals or general listings here.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="GET" action="{{ route('support-desk.transactions.index') }}" class="flex flex-col sm:flex-row items-stretch gap-3">
                <div class="relative flex-grow">
                    <x-text-input type="text" name="search" :value="$search"
                           class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                           placeholder="Transaction reference/ID, or customer's email/phone" />
                    <div class="absolute left-4 top-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                </div>
                <x-primary-button type="submit" class="!text-xs shrink-0">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    Look Up
                </x-primary-button>
                @if ($search !== '')
                    <a href="{{ route('support-desk.transactions.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150 shrink-0">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        @if ($search === '')
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm text-center py-16 text-slate-400">
                <i data-lucide="receipt" class="w-10 h-10 mx-auto text-slate-300"></i>
                <p class="text-sm font-semibold text-slate-600 mt-3">Enter a transaction reference/ID, or a customer's exact email/phone, to look it up.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6">Date</th>
                                <th class="py-4 px-6">Reference</th>
                                <th class="py-4 px-6">Customer</th>
                                <th class="py-4 px-6">Description</th>
                                <th class="py-4 px-6 text-right">Amount</th>
                                <th class="py-4 px-6 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                            @forelse ($transactions as $tx)
                                <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                    <td class="py-4 px-6 text-xs text-slate-500 whitespace-nowrap">{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                                    <td class="py-4 px-6 font-mono text-xs text-slate-600">{{ $tx->transaction_ref }}</td>
                                    <td class="py-4 px-6">
                                        @if ($tx->user)
                                            <a href="{{ route('support-desk.users.show', $tx->user) }}" class="font-bold text-slate-800 hover:text-[#0056D2] hover:underline block">{{ $tx->user->first_name }} {{ $tx->user->last_name }}</a>
                                            <span class="block text-xs text-slate-400">{{ $tx->user->email }}</span>
                                        @else
                                            <span class="text-slate-400 text-xs">System</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-slate-700 max-w-[220px] truncate" title="{{ $tx->description }}">{{ $tx->description }}</td>
                                    <td class="py-4 px-6 text-right font-extrabold {{ in_array($tx->type, ['credit', 'refund', 'bonus', 'manual_credit']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ in_array($tx->type, ['credit', 'refund', 'bonus', 'manual_credit']) ? '+' : '-' }}₦{{ number_format($tx->amount, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        @if(in_array($tx->status, ['completed', 'successful']))
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Success
                                            </span>
                                        @elseif($tx->status == 'failed')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-extrabold bg-rose-50 text-rose-700 border border-rose-100 rounded-full uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span> Failed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-100 rounded-full uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> {{ ucfirst($tx->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-16 text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-4 max-w-sm mx-auto">
                                            <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100 shadow-inner">
                                                <i data-lucide="search-x" class="w-8 h-8"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-extrabold text-slate-800 font-display">No match found</h6>
                                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">No transaction matches that reference/ID, and no customer matches that exact email/phone.</p>
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
