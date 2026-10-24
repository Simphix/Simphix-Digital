<x-app-layout>
    <title>SimPhix - Generate Payment Account</title>

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                </div>
                Complete Your Eligibility Payment
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Track your SIM order and payment status below.
            </p>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 text-amber-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('warning') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if ($payment && $payment->status === 'paid' && $application->status === 'fulfilled')
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                    Your order has been fulfilled
                </h3>
                <div class="space-y-2 text-sm">
                    @foreach ($application->items as $item)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $item->sim_category }} ({{ strtoupper($item->provider) }})</span>
                            <span class="font-bold {{ $item->fulfilled_quantity < $item->quantity ? 'text-amber-600' : 'text-emerald-600' }}">
                                {{ $item->fulfilled_quantity }} / {{ $item->quantity }} received
                            </span>
                        </div>
                    @endforeach
                </div>
                @if ($application->items->sum('fulfilled_quantity') < $application->items->sum('quantity'))
                    <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs text-amber-800">
                        Some items were fulfilled below what you ordered — reach out to support if you have questions about your order.
                    </div>
                @endif
                <p class="text-xs text-slate-400">Your dashboard is unlocked — this page is just a record of your order.</p>
            </div>
        @elseif ($payment && $payment->status === 'paid')
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
                    Payment confirmed — order under review
                </h3>
                <p class="text-sm text-slate-600">Your payment has been received. Our team is reviewing your order and will assign your SIMs shortly — you'll be able to use your dashboard once that's done.</p>
                <div class="bg-slate-50 border border-slate-100 rounded-lg p-3 text-xs text-slate-600 space-y-2">
                    @foreach ($application->items as $item)
                        <div class="flex items-center justify-between">
                            <span>{{ $item->quantity }} &times; {{ $item->sim_category }} ({{ strtoupper($item->provider) }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif ($payment)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">Your payment account</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Bank</p>
                        <p class="font-semibold text-slate-800">{{ $payment->bank_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Account Number</p>
                        <p class="font-bold text-slate-800 font-mono">{{ $payment->account_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Account Name</p>
                        <p class="font-semibold text-slate-800">{{ $payment->account_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Amount to Pay</p>
                        <p class="font-bold text-primary text-base">₦{{ number_format($payment->expected_amount, 2) }}</p>
                    </div>
                </div>
                <div class="bg-primary/5 border border-primary/10 rounded-lg p-3 text-xs text-slate-600">
                    Transfer the exact amount above to this account. Your dashboard unlocks once your payment is confirmed and our team fulfills your order.
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5"
                 x-data="{ idType: '{{ old('identity_type', 'bvn') }}' }">
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">Verify your identity to generate an account</h3>

                @if ($application)
                    <div class="bg-slate-50 border border-slate-100 rounded-lg p-3 text-xs text-slate-600 space-y-2">
                        @foreach ($application->items as $item)
                            <div class="flex items-center justify-between">
                                <span>{{ $item->quantity }} &times; {{ $item->sim_category }} ({{ strtoupper($item->provider) }})</span>
                                <span class="font-semibold text-slate-700">₦{{ number_format($item->line_total_snapshot, 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                            <span class="font-semibold text-slate-700">Total Due</span>
                            <span class="font-bold text-slate-800">₦{{ number_format($application->total_amount_snapshot, 2) }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('enterprise-partner.generate-account.create') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="identity_type" x-bind:value="idType">

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
                        <x-text-input type="text" name="bvn" :value="old('bvn')" placeholder="Enter 11-digit BVN"
                               maxlength="11" pattern="\d{11}" title="BVN must be exactly 11 digits"
                               x-bind:required="idType === 'bvn'" class="w-full" />
                        <x-input-error :messages="$errors->get('bvn')" class="mt-1.5" />
                    </div>

                    <div x-show="idType === 'nin'">
                        <x-input-label value="National Identification Number (NIN)" />
                        <x-text-input type="text" name="nin" :value="old('nin')" placeholder="Enter 11-digit NIN"
                               maxlength="11" pattern="\d{11}" title="NIN must be exactly 11 digits"
                               x-bind:required="idType === 'nin'" class="w-full" />
                        <x-input-error :messages="$errors->get('nin')" class="mt-1.5" />
                    </div>

                    <x-primary-button type="submit" class="w-full justify-center">Generate Payment Account</x-primary-button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
