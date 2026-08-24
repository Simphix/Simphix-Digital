<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Platform Withdrawal Account</h1>
            <p class="text-xs text-slate-400 mt-1">The single shared bank account platform wallet cash-outs (welfare, utilities revenue) are paid into.</p>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl space-y-1">
                <ul class="list-disc pl-5 text-xs font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($account)
            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 text-[#0056D2]">
                    <i data-lucide="landmark" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-grow">
                    <p class="font-bold text-xs text-slate-800 truncate">{{ $account->account_name }}</p>
                    <p class="text-xs text-slate-400 font-semibold tracking-wide">{{ $account->bank_name }} &bull; {{ substr($account->account_no, 0, 3) }}****{{ substr($account->account_no, -3) }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <form method="POST" action="{{ route('admin.platform-wallets.withdrawal-account.update') }}" class="space-y-5">
                @csrf

                <div class="space-y-1.5">
                    <label for="bankCode" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Bank</label>
                    <x-select-input id="bankCode" name="bankCode" required class="rounded-xl !text-xs bg-slate-50">
                        <option value="">Select a bank</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->bank_code }}" {{ old('bankCode', $account->bank_code ?? '') === $bank->bank_code ? 'selected' : '' }}>
                                {{ $bank->bank_name }}
                            </option>
                        @endforeach
                    </x-select-input>
                </div>

                <div class="space-y-1.5">
                    <label for="account_no" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Account Number</label>
                    <x-text-input type="text" id="account_no" name="account_no" inputmode="numeric" maxlength="10"
                           :value="old('account_no', $account->account_no ?? '')" required
                           class="rounded-xl !text-xs bg-slate-50" placeholder="10-digit account number" />
                    <p class="text-[11px] text-slate-400">The account name is resolved automatically once saved — no need to enter it.</p>
                </div>

                <x-primary-button type="submit" class="!text-xs">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Withdrawal Account
                </x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
