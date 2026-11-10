<x-app-layout>
    <title>SimPhix - Customer Fintava Virtual Accounts</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="landmark" class="w-5 h-5"></i>
                    </div>
                    Customer Fintava Accounts
                </h1>
                <p class="text-sm text-slate-500 mt-1">Inspect live Fintava (Loma Bank) virtual account balances, sweep funds, and export customer transactions.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.fintava-sweeps.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition shadow-xs">
                    <i data-lucide="arrow-left-right" class="w-4 h-4 text-slate-400"></i>
                    Wallet Sweeps Audit
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Search Bar -->
        <form method="GET" action="{{ route('admin.fintava-accounts.index') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[240px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search by account number, email, phone, name, or customer ID..."
                       class="w-full pl-9 pr-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs" />
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition shadow-xs">
                Search Account
            </button>

            @if ($search !== '')
                <a href="{{ route('admin.fintava-accounts.index') }}" class="text-xs font-bold text-slate-400 hover:text-slate-600">
                    Clear Search
                </a>
            @endif
        </form>

        <!-- Accounts Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">User</th>
                            <th class="py-2.5">Account Number</th>
                            <th class="py-2.5">Bank</th>
                            <th class="py-2.5">Account Name</th>
                            <th class="py-2.5">Customer / Wallet ID</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Created</th>
                            <th class="py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $acc)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors align-middle">
                                <td class="py-3 text-slate-700">
                                    @if ($acc->user)
                                        <div class="font-semibold">{{ $acc->user->first_name }} {{ $acc->user->last_name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $acc->user->email }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 font-mono font-bold text-slate-800">{{ $acc->account_number }}</td>
                                <td class="py-3 text-slate-600 font-medium">{{ $acc->bank_name }}</td>
                                <td class="py-3 text-slate-700">{{ $acc->account_name }}</td>
                                <td class="py-3 font-mono text-[11px] text-slate-500">
                                    <div>Cust: {{ $acc->provider_customer_id ?? 'N/A' }}</div>
                                    <div>Wall: {{ $acc->provider_wallet_id ?? 'N/A' }}</div>
                                </td>
                                <td class="py-3">
                                    @if ($acc->is_active)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-400">Inactive</span>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-500 whitespace-nowrap">{{ $acc->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.fintava-accounts.show', $acc) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#0056D2] text-white text-xs font-bold hover:bg-[#0046ad] transition shadow-xs">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        Inspect & Transfer
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    No Fintava virtual accounts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($accounts->hasPages())
                <div class="pt-4 border-t border-slate-100">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
