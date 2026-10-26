<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto" x-data="{ activeTab: 'overview' }">

        <!-- Back Link & Title -->
        <div class="space-y-4">
            <a href="{{ route('admin.manage.users') }}" 
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-[#0056D2] transition-colors duration-150">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Users List
            </a>
            
            <!-- Hero User Info Profile -->
            <div class="relative overflow-hidden bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Background decoration -->
                <div class="absolute right-0 top-0 w-64 h-64 bg-[#0056D2]/2.5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 z-10">
                    <!-- Profile avatar status ring -->
                    <div class="relative">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden flex items-center justify-center font-extrabold text-2xl uppercase shadow-md border-2 
                            @if ($user->status === 'active') border-emerald-500 ring-4 ring-emerald-500/10
                            @elseif ($user->status === 'suspended') border-amber-500 ring-4 ring-amber-500/10
                            @else border-rose-500 ring-4 ring-rose-500/10 @endif">
                            @if ($user->profile_photo)
                                <img src="{{ asset($user->profile_photo) }}" alt="Profile Photo" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-[#0056D2] to-[#0049b8] text-white flex items-center justify-center">
                                    {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center shadow-sm
                            @if ($user->status === 'active') bg-emerald-500
                            @elseif ($user->status === 'suspended') bg-amber-500
                            @else bg-rose-500 @endif">
                            <i data-lucide="{{ $user->status === 'active' ? 'check' : ($user->status === 'suspended' ? 'alert-triangle' : 'shield-alert') }}" class="w-3 h-3 text-white"></i>
                        </span>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">
                                {{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}
                            </h1>
                            <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-slate-100 text-slate-600 border border-slate-200/50 uppercase tracking-wider">
                                {{ $user->role === 'business' ? 'Enterprise Business' : ($user->role === 'business_agent' ? 'Business Agent' : str_replace('_', ' ', $user->role)) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                            <span>Account ID: #{{ $user->id }}</span>
                            <span class="text-slate-400">•</span>
                            <span>Member since {{ $user->created_at->format('M d, Y') }}</span>
                        </p>
                    </div>
                </div>

                <!-- Quick info highlights & edit button -->
                <div class="flex flex-wrap items-center gap-4 z-10">
                    <div class="hidden sm:flex items-center gap-4 bg-slate-50 border border-slate-100 p-3 rounded-2xl">
                        <div class="border-r border-slate-200 pr-4">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Tier Level</span>
                            <span class="text-xs font-bold text-indigo-600">Tier {{ $user->account_tier ?? 1 }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Wallet Balance</span>
                            <span class="text-xs font-bold text-slate-700">
                                ₦{{ $user->wallet ? number_format($user->wallet->spendable(), 2) : '0.00' }}
                            </span>
                        </div>
                        @if ($user->wallet && $user->wallet->lien_amount > 0)
                            <div class="border-l border-slate-200 pl-4">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Lien Held</span>
                                <span class="text-xs font-bold text-violet-600">₦{{ number_format($user->wallet->lien_amount, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        @if ($user->wallet && $user->wallet->is_locked)
                            <button type="button" onclick="liftRestriction('pnd/lift', {{ $user->id }})"
                                    class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all duration-200">
                                Lift PND
                            </button>
                        @else
                            <button type="button" onclick="placeRestriction('pnd', {{ $user->id }})"
                                    class="px-3 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-100 text-xs font-semibold rounded-xl transition-all duration-200">
                                Place PND
                            </button>
                        @endif
                        @if ($user->isSuspended())
                            <button type="button" onclick="liftRestriction('reinstate', {{ $user->id }})"
                                    class="px-3 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-100 text-xs font-semibold rounded-xl transition-all duration-200">
                                Reinstate
                            </button>
                        @else
                            <button type="button" onclick="placeRestriction('suspend', {{ $user->id }})"
                                    class="px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-100 text-xs font-semibold rounded-xl transition-all duration-200">
                                Suspend
                            </button>
                        @endif
                        @if ($user->wallet && $user->wallet->lien_amount > 0)
                            <button type="button" onclick="liftRestriction('lien/lift', {{ $user->id }})"
                                    class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all duration-200">
                                Lift Lien
                            </button>
                        @else
                            <button type="button" onclick="placeLien({{ $user->id }})"
                                    class="px-3 py-2.5 bg-violet-50 hover:bg-violet-100 text-violet-700 border border-violet-100 text-xs font-semibold rounded-xl transition-all duration-200">
                                Place Lien
                            </button>
                        @endif
                        @if (\App\Services\ManagerReplacementService::roleColumn($user->role))
                            <a href="{{ route('admin.manage.users.replace', $user) }}"
                               class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all duration-200 flex items-center gap-2">
                                <i data-lucide="repeat" class="w-3.5 h-3.5"></i>
                                Replace Manager
                            </a>
                        @endif
                        <a href="{{ route('admin.manage.users.edit', $user) }}"
                           class="px-4 py-2.5 bg-gradient-to-r from-[#0056D2] to-[#0049b8] hover:from-[#374368] hover:to-[#465785] text-white text-xs font-semibold rounded-xl shadow-md shadow-[#0056D2]/10 transition-all duration-200 flex items-center gap-2">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            Edit Account
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-slate-100/50 p-1.5 rounded-2xl border border-slate-200/50 flex gap-1 max-w-2xl">
            <button @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="user" class="w-4 h-4"></i>
                Overview & KYC
            </button>
            <button @click="activeTab = 'financials'"
                    :class="activeTab === 'financials' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="wallet" class="w-4 h-4"></i>
                Wallet & Bank
            </button>
            <button @click="activeTab = 'metrics'"
                    :class="activeTab === 'metrics' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                Metrics
            </button>
            <button @click="activeTab = 'transactions'"
                    :class="activeTab === 'transactions' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="list" class="w-4 h-4"></i>
                Ledger Logs
            </button>
        </div>

        <!-- Tab contents -->
        <div class="space-y-6">

            <!-- Overview & KYC Tab -->
            <div x-show="activeTab === 'overview'" class="space-y-6" x-cloak>
                
                <!-- KYC Completion Indicator -->
                <div class="p-5 rounded-xl border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 
                    @if ($user->bvn && $user->nin) bg-emerald-50/50 border-emerald-100 @else bg-amber-50/50 border-amber-100 @endif">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0
                            @if ($user->bvn && $user->nin) bg-emerald-100 text-emerald-600 @else bg-amber-100 text-amber-600 @endif">
                            <i data-lucide="{{ $user->bvn && $user->nin ? 'shield-check' : 'shield-alert' }}" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold @if ($user->bvn && $user->nin) text-emerald-800 @else text-amber-800 @endif">
                                KYC Identity Profile
                            </h3>
                            <p class="text-xs @if ($user->bvn && $user->nin) text-emerald-600 @else text-amber-600 @endif mt-0.5">
                                @if ($user->bvn && $user->nin)
                                    This user's BVN and NIN have been provided and verified.
                                @else
                                    This user profile is missing BVN or NIN registration.
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        @if ($user->bvn && $user->nin)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-extrabold bg-emerald-100 text-emerald-700 rounded-xl uppercase tracking-wider">
                                verified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-extrabold bg-amber-100 text-amber-700 rounded-xl uppercase tracking-wider">
                                pending
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Profile Info -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                                <i data-lucide="user-cog" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Personal Profile Details</h2>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- First Name -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">First Name</span>
                                    <span class="text-xs font-bold text-slate-700">{{ $user->first_name }}</span>
                                </div>
                            </div>
                            <!-- Middle Name -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Middle Name</span>
                                    <span class="text-xs font-bold text-slate-700">{{ $user->middle_name ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <!-- Last Name -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Last Name</span>
                                    <span class="text-xs font-bold text-slate-700">{{ $user->last_name }}</span>
                                </div>
                            </div>
                            <!-- Gender -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Gender</span>
                                    <span class="text-xs font-bold text-slate-700 capitalize">{{ $user->gender ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <!-- Email -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between col-span-1 sm:col-span-2">
                                <div class="min-w-0">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Email Address</span>
                                    <span class="text-xs font-bold text-slate-700 truncate block">{{ $user->email }}</span>
                                </div>
                                <button @click="copyToClipboard('{{ $user->email }}', 'Email Address')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <!-- Phone -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Phone Number</span>
                                    <span class="text-xs font-bold text-slate-700">{{ $user->phone ?? 'N/A' }}</span>
                                </div>
                                @if ($user->phone)
                                    <button @click="copyToClipboard('{{ $user->phone }}', 'Phone Number')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                            <!-- Date of Birth -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Date of Birth</span>
                                    <span class="text-xs font-bold text-slate-700">
                                        {{ $user->date_of_birth ? $user->date_of_birth->format('M d, Y') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <!-- Referral Code -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Referral Code</span>
                                    <span class="text-xs font-bold text-slate-700 font-mono">{{ $user->referral_code ?? 'N/A' }}</span>
                                </div>
                                @if ($user->referral_code)
                                    <button @click="copyToClipboard('{{ $user->referral_code }}', 'Referral Code')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- KYC Details & Documents -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <i data-lucide="file-check" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Identity & Address (KYC)</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- BVN -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">BVN Number</span>
                                    <span class="text-xs font-mono font-bold text-slate-700">{{ $user->bvn ?? 'Not Provided' }}</span>
                                </div>
                                @if ($user->bvn)
                                    <button @click="copyToClipboard('{{ $user->bvn }}', 'BVN')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                            <!-- NIN -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">NIN Number</span>
                                    <span class="text-xs font-mono font-bold text-slate-700">{{ $user->nin ?? 'Not Provided' }}</span>
                                </div>
                                @if ($user->nin)
                                    <button @click="copyToClipboard('{{ $user->nin }}', 'NIN')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                            <!-- Address -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between col-span-1 sm:col-span-2">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Home Address</span>
                                    <span class="text-xs font-bold text-slate-700">{{ $user->address ?? 'Not Provided' }}</span>
                                </div>
                            </div>
                            <!-- State -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">State</span>
                                    <span class="text-xs font-bold text-slate-700 capitalize">{{ $user->state ?? 'Not Provided' }}</span>
                                </div>
                            </div>
                            <!-- LGA -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">LGA</span>
                                    <span class="text-xs font-bold text-slate-700 capitalize">{{ $user->lga ?? 'Not Provided' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Upgrade Details -->
                @if ($user->business_name || $user->cac_number || $user->pending_role)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                                </div>
                                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Business Credentials & Upgrades</h2>
                            </div>
                            <span class="px-3 py-1 text-xs font-extrabold rounded-full 
                                @if($user->upgrade_status === 'pending') bg-amber-50 text-amber-700 border border-amber-100
                                @elseif($user->upgrade_status === 'approved') bg-emerald-50 text-emerald-700 border border-emerald-100
                                @else bg-slate-100 text-slate-500 border border-slate-200 @endif uppercase tracking-wider">
                                Upgrade status: {{ $user->upgrade_status ?? 'None' }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Business Name -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Business Name</span>
                                <span class="text-xs font-bold text-slate-700">{{ $user->business_name ?? 'N/A' }}</span>
                            </div>
                            <!-- Business Type -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Business Type</span>
                                <span class="text-xs font-bold text-slate-700 capitalize">{{ str_replace('_', ' ', $user->business_type ?? 'N/A') }}</span>
                            </div>
                            <!-- CAC Number -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between">
                                <div>
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">CAC Number</span>
                                    <span class="text-xs font-mono font-bold text-slate-700">{{ $user->cac_number ?? 'N/A' }}</span>
                                </div>
                                @if ($user->cac_number)
                                    <button @click="copyToClipboard('{{ $user->cac_number }}', 'CAC Number')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                            <!-- Requested Role -->
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Requested Tier Role</span>
                                <span class="text-xs font-bold text-[#0056D2] uppercase tracking-wider">{{ $user->pending_role ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Financials & Wallet Tab -->
            <div x-show="activeTab === 'financials'" class="space-y-6" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Wallet balances card -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6 md:col-span-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="wallet" class="w-4 h-4"></i>
                                </div>
                                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Wallet Account details</h2>
                            </div>
                            @if ($user->wallet)
                                <div class="flex items-center gap-1">
                                    <span class="px-2.5 py-1 text-xs font-mono font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wider">
                                        {{ $user->wallet->wallet_number }}
                                    </span>
                                    <button @click="copyToClipboard('{{ $user->wallet->wallet_number }}', 'Wallet Account Number')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($user->wallet)
                            <!-- Financial Ledger Balances Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                <!-- Virtual Debit Card representation -->
                                <div class="sm:col-span-3 lg:col-span-1 relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 border border-slate-800 text-white min-h-[170px] flex flex-col justify-between shadow-lg">
                                    <div class="absolute right-0 top-0 w-32 h-32 bg-[#0049b8]/10 rounded-full blur-2xl pointer-events-none"></div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold tracking-widest uppercase opacity-70">SimPhix wallet</span>
                                        <i data-lucide="contactless" class="w-4 h-4 opacity-50"></i>
                                    </div>
                                    
                                    <div>
                                        <span class="block text-xs font-bold uppercase opacity-60 tracking-wider">Spendable Balance</span>
                                        <span class="text-xl font-extrabold tracking-tight">
                                            ₦{{ number_format($user->wallet->spendable(), 2) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <span class="block text-xs opacity-45 uppercase font-medium">Wallet ID</span>
                                            <span class="text-xs font-mono font-bold tracking-wider opacity-80">{{ implode(' ', str_split($user->wallet->wallet_number, 4)) }}</span>
                                        </div>
                                        <div class="w-8 h-5 rounded bg-white/10 border border-white/5 flex items-center justify-center">
                                            <div class="w-3 h-3 rounded-full bg-red-500 opacity-80 translate-x-1"></div>
                                            <div class="w-3 h-3 rounded-full bg-yellow-500 opacity-80 -translate-x-1"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sub-Balances metrics -->
                                <div class="sm:col-span-3 lg:col-span-2 grid grid-cols-2 gap-4">
                                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Main Balance</span>
                                        <span class="text-sm font-extrabold text-slate-700">₦{{ number_format($user->wallet->balance, 2) }}</span>
                                    </div>
                                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Bonus Balance</span>
                                        <span class="text-sm font-extrabold text-slate-700">₦{{ number_format($user->wallet->bonus, 2) }}</span>
                                    </div>
                                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Hold Amount</span>
                                        <span class="text-sm font-extrabold text-slate-700">₦{{ number_format($user->wallet->hold_amount, 2) }}</span>
                                    </div>
                                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Currency Type</span>
                                        <span class="text-sm font-extrabold text-[#0056D2] uppercase tracking-wider">{{ $user->wallet->currency }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Credited vs Debited visual progress -->
                            <div class="border-t border-slate-50 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 bg-emerald-50/30 border border-emerald-100/50 rounded-2xl flex items-center justify-between">
                                    <div>
                                        <span class="block text-xs font-bold text-emerald-500 uppercase tracking-wider">Total Fundings (Credited)</span>
                                        <span class="text-base font-extrabold text-emerald-700">+₦{{ number_format($user->wallet->total_credited, 2) }}</span>
                                    </div>
                                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                <div class="p-4 bg-rose-50/30 border border-rose-100/50 rounded-2xl flex items-center justify-between">
                                    <div>
                                        <span class="block text-xs font-bold text-rose-500 uppercase tracking-wider">Total Debits (Spent)</span>
                                        <span class="text-base font-extrabold text-rose-700">-₦{{ number_format($user->wallet->total_debited, 2) }}</span>
                                    </div>
                                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                                        <i data-lucide="trending-down" class="w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Tx Limits progress details -->
                            <div class="border-t border-slate-50 pt-5 space-y-4">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Transaction Limit Controls</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-bold text-slate-500">Daily limit status</span>
                                            <span class="font-bold text-slate-700">₦{{ number_format($user->wallet->daily_limit, 2) }}</span>
                                        </div>
                                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                            <div class="bg-indigo-700 h-full rounded-full" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-bold text-slate-500">Monthly limit status</span>
                                            <span class="font-bold text-slate-700">₦{{ number_format($user->wallet->monthly_limit, 2) }}</span>
                                        </div>
                                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                            <div class="bg-indigo-700 h-full rounded-full" style="width: 100%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-10 text-slate-400 text-sm flex flex-col items-center justify-center border border-dashed rounded-3xl">
                                <i data-lucide="wallet-cards" class="w-10 h-10 text-slate-300 mb-2.5"></i>
                                No wallet account registered for this user.
                            </div>
                        @endif
                    </div>

                    <!-- Wallet lock state -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Wallet Lock Settings</h2>
                        </div>
                        
                        @if ($user->wallet)
                            <div class="flex flex-col gap-5">
                                <div class="flex items-center gap-4 p-4 rounded-2xl border
                                    @if ($user->wallet->is_locked) bg-rose-50/50 border-rose-100 @else bg-emerald-50/50 border-emerald-100 @endif">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs 
                                        @if ($user->wallet->is_locked) bg-rose-100 text-rose-600 @else bg-emerald-100 text-emerald-600 @endif">
                                        <i data-lucide="{{ $user->wallet->is_locked ? 'lock' : 'unlock' }}" class="w-5 h-5 animate-pulse"></i>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-extrabold uppercase tracking-wide
                                            @if ($user->wallet->is_locked) text-rose-700 @else text-emerald-700 @endif">
                                            Wallet is {{ $user->wallet->is_locked ? 'locked' : 'unlocked' }}
                                        </span>
                                        <span class="block text-xs text-slate-400 mt-0.5">Locks debit activity</span>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50 p-4 border border-slate-100 rounded-2xl">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Last Balance Activity</span>
                                    <span class="block text-xs font-semibold text-slate-700">
                                        {{ $user->wallet->last_activity ? $user->wallet->last_activity->format('M d, Y h:i A') : 'No Recent Ledger Activity' }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-6 text-slate-400 text-xs">
                                No activity control config available.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Linked Virtual Bank Accounts -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="landmark" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Linked Virtual Bank Accounts ({{ count($user->virtualAccounts) }})</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @forelse ($user->virtualAccounts as $acc)
                            <div class="p-5 bg-gradient-to-br from-slate-50 to-slate-100/50 border border-slate-200 rounded-2xl space-y-4 relative hover:shadow-md transition-all duration-200">
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-[#0056D2]/10 text-[#0056D2] uppercase tracking-wider">
                                        {{ $acc->provider }}
                                    </span>
                                    @if ($acc->is_active)
                                        <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase tracking-wider flex items-center gap-1">
                                            <span class="w-1 h-1 bg-emerald-500 rounded-full"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-slate-100 text-slate-400 border border-slate-200 uppercase tracking-wider">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                                <div class="space-y-2">
                                    <div>
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Bank Name</span>
                                        <span class="text-xs font-bold text-slate-700">{{ $acc->bank_name }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Account Number</span>
                                            <span class="text-sm font-mono font-bold text-slate-800 tracking-wider">{{ $acc->account_number }}</span>
                                        </div>
                                        <button @click="copyToClipboard('{{ $acc->account_number }}', 'Account Number')" class="text-slate-400 hover:text-[#0056D2] p-1.5 rounded-lg hover:bg-slate-200/50 transition-colors">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Account Name</span>
                                        <span class="text-xs font-semibold text-slate-500">{{ $acc->account_name }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-10 text-center text-slate-400 text-sm flex flex-col items-center justify-center border border-dashed rounded-3xl">
                                <i data-lucide="piggy-bank" class="w-10 h-10 text-slate-400 mb-2.5"></i>
                                No virtual bank accounts linked to this profile.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Metrics Tab -->
            <div x-show="activeTab === 'metrics'" class="space-y-6" x-cloak>
                @if ($nextRole)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Downline Onboarding</h2>
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="{{ route('admin.manage.users.team', $user) }}"
                               class="p-4 bg-slate-50 border border-slate-100 rounded-2xl min-w-[140px] hover:bg-slate-100 transition-colors block">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Onboarded</span>
                                <span class="text-xl font-extrabold font-display text-[#0056D2]">{{ $totalDownline }}</span>
                            </a>
                            @forelse ($roleBreakdown as $role => $count)
                                <a href="{{ route('admin.manage.users.team', ['user' => $user, 'role' => $role]) }}"
                                   class="p-4 bg-slate-50 border border-slate-100 rounded-2xl min-w-[120px] hover:bg-slate-100 transition-colors block">
                                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        {{ $role === 'business' ? 'Enterprise Business' : ($role === 'business_agent' ? 'Business Agent' : str_replace('_', ' ', $role)) }}
                                    </span>
                                    <span class="text-xl font-extrabold font-display text-slate-800">{{ $count }}</span>
                                </a>
                            @empty
                                <p class="text-xs text-slate-400">Hasn't onboarded anyone yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 {{ $isCatalogRole ? 'md:grid-cols-2' : '' }} gap-6">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Personal SIM Inventory</h2>
                        <p class="text-xs text-slate-400">SIMs held directly by {{ $user->first_name }}, not yet handed further down.</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                                <span class="block text-lg font-extrabold font-display text-slate-800">{{ $personalTotal }}</span>
                                <span class="block text-[11px] text-slate-400 font-semibold uppercase">Held</span>
                            </div>
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                                <span class="block text-lg font-extrabold font-display text-emerald-600">{{ $personalActivated }}</span>
                                <span class="block text-[11px] text-slate-400 font-semibold uppercase">Activated</span>
                            </div>
                            <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                                <span class="block text-lg font-extrabold font-display text-[#0056D2]">{{ $personalConversion }}%</span>
                                <span class="block text-[11px] text-slate-400 font-semibold uppercase">Conversion</span>
                            </div>
                        </div>
                    </div>

                    @if ($isCatalogRole)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4">
                            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Team SIM Inventory</h2>
                            <p class="text-xs text-slate-400">Every SIM anywhere in {{ $user->first_name }}'s downline cascade.</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                                    <span class="block text-lg font-extrabold font-display text-slate-800">{{ $teamTotal }}</span>
                                    <span class="block text-[11px] text-slate-400 font-semibold uppercase">Held</span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                                    <span class="block text-lg font-extrabold font-display text-emerald-600">{{ $teamActivated }}</span>
                                    <span class="block text-[11px] text-slate-400 font-semibold uppercase">Activated</span>
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                                    <span class="block text-lg font-extrabold font-display text-[#0056D2]">{{ $teamConversion }}%</span>
                                    <span class="block text-[11px] text-slate-400 font-semibold uppercase">Conversion</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Activation Counts</h2>
                    <div class="flex flex-wrap gap-4">
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl min-w-[140px]">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">All-Time Activations</span>
                            <span class="text-xl font-extrabold font-display text-slate-800">{{ $totalActivations }}</span>
                        </div>
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl min-w-[140px]">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">This Period</span>
                            <span class="text-xl font-extrabold font-display text-slate-800">{{ $periodActivations }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Logs Tab -->
            <div x-show="activeTab === 'transactions'" class="space-y-6" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                            <i data-lucide="receipt" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Transaction Activity History</h2>
                    </div>
                    
                    <div class="flex flex-col items-center justify-center py-16 text-center text-slate-400">
                        <div class="w-14 h-14 bg-slate-50 border border-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mb-4 shadow-sm">
                            <i data-lucide="file-minus" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700">No Transactions Logged</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm">There are no financial ledger transactions recorded under this user's profile.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function submitDownlineAction(path, userId, fields) {
            const f = document.createElement('form');
            f.action = '/network/downline/' + userId + '/' + path;
            f.method = 'POST';
            let inputs = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            if (fields) {
                for (const key in fields) {
                    inputs += '<input type="hidden" name="' + key + '" value="' + String(fields[key]).replace(/"/g, '&quot;') + '">';
                }
            }
            f.innerHTML = inputs;
            document.body.appendChild(f);
            f.submit();
        }

        function placeRestriction(path, userId) {
            const isSuspend = path === 'suspend';
            Swal.fire({
                title: isSuspend ? 'Suspend Account' : 'Place Post No Debit',
                text: isSuspend
                    ? 'This blocks the user from logging in until reinstated.'
                    : 'This blocks the user from cashing out until lifted — other spending stays available.',
                input: 'text',
                inputLabel: 'Reason',
                inputPlaceholder: 'Enter a reason...',
                showCancelButton: true,
                confirmButtonColor: isSuspend ? '#e11d48' : '#d97706',
                cancelButtonColor: '#64748b',
                confirmButtonText: isSuspend ? 'Suspend' : 'Place PND',
                preConfirm: (value) => {
                    if (!value || !value.trim()) {
                        Swal.showValidationMessage('A reason is required');
                    }
                    return value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitDownlineAction(path, userId, { reason: result.value });
                }
            });
        }

        function liftRestriction(path, userId) {
            Swal.fire({
                title: 'Are you sure?',
                showCancelButton: true,
                confirmButtonColor: '#0056D2',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, confirm'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitDownlineAction(path, userId, null);
                }
            });
        }

        function placeLien(userId) {
            Swal.fire({
                title: 'Place Lien',
                html: `
                    <div class="text-left space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase block">Amount (₦)</label>
                        <input id="lien_amount" type="number" min="0.01" step="0.01" class="swal2-input" placeholder="0.00" style="margin:0 0 8px;">
                        <label class="text-xs font-bold text-slate-500 uppercase block">Reason</label>
                        <input id="lien_reason" type="text" class="swal2-input" placeholder="Enter a reason..." style="margin:0;">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Place Lien',
                preConfirm: () => {
                    const amount = document.getElementById('lien_amount').value;
                    const reason = document.getElementById('lien_reason').value;
                    if (!amount || parseFloat(amount) <= 0) {
                        Swal.showValidationMessage('Enter a valid amount');
                        return false;
                    }
                    if (!reason || !reason.trim()) {
                        Swal.showValidationMessage('A reason is required');
                        return false;
                    }
                    return { amount, reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitDownlineAction('lien', userId, result.value);
                }
            });
        }
    </script>
</x-app-layout>
