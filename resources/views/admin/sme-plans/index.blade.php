<x-app-layout>
    <div x-data="{ 
        addModalOpen: false,
        editModalOpen: false,
        addProvider: 'legacy',
        editPlan: { id: '', data_id: '', provider: 'legacy', network: '', plan_type: '', business_price: '', personal_price: '', agent_price: '', partner_price: '', vendor_amount: '', size: '', validity: '', status: 'enabled' }
    }" class="space-y-8 max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="relative overflow-hidden p-6 sm:p-8 bg-white border border-slate-200/80 rounded-xl shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-grid-pattern">
            <div class="absolute -right-16 -top-16 w-36 h-36 rounded-full bg-slate-50 blur-2xl opacity-50"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight font-display bg-gradient-to-r from-slate-900 via-slate-800 to-[#0056D2] bg-clip-text text-transparent">SME Data Plans Management</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">Add, update, activate or disable SME and Gifting data subscription plans for all networks.</p>
            </div>
            <div class="relative z-10 flex-shrink-0">
                <x-primary-button @click="addModalOpen = true" class="!text-xs">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add New Plan
                </x-primary-button>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0 shadow-inner">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0 shadow-inner">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    </div>
                    <span class="text-sm font-bold">Please resolve the following errors:</span>
                </div>
                <ul class="list-disc pl-11 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php $vendorLabels = ['gogoz' => 'Gogoz Concept', 'smeplug' => 'SME Plug', 'amsub' => 'AMSUB', 'legacy' => 'Fadeelposdatasub (Legacy)']; @endphp

        <!-- Activation Bonus Settings -->
        <div class="p-6 bg-white border border-slate-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center text-[#0056D2]">
                    <i data-lucide="gift" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-800 font-display">SIM Activation Data Bonus</h3>
                    <p class="text-xs text-slate-400">When on for a network, every SIM on that network silently receives a free data top-up on its own number when activated — no wallet is charged. Each provider has its own plan and switch, since a MTN SIM can only be topped up with a MTN plan. Plan choices below are limited to the currently active SME Data Vendor ({{ $vendorLabels[$activeDataProvider] ?? $activeDataProvider }}).</p>
                </div>
            </div>
            @php $bonusLogoMap = ['mtn' => 'mtn.jpg', 'airtel' => 'Airtel.png', 'glo' => 'glo.jpg', '9mobile' => '9Mobile.jpg']; @endphp

            {{-- One hidden form per provider; row controls below reference it via form="..." so the table stays valid HTML. --}}
            @foreach (\App\Models\ActivationBonusSettings::PROVIDERS as $provider)
                <form id="bonus-form-{{ $provider }}" method="POST" action="{{ route('admin.sme-plans.activation-bonus.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="provider" value="{{ $provider }}">
                </form>
            @endforeach

            <div class="rounded-xl border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-xs font-extrabold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Network</th>
                            <th class="py-3 px-4">Active</th>
                            <th class="py-3 px-4">Bonus Plan</th>
                            <th class="py-3 px-4 text-right pr-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (\App\Models\ActivationBonusSettings::PROVIDERS as $provider)
                            @php
                                $bonusSettings = $bonusSettingsByProvider[$provider];
                                $eligiblePlans = $bonusEligiblePlansByNetwork[strtoupper($provider)] ?? collect();
                            @endphp
                            <tr class="align-middle">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ asset('assets/images/apps/' . $bonusLogoMap[$provider]) }}"
                                             alt="{{ $provider }}"
                                             class="w-8 h-8 rounded-xl object-contain border border-slate-100 flex-shrink-0"
                                             onerror="this.src='{{ asset('assets/images/apps/default.png') }}'">
                                        <span class="text-sm font-bold text-slate-700 uppercase">{{ $provider }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="is_active" value="1" form="bonus-form-{{ $provider }}"
                                               {{ $bonusSettings->is_active ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </label>
                                </td>
                                <td class="py-3 px-4 min-w-[240px]">
                                    <x-select-input name="sme_data_id" form="bonus-form-{{ $provider }}" class="rounded-lg !py-2 !text-xs" :disabled="$eligiblePlans->isEmpty()">
                                        <option value="">None selected</option>
                                        @foreach ($eligiblePlans as $p)
                                            <option value="{{ $p->id }}" {{ $bonusSettings->sme_data_id === $p->id ? 'selected' : '' }}>
                                                {{ $p->size }} ({{ $p->plan_type }}, {{ $p->validity }})
                                            </option>
                                        @endforeach
                                    </x-select-input>
                                    @if ($eligiblePlans->isEmpty())
                                        <p class="text-xs text-amber-600 mt-1">No enabled {{ strtoupper($provider) }} plans yet.</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right pr-4">
                                    <x-primary-button type="submit" form="bonus-form-{{ $provider }}" class="!text-xs">
                                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                        Save
                                    </x-primary-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SME Data Vendor -->
        <div class="p-6 bg-white border border-slate-200 rounded-xl shadow-sm">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center text-[#0056D2]">
                    <i data-lucide="plug" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-800 font-display">SME Data Vendor</h3>
                    <p class="text-xs text-slate-400">Only the active vendor's plans are purchasable by users. Switch anytime — plans for the inactive vendor stay editable so you can prep it first.</p>
                </div>
            </div>

            {{-- One settings form + one activate form per vendor. --}}
            @foreach (\App\Models\SmeDataProviderSetting::PROVIDERS as $providerKey)
                <form id="provider-settings-form-{{ $providerKey }}" method="POST" action="{{ route('admin.sme-plans.provider-settings.update') }}" class="hidden">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="provider" value="{{ $providerKey }}">
                </form>
                <form id="provider-activate-form-{{ $providerKey }}" method="POST" action="{{ route('admin.sme-plans.provider-settings.activate') }}" class="hidden">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="provider" value="{{ $providerKey }}">
                </form>
            @endforeach

            <div class="space-y-4">
                @foreach (\App\Models\SmeDataProviderSetting::PROVIDERS as $providerKey)
                    @php $vendorSettings = $dataProviders[$providerKey]; @endphp
                    <div class="rounded-xl border border-slate-100 p-4 {{ $vendorSettings->is_active ? 'bg-emerald-50/40 border-emerald-100' : '' }}">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-3">
                            <div class="flex items-center gap-2.5 flex-1">
                                <span class="text-sm font-bold text-slate-700">{{ $vendorLabels[$providerKey] }}</span>
                                @if ($vendorSettings->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                        Active
                                    </span>
                                @endif
                            </div>
                            @unless ($vendorSettings->is_active)
                                <x-secondary-button type="submit" form="provider-activate-form-{{ $providerKey }}" class="!text-xs">
                                    <i data-lucide="power" class="w-3.5 h-3.5"></i>
                                    Set Active
                                </x-secondary-button>
                            @endunless
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <div class="sm:col-span-1">
                                <x-input-label value="Base URL" />
                                <x-text-input type="url" name="base_url" form="provider-settings-form-{{ $providerKey }}"
                                       value="{{ old('base_url', $vendorSettings->base_url) }}" required
                                       class="rounded-xl !text-xs" />
                            </div>
                            <div class="sm:col-span-1">
                                <x-input-label value="API Key" />
                                <x-text-input type="password" name="api_key" form="provider-settings-form-{{ $providerKey }}"
                                       placeholder="{{ $vendorSettings->api_key ? '•••••••• (leave blank to keep current)' : 'Not set' }}"
                                       class="rounded-xl !text-xs" />
                            </div>
                            <div class="sm:col-span-1">
                                <x-primary-button type="submit" form="provider-settings-form-{{ $providerKey }}" class="w-full !text-xs">
                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                    Save
                                </x-primary-button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Total Plans -->
            <x-card padding="p-3.5" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary shrink-0">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Plans</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $totalPlansCount }}</p>
                </div>
            </x-card>

            <!-- Active Plans -->
            <x-card padding="p-3.5" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Active Plans</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $activePlansCount }}</p>
                </div>
            </x-card>

            <!-- Disabled Plans -->
            <x-card padding="p-3.5" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 shrink-0">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Disabled Plans</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $disabledPlansCount }}</p>
                </div>
            </x-card>
        </div>

        <!-- Filter & Table Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-md shadow-slate-100/40 overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50/50 via-white to-slate-50/20">
                <form method="GET" action="{{ route('admin.sme-plans.index') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center gap-4">
                    <!-- Search Input -->
                    <div class="relative flex-grow">
                        <x-text-input type="text" name="search" :value="request('search')"
                                class="pl-11 pr-4 rounded-2xl font-semibold shadow-sm"
                                placeholder="Search by Data ID or bundle size..." />
                        <div class="absolute left-4 top-4 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>

                    <!-- Vendor Filter -->
                    <div class="w-full lg:w-48">
                        <div class="relative">
                            <x-select-input name="provider" onchange="this.form.submit()"
                                    class="pl-3 pr-8 rounded-2xl !text-xs font-bold text-slate-600 shadow-sm appearance-none cursor-pointer">
                                <option value="">All Vendors</option>
                                <option value="gogoz" {{ request('provider') === 'gogoz' ? 'selected' : '' }}>Gogoz Concept</option>
                                <option value="smeplug" {{ request('provider') === 'smeplug' ? 'selected' : '' }}>SME Plug</option>
                                <option value="amsub" {{ request('provider') === 'amsub' ? 'selected' : '' }}>AMSUB</option>
                                <option value="legacy" {{ request('provider') === 'legacy' ? 'selected' : '' }}>Legacy</option>
                            </x-select-input>
                            <div class="absolute right-3.5 top-4 pointer-events-none text-slate-400">
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Network Filter -->
                    <div class="w-full lg:w-48">
                        <div class="relative">
                            <x-select-input name="network" onchange="this.form.submit()"
                                    class="pl-3 pr-8 rounded-2xl !text-xs font-bold text-slate-600 shadow-sm appearance-none cursor-pointer">
                                <option value="">All Networks</option>
                                <option value="MTN" {{ request('network') === 'MTN' ? 'selected' : '' }}>MTN</option>
                                <option value="GLO" {{ request('network') === 'GLO' ? 'selected' : '' }}>GLO</option>
                                <option value="AIRTEL" {{ request('network') === 'AIRTEL' ? 'selected' : '' }}>Airtel</option>
                                <option value="9MOBILE" {{ request('network') === '9MOBILE' ? 'selected' : '' }}>9mobile</option>
                            </x-select-input>
                            <div class="absolute right-3.5 top-4 pointer-events-none text-slate-400">
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full lg:w-48">
                        <div class="relative">
                            <x-select-input name="status" onchange="this.form.submit()"
                                    class="pl-3 pr-8 rounded-2xl !text-xs font-bold text-slate-600 shadow-sm appearance-none cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="enabled" {{ request('status') === 'enabled' ? 'selected' : '' }}>Enabled Only</option>
                                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled Only</option>
                            </x-select-input>
                            <div class="absolute right-3.5 top-4 pointer-events-none text-slate-400">
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Button -->
                    @if(request('search') || request('provider') || request('network') || request('status'))
                        <div class="flex items-center flex-shrink-0">
                            <a href="{{ route('admin.sme-plans.index') }}" class="w-full lg:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-3 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-2xl transition-all duration-150 hover:shadow-sm">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                Reset
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-extrabold text-slate-400 uppercase tracking-wider bg-slate-50/30">
                            <th class="py-4 px-6 text-center w-16">S/N</th>
                            <th class="py-4 px-6">Plan Info</th>
                            <th class="py-4 px-6">Vendor</th>
                            <th class="py-4 px-6">Data ID</th>
                            <th class="py-4 px-6">Business Price</th>
                            <th class="py-4 px-6">Personal Price</th>
                            <th class="py-4 px-6">Agent Price</th>
                            <th class="py-4 px-6">Partner Price</th>
                            <th class="py-4 px-6">Validity</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right pr-8">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-800">
                        @forelse($plans as $plan)
                            <tr class="group border-l-4 border-l-transparent hover:border-l-[#0056D2] hover:bg-slate-50/40 transition-all duration-200">
                                <td class="py-4 px-6 text-center text-slate-400 text-xs font-semibold">
                                    {{ $plans->firstItem() + $loop->index }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <!-- Network Logo Image -->
                                        @php
                                            $logoMap = [
                                                'MTN' => 'mtn.jpg',
                                                'AIRTEL' => 'Airtel.png',
                                                'GLO' => 'glo.jpg',
                                                '9MOBILE' => '9Mobile.jpg'
                                            ];
                                            $logo = $logoMap[strtoupper($plan->network)] ?? 'default.png';
                                        @endphp
                                        <img src="{{ asset('assets/images/apps/' . $logo) }}" 
                                             alt="{{ $plan->network }}" 
                                             class="w-10 h-10 rounded-2xl object-contain shadow-sm border border-slate-100 flex-shrink-0"
                                             onerror="this.src='{{ asset('assets/images/apps/default.png') }}'">
                                        <div>
                                            <span class="block text-slate-800 font-extrabold text-sm leading-tight">{{ $plan->size }}</span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-extrabold mt-1 rounded-full uppercase tracking-wider
                                                {{ strtoupper($plan->plan_type) === 'SME' ? 'bg-indigo-50 bg-opacity-70 text-indigo-700 border border-indigo-100' : 'bg-amber-50 bg-opacity-70 text-amber-700 border border-amber-100' }}">
                                                {{ $plan->plan_type }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold rounded-full uppercase tracking-wider
                                        {{ match ($plan->provider) {
                                            'gogoz' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                                            'smeplug' => 'bg-purple-50 text-purple-700 border border-purple-100',
                                            'amsub' => 'bg-indigo-50 text-indigo-700 border border-indigo-100',
                                            default => 'bg-slate-100 text-slate-600 border border-slate-200',
                                        } }}">
                                        {{ match ($plan->provider) {
                                            'gogoz' => 'Gogoz Concept',
                                            'smeplug' => 'SME Plug',
                                            'amsub' => 'AMSUB',
                                            default => 'Legacy',
                                        } }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-600 font-mono text-xs font-semibold">
                                    {{ $plan->data_id }}
                                </td>
                                <td class="py-4 px-6 text-slate-800 font-extrabold">
                                    ₦{{ number_format($plan->business_price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-slate-800 font-extrabold">
                                    ₦{{ number_format($plan->personal_price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-slate-800 font-extrabold">
                                    ₦{{ number_format($plan->agent_price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-slate-800 font-extrabold">
                                    ₦{{ number_format($plan->partner_price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-slate-400 text-xs font-semibold">
                                    {{ $plan->validity }} Days
                                </td>
                                <td class="py-4 px-6">
                                    @if($plan->status === 'enabled')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full uppercase tracking-wider whitespace-nowrap">
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                            </span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-rose-50 text-rose-500 border border-rose-200 rounded-full uppercase tracking-wider whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                            Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right pr-8">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Button -->
                                        <button @click="
                                            editPlan = {
                                                id: '{{ $plan->id }}',
                                                data_id: '{{ $plan->data_id }}',
                                                provider: '{{ $plan->provider }}',
                                                network: '{{ $plan->network }}',
                                                plan_type: '{{ $plan->plan_type }}', 
                                                business_price: '{{ $plan->business_price }}', 
                                                personal_price: '{{ $plan->personal_price }}', 
                                                agent_price: '{{ $plan->agent_price }}', 
                                                partner_price: '{{ $plan->partner_price }}',
                                                vendor_amount: '{{ $plan->vendor_amount }}',
                                                size: '{{ addslashes($plan->size) }}',
                                                validity: '{{ $plan->validity }}', 
                                                status: '{{ $plan->status }}' 
                                            }; 
                                            editModalOpen = true;
                                        " class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl shadow-sm transition-all duration-150">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" 
                                                @click="confirmDelete('{{ $plan->id }}', '{{ addslashes($plan->size) }}')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-500 text-rose-600 hover:text-white border border-rose-100 hover:border-rose-500 rounded-xl shadow-sm transition-all duration-150">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                        
                                        <form id="delete-form-{{ $plan->id }}" action="{{ route('admin.sme-plans.destroy', $plan) }}" method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-12 text-slate-400 text-sm font-medium">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                            <i data-lucide="wifi-off" class="w-6 h-6"></i>
                                        </div>
                                        <span>No SME plans configured. Create one to get started!</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            {{ $plans->withQueryString()->links('vendor.pagination.custom') }}
        </div>

        <!-- Add Plan Modal -->
        <div x-show="addModalOpen" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="addModalOpen = false"></div>
            
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="addModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden bg-grid-pattern">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-extrabold text-slate-900 font-display">Add New SME Plan</h3>
                        <button @click="addModalOpen = false" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all duration-200 hover:rotate-90">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.sme-plans.store') }}" method="POST" id="addPlanForm">
                        @csrf
                        <div class="space-y-4 max-h-[60vh] overflow-y-auto px-1">
                            <!-- Data ID -->
                            <div>
                                <x-input-label value="Data ID (API Plan Code)" />
                                <x-text-input type="text" name="data_id" required placeholder="e.g., MTN_SME_1GB"
                                       class="rounded-2xl bg-slate-50/50" />
                            </div>

                            <!-- Vendor -->
                            <div>
                                <x-input-label value="Vendor" />
                                <x-select-input name="provider" x-model="addProvider" required class="rounded-2xl bg-slate-50/50">
                                    <option value="gogoz">Gogoz Concept</option>
                                    <option value="smeplug">SME Plug</option>
                                    <option value="amsub">AMSUB</option>
                                    <option value="legacy">Legacy (Fadeelposdatasub)</option>
                                </x-select-input>
                            </div>

                            <!-- Network & Type -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label value="Network" />
                                    <x-select-input name="network" required class="rounded-2xl bg-slate-50/50">
                                        <option value="MTN">MTN</option>
                                        <option value="GLO">GLO</option>
                                        <option value="AIRTEL">Airtel</option>
                                        <option value="9MOBILE">9mobile</option>
                                    </x-select-input>
                                </div>
                                <div>
                                    <x-input-label value="Plan Type" />
                                    <x-text-input type="text" name="plan_type" required placeholder="SME, GIFTING, etc"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                            </div>

                            <!-- Size & Validity -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label value="Size" />
                                    <x-text-input type="text" name="size" required placeholder="e.g., 5GB SME"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Validity (Days)" />
                                    <x-text-input type="text" name="validity" required placeholder="30"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                            </div>

                            <!-- Prices Grid -->
                            <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                                <div>
                                    <x-input-label value="Business Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="business_price" required placeholder="7150.00"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Personal Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="personal_price" required placeholder="7500.00"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Agent Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="agent_price" required placeholder="7200.00"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Partner Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="partner_price" required placeholder="7300.00"
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                            </div>

                            <!-- Status Toggle -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200/65 rounded-2xl shadow-sm">
                                <div>
                                    <span class="block text-sm font-bold text-slate-900">Enabled Status</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">Control availability of this data plan on frontend.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="status" value="enabled" checked class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-5">
                            <x-secondary-button type="button" @click="addModalOpen = false" class="!text-xs">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">
                                Create Plan
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <div x-show="editModalOpen" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="editModalOpen = false"></div>
            
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="editModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden bg-grid-pattern">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-extrabold text-slate-900 font-display">Edit SME Plan</h3>
                        <button @click="editModalOpen = false" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all duration-200 hover:rotate-90">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form :action="`/admin/sme-plans/${editPlan.id}`" method="POST" id="editPlanForm">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4 max-h-[60vh] overflow-y-auto px-1">
                            <!-- Data ID -->
                            <div>
                                <x-input-label value="Data ID (API Plan Code)" />
                                <x-text-input type="text" name="data_id" x-bind:value="editPlan.data_id" required
                                       class="rounded-2xl bg-slate-50/50" />
                            </div>

                            <!-- Vendor -->
                            <div>
                                <x-input-label value="Vendor" />
                                <x-select-input name="provider" x-model="editPlan.provider" required class="rounded-2xl bg-slate-50/50">
                                    <option value="gogoz">Gogoz Concept</option>
                                    <option value="smeplug">SME Plug</option>
                                    <option value="amsub">AMSUB</option>
                                    <option value="legacy">Legacy (Fadeelposdatasub)</option>
                                </x-select-input>
                            </div>

                            <!-- Network & Type -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label value="Network" />
                                    <x-select-input name="network" x-bind:value="editPlan.network" x-model="editPlan.network" required class="rounded-2xl bg-slate-50/50">
                                        <option value="MTN">MTN</option>
                                        <option value="GLO">GLO</option>
                                        <option value="AIRTEL">Airtel</option>
                                        <option value="9MOBILE">9mobile</option>
                                    </x-select-input>
                                </div>
                                <div>
                                    <x-input-label value="Plan Type" />
                                    <x-text-input type="text" name="plan_type" x-bind:value="editPlan.plan_type" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                            </div>

                            <!-- Size & Validity -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label value="Size" />
                                    <x-text-input type="text" name="size" x-bind:value="editPlan.size" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Validity (Days)" />
                                    <x-text-input type="text" name="validity" x-bind:value="editPlan.validity" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                            </div>

                            <!-- Prices Grid -->
                            <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                                <div>
                                    <x-input-label value="Business Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="business_price" x-bind:value="editPlan.business_price" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Personal Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="personal_price" x-bind:value="editPlan.personal_price" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Agent Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="agent_price" x-bind:value="editPlan.agent_price" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                                <div>
                                    <x-input-label value="Partner Price (₦)" />
                                    <x-text-input type="number" step="0.01" name="partner_price" x-bind:value="editPlan.partner_price" required
                                           class="rounded-2xl bg-slate-50/50" />
                                </div>
                            </div>

                            <!-- Status Toggle -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200/65 rounded-2xl shadow-sm">
                                <div>
                                    <span class="block text-sm font-bold text-slate-900">Enabled Status</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">Control availability of this data plan on frontend.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="status" value="enabled" :checked="editPlan.status === 'enabled'" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-5">
                            <x-secondary-button type="button" @click="editModalOpen = false" class="!text-xs">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">
                                Update Plan
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- SweetAlert CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Form confirmation helper
        function confirmAction(formId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to save this SME Data Plan config?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0056D2',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addForm = document.getElementById('addPlanForm');
            if(addForm) {
                addForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('addPlanForm');
                });
            }

            const editForm = document.getElementById('editPlanForm');
            if(editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('editPlanForm');
                });
            }
        });

        function confirmDelete(id, size) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete the SME Data plan "${size}". This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
</x-app-layout>
