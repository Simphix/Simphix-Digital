<x-app-layout>
    <title>SmartSIM - Build Your SIM Order</title>

    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                </div>
                Build Your SIM Order, {{ $user->first_name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Mix any categories and networks that currently have stock, in whatever quantities you like — you just
                need at least {{ $eligibilityQuantity }} SIMs total before you can pay.
            </p>
        </div>

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
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($hasPayment)
            <div class="bg-amber-50/70 border border-amber-100 rounded-xl p-4 text-amber-800 flex items-start gap-3">
                <i data-lucide="lock" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <p class="text-xs leading-relaxed">
                    Your order is locked in for payment and can no longer be changed.
                    <a href="{{ route('enterprise-partner.generate-account') }}" class="font-bold underline">View your payment details</a>.
                </p>
            </div>
        @else
            <!-- Add item -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
                 x-data='{
                    matrix: @json($matrix),
                    category: "{{ old('sim_category', array_key_first($matrix) ?? '') }}",
                    provider: "{{ old('provider', 'mtn') }}",
                    quantity: {{ (int) old('quantity', 1) }},
                    get unitPrice() { return this.matrix[this.category]?.unit_price ?? 0; },
                    get lineTotal() { return this.unitPrice * this.quantity; },
                    isAvailable(cat, prov) { return this.matrix[cat]?.providers?.[prov]?.available ?? false; },
                 }'>
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">Add to your order</h3>

                <form method="POST" action="{{ route('enterprise-partner.cart.items.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label value="SIM Category" />
                            <x-select-input name="sim_category" x-model="category" class="w-full">
                                <template x-for="(data, cat) in matrix" :key="cat">
                                    <option :value="cat" x-text="cat"></option>
                                </template>
                            </x-select-input>
                        </div>
                        <div>
                            <x-input-label value="Network" />
                            <x-select-input name="provider" x-model="provider" class="w-full">
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}" :disabled="!isAvailable(category, '{{ $prov }}')">
                                        {{ strtoupper($prov) }} <template x-if="!isAvailable(category, '{{ $prov }}')"> (not available)</template>
                                    </option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div>
                            <x-input-label value="Quantity" />
                            <x-text-input type="number" name="quantity" x-model.number="quantity" min="1" class="w-full" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                        <span class="flex items-center gap-1.5">
                            <span :class="isAvailable(category, provider) ? 'text-emerald-600' : 'text-rose-600'" class="font-bold" x-text="isAvailable(category, provider) ? 'Available' : 'Not available'"></span>
                            <span>&middot; ₦<span x-text="unitPrice.toLocaleString(undefined, {minimumFractionDigits: 2})"></span> each</span>
                        </span>
                        <span class="font-bold text-slate-700">Line total: ₦<span x-text="lineTotal.toLocaleString(undefined, {minimumFractionDigits: 2})"></span></span>
                    </div>

                    <x-primary-button type="submit">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add to Order
                    </x-primary-button>
                </form>
            </div>
        @endif

        <!-- Order summary -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">Your order</h3>

            @forelse ($application->items as $item)
                <div class="flex items-center justify-between gap-3 p-3 bg-slate-50/60 border border-slate-100 rounded-xl">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800">{{ $item->sim_category }} &middot; {{ strtoupper($item->provider) }}</p>
                        <p class="text-xs text-slate-400">₦{{ number_format($item->unit_price_snapshot, 2) }} each</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        @unless ($hasPayment)
                            <form method="POST" action="{{ route('enterprise-partner.cart.items.update', $item) }}" class="flex items-center gap-1.5">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                       class="w-20 rounded-lg border-slate-200 text-xs font-semibold py-1.5">
                                <button type="submit" class="text-[11px] font-bold text-primary hover:text-[#0049b8] px-2 py-1.5">Update</button>
                            </form>
                        @else
                            <span class="text-xs font-semibold text-slate-600">Qty {{ $item->quantity }}</span>
                        @endunless
                        <span class="text-sm font-bold text-slate-800 w-28 text-right">₦{{ number_format($item->line_total_snapshot, 2) }}</span>
                        @unless ($hasPayment)
                            <form method="POST" action="{{ route('enterprise-partner.cart.items.destroy', $item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400 italic text-center py-4">Your order is empty — add a SIM category/network above to get started.</p>
            @endforelse

            <div class="pt-3 border-t border-slate-100 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Total quantity</span>
                    <span class="font-bold {{ $eligible ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $totalQuantity }} / {{ $eligibilityQuantity }} minimum
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Total due</span>
                    <span class="text-xl font-extrabold text-primary font-display">₦{{ number_format($application->total_amount_snapshot, 2) }}</span>
                </div>
            </div>
        </div>

        @unless ($hasPayment)
            @if ($eligible)
                <a href="{{ route('enterprise-partner.generate-account') }}"
                   class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg bg-primary text-white text-sm font-bold shadow-sm transition-all hover:bg-primary/90">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                    Proceed to Payment
                </a>
            @else
                <div class="w-full px-4 py-3 rounded-lg bg-slate-100 text-slate-400 text-sm font-bold text-center flex items-center justify-center gap-2">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    Add {{ $eligibilityQuantity - $totalQuantity }} more SIM(s) to proceed to payment
                </div>
            @endif
        @endunless
    </div>
</x-app-layout>
