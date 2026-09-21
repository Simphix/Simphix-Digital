<x-app-layout>
    <title>SmartSIM - Commission Rates</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="badge-percent" class="w-5 h-5"></i>
                </div>
                Commission Rates
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Set the price your business agents charge per category and how much of it they keep.
                You keep the rest (price &minus; commission) as your margin.
            </p>
        </div>

        <div class="bg-emerald-50/60 border border-emerald-100 rounded-xl p-4 flex items-start gap-3">
            <i data-lucide="badge-check" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
            <p class="text-xs text-emerald-900 leading-relaxed">
                <strong>Your rates override the platform pricing completely.</strong>
                Customers activated through your business are debited exactly the price you set here, your agents earn the commission you set, and you keep the difference.
                Platform prices below are shown for reference only. A category with no rate of its own uses your default rate; with neither, platform pricing applies.
            </p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Default rate</h3>
            <p class="text-xs text-slate-500 -mt-2">Applies to any category below that doesn't have its own rate set.</p>

            @php $defaultRate = $rates->get('default'); @endphp
            <form action="{{ route('sims.business.commission-rates.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label value="Price (₦)" />
                    <x-text-input type="number" step="0.01" min="0" name="price" class="w-full" value="{{ old('price', $defaultRate->price ?? '') }}" required />
                </div>
                <div>
                    <x-input-label value="Agent commission (₦)" />
                    <x-text-input type="number" step="0.01" min="0" name="commission" class="w-full" value="{{ old('commission', $defaultRate->commission ?? '') }}" required />
                </div>
                <x-primary-button type="submit" class="!text-xs">Save default</x-primary-button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Per-category rates</h3>

            @forelse ($fields as $field)
                @php
                    $rate = $rates->get($field->id);
                    $effective = $rate ?? $defaultRate;
                    $platformPrice = $platformPrices[$field->id] ?? null;
                @endphp
                <form action="{{ route('sims.business.commission-rates.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end border-b border-slate-50 pb-4 last:border-0 last:pb-0">
                    @csrf
                    <input type="hidden" name="service_fields_id" value="{{ $field->id }}" />
                    <div class="sm:col-span-1">
                        <span class="block text-sm font-semibold text-slate-700">{{ $field->field_name }}</span>
                        <span class="block text-[11px] text-slate-400">Platform: ₦{{ number_format($platformPrice ?? 0, 2) }}</span>
                        @if ($effective && (float) $effective->price > 0)
                            <span class="inline-flex mt-1 px-1.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 text-[10px] font-bold text-emerald-700">{{ $rate ? 'Custom' : 'Default' }} rate active</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Customer pays ₦{{ number_format($effective->price, 2) }} · agent ₦{{ number_format($effective->commission, 2) }} · you ₦{{ number_format(max($effective->price - $effective->commission, 0), 2) }}</span>
                        @else
                            <span class="inline-flex mt-1 px-1.5 py-0.5 rounded-full bg-amber-50 border border-amber-100 text-[10px] font-bold text-amber-700">Platform pricing</span>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label value="Price (₦)" />
                        <x-text-input type="number" step="0.01" min="0" name="price" class="w-full" value="{{ old('price', $rate->price ?? '') }}" placeholder="Uses default" />
                    </div>
                    <div>
                        <x-input-label value="Agent commission (₦)" />
                        <x-text-input type="number" step="0.01" min="0" name="commission" class="w-full" value="{{ old('commission', $rate->commission ?? '') }}" placeholder="Uses default" />
                    </div>
                    <x-secondary-button type="submit" class="!text-xs">Save</x-secondary-button>
                </form>
            @empty
                <div class="text-center text-slate-400 text-sm py-6">No SIM categories configured yet.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
