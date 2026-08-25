<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Platform Welfare Wallet Settings</h1>
            <p class="text-xs text-slate-400 mt-1">Set the fixed amount credited to the Platform Welfare Wallet every time a SIM activates.</p>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (!$settings->is_active || $settings->amount_per_activation <= 0)
            <div class="p-4 bg-amber-50/70 border border-amber-100 text-amber-800 rounded-2xl flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-xs leading-relaxed">
                    <strong class="text-sm block mb-0.5">Nothing is being credited right now</strong>
                    @if (!$settings->is_active)
                        Welfare wallet crediting is switched off — check the box below and save to resume it.
                    @else
                        The amount per activation is ₦0.00 — SIM activations won't add anything to the wallet until you set a real amount below and save.
                    @endif
                </div>
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <form method="POST" action="{{ route('admin.platform-wallets.welfare-settings.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-1.5">
                    <label for="amount_per_activation" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Amount per Activation (₦)</label>
                    <div class="relative">
                        <x-text-input type="number" id="amount_per_activation" name="amount_per_activation" step="0.01" min="0"
                               :value="old('amount_per_activation', $settings->amount_per_activation)" required
                               class="pl-8 pr-4 rounded-xl !text-xs bg-slate-50" placeholder="0.00" />
                        <div class="absolute left-3.5 top-3 text-slate-400 text-xs font-bold">₦</div>
                    </div>
                    <x-input-error :messages="$errors->get('amount_per_activation')" class="mt-1" />
                </div>

                <div class="flex items-center gap-3 p-3 bg-slate-50/60 border border-slate-100 rounded-2xl">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           class="w-4 h-4 text-primary border-slate-300 focus:ring-primary rounded cursor-pointer"
                           @checked(old('is_active', $settings->is_active))>
                    <label for="is_active" class="text-xs font-semibold text-slate-700 cursor-pointer select-none">
                        Welfare wallet crediting is active
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
