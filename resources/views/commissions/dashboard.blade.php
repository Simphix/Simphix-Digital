<x-app-layout>
    <title>SmartSIM - Commission Dashboard</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="badge-percent" class="w-5 h-5"></i>
                </div>
                Commission Dashboard
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $user->hasRole('super_admin') ? 'Every commission payout across the network.' : 'Your commission earnings from SIM activations in your downline.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-card padding="p-3.5" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Commission Paid</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">₦{{ number_format($totalPaid, 2) }}</p>
                </div>
            </x-card>

            <x-card padding="p-3.5" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">{{ $user->hasRole('super_admin') ? 'Total Pending Commission' : 'Your Pending Commission' }}</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">₦{{ number_format($totalPending, 2) }}</p>
                </div>
            </x-card>
        </div>

        <x-card padding="p-6">
            <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">
                Earnings History
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            @if ($user->hasRole('super_admin'))
                                <th class="py-2.5">Earner</th>
                            @endif
                            <th class="py-2.5">Role</th>
                            <th class="py-2.5">SIM Number</th>
                            <th class="py-2.5">Week</th>
                            @if ($user->role === 'business')
                                <th class="py-2.5">Business Agent</th>
                                <th class="py-2.5 text-right">Price</th>
                                <th class="py-2.5 text-right">Agent Commission</th>
                            @endif
                            <th class="py-2.5 text-right">{{ $user->role === 'business' ? 'You Kept' : 'Amount' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($earnings as $earning)
                            @php $breakdown = $downlineBreakdown->get($earning->id); @endphp
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                @if ($user->hasRole('super_admin'))
                                    <td class="py-3 font-bold text-slate-800">{{ $earning->user ? ($earning->user->first_name . ' ' . $earning->user->last_name) : 'Unknown' }}</td>
                                @endif
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase bg-primary/10 text-primary">
                                        {{ str_replace('_', ' ', $earning->role) }}
                                    </span>
                                </td>
                                <td class="py-3 font-semibold text-slate-700">{{ $earning->sim->number ?? '—' }}</td>
                                <td class="py-3 text-slate-400 font-medium">{{ $earning->period_week->format('M d, Y') }}</td>
                                @if ($user->role === 'business')
                                    <td class="py-3 text-slate-600">{{ $breakdown['agent_name'] ?? '—' }}</td>
                                    <td class="py-3 text-right text-slate-500">₦{{ number_format($breakdown['price'] ?? 0, 2) }}</td>
                                    <td class="py-3 text-right text-slate-500">₦{{ number_format($breakdown['agent_amount'] ?? 0, 2) }}</td>
                                @endif
                                <td class="py-3 text-right font-bold text-slate-800">₦{{ number_format($earning->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $user->role === 'business' ? 7 : 5 }}" class="py-12 text-center text-slate-400">
                                    <div class="bg-slate-50 rounded-2xl w-14 h-14 flex items-center justify-center mb-3 mx-auto border border-slate-100">
                                        <i data-lucide="badge-percent" class="w-6 h-6 text-slate-400"></i>
                                    </div>
                                    <h6 class="font-bold text-slate-800 text-sm">No commissions yet</h6>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[280px] mx-auto">
                                        Commission is earned automatically whenever a SIM in your downline is activated.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $earnings->links('vendor.pagination.custom') }}
        </x-card>
    </div>
</x-app-layout>
