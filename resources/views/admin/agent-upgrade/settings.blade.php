<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Agent Upgrade Settings</h1>
            <p class="text-xs text-slate-400 mt-1">Set the minimum wallet balance required to upgrade to Agent, and the one-time commission paid to a Partner upline when their downline upgrades.</p>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (!$settings->is_active)
            <div class="p-4 bg-amber-50/70 border border-amber-100 text-amber-800 rounded-2xl flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-xs leading-relaxed">
                    <strong class="text-sm block mb-0.5">Partner commission payouts are switched off</strong>
                    Agent upgrades still happen instantly — check the box below and save to resume paying the upline partner's commission.
                </div>
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <form method="POST" action="{{ route('admin.agent-upgrade-settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-1.5">
                    <label for="minimum_wallet_balance" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Minimum Wallet Balance (₦)</label>
                    <div class="relative">
                        <x-text-input type="number" id="minimum_wallet_balance" name="minimum_wallet_balance" step="0.01" min="0"
                               :value="old('minimum_wallet_balance', $settings->minimum_wallet_balance)" required
                               class="pl-8 pr-4 rounded-xl !text-xs bg-slate-50" placeholder="10000.00" />
                        <div class="absolute left-3.5 top-3 text-slate-400 text-xs font-bold">₦</div>
                    </div>
                    <x-input-error :messages="$errors->get('minimum_wallet_balance')" class="mt-1" />
                </div>

                <div class="space-y-1.5">
                    <label for="partner_commission_amount" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Partner Referral Commission (₦)</label>
                    <div class="relative">
                        <x-text-input type="number" id="partner_commission_amount" name="partner_commission_amount" step="0.01" min="0"
                               :value="old('partner_commission_amount', $settings->partner_commission_amount)" required
                               class="pl-8 pr-4 rounded-xl !text-xs bg-slate-50" placeholder="0.00" />
                        <div class="absolute left-3.5 top-3 text-slate-400 text-xs font-bold">₦</div>
                    </div>
                    <x-input-error :messages="$errors->get('partner_commission_amount')" class="mt-1" />
                </div>

                <div class="flex items-center gap-3 p-3 bg-slate-50/60 border border-slate-100 rounded-2xl">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           class="w-4 h-4 text-primary border-slate-300 focus:ring-primary rounded cursor-pointer"
                           @checked(old('is_active', $settings->is_active))>
                    <label for="is_active" class="text-xs font-semibold text-slate-700 cursor-pointer select-none">
                        Partner commission payouts are active
                    </label>
                </div>

                <x-primary-button type="submit" class="!text-xs">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Settings
                </x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
