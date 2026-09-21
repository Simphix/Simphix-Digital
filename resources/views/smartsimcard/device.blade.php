<x-app-layout>
    <title>SmartSIM - {{ $device['label'] }}</title>

    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Back Link -->
        <a href="{{ route('sims.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to SIM Services
        </a>

        <!-- Device Header Card -->
        <div class="flex flex-col sm:flex-row items-center gap-6 bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <x-sim-illustration :illustration="$device['illustration']" class="w-24 h-24 sm:w-28 sm:h-28 shrink-0" />
            <div class="flex-1 text-center sm:text-left">
                <h1 class="text-2xl font-bold font-display text-slate-800">{{ $device['label'] }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ $device['desc'] }}</p>
            </div>
            <div class="shrink-0 text-center sm:text-right">
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $epOverride ? 'Customer Price' : 'Activation Fee' }}</span>
                <span class="text-2xl font-extrabold text-primary font-display">₦{{ number_format($price ?? 0, 2) }}</span>
                @if ($epOverride)
                    <span class="block text-[11px] font-semibold text-emerald-600 mt-0.5">
                        {{ $user->role === 'business_agent' ? 'You earn' : 'Your margin' }} ₦{{ number_format($epEarning ?? 0, 2) }} per sale
                    </span>
                    <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 text-[10px] font-bold text-emerald-700">
                        <i data-lucide="badge-check" class="w-3 h-3"></i> Enterprise pricing — overrides platform rates
                    </span>
                @endif
            </div>
        </div>

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

        {{-- Shared Alpine scope for the search field (or select, for self-activate) that the mini SIM
             list below and the action card's form both bind to — kept as one scope so a chip click
             from the mini list can drive the field inside the card. --}}
        @if ($mode === 'self_activate')
            <div x-data="{ selected: '{{ old('sim_id') }}' }" class="space-y-6">
        @else
            <div x-data='{
                    numbers: @json($heldSims->pluck("id", "number")),
                    numberInput: "",
                    simId: "",
                    lookup() { this.simId = this.numbers[this.numberInput.trim()] ?? ""; },
                    users: @json($downlineUsers->mapWithKeys(fn ($du) => [trim("{$du->first_name} {$du->last_name}") . " · " . ($du->phone ?? '') . " #{$du->id}" => $du->id])),
                    userInput: "",
                    userId: "",
                    lookupUser() { this.userId = this.users[this.userInput.trim()] ?? ""; }
                }' class="space-y-6">
        @endif

            @if ($heldSims->isNotEmpty())
                <!-- Mini list: Available category SIMs -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Available {{ $device['label'] }} Numbers</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($heldSims as $sim)
                            <button type="button"
                                    @click="{{ $mode === 'self_activate' ? "selected = '{$sim->id}'" : "numberInput = '{$sim->number}'; lookup()" }}"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-primary/5 hover:border-primary/30 text-xs font-semibold text-slate-700 transition-colors">
                                {{ $sim->number }}
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">{{ \App\Support\SimStatus::label($sim->status) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left: role-specific action -->
                <div class="lg:col-span-6">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        @if ($mode === 'distribute')
                            @php $nextRoleLabel = str_replace('_', ' ', \App\Support\RoleHierarchy::nextRole($user->role)); @endphp
                            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">Assign to Downline</h3>
                                    <p class="text-xs text-slate-400">Hand off a {{ $device['label'] }} you hold to one of your {{ $nextRoleLabel }}s.</p>
                                </div>
                            </div>

                            @if ($heldSims->isEmpty())
                                <p class="text-xs text-slate-400">You aren't currently holding any {{ $device['label'] }} numbers to assign.</p>
                            @elseif ($downlineUsers->isEmpty())
                                <p class="text-xs text-slate-400">
                                    You haven't onboarded any {{ $nextRoleLabel }}s yet.
                                    <a href="{{ route('onboarding.invite') }}" class="text-primary font-semibold hover:underline">Invite one</a>.
                                </p>
                            @else
                                <form action="{{ route('partner.sims.assign') }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="sim_id" x-bind:value="simId">

                                    <div class="space-y-1.5">
                                        <x-input-label for="assign_number_{{ $slug }}" value="Look Up / Search SIM Number" />
                                        <input type="text" id="assign_number_{{ $slug }}" list="assign_numbers_{{ $slug }}"
                                               x-model="numberInput" @input="lookup()" autocomplete="off"
                                               class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                               placeholder="Start typing a number you hold…">
                                        <datalist id="assign_numbers_{{ $slug }}">
                                            @foreach ($heldSims as $sim)
                                                <option value="{{ $sim->number }}"></option>
                                            @endforeach
                                        </datalist>
                                        <p x-show="numberInput.length > 0 && !simId" x-cloak class="text-xs font-medium text-rose-500">That number isn't one you currently hold.</p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <x-input-label for="assign_user_{{ $slug }}" value="Search by Name or Phone" />
                                        <input type="text" id="assign_user_{{ $slug }}" list="assign_users_{{ $slug }}"
                                               x-model="userInput" @input="lookupUser()" autocomplete="off"
                                               class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                               placeholder="Start typing a {{ $nextRoleLabel }}'s name or phone…">
                                        <datalist id="assign_users_{{ $slug }}">
                                            @foreach ($downlineUsers as $du)
                                                <option value="{{ trim($du->first_name . ' ' . $du->last_name) }} · {{ $du->phone }} #{{ $du->id }}"></option>
                                            @endforeach
                                        </datalist>
                                        <input type="hidden" name="user_id" x-bind:value="userId">
                                        <p x-show="userInput.length > 0 && !userId" x-cloak class="text-xs font-medium text-rose-500">That's not one of your {{ $nextRoleLabel }}s.</p>
                                    </div>

                                    <x-primary-button type="submit" class="w-full !text-xs" x-bind:disabled="!simId || !userId">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        Assign to Downline
                                    </x-primary-button>
                                </form>
                            @endif
                        @elseif ($mode === 'activate_for_user')
                            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <i data-lucide="zap" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">Activate for a User</h3>
                                    <p class="text-xs text-slate-400">Activate a {{ $device['label'] }} you hold on behalf of a User in your downline.</p>
                                </div>
                            </div>

                            @if ($heldSims->isEmpty())
                                <p class="text-xs text-slate-400">You aren't currently holding any {{ $device['label'] }} numbers to activate.</p>
                            @elseif ($downlineUsers->isEmpty())
                                <p class="text-xs text-slate-400">
                                    You haven't onboarded any Users yet.
                                    <a href="{{ route('onboarding.invite') }}" class="text-primary font-semibold hover:underline">Invite one</a>.
                                </p>
                            @else
                                <form action="{{ route('sims.activate-for-user') }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="sim_id" x-bind:value="simId">

                                    <div class="space-y-1.5">
                                        <x-input-label for="activate_number_{{ $slug }}" value="Look Up / Search SIM Number" />
                                        <input type="text" id="activate_number_{{ $slug }}" list="activate_numbers_{{ $slug }}"
                                               x-model="numberInput" @input="lookup()" autocomplete="off"
                                               class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                               placeholder="Start typing a number you hold…">
                                        <datalist id="activate_numbers_{{ $slug }}">
                                            @foreach ($heldSims as $sim)
                                                <option value="{{ $sim->number }}"></option>
                                            @endforeach
                                        </datalist>
                                        <p x-show="numberInput.length > 0 && !simId" x-cloak class="text-xs font-medium text-rose-500">That number isn't one you currently hold.</p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <x-input-label for="activate_user_{{ $slug }}" value="Search by Name or Phone" />
                                        <input type="text" id="activate_user_{{ $slug }}" list="activate_users_{{ $slug }}"
                                               x-model="userInput" @input="lookupUser()" autocomplete="off"
                                               class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                               placeholder="Start typing a user's name or phone…">
                                        <datalist id="activate_users_{{ $slug }}">
                                            @foreach ($downlineUsers as $du)
                                                <option value="{{ trim($du->first_name . ' ' . $du->last_name) }} · {{ $du->phone }} #{{ $du->id }}"></option>
                                            @endforeach
                                        </datalist>
                                        <input type="hidden" name="user_id" x-bind:value="userId">
                                        <p x-show="userInput.length > 0 && !userId" x-cloak class="text-xs font-medium text-rose-500">That's not one of your downline users.</p>
                                    </div>

                                    <div class="bg-primary/5 border border-primary/10 rounded-lg p-4 space-y-1 shadow-inner">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Activation Fee</span>
                                            <span class="text-base font-extrabold text-primary font-display">₦{{ number_format($personalPrice ?? 0, 2) }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400">This amount is debited from the selected user's wallet — not yours.</p>
                                    </div>

                                    <x-primary-button type="submit" class="w-full !text-xs !bg-emerald-600 hover:!bg-emerald-700" x-bind:disabled="!simId || !userId || {{ $activationDisabled ? 'true' : 'false' }}">
                                        <i data-lucide="power" class="w-3.5 h-3.5"></i>
                                        Activate SIM
                                    </x-primary-button>
                                </form>
                            @endif
                        @else
                            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <i data-lucide="zap" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">Activate a {{ $device['label'] }}</h3>
                                    <p class="text-xs text-slate-400">Request activation for a {{ $device['label'] }} assigned to your account.</p>
                                </div>
                            </div>

                            <form action="{{ route('sims.activate') }}" method="POST" class="space-y-4">
                                @csrf

                                <div class="space-y-1.5">
                                    <x-input-label for="activate_sim_id" value="Select SIM Number" />
                                    <x-select-input id="activate_sim_id" name="sim_id" required class="rounded-xl font-medium" x-model="selected">
                                        <option value="">Select SIM</option>
                                        @forelse ($heldSims as $sim)
                                            <option value="{{ $sim->id }}">{{ $sim->number }} ({{ strtoupper($sim->provider) }})</option>
                                        @empty
                                            <option value="" disabled>No inactive {{ $device['label'] }}s found.</option>
                                        @endforelse
                                    </x-select-input>
                                </div>

                                <div class="bg-primary/5 border border-primary/10 rounded-lg p-4 space-y-1 shadow-inner">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Activation Fee</span>
                                        <span class="text-base font-extrabold text-primary font-display">₦{{ number_format($price ?? 0, 2) }}</span>
                                    </div>
                                    <p class="text-xs text-slate-400">This amount will be debited from your wallet balance.</p>
                                </div>

                                <x-primary-button type="submit" class="w-full !text-xs !bg-emerald-600 hover:!bg-emerald-700">
                                    <i data-lucide="power" class="w-3.5 h-3.5"></i>
                                    Request Activation
                                </x-primary-button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Right: Request a new number -->
                <div class="lg:col-span-6">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col items-start gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800 font-display">Don't have a {{ $device['label'] }} yet?</h3>
                                <p class="text-xs text-slate-400">Request one from our uploaded number inventory.</p>
                            </div>
                        </div>
                        <a href="{{ route('sims.' . $slug . '.request') }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-white font-semibold text-xs shadow-sm transition-all hover:bg-primary/90 active:scale-[0.98]">
                            <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                            Request {{ $device['label'] }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
