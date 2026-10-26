<x-app-layout>
    <title>SimPhix - Activate SIM Card</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Back Link & Header -->
        <div class="space-y-3">
            <a href="{{ route('sims.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to SIM Services
            </a>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                        </div>
                        SIM Card Activation
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">Activate any SIM card held in your inventory for a customer in 3 quick steps.</p>
                </div>

                <div class="flex items-center gap-2.5">
                    <a href="{{ route('sims.inventory') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="database" class="w-3.5 h-3.5 text-slate-400"></i>
                        View Inventory
                    </a>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-rose-800 space-y-1 shadow-sm">
                <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wider text-rose-600">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    Please resolve the following:
                </div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Multi-Step Wizard --}}
        @include('smartsimcard.partials.activation-wizard', [
            'simsData' => $simsData,
            'wallet'   => $wallet,
        ])
    </div>
</x-app-layout>
