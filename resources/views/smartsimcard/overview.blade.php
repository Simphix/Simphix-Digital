<x-app-layout>
    <title>SmartSIM - SIM Services</title>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="cpu" class="w-5 h-5"></i>
                </div>
                SIM Services
            </h1>
            <p class="text-sm text-slate-500 mt-1">Pick the SIM built for what you're connecting. Each device has its own activation fee and instant setup.</p>
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

        <!-- Device Catalog: vertical illustrated sections -->
        <div class="flex flex-col gap-4">
            @foreach ($devices as $device)
                @if ($device['comingSoon'])
                    <div class="flex flex-col sm:flex-row items-center gap-6 bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 opacity-60 cursor-not-allowed" aria-disabled="true">
                        <x-sim-illustration :illustration="$device['illustration']" class="w-28 h-28 sm:w-32 sm:h-32 shrink-0" />
                        <div class="flex-1 text-center sm:text-left">
                            <div class="flex items-center justify-center sm:justify-start gap-2">
                                <h3 class="text-lg font-bold text-slate-800 font-display">{{ $device['label'] }}</h3>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 border border-slate-200 rounded-full px-2 py-0.5">Coming Soon</span>
                            </div>
                            <p class="text-sm text-slate-400 mt-1">{{ $device['desc'] }}</p>
                        </div>
                        <div class="shrink-0 text-center sm:text-right">
                            <span class="text-sm font-semibold text-slate-400">Not yet available</span>
                        </div>
                    </div>
                @else
                    <a href="{{ $device['route'] }}"
                       class="group flex flex-col sm:flex-row items-center gap-6 bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-primary/30">
                        <x-sim-illustration :illustration="$device['illustration']" class="w-28 h-28 sm:w-32 sm:h-32 shrink-0" />
                        <div class="flex-1 text-center sm:text-left">
                            <h3 class="text-lg font-bold text-slate-800 font-display group-hover:text-primary transition-colors">{{ $device['label'] }}</h3>
                            <p class="text-sm text-slate-400 mt-1">{{ $device['desc'] }}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-4">
                            <div class="text-center sm:text-right">
                                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ !empty($device['ep_override']) ? 'Your Price' : 'From' }}</span>
                                <span class="text-xl font-extrabold text-primary font-display">₦{{ number_format($device['price'] ?? 0, 2) }}</span>
                                @if (!is_null($device['commission']) && $device['commission'] > 0)
                                    <span class="block text-[11px] font-semibold text-emerald-600 mt-0.5">Earn ₦{{ number_format($device['commission'], 2) }} per sale</span>
                                @endif
                                @if (!empty($device['ep_override']))
                                    <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 text-[10px] font-bold text-emerald-700">
                                        <i data-lucide="badge-check" class="w-3 h-3"></i> Enterprise pricing
                                    </span>
                                @endif
                            </div>
                            <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:translate-x-0.5">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
