<!-- Landscape Banner for Travel & Tours (Umrah, Hajj & International Travels) -->
<div class="mb-4 w-full">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-950 via-[#0B2559] to-[#0056D2] p-4 sm:p-6 lg:p-7 shadow-lg border border-sky-700/30 text-white">
        <!-- Decorative Ambient Glow & Background Elements -->
        <div class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>
        
        <!-- Subtle Pattern Overlay -->
        <div class="pointer-events-none absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4 lg:gap-8">
            <!-- Left Info Area -->
            <div class="min-w-0 flex-1">
                <!-- Pill Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-amber-300/30 text-amber-300 text-[11px] sm:text-xs font-semibold mb-2.5">
                    <span class="flex h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>New &bull; Holy Pilgrimages &amp; Global Travels</span>
                </div>

                <!-- Main Title -->
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white font-display tracking-tight leading-snug">
                    Umrah, Hajj &amp; International Travel Packages
                </h2>

                <!-- Subtitle / Description -->
                <p class="mt-1.5 text-xs sm:text-sm text-sky-100/90 leading-relaxed max-w-2xl">
                    Embark on spiritual journeys to Makkah &amp; Madinah or explore prime destinations worldwide with complete visa support, confirmed flights, and luxury hotel accommodations.
                </p>

                <!-- Value Highlights / Tags -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-950/60 border border-sky-400/20 text-sky-200 text-[11px] font-medium">
                        <i data-lucide="moon" class="w-3.5 h-3.5 text-amber-300"></i>
                        <span>Umrah &amp; Hajj 2026/2027</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-950/60 border border-sky-400/20 text-sky-200 text-[11px] font-medium">
                        <i data-lucide="plane-takeoff" class="w-3.5 h-3.5 text-sky-300"></i>
                        <span>Worldwide Flights &amp; Tours</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-950/60 border border-sky-400/20 text-sky-200 text-[11px] font-medium">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Visa &amp; Logistics Processing</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-950/60 border border-sky-400/20 text-sky-200 text-[11px] font-medium">
                        <i data-lucide="hotel" class="w-3.5 h-3.5 text-amber-200"></i>
                        <span>Haram-View &amp; Star Hotels</span>
                    </span>
                </div>
            </div>

            <!-- Right Action Buttons -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0 pt-2 lg:pt-0">
                <a href="{{ route('travel.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-amber-300 hover:from-amber-300 hover:to-amber-200 text-slate-950 font-bold text-xs sm:text-sm shadow-md shadow-amber-950/20 transition-all transform active:scale-95 no-underline">
                    <i data-lucide="plane" class="w-4 h-4 text-slate-950"></i>
                    <span>Explore Packages &amp; Book</span>
                </a>
                <a href="{{ route('travel.tracking') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 font-semibold text-xs sm:text-sm backdrop-blur-sm transition-all no-underline">
                    <i data-lucide="search" class="w-4 h-4 text-sky-200"></i>
                    <span>Track My Request</span>
                </a>
            </div>
        </div>
    </div>
</div>
