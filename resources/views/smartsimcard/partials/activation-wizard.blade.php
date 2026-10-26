@php
    $spendableMain = max(0, (float) ($wallet->balance ?? 0) - (float) ($wallet->lien_amount ?? 0));
    $bonusBalance = (float) ($wallet->bonus ?? 0);
    $initialSims = collect($simsData ?? []);
    if (!empty($preselectedCategory)) {
        $initialSims = $initialSims->filter(fn ($s) => $s['category'] === $preselectedCategory)->values();
    }
@endphp

<div x-data="{
        step: 1,
        sims: @js($initialSims),
        selectedCategory: '{{ $preselectedCategory ?? '' }}',
        selectedSimId: '{{ old('sim_id', '') }}',
        selectedSim: null,
        simSearch: '',
        dropdownOpen: false,
        customerName: '{{ old('full_name', '') }}',
        customerPhone: '{{ old('phone_number', '') }}',
        customerEmail: '{{ old('email', '') }}',
        customerAddress: '{{ old('address', '') }}',
        walletSource: '{{ old('wallet_source', 'main') }}',
        mainBalance: {{ $spendableMain }},
        bonusBalance: {{ $bonusBalance }},
        isSubmitting: false,
        nameError: '',
        phoneError: '',

        init() {
            if (this.selectedSimId) {
                this.selectedSim = this.sims.find(s => s.id == this.selectedSimId) || null;
                if (this.selectedSim && !this.selectedCategory) {
                    this.selectedCategory = this.selectedSim.category;
                }
            }
            if (this.filteredSims.length === 1 && !this.selectedSim) {
                this.selectSim(this.filteredSims[0]);
            }
        },

        get availableCategories() {
            const map = {};
            this.sims.forEach(s => {
                if (!s.category) return;
                map[s.category] = (map[s.category] || 0) + 1;
            });
            return Object.keys(map).map(cat => ({
                name: cat,
                count: map[cat]
            }));
        },

        get filteredSims() {
            let result = this.sims;
            if (this.selectedCategory) {
                result = result.filter(s => s.category.toLowerCase() === this.selectedCategory.toLowerCase());
            }
            if (!this.simSearch.trim()) return result;
            const q = this.simSearch.toLowerCase().trim();
            return result.filter(s =>
                s.number.toLowerCase().includes(q) ||
                s.provider.toLowerCase().includes(q) ||
                s.category.toLowerCase().includes(q)
            );
        },

        onCategoryChange() {
            if (this.selectedSim && this.selectedCategory && this.selectedSim.category.toLowerCase() !== this.selectedCategory.toLowerCase()) {
                this.selectedSim = null;
                this.selectedSimId = '';
            }
        },

        selectSim(sim) {
            this.selectedSim = sim;
            this.selectedSimId = sim.id;
            this.dropdownOpen = false;
            this.simSearch = '';
        },

        goToStep2() {
            if (!this.selectedSim) return;
            this.step = 2;
        },

        goToStep3() {
            this.nameError = '';
            this.phoneError = '';

            if (!this.customerName.trim()) {
                this.nameError = 'Please enter the customer\'s full name.';
            }

            const cleanPhone = this.customerPhone.replace(/\s+/g, '');
            if (!cleanPhone) {
                this.phoneError = 'Please enter the customer\'s phone number.';
            } else if (cleanPhone.length < 10) {
                this.phoneError = 'Please enter a valid phone number (at least 10 digits).';
            }

            if (this.nameError || this.phoneError) return;

            // Auto-select affordable wallet if preferred
            const price = this.selectedSim ? this.selectedSim.price : 0;
            if (this.walletSource === 'main' && this.mainBalance < price && this.bonusBalance >= price) {
                this.walletSource = 'bonus';
            } else if (this.walletSource === 'bonus' && this.bonusBalance < price && this.mainBalance >= price) {
                this.walletSource = 'main';
            }

            this.step = 3;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        canAfford() {
            if (!this.selectedSim) return false;
            const price = this.selectedSim.price;
            if (this.walletSource === 'main') {
                return this.mainBalance >= price;
            }
            return this.bonusBalance >= price;
        },

        handleSubmit(e) {
            if (!this.canAfford() || this.isSubmitting) {
                e.preventDefault();
                return;
            }
            this.isSubmitting = true;
        }
    }"
    class="space-y-6">

    <!-- Stepper Navigation Header -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="flex items-center justify-between max-w-xl mx-auto">
            <!-- Step 1 -->
            <button type="button"
                    @click="if (step > 1) step = 1"
                    :class="step >= 1 ? 'cursor-pointer' : 'cursor-default'"
                    class="flex items-center gap-2 group text-left transition-colors">
                <div :class="step === 1 ? 'bg-primary text-white ring-4 ring-primary/20' : (step > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400')"
                     class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 transition-all duration-200">
                    <template x-if="step > 1">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    <template x-if="step <= 1">
                        <span>1</span>
                    </template>
                </div>
                <div class="hidden sm:block">
                    <div :class="step === 1 ? 'text-primary font-bold' : (step > 1 ? 'text-slate-800 font-semibold' : 'text-slate-400 font-medium')"
                         class="text-xs font-display">Step 1</div>
                    <div class="text-[11px] text-slate-500">Select SIM</div>
                </div>
            </button>

            <!-- Line 1-2 -->
            <div :class="step > 1 ? 'bg-emerald-500' : 'bg-slate-200'" class="flex-1 h-0.5 mx-3 transition-colors duration-200"></div>

            <!-- Step 2 -->
            <button type="button"
                    @click="if (step > 2) step = 2"
                    :class="step >= 2 ? 'cursor-pointer' : 'cursor-default'"
                    class="flex items-center gap-2 group text-left transition-colors">
                <div :class="step === 2 ? 'bg-primary text-white ring-4 ring-primary/20' : (step > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400')"
                     class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 transition-all duration-200">
                    <template x-if="step > 2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </template>
                    <template x-if="step <= 2">
                        <span>2</span>
                    </template>
                </div>
                <div class="hidden sm:block">
                    <div :class="step === 2 ? 'text-primary font-bold' : (step > 2 ? 'text-slate-800 font-semibold' : 'text-slate-400 font-medium')"
                         class="text-xs font-display">Step 2</div>
                    <div class="text-[11px] text-slate-500">Customer Info</div>
                </div>
            </button>

            <!-- Line 2-3 -->
            <div :class="step > 2 ? 'bg-emerald-500' : 'bg-slate-200'" class="flex-1 h-0.5 mx-3 transition-colors duration-200"></div>

            <!-- Step 3 -->
            <div class="flex items-center gap-2 text-left">
                <div :class="step === 3 ? 'bg-primary text-white ring-4 ring-primary/20' : 'bg-slate-100 text-slate-400'"
                     class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 transition-all duration-200">
                    <span>3</span>
                </div>
                <div class="hidden sm:block">
                    <div :class="step === 3 ? 'text-primary font-bold' : 'text-slate-400 font-medium'"
                         class="text-xs font-display">Step 3</div>
                    <div class="text-[11px] text-slate-500">Summary & Pay</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Wizard Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-7">
        <form action="{{ route('sims.activate') }}" method="POST" @submit="handleSubmit($event)">
            @csrf

            <!-- Hidden Inputs for Submission -->
            <input type="hidden" name="sim_id" x-bind:value="selectedSimId">
            <input type="hidden" name="full_name" x-bind:value="customerName">
            <input type="hidden" name="phone_number" x-bind:value="customerPhone">
            <input type="hidden" name="email" x-bind:value="customerEmail">
            <input type="hidden" name="address" x-bind:value="customerAddress">
            <input type="hidden" name="wallet_source" x-bind:value="walletSource">

            <!-- ============================================================= -->
            <!-- STEP 1: Select / Search SIM from dropdown                     -->
            <!-- ============================================================= -->
            <div x-show="step === 1" x-cloak class="space-y-6">
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="smartphone" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800 font-display">Step 1: Select SIM to Activate</h3>
                        <p class="text-xs text-slate-500">Search and pick an available SIM card from your held inventory.</p>
                    </div>
                </div>

                <template x-if="sims.length === 0">
                    <div class="py-10 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                            <i data-lucide="inbox" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No available SIMs to activate</p>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto">
                            You don't currently have any unactivated SIM cards assigned to your account.
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('sims.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary text-white text-xs font-semibold shadow hover:bg-primary/90">
                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                                Request SIM Cards
                            </a>
                        </div>
                    </div>
                </template>

                <template x-if="sims.length > 0">
                    <div class="space-y-5">
                        <!-- Category and Number Selection Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-start">
                            <!-- Select SIM Category Option -->
                            <div class="sm:col-span-5 space-y-1.5">
                                <label for="wizard_sim_category" class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
                                    <span>SIM Category</span>
                                    <span class="text-[10px] text-slate-400 font-normal lowercase" x-text="availableCategories.length + ' types available'"></span>
                                </label>
                                <div class="relative">
                                    <select id="wizard_sim_category"
                                            x-model="selectedCategory"
                                            @change="onCategoryChange()"
                                            class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-xs font-semibold text-slate-800 transition-all focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-sm">
                                        <option value="">All Categories</option>
                                        <template x-for="cat in availableCategories" :key="cat.name">
                                            <option :value="cat.name" x-text="cat.name + ' (' + cat.count + ' available)'"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <!-- Custom Searchable Dropdown -->
                            <div class="sm:col-span-7 space-y-1.5 relative" @click.away="dropdownOpen = false">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
                                    <span>Select SIM Number</span>
                                    <span class="text-[10px] text-primary font-bold" x-text="filteredSims.length + ' matching'"></span>
                                </label>

                                <!-- Dropdown trigger button -->
                                <button type="button"
                                        @click="dropdownOpen = !dropdownOpen; if(dropdownOpen) $nextTick(() => $refs.searchInput.focus())"
                                        class="w-full flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm transition-all hover:border-primary/50 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    <div class="flex items-center gap-3 truncate">
                                        <template x-if="selectedSim">
                                            <div class="flex items-center gap-2.5 truncate">
                                                <span class="font-bold text-sm text-slate-900 tracking-wide font-mono" x-text="selectedSim.number"></span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200"
                                                      x-text="selectedSim.provider"></span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary/10 text-primary border border-primary/20"
                                                      x-text="selectedSim.category"></span>
                                            </div>
                                        </template>
                                        <template x-if="!selectedSim">
                                            <span class="text-xs text-slate-400" x-text="selectedCategory ? 'Choose ' + selectedCategory + ' number…' : 'Choose a SIM number from dropdown…'"></span>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 text-slate-400">
                                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''"></i>
                                    </div>
                                </button>

                                <!-- Dropdown menu panel -->
                                <div x-show="dropdownOpen"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 translate-y-0"
                                     x-transition:leave-end="opacity-0 translate-y-1"
                                     class="absolute z-30 left-0 right-0 mt-1.5 max-h-72 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden flex flex-col"
                                     style="display: none;">

                                    <!-- Search Input inside dropdown -->
                                    <div class="p-2.5 border-b border-slate-100 bg-slate-50">
                                        <div class="relative">
                                            <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-3 text-slate-400"></i>
                                            <input type="text"
                                                   x-ref="searchInput"
                                                   x-model="simSearch"
                                                   placeholder="Search by number or network..."
                                                   class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                                        </div>
                                    </div>

                                    <!-- Options List -->
                                    <div class="overflow-y-auto flex-1 divide-y divide-slate-50">
                                        <template x-for="sim in filteredSims" :key="sim.id">
                                            <button type="button"
                                                    @click="selectSim(sim)"
                                                    class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-slate-50 transition-colors group"
                                                    :class="selectedSim && selectedSim.id === sim.id ? 'bg-primary/5' : ''">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs uppercase"
                                                         :class="sim.provider === 'mtn' ? 'bg-amber-100 text-amber-800' : (sim.provider === 'airtel' ? 'bg-rose-100 text-rose-800' : (sim.provider === 'glo' ? 'bg-emerald-100 text-emerald-800' : 'bg-lime-100 text-lime-900'))"
                                                         x-text="sim.provider.substring(0, 3)">
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-sm text-slate-900 group-hover:text-primary transition-colors font-mono" x-text="sim.number"></div>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="text-[11px] font-medium text-slate-500" x-text="sim.category"></span>
                                                            <span class="text-slate-300">·</span>
                                                            <span class="text-[11px] font-semibold text-emerald-600" x-text="'Earn ₦' + sim.formatted_commission"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-xs font-bold text-slate-800" x-text="'₦' + sim.formatted_price"></div>
                                                    <div class="text-[10px] text-slate-400">Activation Fee</div>
                                                </div>
                                            </button>
                                        </template>

                                        <template x-if="filteredSims.length === 0">
                                            <div class="py-6 text-center text-xs text-slate-400 space-y-1 px-4">
                                                <div>No SIM numbers found matching your criteria.</div>
                                                <button type="button" @click="selectedCategory = ''; simSearch = ''" class="text-primary font-semibold hover:underline">
                                                    Clear filters
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Category Pills -->
                        <div x-show="availableCategories.length > 1" class="flex flex-wrap items-center gap-1.5 pt-0.5">
                            <span class="text-[11px] font-semibold text-slate-400 mr-1">Filter Category:</span>
                            <button type="button"
                                    @click="selectedCategory = ''; onCategoryChange()"
                                    :class="selectedCategory === '' ? 'bg-primary text-white font-bold border-primary shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200 font-medium'"
                                    class="px-2.5 py-1 rounded-lg border text-xs transition-all flex items-center gap-1.5">
                                <span>All</span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded-full" :class="selectedCategory === '' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'" x-text="sims.length"></span>
                            </button>
                            <template x-for="cat in availableCategories" :key="cat.name">
                                <button type="button"
                                        @click="selectedCategory = cat.name; onCategoryChange()"
                                        :class="selectedCategory.toLowerCase() === cat.name.toLowerCase() ? 'bg-primary text-white font-bold border-primary shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border-slate-200 font-medium'"
                                        class="px-2.5 py-1 rounded-lg border text-xs transition-all flex items-center gap-1.5">
                                    <span x-text="cat.name"></span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded-full" :class="selectedCategory.toLowerCase() === cat.name.toLowerCase() ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'" x-text="cat.count"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Selected SIM Preview Card -->
                        <template x-if="selectedSim">
                            <div class="rounded-xl border border-primary/20 bg-primary/5 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 rounded-xl bg-primary text-white flex items-center justify-center shadow-sm">
                                        <i data-lucide="cpu" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base font-bold text-slate-900 font-mono tracking-wide" x-text="selectedSim.number"></span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                  x-text="selectedSim.provider"></span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5" x-text="selectedSim.category + ' Card'"></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 sm:border-l sm:border-primary/10 sm:pl-4">
                                    <div>
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fee to Debit</span>
                                        <span class="text-sm font-extrabold text-slate-900" x-text="'₦' + selectedSim.formatted_price"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Your Commission</span>
                                        <span class="text-sm font-extrabold text-emerald-600" x-text="'₦' + selectedSim.formatted_commission"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Next Action -->
                        <div class="pt-4 flex justify-end">
                            <button type="button"
                                    @click="goToStep2()"
                                    :disabled="!selectedSim"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span>Continue to Customer Info</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 2: Customer Information                                  -->
            <!-- ============================================================= -->
            <div x-show="step === 2" x-cloak class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 font-display">Step 2: Customer Information</h3>
                            <p class="text-xs text-slate-500">Enter the customer details for whom this SIM card is being activated.</p>
                        </div>
                    </div>

                    <template x-if="selectedSim">
                        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 text-xs font-semibold text-slate-700">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5 text-primary"></i>
                            <span class="font-mono" x-text="selectedSim.number"></span>
                        </div>
                    </template>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Full Name (Required) -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="cust_full_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Customer Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="cust_full_name"
                               x-model="customerName"
                               placeholder="e.g. Adebayo Ogunlesi"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                               :class="nameError ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''">
                        <p x-show="nameError" x-text="nameError" class="text-xs font-semibold text-rose-500"></p>
                    </div>

                    <!-- Phone Number (Required) -->
                    <div class="space-y-1.5">
                        <label for="cust_phone_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Customer Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel"
                               id="cust_phone_number"
                               x-model="customerPhone"
                               placeholder="e.g. 08012345678 or +234..."
                               class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                               :class="phoneError ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' : ''">
                        <p x-show="phoneError" x-text="phoneError" class="text-xs font-semibold text-rose-500"></p>
                        <p class="text-[11px] text-slate-400">Customer contact number for SMS receipts and service notifications.</p>
                    </div>

                    <!-- Email (Optional) -->
                    <div class="space-y-1.5">
                        <label for="cust_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Email Address <span class="text-slate-400 font-normal lowercase">(optional)</span>
                        </label>
                        <input type="email"
                               id="cust_email"
                               x-model="customerEmail"
                               placeholder="e.g. adebayo@example.com"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <p class="text-[11px] text-slate-400">Optional: Used to email customer invoice and details.</p>
                    </div>

                    <!-- Full Address (Optional) -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="cust_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Full Address <span class="text-slate-400 font-normal lowercase">(optional)</span>
                        </label>
                        <textarea id="cust_address"
                                  rows="2"
                                  x-model="customerAddress"
                                  placeholder="e.g. 15 Marina Boulevard, Victoria Island, Lagos"
                                  class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                    <button type="button"
                            @click="step = 1"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition-colors">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        <span>Back</span>
                    </button>

                    <button type="button"
                            @click="goToStep3()"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-sm transition-all hover:bg-primary/90">
                        <span>Continue to Summary</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 3: Summary, Wallet Selection & Activation                -->
            <!-- ============================================================= -->
            <div x-show="step === 3" x-cloak class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 font-display">Step 3: Review & Activate</h3>
                            <p class="text-xs text-slate-500">Confirm details, choose payment balance, and activate the SIM card.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Left: Details Summary -->
                    <div class="lg:col-span-7 space-y-4">
                        <!-- SIM Summary Card -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">SIM Details</span>
                                <button type="button" @click="step = 1" class="text-xs font-semibold text-primary hover:underline">Change</button>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">SIM Number</span>
                                    <span class="font-bold text-slate-800 font-mono text-sm" x-text="selectedSim?.number"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Network Provider</span>
                                    <span class="font-bold text-slate-800 uppercase" x-text="selectedSim?.provider"></span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-slate-400 block text-[11px]">Category</span>
                                    <span class="font-bold text-slate-800" x-text="selectedSim?.category"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Summary Card -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Customer Information</span>
                                <button type="button" @click="step = 2" class="text-xs font-semibold text-primary hover:underline">Edit</button>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Full Name</span>
                                    <span class="font-bold text-slate-800" x-text="customerName"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Phone Number</span>
                                    <span class="font-bold text-slate-800 font-mono" x-text="customerPhone"></span>
                                </div>
                                <div x-show="customerEmail">
                                    <span class="text-slate-400 block text-[11px]">Email</span>
                                    <span class="font-semibold text-slate-700" x-text="customerEmail"></span>
                                </div>
                                <div x-show="customerAddress" class="col-span-2">
                                    <span class="text-slate-400 block text-[11px]">Address</span>
                                    <span class="font-semibold text-slate-700" x-text="customerAddress"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Financial Summary & Payment Source -->
                    <div class="lg:col-span-5 space-y-4">
                        <!-- Financial Breakdown Card -->
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Price & Earnings</span>

                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 font-medium">Activation Fee (Debited)</span>
                                    <span class="font-extrabold text-sm text-slate-900" x-text="'₦' + (selectedSim ? selectedSim.formatted_price : '0.00')"></span>
                                </div>

                                <div class="flex justify-between items-center py-1.5 px-2.5 rounded-lg bg-emerald-50 border border-emerald-100">
                                    <span class="text-emerald-800 font-semibold flex items-center gap-1.5">
                                        <i data-lucide="badge-percent" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        Your Commission
                                    </span>
                                    <span class="font-extrabold text-emerald-700" x-text="'+₦' + (selectedSim ? selectedSim.formatted_commission : '0.00')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Spend From: Main Balance vs Commission Balance -->
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block">
                                Spend Activation Fee From:
                            </label>

                            <div class="space-y-2">
                                <!-- Option 1: Main Balance -->
                                <label class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all"
                                       :class="walletSource === 'main' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-slate-200 hover:bg-slate-50'">
                                    <div class="flex items-center gap-3">
                                        <input type="radio"
                                               name="wallet_option_radio"
                                               value="main"
                                               x-model="walletSource"
                                               class="text-primary focus:ring-primary h-4 w-4">
                                        <div>
                                            <span class="block text-xs font-bold text-slate-800">Main Wallet Balance</span>
                                            <span class="block text-[11px] text-slate-500" x-text="'Available: ₦' + Number(mainBalance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                        </div>
                                    </div>
                                    <template x-if="selectedSim && mainBalance < selectedSim.price">
                                        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">Low balance</span>
                                    </template>
                                </label>

                                <!-- Option 2: Commission Balance -->
                                <label class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all"
                                       :class="walletSource === 'bonus' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-slate-200 hover:bg-slate-50'">
                                    <div class="flex items-center gap-3">
                                        <input type="radio"
                                               name="wallet_option_radio"
                                               value="bonus"
                                               x-model="walletSource"
                                               class="text-primary focus:ring-primary h-4 w-4">
                                        <div>
                                            <span class="block text-xs font-bold text-slate-800">Commission / Bonus Balance</span>
                                            <span class="block text-[11px] text-slate-500" x-text="'Available: ₦' + Number(bonusBalance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                        </div>
                                    </div>
                                    <template x-if="selectedSim && bonusBalance < selectedSim.price">
                                        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">Low balance</span>
                                    </template>
                                </label>
                            </div>

                            <template x-if="!canAfford()">
                                <div class="p-2.5 rounded-lg bg-rose-50 border border-rose-100 text-rose-700 text-xs flex items-center gap-2">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500 shrink-0"></i>
                                    <span>Insufficient balance in the selected wallet. Please choose another source or fund your wallet.</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                    <button type="button"
                            @click="step = 2"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition-colors">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        <span>Back</span>
                    </button>

                    <button type="submit"
                            :disabled="!canAfford() || isSubmitting"
                            class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-md hover:bg-emerald-700 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="isSubmitting">
                            <div class="flex items-center gap-2">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Activating SIM…</span>
                            </div>
                        </template>
                        <template x-if="!isSubmitting">
                            <div class="flex items-center gap-2">
                                <i data-lucide="zap" class="w-4 h-4"></i>
                                <span>Activate SIM Now</span>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
