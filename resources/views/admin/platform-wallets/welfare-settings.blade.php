<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-sky-50 flex items-center justify-center text-sky-600 border border-sky-100/50 shadow-sm">
                        <i data-lucide="shield-plus" class="w-5 h-5"></i>
                    </div>
                    Platform Welfare Settings
                </h1>
                <p class="text-xs text-slate-400 mt-1">Configure welfare allocations and automatic deduction from the Platform SIM Operations Wallet on SIM activations.</p>
            </div>
            <div>
                <a href="{{ route('admin.manage.adminwallet') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition">
                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-slate-400"></i>
                    Admin Wallets
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl space-y-1 shadow-sm">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc pl-8 text-xs font-semibold space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Live Wallets Snapshot -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Platform SIM Operations Wallet -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">SIM Operations Wallet</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="smartphone" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-slate-900 font-display">₦{{ number_format($simOperationsWallet->balance, 2) }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Source wallet debited for welfare allocations upon retail activation.</p>
                </div>
            </div>

            <!-- Platform Welfare Wallet -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Welfare Wallet</span>
                    <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <i data-lucide="shield-plus" class="w-4 h-4"></i>
                    </span>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-slate-900 font-display">₦{{ number_format($welfareWallet->balance, 2) }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Total credited all-time: ₦{{ number_format($welfareWallet->total_credited, 2) }}</p>
                </div>
            </div>
        </div>

        @if (!$settings->is_active || $settings->amount_per_activation <= 0)
            <div class="p-4 bg-amber-50 border border-amber-100 text-amber-800 rounded-2xl flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-xs leading-relaxed">
                    <strong class="text-sm block mb-0.5">Welfare Allocations Paused</strong>
                    @if (!$settings->is_active)
                        Welfare wallet crediting is switched OFF — enable the toggle below and save to resume.
                    @else
                        The welfare rate per activation is ₦0.00 — SIM activations will not allocate welfare funds until an amount is set below.
                    @endif
                </div>
            </div>
        @endif

        <!-- Settings Form Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-[#0056D2] flex items-center justify-center shrink-0">
                    <i data-lucide="sliders" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Welfare Allocation Configuration</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Define the welfare contribution deducted from the SIM Operations Wallet and credited to Welfare.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.platform-wallets.welfare-settings.update') }}">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-6">
                    <!-- Rate & Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="amount_per_activation" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Welfare Rate / Amount <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">
                                    {{ old('welfare_type', $settings->welfare_type ?? 'fixed') === 'percent' ? '%' : '₦' }}
                                </span>
                                <input type="number" id="amount_per_activation" name="amount_per_activation" step="0.01" min="0"
                                       value="{{ old('amount_per_activation', $settings->amount_per_activation) }}" required
                                       class="w-full pl-8 pr-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs"
                                       placeholder="0.00" />
                            </div>
                            <x-input-error :messages="$errors->get('amount_per_activation')" class="mt-1" />
                            <p class="text-[10px] text-slate-400">Fixed Naira amount per activation, or percentage of SIM price.</p>
                        </div>

                        <div class="space-y-1.5">
                            <label for="welfare_type" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Calculation Type <span class="text-rose-500">*</span>
                            </label>
                            <select name="welfare_type" id="welfare_type"
                                    class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs">
                                <option value="fixed" {{ old('welfare_type', $settings->welfare_type ?? 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Amount (₦ per activation)</option>
                                <option value="percent" {{ old('welfare_type', $settings->welfare_type ?? 'fixed') === 'percent' ? 'selected' : '' }}>Percentage (% of SIM activation price)</option>
                            </select>
                            <x-input-error :messages="$errors->get('welfare_type')" class="mt-1" />
                            <p class="text-[10px] text-slate-400">Choose between flat fee or percentage rate.</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <label for="description" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Transaction Narration / Description
                        </label>
                        <input type="text" id="description" name="description" maxlength="255"
                               value="{{ old('description', $settings->description ?? 'Platform Welfare Contribution') }}"
                               class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs"
                               placeholder="e.g. Platform Welfare Contribution" />
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        <p class="text-[10px] text-slate-400">Statement description on platform wallet transactions.</p>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Toggles -->
                    <div class="space-y-3">
                        <!-- Deduct from SIM Operations Wallet Checkbox -->
                        <div class="flex items-start gap-3 p-3.5 bg-slate-50/70 border border-slate-100 rounded-2xl transition hover:bg-slate-50">
                            <input type="checkbox" id="deduct_from_sim_operations" name="deduct_from_sim_operations" value="1"
                                   class="w-4 h-4 text-[#0056D2] border-slate-300 focus:ring-[#0056D2] rounded cursor-pointer mt-0.5"
                                   @checked(old('deduct_from_sim_operations', $settings->deduct_from_sim_operations ?? true))>
                            <div>
                                <label for="deduct_from_sim_operations" class="text-xs font-bold text-slate-700 cursor-pointer select-none">
                                    Deduct welfare from the Platform SIM Operations Wallet on activation
                                </label>
                                <p class="text-[11px] text-slate-400 mt-0.5 leading-normal">
                                    When enabled, every retail SIM activation automatically debits the welfare allocation from the Platform SIM Operations Wallet and credits it into the Platform Welfare Wallet.
                                </p>
                            </div>
                        </div>

                        <!-- Active Toggle Checkbox -->
                        <div class="flex items-start gap-3 p-3.5 bg-slate-50/70 border border-slate-100 rounded-2xl transition hover:bg-slate-50">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   class="w-4 h-4 text-[#0056D2] border-slate-300 focus:ring-[#0056D2] rounded cursor-pointer mt-0.5"
                                   @checked(old('is_active', $settings->is_active))>
                            <div>
                                <label for="is_active" class="text-xs font-bold text-slate-700 cursor-pointer select-none">
                                    Platform Welfare allocation is active
                                </label>
                                <p class="text-[11px] text-slate-400 mt-0.5 leading-normal">
                                    Master switch. When unchecked, welfare allocations on SIM activations are paused.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-xs text-slate-400">
                        Changes take effect immediately on all subsequent SIM activations across the platform.
                    </p>
                    <x-primary-button type="submit" class="px-6 py-2.5">
                        <i data-lucide="save" class="w-4 h-4 mr-1.5"></i>
                        Save Welfare Settings
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
