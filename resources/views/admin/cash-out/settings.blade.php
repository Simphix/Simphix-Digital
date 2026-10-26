<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
                    </div>
                    Cash Out Control
                </h1>
                <p class="text-xs text-slate-400 mt-1">Manage global cash out availability and role-specific withdrawal limits across the platform.</p>
            </div>
            <div>
                <a href="{{ route('admin.cash-out.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition">
                    <i data-lucide="banknote" class="w-3.5 h-3.5 text-slate-400"></i>
                    Cash Out Approvals
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl shadow-sm space-y-1">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside text-xs pl-6 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Master Switch Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl {{ $settings->is_enabled ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100' }} flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $settings->is_enabled ? 'power' : 'power-off' }}" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-800">Platform Cash Out Master Switch</h2>
                            <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded-full {{ $settings->is_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $settings->is_enabled ? 'Active / ON' : 'Disabled / OFF' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 max-w-xl leading-relaxed">
                            @if ($settings->is_enabled)
                                Cash outs are currently active. Users across permitted roles can submit cash out requests and administrators can process payouts.
                            @else
                                Cash outs are temporarily stopped. Users and platform reserve wallets cannot submit withdrawal requests, and payouts are suspended.
                            @endif
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.cash-out.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_enabled" value="{{ $settings->is_enabled ? 0 : 1 }}">
                    <button type="submit" role="switch" aria-checked="{{ $settings->is_enabled ? 'true' : 'false' }}"
                            aria-label="Toggle cash out"
                            onclick="return confirm('{{ $settings->is_enabled ? 'Switch cash out OFF for the whole platform?' : 'Switch cash out back ON?' }}')"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition {{ $settings->is_enabled ? 'bg-emerald-500' : 'bg-slate-300' }}">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $settings->is_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Role Cashout Limits Configuration -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-[#0056D2] flex items-center justify-center shrink-0">
                        <i data-lucide="scale" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Role-Based Cash Out Limits</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Define minimum & maximum withdrawal limits per transaction for each user role.</p>
                    </div>
                </div>
                <div class="text-[11px] text-slate-400 font-medium bg-slate-50 border border-slate-100 px-3 py-1.5 rounded-xl">
                    Currency: <strong class="text-slate-700">NGN (₦)</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.cash-out.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="divide-y divide-slate-100">
                    @foreach ($roleLimits as $roleKey => $info)
                        @php
                            $isEnterprise = in_array($roleKey, ['business', 'business_agent'], true);
                            $roleBadgeColor = match($roleKey) {
                                'business' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'business_agent' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'partner' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'coordinator' => 'bg-sky-50 text-sky-700 border-sky-200',
                                'regional_manager' => 'bg-teal-50 text-teal-700 border-teal-200',
                                'agent' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $roleDescription = match($roleKey) {
                                'business' => 'Enterprise Business (Corporate SIM Distributor)',
                                'business_agent' => 'Business Agent (Agent under Enterprise Business)',
                                'partner' => 'SIM Partner (Independent Distributor)',
                                'coordinator' => 'Area Operations Coordinator',
                                'regional_manager' => 'Regional Operations Manager',
                                'agent' => 'Retail / Field Agent',
                                'personal' => 'Standard Retail User Account',
                                default => 'User Role',
                            };
                        @endphp

                        <div class="p-6 transition hover:bg-slate-50/50 {{ $isEnterprise ? 'bg-indigo-50/20' : '' }}">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                                <!-- Role Title & Description -->
                                <div class="md:col-span-5 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-slate-800">{{ $info['label'] }}</span>
                                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md border {{ $roleBadgeColor }}">
                                            {{ $roleKey }}
                                        </span>
                                        @if ($isEnterprise)
                                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-indigo-600 text-white">
                                                Enterprise Tier
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-400 leading-normal">{{ $roleDescription }}</p>
                                </div>

                                <!-- Minimum Amount -->
                                <div class="md:col-span-3 space-y-1.5">
                                    <label for="min_{{ $roleKey }}" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Minimum Amount
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">
                                            ₦
                                        </span>
                                        <input type="number"
                                               name="role_limits[{{ $roleKey }}][min]"
                                               id="min_{{ $roleKey }}"
                                               value="{{ old("role_limits.{$roleKey}.min", $info['min']) }}"
                                               step="0.01"
                                               min="0"
                                               required
                                               class="w-full pl-7 pr-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs"
                                               placeholder="0.00" />
                                    </div>
                                    @error("role_limits.{$roleKey}.min")
                                        <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Maximum Amount -->
                                <div class="md:col-span-4 space-y-1.5">
                                    <label for="max_{{ $roleKey }}" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        Maximum Amount
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">
                                            ₦
                                        </span>
                                        <input type="number"
                                               name="role_limits[{{ $roleKey }}][max]"
                                               id="max_{{ $roleKey }}"
                                               value="{{ old("role_limits.{$roleKey}.max", $info['max']) }}"
                                               step="0.01"
                                               min="0"
                                               required
                                               class="w-full pl-7 pr-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs"
                                               placeholder="0.00" />
                                    </div>
                                    @error("role_limits.{$roleKey}.max")
                                        <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer Save Action -->
                <div class="p-6 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-xs text-slate-400">
                        Changes apply immediately to all subsequent cash out submissions for these roles.
                    </p>
                    <x-primary-button type="submit" class="px-6 py-2.5">
                        <i data-lucide="save" class="w-4 h-4 mr-1.5"></i>
                        Save Role Limits
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
