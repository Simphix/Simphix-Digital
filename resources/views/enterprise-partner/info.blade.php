<x-app-layout>
    <title>SmartSIM - Enterprise Partner Eligibility</title>

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="rocket" class="w-5 h-5"></i>
                </div>
                Almost there, {{ $user->first_name }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Your Enterprise Partner application is in. One step left: complete your eligibility payment to unlock your dashboard.
            </p>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if ($application)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">Eligibility summary</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">SIM Category</p>
                        <p class="font-semibold text-slate-800">{{ $application->sim_category }} ({{ strtoupper($application->provider) }})</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Quantity</p>
                        <p class="font-semibold text-slate-800">{{ $application->quantity }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Unit Price</p>
                        <p class="font-semibold text-slate-800">₦{{ number_format($application->unit_price_snapshot, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Total Due</p>
                        <p class="font-bold text-primary text-base">₦{{ number_format($application->total_amount_snapshot, 2) }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
            <h3 class="text-sm font-semibold text-slate-800 font-display">What happens next</h3>
            <ol class="text-xs text-slate-500 space-y-2 list-decimal list-inside">
                <li>Generate a dedicated payment account for your eligibility purchase.</li>
                <li>Transfer the exact amount shown above.</li>
                <li>Once confirmed, your 100 MTN POS SIMs are credited to your inventory automatically and your dashboard unlocks.</li>
            </ol>
        </div>

        <a href="{{ route('enterprise-partner.generate-account') }}"
           class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg bg-primary text-white text-sm font-bold shadow-sm transition-all hover:bg-primary/90">
            <i data-lucide="credit-card" class="w-4 h-4"></i>
            Generate Payment Account
        </a>
    </div>
</x-app-layout>
