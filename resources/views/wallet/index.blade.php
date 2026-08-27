<x-app-layout>
    <title>SmartSIM - Wallet Funding & Rewards</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="wallet" class="w-5 h-5"></i>
                    </div>
                    My Wallet
                </h1>
                <p class="text-sm text-slate-500 mt-1">Manage your funds, track reward milestones, and configure virtual accounts.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                @if (auth()->user()->role !== 'agent')
                    <a href="{{ route('transfer') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">
                        <i data-lucide="send" class="w-3.5 h-3.5 text-slate-400"></i>
                        P2P Transfer
                    </a>
                    <a href="{{ route('withdraw') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">
                        <i data-lucide="banknote" class="w-3.5 h-3.5 text-slate-400"></i>
                        Cash Out
                    </a>
                @endif
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 space-y-2 shadow-sm animate-in fade-in duration-300">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                    <div class="text-sm font-bold">Please correct the following errors:</div>
                </div>
                <ul class="text-xs list-disc pl-8 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Left Side: Rewards & Activity Bonus -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                
                <!-- Rewards & Bonus Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col justify-between gap-5">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-vibrant/10 flex items-center justify-center text-vibrant">
                                    <i data-lucide="gift" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-800 font-display">{{ \App\Support\SimAccess::canBrowseCatalog(auth()->user()) ? 'Commission Balance' : 'Rewards & Bonus' }}</h3>
                                    <span class="text-xs text-slate-400">Claimable balance</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs text-slate-400 block mb-1">Accumulated Balance</span>
                            <div class="text-2xl font-bold font-display text-slate-900">
                                ₦{{ number_format($walletData['bonus'] ?? 0, 2) }}
                            </div>
                        </div>
                    </div>

                    <div>
                        @if(isset($walletData) && $walletData['bonus'] > 0)
                            <form method="POST" action="{{ route('wallet.claimBonus') }}">
                                @csrf
                                <x-primary-button type="submit" class="w-full font-display !bg-vibrant hover:!bg-[#008f4c]">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    Claim & Transfer to Main Wallet
                                </x-primary-button>
                            </form>
                        @else
                            <button class="w-full py-3 px-6 bg-slate-50 border border-slate-200 text-slate-400 font-semibold text-xs rounded-lg flex items-center justify-center gap-2 cursor-not-allowed font-display" disabled>
                                <i data-lucide="lock" class="w-4 h-4"></i>
                                No Bonus Available to Claim
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Motivation & Progress Card -->
                <div class="bg-primary/5 border border-primary/10 rounded-xl p-5 flex flex-col justify-between gap-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="bg-primary/10 text-primary text-xs font-semibold px-2 py-0.5 rounded-full flex items-center gap-1">
                                <i data-lucide="zap" class="w-3 h-3"></i>
                                Benefit
                            </span>
                        </div>

                        <div>
                            <h3 class="text-base font-semibold font-display leading-snug text-slate-800">Turn Transactions into Real Cash Rewards</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">Every transaction brings you closer to your next tier cash bonus.</p>
                        </div>

                        <!-- Progress Tracker -->
                        <div class="bg-white rounded-lg p-3.5 space-y-2 border border-primary/10">
                            <div class="flex justify-between items-center text-xs font-semibold">
                                <span class="text-slate-500">Next Target Level</span>
                                <span class="text-primary">₦{{ number_format($nextBonusTarget ?? 50000, 2) }}</span>
                            </div>

                            <!-- Custom Progress Bar -->
                            <div class="w-full bg-primary/10 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full transition-all duration-500" style="width: {{ $bonusProgress ?? 0 }}%"></div>
                            </div>

                            <div class="flex justify-between items-center text-xs text-slate-500 font-medium">
                                <span>Spend: ₦{{ number_format($currentSpend ?? 0, 2) }}</span>
                                <span>Progress: {{ $bonusProgress ?? 0 }}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white rounded-lg p-2.5 text-center border border-primary/10">
                            <i data-lucide="coins" class="w-4 h-4 text-primary mx-auto mb-1"></i>
                            <span class="block text-xs text-slate-600 font-semibold">5-10% Cashbacks</span>
                        </div>
                        <div class="bg-white rounded-lg p-2.5 text-center border border-primary/10">
                            <i data-lucide="medal" class="w-4 h-4 text-primary mx-auto mb-1"></i>
                            <span class="block text-xs text-slate-600 font-semibold">VIP Status Badges</span>
                        </div>
                    </div>
                </div>

                <!-- Pro Tips / Alert banner -->
                @if(!isset($walletData) || $walletData['bonus'] == 0)
                    <div class="bg-amber-50 border border-amber-100/80 rounded-lg p-4 text-amber-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                        <i data-lucide="lightbulb" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                        <div class="text-xs leading-normal">
                            <strong>Pro Tip:</strong> Complete just ₦10,000 more in transactions to unlock a ₦500 wallet bonus instantly!
                        </div>
                    </div>
                @endif

            </div>

            <!-- Right Side: Automatic Wallet Funding -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-100 flex flex-col justify-between">
                    
                    <!-- Card Header -->
                    <div class="bg-white px-6 py-5 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/10 text-primary">
                                <i data-lucide="bank" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="font-bold font-display text-slate-800">Automatic Funding</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Instant settlement to your wallet 24/7.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6 flex-grow space-y-6">
                        
                        <!-- Brief Description -->
                        <div class="text-center max-w-md mx-auto space-y-2">
                            <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                                <i data-lucide="zap" class="w-6 h-6"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm">Transfer & Receive Credits Instantly</h4>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Simply transfer funds from any bank app to your unique virtual bank account below to credit your SmartSIM wallet.
                            </p>
                        </div>

                        <!-- Important Warning Alert -->
                        <div class="bg-amber-50 border border-amber-100/70 rounded-lg p-4 text-amber-800 flex gap-3.5 shadow-sm">
                            <div class="bg-amber-100 text-amber-700 rounded-xl p-2 shrink-0">
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            </div>
                            <div class="space-y-1">
                                <h5 class="text-xs font-bold">Important: Avoid Same-Amount Transfers</h5>
                                <p class="text-[11px] text-slate-500 leading-normal">
                                    To prevent automated credit delays, please avoid making multiple transactions of the <strong>exact same amount</strong> within 3 minutes.
                                </p>
                            </div>
                        </div>

                        <!-- Account Details Section -->
                        @if($virtualAccount)
                            <div class="bg-slate-50 border border-slate-100 rounded-lg p-5 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Account Name</span>
                                        <span class="block text-xs font-bold text-slate-800">{{ $virtualAccount->account_name ?? $virtualAccount->accountName }}</span>
                                    </div>

                                    <div class="space-y-1">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Bank Name</span>
                                        <span class="block text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                                            {{ $virtualAccount->bank_name ?? $virtualAccount->bankName }}
                                        </span>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/60 pt-4 space-y-1" x-data="{ copied: false }">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Account Number</span>
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl font-extrabold font-display text-[#0056D2] tracking-wide" x-ref="accNum">{{ $virtualAccount->account_number ?? $virtualAccount->accountNo }}</span>
                                        <button @click="navigator.clipboard.writeText($refs.accNum.innerText); copied = true; setTimeout(() => copied = false, 2000)" 
                                                type="button" 
                                                class="px-2.5 py-1.5 bg-white border border-slate-200 text-slate-500 hover:text-[#0056D2] rounded-lg text-xs font-bold flex items-center gap-1 hover:bg-slate-50 shadow-sm transition-all">
                                            <i x-show="!copied" data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            <i x-show="copied" data-lucide="check" class="w-3.5 h-3.5 text-emerald-500" style="display: none;"></i>
                                            <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary bg-primary/10 px-3 py-1.5 rounded-full">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                    Secured Instant Settlement Gateway
                                </span>
                            </div>
                        @else
                            <!-- Inline Setup Form (Creates Virtual Account) -->
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 space-y-4">
                                <div class="text-center space-y-1.5 mb-4">
                                    <h4 class="font-bold text-slate-800 text-sm">Generate Funding Account</h4>
                                    <p class="text-xs text-slate-400">Generate your dedicated virtual account to start automated funding.</p>
                                </div>

                                <form method="POST" action="{{ route('virtual.account.create') }}" class="space-y-4"
                                      x-data="{ idType: '{{ old('identity_type', 'bvn') }}' }">
                                    @csrf
                                    <input type="hidden" name="identity_type" x-bind:value="idType">
                                    <div>
                                        <x-input-label value="Full Name" />
                                        <x-text-input type="text" name="name"
                                               :value="auth()->user()->first_name . ' ' . auth()->user()->last_name . ' ' . auth()->user()->middle_name"
                                               required />
                                    </div>

                                    <div>
                                        <x-input-label value="Phone Number" />
                                        <x-text-input type="tel" name="phone"
                                               :value="auth()->user()->phone"
                                               required />
                                    </div>

                                    <div>
                                        <x-input-label value="Verify Identity With" />
                                        <div class="flex gap-2">
                                            <button type="button" @click="idType = 'bvn'"
                                                    :class="idType === 'bvn' ? 'bg-[#0056D2] text-white' : 'bg-white text-slate-600 border border-slate-200'"
                                                    class="flex-1 py-2 rounded-lg text-xs font-bold transition-colors">
                                                BVN
                                            </button>
                                            <button type="button" @click="idType = 'nin'"
                                                    :class="idType === 'nin' ? 'bg-[#0056D2] text-white' : 'bg-white text-slate-600 border border-slate-200'"
                                                    class="flex-1 py-2 rounded-lg text-xs font-bold transition-colors">
                                                NIN
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-1">If your BVN fails verification, try NIN instead.</p>
                                    </div>

                                    <div x-show="idType === 'bvn'">
                                        <x-input-label value="Bank Verification Number (BVN)" />
                                        <x-text-input type="text" name="bvn"
                                               :value="old('bvn', auth()->user()->bvn)"
                                               placeholder="Enter 11-digit BVN"
                                               maxlength="11"
                                               pattern="\d{11}"
                                               title="BVN must be exactly 11 digits"
                                               x-bind:required="idType === 'bvn'" />
                                    </div>

                                    <div x-show="idType === 'nin'" x-cloak>
                                        <x-input-label value="National Identification Number (NIN)" />
                                        <x-text-input type="text" name="nin"
                                               :value="old('nin', auth()->user()->nin)"
                                               placeholder="Enter 11-digit NIN"
                                               maxlength="11"
                                               pattern="\d{11}"
                                               title="NIN must be exactly 11 digits"
                                               x-bind:required="idType === 'nin'" />
                                    </div>

                                    <div class="flex items-start gap-2.5 pt-2">
                                        <input type="checkbox" id="confirmCheck" class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary/20 shrink-0" required>
                                        <label for="confirmCheck" class="text-[11px] text-slate-500 leading-relaxed">
                                            I confirm that the above details are accurate and consent to create a virtual account.
                                        </label>
                                    </div>

                                    <x-primary-button type="submit" class="w-full mt-4">
                                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                        Generate Virtual Account
                                    </x-primary-button>
                                </form>
                            </div>
                        @endif

                    </div>

                    <!-- Card Footer -->
                    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-between text-xs text-slate-400 font-semibold">
                        <div class="flex items-center gap-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <span>PCI-DSS Compliant</span>
                        </div>
                        <span>Powered by PalmPay</span>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>