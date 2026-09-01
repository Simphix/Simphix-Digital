<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 font-display">Become an Enterprise Partner</h1>
        <p class="text-sm text-slate-500 mt-1">
            You were invited by <strong class="text-slate-900">{{ $inviter->name }}</strong>. Register your business below to get started.
        </p>
    </div>

    <form method="POST" action="{{ route('enterprise-partner.register.store') }}" class="space-y-5"
          x-data="{
            matrix: @json($matrix),
            category: '{{ old('sim_category', array_key_first($matrix) ?? '') }}',
            provider: '{{ old('provider', 'mtn') }}',
            get unitPrice() { return this.matrix[this.category]?.unit_price ?? 0; },
            get totalAmount() { return this.unitPrice * {{ $quantity }}; },
            isAvailable(cat, prov) { return this.matrix[cat]?.providers?.[prov]?.available ?? false; },
          }">
        @csrf
        <input type="hidden" name="ref" value="{{ old('ref', request('ref')) }}">

        <div class="p-4 rounded-lg bg-primary/5 border border-primary/10 text-slate-700 text-sm flex gap-3 items-start">
            <i data-lucide="info" class="w-5 h-5 text-primary shrink-0 mt-0.5"></i>
            <span>
                Eligibility requires purchasing <strong>{{ $quantity }}</strong> SIM cards, in any category and network
                that currently has enough stock, at <strong x-text="'₦' + unitPrice.toLocaleString(undefined, {minimumFractionDigits: 2})"></strong>
                each &mdash; a total of <strong x-text="'₦' + totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2})"></strong>.
                You'll pay after registering.
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="sim_category" value="SIM Category" />
                <x-select-input id="sim_category" name="sim_category" x-model="category" class="w-full">
                    <template x-for="(data, cat) in matrix" :key="cat">
                        <option :value="cat" x-text="cat"></option>
                    </template>
                </x-select-input>
                <x-input-error :messages="$errors->get('sim_category')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="provider" value="Network" />
                <x-select-input id="provider" name="provider" x-model="provider" class="w-full">
                    @foreach ($providers as $prov)
                        <option value="{{ $prov }}" :disabled="!isAvailable(category, '{{ $prov }}')">
                            {{ strtoupper($prov) }} <template x-if="!isAvailable(category, '{{ $prov }}')"> (unavailable)</template>
                        </option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('provider')" class="mt-1.5" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="first_name" value="First Name" />
                <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required class="w-full" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="last_name" value="Last Name" />
                <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required class="w-full" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-1.5" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="Email Address" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" class="w-full" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="phone" value="Phone Number" />
            <x-text-input id="phone" type="text" name="phone" :value="old('phone')" required class="w-full" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="business_name" value="Business Name" />
            <x-text-input id="business_name" type="text" name="business_name" :value="old('business_name')" required class="w-full" />
            <x-input-error :messages="$errors->get('business_name')" class="mt-1.5" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="business_type" value="Business Type" />
                <x-select-input id="business_type" name="business_type" class="w-full">
                    <option value="">Select type</option>
                    <option value="sole_proprietor" @selected(old('business_type') === 'sole_proprietor')>Sole Proprietor</option>
                    <option value="llc" @selected(old('business_type') === 'llc')>LLC</option>
                    <option value="partnership" @selected(old('business_type') === 'partnership')>Partnership</option>
                </x-select-input>
                <x-input-error :messages="$errors->get('business_type')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="cac_number" value="CAC Number" />
                <x-text-input id="cac_number" type="text" name="cac_number" :value="old('cac_number')" required class="w-full" />
                <x-input-error :messages="$errors->get('cac_number')" class="mt-1.5" />
            </div>
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" class="w-full" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="w-full" />
        </div>

        <x-primary-button class="w-full justify-center">Register as Enterprise Partner</x-primary-button>
    </form>
</x-guest-layout>
