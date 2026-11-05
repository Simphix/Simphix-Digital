<x-app-layout>
    <title>SimPhix - {{ $user->first_name }} {{ $user->last_name }}</title>

    <div class="space-y-6 max-w-6xl mx-auto" x-data="{ activeTab: 'info' }">

        <!-- Back Link -->
        <a href="{{ route('network') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to My Network
        </a>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Header -->
        <div class="relative overflow-hidden bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="relative">
                    <div class="w-20 h-20 rounded-2xl overflow-hidden flex items-center justify-center font-extrabold text-2xl uppercase shadow-md border-2
                        @if ($user->status === 'active') border-emerald-500 ring-4 ring-emerald-500/10
                        @elseif ($user->status === 'suspended') border-amber-500 ring-4 ring-amber-500/10
                        @else border-rose-500 ring-4 ring-rose-500/10 @endif">
                        <div class="w-full h-full bg-gradient-to-br from-[#0056D2] to-[#0049b8] text-white flex items-center justify-center">
                            {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                        </div>
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
                            {{ str_replace('_', ' ', $user->role) }}
                        </span>
                        @if ($wallet->is_locked)
                            <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-amber-50 text-amber-600 border border-amber-100 uppercase tracking-wider">PND</span>
                        @endif
                        @if ($wallet->lien_amount > 0)
                            <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-violet-50 text-violet-600 border border-violet-100 uppercase tracking-wider">Lien</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                        <span>Account ID: #{{ $user->id }}</span>
                        <span>•</span>
                        <span>Member since {{ $user->created_at->format('M d, Y') }}</span>
                        @if ($user->role === 'coordinator')
                            <span>•</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $coordinatorDistrict ? 'text-[#0056D2]' : 'text-amber-600' }}">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                {{ $coordinatorDistrict ? $coordinatorDistrict->name . ' (' . ($coordinatorDistrict->state?->name ?? 'N/A') . ')' : 'No territory assigned' }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-4 bg-slate-50 border border-slate-100 p-3 rounded-2xl">
                @if ($user->role === 'coordinator')
                    <div class="border-r border-slate-200 pr-4">
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Territory</span>
                        <span class="text-xs font-bold {{ $coordinatorDistrict ? 'text-slate-700' : 'text-amber-600' }} truncate max-w-[130px] block" title="{{ $coordinatorDistrict ? $coordinatorDistrict->name : 'Unassigned' }}">
                            {{ $coordinatorDistrict ? $coordinatorDistrict->name : 'Unassigned' }}
                        </span>
                    </div>
                @endif
                <div class="border-r border-slate-200 pr-4">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Wallet Balance</span>
                    <span class="text-xs font-bold text-slate-700">₦{{ number_format($wallet->spendable(), 2) }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Onboarding</span>
                    <span class="text-xs font-bold {{ $user->account_tier > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $user->account_tier > 0 ? 'Complete' : 'Incomplete' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-slate-100/50 p-1.5 rounded-2xl border border-slate-200/50 flex gap-1 max-w-md">
            <button @click="activeTab = 'info'"
                    :class="activeTab === 'info' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="user" class="w-4 h-4"></i> Info
            </button>
            <button @click="activeTab = 'metrics'"
                    :class="activeTab === 'metrics' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Metrics
            </button>
            <button @click="activeTab = 'tier'"
                    :class="activeTab === 'tier' ? 'bg-white text-[#0056D2] shadow-sm font-bold border-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 text-xs font-semibold rounded-xl transition-all duration-150 flex items-center justify-center gap-2 border">
                <i data-lucide="trophy" class="w-4 h-4"></i> Tier
            </button>
        </div>

        <!-- Tab: Info -->
        <div x-show="activeTab === 'info'" class="space-y-6" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-card padding="p-6 sm:p-8" class="space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Profile</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">First Name</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->first_name }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Last Name</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->last_name }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl sm:col-span-2">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Email Address</span>
                            <span class="text-xs font-bold text-slate-700 truncate block">{{ $user->email }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Phone Number</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->phone ?? 'Not provided' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Account Tier</span>
                            <span class="text-xs font-bold text-slate-700">Tier {{ $user->account_tier ?? 0 }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">BVN</span>
                            <span class="text-xs font-mono font-bold text-slate-700">{{ $user->bvn ?? 'Not provided' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">NIN</span>
                            <span class="text-xs font-mono font-bold text-slate-700">{{ $user->nin ?? 'Not provided' }}</span>
                        </div>
                    </div>
                </x-card>

                <x-card padding="p-6 sm:p-8" class="space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Location & Wallet</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">State</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->state ?? 'Not provided' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">LGA</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->lga ?? 'Not provided' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl sm:col-span-2">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Address</span>
                            <span class="text-xs font-bold text-slate-700">{{ $user->address ?? 'Not provided' }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Wallet Balance</span>
                            <span class="text-xs font-bold text-slate-700">₦{{ number_format($wallet->spendable(), 2) }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Status</span>
                            <span class="text-xs font-bold text-slate-700 capitalize">{{ $user->status }}</span>
                        </div>
                    </div>
                </x-card>
            </div>

            @if ($user->role === 'coordinator')
                <x-card padding="p-6 sm:p-8" class="space-y-5" x-data="{ 
                    showAssignForm: {{ ($errors->has('district_id') || $errors->has('new_district_name') || $errors->has('new_district_state_id')) ? 'true' : ($coordinatorDistrict ? 'false' : 'true') }},
                    territoryMode: '{{ old('territory_mode', 'existing') }}'
                }">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-[#0056D2]">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                </div>
                                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Territory (Senatorial District)</h2>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Geographic jurisdiction assigned to this coordinator within their regional boundary.</p>
                        </div>
                        
                        @if ($canManageTerritory && $coordinatorDistrict)
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        @click="showAssignForm = !showAssignForm" 
                                        class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors flex items-center gap-1.5">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    <span x-text="showAssignForm ? 'Close Form' : 'Reassign District'">Reassign District</span>
                                </button>
                                
                                <form method="POST" action="{{ route('network.downline.unassign-district', $user) }}" onsubmit="return confirm('Are you sure you want to unassign this territory from {{ $user->first_name }} {{ $user->last_name }}?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors flex items-center gap-1.5">
                                        <i data-lucide="unlink" class="w-3.5 h-3.5"></i>
                                        <span>Unassign</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    {{-- Current Territory Display --}}
                    @if ($coordinatorDistrict)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Senatorial District</span>
                                <span class="text-sm font-bold text-slate-800 font-display mt-0.5 block">{{ $coordinatorDistrict->name }}</span>
                            </div>
                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">State</span>
                                <span class="text-sm font-bold text-slate-800 font-display mt-0.5 block">{{ $coordinatorDistrict->state?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Region</span>
                                <span class="text-sm font-bold text-slate-800 font-display mt-0.5 block">{{ $coordinatorDistrict->state?->region?->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="p-4 bg-amber-50/70 border border-amber-200/70 rounded-2xl flex items-start gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                            <div>
                                <h3 class="text-xs font-bold text-amber-900">No Territory Assigned</h3>
                                <p class="text-xs text-amber-700 mt-0.5">This coordinator does not have an active senatorial district territory assigned. SIMs registered in a territory rely on coordinator mapping.</p>
                            </div>
                        </div>
                    @endif

                    {{-- Territory Assignment Form (for RM or Super Admin) --}}
                    @if ($canManageTerritory)
                        <div x-show="showAssignForm" x-cloak class="mt-4 pt-4 border-t border-slate-100">
                            @if ($actorRegion || auth()->user()->hasRole('super_admin'))
                                <form method="POST" action="{{ route('network.downline.assign-district', $user) }}" class="space-y-4">
                                    @csrf

                                    <div class="flex items-center justify-between">
                                        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                                            {{ $coordinatorDistrict ? 'Reassign Territory' : 'Assign Senatorial District' }}
                                        </h3>

                                        <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs">
                                            <button type="button"
                                                    @click="territoryMode = 'existing'"
                                                    :class="territoryMode === 'existing' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                                    class="rounded-md px-2.5 py-1 transition">
                                                Available Districts
                                            </button>
                                            <button type="button"
                                                    @click="territoryMode = 'new'"
                                                    :class="territoryMode === 'new' ? 'bg-white font-bold text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                                    class="rounded-md px-2.5 py-1 transition">
                                                + Create New
                                            </button>
                                        </div>
                                    </div>

                                    <input type="hidden" name="territory_mode" :value="territoryMode">

                                    {{-- Mode: Existing District --}}
                                    <div x-show="territoryMode === 'existing'" class="space-y-2">
                                        <x-input-label for="district_id" value="Select Senatorial District" />
                                        @if ($availableDistricts->isEmpty())
                                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 flex items-center justify-between">
                                                <span>No unassigned senatorial districts available in {{ $actorRegion ? $actorRegion->name : 'your region' }}.</span>
                                                <button type="button" @click="territoryMode = 'new'" class="text-[#0056D2] font-bold hover:underline">
                                                    + Create a new district
                                                </button>
                                            </div>
                                        @else
                                            <select id="district_id" name="district_id" class="w-full rounded-xl border-slate-200 text-xs font-medium text-slate-800 focus:border-[#0056D2] focus:ring-[#0056D2]">
                                                <option value="">-- Choose an available district --</option>
                                                @foreach ($availableDistricts as $dist)
                                                    <option value="{{ $dist->id }}" {{ (old('district_id', $coordinatorDistrict?->id) == $dist->id) ? 'selected' : '' }}>
                                                        {{ $dist->name }} ({{ $dist->state?->name ?? 'Unknown State' }})
                                                        {{ $coordinatorDistrict?->id === $dist->id ? '— Currently Assigned' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('district_id')" class="mt-1" />
                                            <p class="text-[11px] text-slate-400">Districts without an assigned coordinator in {{ $actorRegion ? $actorRegion->name : 'the region' }}.</p>
                                        @endif
                                    </div>

                                    {{-- Mode: Create New District --}}
                                    <div x-show="territoryMode === 'new'" class="space-y-4 bg-slate-50/70 p-4 rounded-2xl border border-slate-200/70" x-cloak>
                                        <div class="flex items-center gap-2 text-xs font-bold text-[#0056D2]">
                                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                            <span>Create &amp; Assign New Senatorial District</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label for="new_district_name" value="District Name" />
                                                <x-text-input id="new_district_name" type="text" name="new_district_name" :value="old('new_district_name')" placeholder="e.g. Lagos West, Imo East" class="mt-1 text-xs" />
                                                <x-input-error :messages="$errors->get('new_district_name')" class="mt-1" />
                                            </div>

                                            <div>
                                                <x-input-label for="new_district_state_id" value="State" />
                                                <select id="new_district_state_id" name="new_district_state_id" class="mt-1 w-full rounded-xl border-slate-200 text-xs font-medium text-slate-800 focus:border-[#0056D2] focus:ring-[#0056D2]">
                                                    <option value="">-- Select a state --</option>
                                                    @foreach ($regionStates as $st)
                                                        <option value="{{ $st->id }}" {{ old('new_district_state_id') == $st->id ? 'selected' : '' }}>
                                                            {{ $st->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <x-input-error :messages="$errors->get('new_district_state_id')" class="mt-1" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 pt-2">
                                        <x-primary-button type="submit" class="text-xs">
                                            {{ $coordinatorDistrict ? 'Save Territory' : 'Assign Territory' }}
                                        </x-primary-button>
                                        @if ($coordinatorDistrict)
                                            <button type="button" @click="showAssignForm = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            @else
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                                    No managed region is associated with your account. A regional manager must be assigned to a region before assigning coordinator territories.
                                </div>
                            @endif
                        </div>
                    @endif
                </x-card>
            @endif
        </div>

        <!-- Tab: Metrics -->
        <div x-show="activeTab === 'metrics'" class="space-y-6" x-cloak>
            @if ($nextRole)
                <x-card padding="p-6 sm:p-8" class="space-y-4">
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Downline Onboarding</h2>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl min-w-[140px]">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Onboarded</span>
                            <span class="text-xl font-extrabold font-display text-slate-800">{{ $totalDownline }}</span>
                        </div>
                        @forelse ($roleBreakdown as $role => $count)
                            <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl min-w-[120px]">
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    {{ $role === 'business' ? 'Enterprise Business' : ($role === 'business_agent' ? 'Business Agent' : str_replace('_', ' ', $role)) }}
                                </span>
                                <span class="text-xl font-extrabold font-display text-slate-800">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Hasn't onboarded anyone yet.</p>
                        @endforelse
                    </div>
                </x-card>
            @endif

            <div class="grid grid-cols-1 {{ $isCatalogRole ? 'md:grid-cols-2' : '' }} gap-6">
                <x-card padding="p-6 sm:p-8" class="space-y-4">
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
                            <span class="block text-lg font-extrabold font-display text-primary">{{ $personalConversion }}%</span>
                            <span class="block text-[11px] text-slate-400 font-semibold uppercase">Conversion</span>
                        </div>
                    </div>
                </x-card>

                @if ($isCatalogRole)
                    <x-card padding="p-6 sm:p-8" class="space-y-4">
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
                                <span class="block text-lg font-extrabold font-display text-primary">{{ $teamConversion }}%</span>
                                <span class="block text-[11px] text-slate-400 font-semibold uppercase">Conversion</span>
                            </div>
                        </div>
                    </x-card>
                @endif
            </div>

            <x-card padding="p-6 sm:p-8" class="space-y-4">
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
            </x-card>
        </div>

        <!-- Tab: Tier -->
        <div x-show="activeTab === 'tier'" class="space-y-6" x-cloak>
            <x-card padding="p-6 sm:p-8" class="space-y-4">
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Tier Progress</h2>
                @if ($user->role === 'partner')
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Current Tier</span>
                            @if ($currentTier)
                                <p class="text-lg font-bold font-display text-slate-800">
                                    {{ $currentTier->activation_target }}+ activations
                                    <span class="text-primary">&mdash; ₦{{ number_format($currentTier->bonus_amount, 2) }}</span>
                                </p>
                            @else
                                <p class="text-lg font-bold font-display text-slate-400">No tier yet</p>
                            @endif
                        </div>
                    </div>
                    @if ($nextTier)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-600">Progress to {{ $nextTier->activation_target }} activations (₦{{ number_format($nextTier->bonus_amount, 2) }})</span>
                                <span class="font-bold text-primary">{{ $progressPercent }}%</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: {{ $progressPercent }}%"></div>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-xs text-slate-400">Tier tracking applies to Partner accounts.</p>
                @endif
            </x-card>

            <x-card padding="p-6 sm:p-8" class="space-y-4">
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Onboarding</h2>
                @if ($user->account_tier > 0)
                    <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-2xl flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i>
                        <span class="text-xs font-semibold text-emerald-800">{{ $user->first_name }} has completed the required onboarding profile.</span>
                    </div>
                @else
                    <p class="text-xs text-slate-400">{{ $user->first_name }} hasn't completed the mandatory onboarding pop-up yet. Fill it out on their behalf below.</p>
                    <form method="POST" action="{{ route('network.downline.complete-onboarding', $user) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @csrf
                        <div>
                            <x-input-label value="First Name" />
                            <x-text-input type="text" name="first_name" :value="old('first_name', $user->first_name)" required />
                        </div>
                        <div>
                            <x-input-label value="Last Name" />
                            <x-text-input type="text" name="last_name" :value="old('last_name', $user->last_name)" required />
                        </div>
                        <div>
                            <x-input-label value="Middle Name (optional)" />
                            <x-text-input type="text" name="middle_name" :value="old('middle_name', $user->middle_name)" />
                        </div>
                        <div>
                            <x-input-label value="Phone Number" />
                            <x-text-input type="tel" name="phone" :value="old('phone', $user->phone)" required />
                        </div>
                        <div>
                            <x-input-label value="State" />
                            <x-text-input type="text" name="state" :value="old('state', $user->state)" required />
                        </div>
                        <div>
                            <x-input-label value="LGA" />
                            <x-text-input type="text" name="lga" :value="old('lga', $user->lga)" required />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label value="Address" />
                            <x-text-input type="text" name="address" :value="old('address', $user->address)" required />
                        </div>
                        <div class="sm:col-span-2">
                            <x-primary-button type="submit" class="w-full sm:w-auto">Complete Onboarding</x-primary-button>
                        </div>
                    </form>
                @endif
            </x-card>

            <x-card padding="p-6 sm:p-8" class="space-y-4">
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Virtual Funding Account</h2>
                @if ($virtualAccount)
                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Account Name</span>
                            <span class="text-xs font-bold text-slate-800">{{ $virtualAccount->account_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Bank Name</span>
                            <span class="text-xs font-bold text-slate-800">{{ $virtualAccount->bank_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Account Number</span>
                            <span class="text-sm font-extrabold font-display text-[#0056D2]">{{ $virtualAccount->account_number }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-400">{{ $user->first_name }} doesn't have a virtual funding account yet. Generate one on their behalf below.</p>
                    <form method="POST" action="{{ route('network.downline.virtual-account', $user) }}" class="space-y-4" x-data="{ idType: '{{ old('identity_type', 'bvn') }}' }">
                        @csrf
                        <input type="hidden" name="identity_type" x-bind:value="idType">
                        <div>
                            <x-input-label value="Phone Number" />
                            <x-text-input type="tel" name="phone" :value="old('phone', $user->phone)" required />
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
                            <p class="text-[11px] text-slate-400 mt-1">If BVN fails verification, try NIN instead.</p>
                        </div>
                        <div x-show="idType === 'bvn'">
                            <x-input-label value="Bank Verification Number (BVN)" />
                            <x-text-input type="text" name="bvn" :value="old('bvn', $user->bvn)" placeholder="Enter 11-digit BVN" maxlength="11" pattern="\d{11}" x-bind:required="idType === 'bvn'" />
                        </div>
                        <div x-show="idType === 'nin'" x-cloak>
                            <x-input-label value="National Identification Number (NIN)" />
                            <x-text-input type="text" name="nin" :value="old('nin', $user->nin)" placeholder="Enter 11-digit NIN" maxlength="11" pattern="\d{11}" x-bind:required="idType === 'nin'" />
                        </div>
                        <x-primary-button type="submit" class="w-full sm:w-auto">Generate Funding Account</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>

    </div>
</x-app-layout>
