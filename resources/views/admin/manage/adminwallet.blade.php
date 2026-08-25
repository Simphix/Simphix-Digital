<x-app-layout>
    <div x-data="{ singleModalOpen: {{ ($errors->any() && !$errors->has('confirm_general') && !old('confirm_general')) ? 'true' : 'false' }}, generalModalOpen: {{ ($errors->any() && ($errors->has('confirm_general') || old('confirm_general'))) ? 'true' : 'false' }} }" class="space-y-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Manual Wallet Adjustment</h1>
                <p class="text-xs text-slate-400 mt-1">Fund or debit users manually, or execute general adjustments across all registered accounts.</p>
            </div>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl space-y-1">
                <div class="flex items-center gap-3 font-semibold text-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0"></i>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc pl-8 text-xs font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Platform Welfare Wallet Card -->
            <div class="relative group p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <a href="{{ route('admin.platform-wallets.cashout.show', \App\Models\PlatformWallet::TYPE_WELFARE) }}" class="absolute inset-0" aria-label="Cash out Platform Welfare Wallet"></a>
                <a href="{{ route('admin.platform-wallets.welfare-settings.edit') }}"
                   class="absolute top-3 right-3 z-10 p-1.5 rounded-lg text-slate-300 hover:text-slate-600 hover:bg-slate-50 transition-colors"
                   title="Welfare wallet settings">
                    <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                </a>
                <div class="flex items-center justify-between pointer-events-none">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Platform Welfare Wallet</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">₦{{ number_format($welfareWallet->balance, 2) }}</h3>
                        @if (!$welfareConfigured)
                            <p class="text-[11px] text-amber-600 mt-1.5 font-semibold">Not crediting yet &middot; click the gear to set an amount</p>
                        @else
                            <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Credited ₦{{ number_format($welfareWallet->total_credited, 2) }} all-time &middot; Click to cash out</p>
                        @endif
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-100/50 flex items-center justify-center text-sky-600">
                        <i data-lucide="shield-plus" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Utilities Revenue Wallet Card -->
            <a href="{{ route('admin.platform-wallets.cashout.show', \App\Models\PlatformWallet::TYPE_UTILITIES_REVENUE) }}"
               class="block p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Utilities Revenue Wallet</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">₦{{ number_format($utilitiesWallet->balance, 2) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Credited ₦{{ number_format($utilitiesWallet->total_credited, 2) }} all-time &middot; Click to cash out</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-violet-50 border border-violet-100/50 flex items-center justify-center text-violet-600">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                </div>
            </a>

            <!-- Monthly Manual Credit Card -->
            <div class="p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Credit (This Month)</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">₦{{ number_format($monthlyManualCredit, 2) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Credited in {{ now()->format('F') }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="trending-up" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Monthly Manual Debit Card -->
            <div class="p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Debit (This Month)</p>
                        <h3 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">₦{{ number_format($monthlyManualDebit, 2) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Debited in {{ now()->format('F') }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200/50 flex items-center justify-center text-slate-600">
                        <i data-lucide="trending-down" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Single User Action Card -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between items-start gap-4">
                <div class="space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-[#0056D2]/10 text-[#0056D2] flex items-center justify-center">
                        <i data-lucide="user-cog" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 font-display">Single User Adjustment</h3>
                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">Credit or debit a specific user's wallet manually. Search and verify the user before performing the transaction.</p>
                </div>
                <x-primary-button @click="singleModalOpen = true" class="w-full !text-xs">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                    Open Single Adjustment Form
                </x-primary-button>
            </div>

            <!-- General Action Card -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between items-start gap-4">
                <div class="space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-800 font-display">General Adjustment</h3>
                        <span class="px-2 py-0.5 text-xs font-extrabold bg-[#0056D2]/10 text-[#0056D2] rounded-full uppercase tracking-wider">{{ $totalUsers }} users target</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed font-semibold">Execute manual funding or debiting adjustments globally across all registered users in the database.</p>
                </div>
                <x-danger-button @click="generalModalOpen = true" class="w-full !text-xs">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                    Open System-wide Form
                </x-danger-button>
            </div>
        </div>

        <!-- Single User Adjustment Modal -->
        <div x-show="singleModalOpen" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-cloak>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
                 x-show="singleModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="singleModalOpen = false"></div>

            <!-- Modal Content Wrapper -->
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                     x-show="singleModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <!-- Close button -->
                    <button @click="singleModalOpen = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>

                    <div class="p-6 sm:p-8 space-y-6">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 font-display">Single User Adjustment</h2>
                            <p class="text-xs text-slate-400 mt-1">Manually credit or debit a specific user account.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.manage.adminwallet.single') }}" class="space-y-4">
                            @csrf

                            <!-- Search User Identifier -->
                            <div class="space-y-1.5">
                                <label for="identifier" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">User Identifier</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-grow">
                                        <x-text-input type="text" id="identifier" name="identifier" :value="old('identifier')" required
                                               class="pl-10 pr-4 rounded-xl !text-xs bg-slate-50"
                                               placeholder="Enter user email, phone, or wallet number..." />
                                        <div class="absolute left-3.5 top-3 text-slate-400">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                        </div>
                                    </div>
                                    <x-primary-button type="button" id="btn-verify" class="!text-xs !bg-slate-800 hover:!bg-slate-700 shrink-0">
                                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                        Verify
                                    </x-primary-button>
                                </div>
                            </div>

                            <!-- User Live Preview Container -->
                            <div id="verify-preview" class="hidden p-4 rounded-2xl border border-slate-100 bg-slate-50/50 space-y-2 transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0056D2]/10 text-[#0056D2] flex items-center justify-center font-bold text-xs uppercase" id="preview-avatar">
                                        US
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-800" id="preview-name">User Name</span>
                                        <span class="block text-xs text-slate-400" id="preview-email">user@example.com</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-400 uppercase">Wallet ID</span>
                                        <span class="block text-xs font-semibold text-slate-700" id="preview-wallet">N/A</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-400 uppercase">Current Balance</span>
                                        <span class="block text-xs font-bold text-emerald-600" id="preview-balance">₦0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Adjustment Type -->
                            <div class="space-y-1.5">
                                <label for="single-type" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Adjustment Type</label>
                                <x-select-input id="single-type" name="type" required class="rounded-xl !text-xs bg-slate-50">
                                    <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Credit (Add Funds)</option>
                                    <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit (Deduct Funds)</option>
                                </x-select-input>
                            </div>

                            <!-- Amount -->
                            <div class="space-y-1.5">
                                <label for="single-amount" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Amount (₦)</label>
                                <div class="relative">
                                    <x-text-input type="number" id="single-amount" name="amount" step="0.01" min="0.01"
                                           :value="old('amount')" required
                                           class="pl-8 pr-4 rounded-xl !text-xs bg-slate-50"
                                           placeholder="0.00" />
                                    <div class="absolute left-3.5 top-3 text-slate-400 text-xs font-bold">₦</div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="space-y-1.5">
                                <label for="single-description" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Reason / Description</label>
                                <x-textarea-input id="single-description" name="description" rows="2" required
                                          class="rounded-xl !text-xs bg-slate-50"
                                          placeholder="Provide detail for this transaction (visible to the user)...">{{ old('description') }}</x-textarea-input>
                            </div>

                            <!-- Password Confirmation -->
                            <div class="space-y-1.5 pt-2">
                                <label for="single-password" class="block text-xs font-bold text-rose-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                    Confirm Admin Password
                                </label>
                                <x-text-input type="password" id="single-password" name="password" required
                                       class="rounded-xl !text-xs bg-slate-50 !border-rose-200 focus:!border-rose-400 focus:!ring-rose-400/15"
                                       placeholder="Enter your admin login password..." />
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-2">
                                <x-secondary-button type="button" @click="singleModalOpen = false" class="w-1/2 !text-xs">
                                    Cancel
                                </x-secondary-button>
                                <x-primary-button type="submit" class="w-1/2 !text-xs">
                                    <i data-lucide="check-square" class="w-4 h-4"></i>
                                    Execute
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- General / System-Wide Adjustment Modal -->
        <div x-show="generalModalOpen" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-cloak>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
                 x-show="generalModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="generalModalOpen = false"></div>

            <!-- Modal Content Wrapper -->
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                     x-show="generalModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <!-- Close button -->
                    <button @click="generalModalOpen = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>

                    <div class="p-6 sm:p-8 space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-bold text-slate-800 font-display">General / System-wide Adjustment</h2>
                                <span class="px-2.5 py-1 text-xs font-extrabold bg-[#0056D2]/10 text-[#0056D2] rounded-full uppercase tracking-wider">
                                    {{ $totalUsers }} users target
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Execute system-wide funding or debiting adjustments across all users.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.manage.adminwallet.general') }}" class="space-y-4" onsubmit="return confirmGeneralAction();">
                            @csrf

                            <!-- Adjustment Type -->
                            <div class="space-y-1.5">
                                <label for="general-type" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Adjustment Type</label>
                                <x-select-input id="general-type" name="type" required class="rounded-xl !text-xs bg-slate-50">
                                    <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Credit All Users (Add Funds)</option>
                                    <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit All Users (Deduct Funds)</option>
                                </x-select-input>
                            </div>

                            <!-- Amount -->
                            <div class="space-y-1.5">
                                <label for="general-amount" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Amount per User (₦)</label>
                                <div class="relative">
                                    <x-text-input type="number" id="general-amount" name="amount" step="0.01" min="0.01"
                                           :value="old('amount')" required
                                           class="pl-8 pr-4 rounded-xl !text-xs bg-slate-50"
                                           placeholder="0.00" />
                                    <div class="absolute left-3.5 top-3 text-slate-400 text-xs font-bold">₦</div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="space-y-1.5">
                                <label for="general-description" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Reason / Description</label>
                                <x-textarea-input id="general-description" name="description" rows="2" required
                                          class="rounded-xl !text-xs bg-slate-50"
                                          placeholder="Provide general adjustment reason...">{{ old('description') }}</x-textarea-input>
                            </div>

                            <!-- Confirmation Switch -->
                            <div class="p-3 bg-amber-50/60 border border-amber-100 rounded-2xl flex items-start gap-3">
                                <input type="checkbox" 
                                       id="confirm_general" 
                                       name="confirm_general" 
                                       class="mt-1 w-4 h-4 text-amber-600 border-slate-300 focus:ring-amber-500 rounded cursor-pointer" 
                                       required>
                                <label for="confirm_general" class="text-[11px] font-semibold text-amber-800 leading-relaxed cursor-pointer select-none">
                                    I understand that this action will adjust the wallets of all <strong>{{ $totalUsers }}</strong> users. I have double-checked the amount and type, and understand that this action is irreversible.
                                </label>
                            </div>

                            <!-- Password Confirmation -->
                            <div class="space-y-1.5 pt-2">
                                <label for="general-password" class="block text-xs font-bold text-rose-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                    Confirm Admin Password
                                </label>
                                <x-text-input type="password" id="general-password" name="password" required
                                       class="rounded-xl !text-xs bg-slate-50 !border-rose-200 focus:!border-rose-400 focus:!ring-rose-400/15"
                                       placeholder="Enter your admin login password to confirm..." />
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-2">
                                <x-secondary-button type="button" @click="generalModalOpen = false" class="w-1/2 !text-xs">
                                    Cancel
                                </x-secondary-button>
                                <x-danger-button type="submit" class="w-1/2 !text-xs">
                                    <i data-lucide="users" class="w-4 h-4"></i>
                                    Execute
                                </x-danger-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/30">
                <h2 class="text-lg font-bold text-slate-800 font-display">Recent Manual Activity Logs</h2>
                <p class="text-xs text-slate-400 mt-1">Audit log of all manual credits and debits executed in the system.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">User</th>
                            <th class="py-4 px-6">Ref / ID</th>
                            <th class="py-4 px-6">Type</th>
                            <th class="py-4 px-6">Amount</th>
                            <th class="py-4 px-6">Reason / Details</th>
                            <th class="py-4 px-6">Performed By</th>
                            <th class="py-4 px-6">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($transactions as $tx)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-[#0056D2]/5 text-[#0056D2] rounded-xl flex items-center justify-center font-bold text-xs uppercase">
                                            {{ substr($tx->user->first_name ?? 'U', 0, 1) }}{{ substr($tx->user->last_name ?? 'S', 0, 1) }}
                                        </div>
                                        <div>
                                            <span class="block text-slate-800 font-bold font-display text-xs">
                                                {{ $tx->user ? $tx->user->first_name . ' ' . $tx->user->last_name : 'Deleted User' }}
                                            </span>
                                            <span class="block text-xs text-slate-400">{{ $tx->user->email ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-xs font-semibold text-slate-500 font-mono">{{ $tx->transaction_ref }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    @if ($tx->type === 'manual_credit')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                            Credit
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-100 rounded-full uppercase tracking-wider">
                                            Debit
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 font-bold text-xs">
                                    @if ($tx->type === 'manual_credit')
                                        <span class="text-emerald-600">+₦{{ number_format($tx->amount, 2) }}</span>
                                    @else
                                        <span class="text-rose-600">-₦{{ number_format($tx->amount, 2) }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500 font-semibold max-w-[200px] truncate">
                                    {{ $tx->description }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-600 font-bold">
                                    {{ $tx->performed_by ?? 'System' }}
                                </td>
                                <td class="py-4 px-6 text-slate-400 text-xs">
                                    {{ $tx->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400 text-sm">
                                    No manual adjustment transactions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $transactions->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnVerify = document.getElementById('btn-verify');
            const inputIdentifier = document.getElementById('identifier');
            const previewContainer = document.getElementById('verify-preview');
            const previewAvatar = document.getElementById('preview-avatar');
            const previewName = document.getElementById('preview-name');
            const previewEmail = document.getElementById('preview-email');
            const previewWallet = document.getElementById('preview-wallet');
            const previewBalance = document.getElementById('preview-balance');

            btnVerify.addEventListener('click', function () {
                const identifierValue = inputIdentifier.value.trim();

                if (!identifierValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Empty Identifier',
                        text: 'Please enter a user Email, Phone, or Wallet Number.',
                        confirmButtonColor: '#0056D2'
                    });
                    return;
                }

                // Show processing indicator
                btnVerify.disabled = true;
                btnVerify.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Verifying...';
                lucide.createIcons();

                fetch('{{ route("admin.manage.adminwallet.verify-user") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ identifier: identifierValue })
                })
                .then(response => response.json())
                .then(data => {
                    btnVerify.disabled = false;
                    btnVerify.innerHTML = '<i data-lucide="search" class="w-3.5 h-3.5"></i> Verify';
                    lucide.createIcons();

                    if (data.success) {
                        // Render preview details
                        previewName.textContent = data.name;
                        previewEmail.textContent = data.email;
                        previewWallet.textContent = data.wallet_number;
                        previewBalance.textContent = '₦' + data.balance;

                        // Avatar initials
                        const names = data.name.split(' ');
                        const initials = names.map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
                        previewAvatar.textContent = initials || 'US';

                        // Show preview container
                        previewContainer.classList.remove('hidden');
                        previewContainer.classList.add('animate-in', 'fade-in', 'duration-200');
                    } else {
                        previewContainer.classList.add('hidden');
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Found',
                            text: data.message,
                            confirmButtonColor: '#0056D2'
                        });
                    }
                })
                .catch(err => {
                    btnVerify.disabled = false;
                    btnVerify.innerHTML = '<i data-lucide="search" class="w-3.5 h-3.5"></i> Verify';
                    lucide.createIcons();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while verifying the user. Please try again.',
                        confirmButtonColor: '#0056D2'
                    });
                });
            });
        });

        function confirmGeneralAction() {
            const amount = document.getElementById('general-amount').value;
            const type = document.getElementById('general-type').value;
            const actionText = type === 'credit' ? 'CREDIT (ADD)' : 'DEBIT (DEDUCT)';

            return confirm(`⚠️ DANGER ZONE! ⚠️\n\nAre you absolutely sure you want to ${actionText} ₦${amount} from ALL registered users in the system?\n\nThis will instantly modify all user wallets! Click OK to execute.`);
        }
    </script>
    @endpush
</x-app-layout>
