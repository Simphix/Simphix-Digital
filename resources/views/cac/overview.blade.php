<x-app-layout>
    <title>SmartSIM - CAC Registration</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="file-badge" class="w-5 h-5"></i>
                    </div>
                    CAC Registration
                </h1>
                <p class="text-sm text-slate-500 mt-1">Register your business with the Corporate Affairs Commission. Pick the type that fits — the fee is paid from your wallet and tracked end-to-end.</p>
            </div>
            <a href="{{ route('cac.history') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-sm transition-all shrink-0">
                <i data-lucide="history" class="w-4 h-4"></i>
                My Registrations
                @if ($activeCount > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-primary text-white rounded-full leading-none">{{ $activeCount }}</span>
                @endif
            </a>
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

        <div class="flex flex-col gap-4">
            @foreach ($categories as $c)
                @if ($c['available'])
                    <a href="{{ $c['route'] }}"
                       class="group flex flex-col sm:flex-row items-center gap-6 bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-primary/30">
                        <div class="w-24 h-24 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $c['icon'] }}" class="w-10 h-10"></i>
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h3 class="text-lg font-bold text-slate-800 font-display group-hover:text-primary transition-colors">{{ $c['label'] }}</h3>
                            <p class="text-sm text-slate-400 mt-1">{{ $c['tagline'] }}</p>
                            <p class="text-xs text-slate-400 mt-2">{{ $c['timeline'][0] }}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-4">
                            <div class="text-center sm:text-right">
                                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Fee</span>
                                <span class="text-xl font-extrabold text-primary font-display">₦{{ number_format($c['price'] ?? 0, 2) }}</span>
                            </div>
                            <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:translate-x-0.5">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </a>
                @else
                    <div class="flex flex-col sm:flex-row items-center gap-6 bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 opacity-60 cursor-not-allowed">
                        <div class="w-24 h-24 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $c['icon'] }}" class="w-10 h-10"></i>
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <div class="flex items-center justify-center sm:justify-start gap-2">
                                <h3 class="text-lg font-bold text-slate-800 font-display">{{ $c['label'] }}</h3>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-2 py-0.5">Unavailable</span>
                            </div>
                            <p class="text-sm text-slate-400 mt-1">{{ $c['tagline'] }}</p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
