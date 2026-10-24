<x-app-layout>
    <title>SimPhix - {{ $wallet->name }} Cash Out</title>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="banknote" class="w-5 h-5"></i>
                    </div>
                    {{ $wallet->name }} Cash Out
                </h1>
                <p class="text-sm text-slate-500 mt-1">Current balance: <strong>₦{{ number_format($wallet->balance, 2) }}</strong></p>
            </div>

            <a href="{{ route('admin.manage.adminwallet') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-400"></i>
                Back to Admin Wallet
            </a>
        </div>

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

        @unless ($account)
            <!-- Empty state: no saved platform withdrawal account -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <i data-lucide="landmark" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-sm">No platform withdrawal account saved yet</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Add the shared payout account before submitting a platform cash-out request.</p>
                <a href="{{ route('admin.platform-wallets.withdrawal-account.edit') }}"
                   class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-xs shadow-sm transition-all hover:bg-primary/90">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Withdrawal Account
                </a>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-white px-6 py-5 border-b border-slate-100">
                    <h3 class="font-bold font-display text-slate-800">Request Cash Out</h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-medium">Sent for review by another super admin before payout.</p>
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Cashing Out To</label>
                            <a href="{{ route('admin.platform-wallets.withdrawal-account.edit') }}" class="text-xs font-bold text-primary hover:underline">Change account</a>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-center gap-3.5 shadow-inner">
                            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm text-[#0056D2]">
                                <i data-lucide="landmark" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0 flex-grow">
                                <p class="font-bold text-xs text-slate-800 truncate">{{ $account->account_name }}</p>
                                <p class="text-xs text-slate-400 font-semibold tracking-wide">{{ $account->bank_name }} &bull; {{ substr($account->account_no, 0, 3) }}****{{ substr($account->account_no, -3) }}</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.platform-wallets.cashout.store', $wallet->type) }}" class="space-y-4">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="amount" class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Cash Out Amount (₦)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">₦</div>
                                <x-text-input type="number" step="0.01" min="100" id="amount" name="amount"
                                       :value="old('amount')" required class="pl-8 pr-4" placeholder="0.00" />
                            </div>
                            <p class="text-xs text-slate-400 font-semibold">Min: ₦100.00 &bull; Available: ₦{{ number_format($wallet->balance, 2) }}</p>
                        </div>

                        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-amber-800 flex gap-3 shadow-sm">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                            <div class="text-[11px] leading-relaxed">
                                <strong>Manual Review Required:</strong> The wallet is debited immediately, but the payout only happens once another super admin approves this request.
                            </div>
                        </div>

                        <x-primary-button type="submit" class="w-full !text-xs">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            Request Cash Out
                        </x-primary-button>
                    </form>
                </div>
            </div>
        @endunless
    </div>
</x-app-layout>
