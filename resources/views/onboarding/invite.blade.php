<x-app-layout>
    <title>SimPhix - Onboard a {{ str_replace('_', ' ', $nextRole) }}</title>

    <div class="max-w-xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                </div>
                Onboard a {{ str_replace('_', ' ', $nextRole) }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                They'll receive an email with a link to set their password and activate their account.
            </p>
        </div>

        @if (session('error'))
            <div class="bg-red-50 border border-red-100 rounded-lg p-4 text-red-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <x-card padding="p-6">
            <form method="POST" action="{{ route('onboarding.invite.send') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="first_name" value="First Name" />
                        <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required class="mt-1" />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Last Name" />
                        <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required class="mt-1" />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="email" value="Email Address" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required class="mt-1" placeholder="name@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone Number (optional)" />
                    <x-text-input id="phone" type="text" name="phone" :value="old('phone')" class="mt-1" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
                </div>

                @if ($nextRole === 'coordinator')
                    @if (!$region)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 text-sm flex items-start gap-3">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                            <div>
                                <p class="font-semibold">No Managed Region Assigned</p>
                                <p class="text-xs text-amber-700 mt-0.5">You do not currently have a Region assigned to your account. An administrator must assign your region before you can onboard and assign a Coordinator.</p>
                            </div>
                        </div>
                    @else
                        <div x-data="{ territoryMode: '{{ old('territory_mode', $districts->isEmpty() ? 'new' : 'existing') }}' }" class="border-t border-slate-200 pt-5 space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-semibold text-slate-800">
                                        Territory / Senatorial District Assignment
                                    </label>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        {{ $region->name }} Region
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Assign an available senatorial district within your region, or create a new district onto a state under your account.
                                </p>
                            </div>

                            <!-- Mode Selector Toggle -->
                            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-lg text-sm">
                                <button type="button"
                                        @click="territoryMode = 'existing'"
                                        :class="territoryMode === 'existing' ? 'bg-white shadow-sm text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                        class="py-2 px-3 rounded-md transition-all text-center flex items-center justify-center gap-1.5">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    <span>Assign Existing</span>
                                </button>
                                <button type="button"
                                        @click="territoryMode = 'new'"
                                        :class="territoryMode === 'new' ? 'bg-white shadow-sm text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                                        class="py-2 px-3 rounded-md transition-all text-center flex items-center justify-center gap-1.5">
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                    <span>Create New District</span>
                                </button>
                            </div>
                            <input type="hidden" name="territory_mode" :value="territoryMode">

                            <!-- Mode A: Existing District Dropdown -->
                            <div x-show="territoryMode === 'existing'" x-cloak class="space-y-3">
                                @if ($districts->isEmpty())
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-600 flex items-center justify-between">
                                        <span>No unassigned senatorial districts available in {{ $region->name }}.</span>
                                        <button type="button" @click="territoryMode = 'new'" class="text-primary font-semibold hover:underline">
                                            + Create a new district
                                        </button>
                                    </div>
                                @else
                                    <div>
                                        <x-input-label for="district_id" value="Select Senatorial District" />
                                        <select id="district_id" name="district_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                                            <option value="">-- Choose an available district --</option>
                                            @foreach ($districts as $district)
                                                <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>
                                                    {{ $district->name }} ({{ $district->state?->name ?? 'Unknown State' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('district_id')" class="mt-1.5" />
                                        <p class="text-xs text-slate-400 mt-1">
                                            Only senatorial districts without an assigned coordinator in your region are shown.
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Mode B: Create New District Form -->
                            <div x-show="territoryMode === 'new'" x-cloak class="space-y-4 rounded-xl border border-primary/20 bg-primary/[0.02] p-4">
                                <div class="flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    <span>Create &amp; Assign New District</span>
                                </div>

                                <div>
                                    <x-input-label for="new_district_name" value="District Name" />
                                    <x-text-input id="new_district_name" type="text" name="new_district_name" :value="old('new_district_name')" placeholder="e.g. Lagos West, Imo East" class="mt-1" />
                                    <x-input-error :messages="$errors->get('new_district_name')" class="mt-1.5" />
                                </div>

                                <div>
                                    <x-input-label for="new_district_state_id" value="State (Under Your Region)" />
                                    <select id="new_district_state_id" name="new_district_state_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                                        <option value="">-- Select a state in {{ $region->name }} --</option>
                                        @foreach ($states as $st)
                                            <option value="{{ $st->id }}" {{ old('new_district_state_id') == $st->id ? 'selected' : '' }}>
                                                {{ $st->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('new_district_state_id')" class="mt-1.5" />
                                    <p class="text-xs text-slate-400 mt-1">
                                        The new district will be linked to this state under {{ $region->name }} region.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="pt-2">
                    <x-primary-button type="submit" :disabled="$nextRole === 'coordinator' && !$region" class="w-full font-display justify-center {{ $nextRole === 'coordinator' && !$region ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span>Send Invite</span>
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
