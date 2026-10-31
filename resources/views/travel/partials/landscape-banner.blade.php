@php
    /**
     * Travel & Tours Balanced Landscape Ad Banner
     * 
     * To set or customize your background image:
     * 1. Place your image at: public/assets/images/travel-banner-bg.jpg (or .png / .webp)
     * 2. Or change the $customBannerImage variable below to any local or remote image URL.
     */
    $customBannerImage = asset('assets/images/travel-banner-bg.jpg');
    $hasLocalImage = file_exists(public_path('assets/images/travel-banner-bg.jpg'));
@endphp

<!-- Balanced Landscape Ad Banner for Travel & Tours -->
<div class="mb-4 w-full">
    <div class="relative overflow-hidden rounded-2xl border border-sky-800/40 shadow-md bg-slate-950 text-white"
         @if($hasLocalImage) style="background-image: url('{{ $customBannerImage }}'); background-size: cover; background-position: center;" @endif>
        
        <!-- Background Image layer (falls back to rich gradient when image is not yet uploaded) -->
        <div class="absolute inset-0 bg-cover bg-center pointer-events-none"
             style="background-image: url('{{ $customBannerImage }}');"></div>

        <!-- Dark Gradient Scrim: Ensures text & buttons are 100% visible, sharp & high-contrast over any photo -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-[#0056D2]/75 pointer-events-none" aria-hidden="true"></div>

        <!-- Subtle Light Ambient Accent -->
        <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>

        <!-- Banner Content Container (Balanced padding and vertical rhythm) -->
        <div class="relative z-10 w-full p-4 sm:p-5 lg:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 lg:gap-6">
            
            <!-- Left Information Block -->
            <div class="min-w-0 flex-1">
                <!-- Tag / Category Pill -->
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-semibold backdrop-blur-sm mb-2">
                    <span class="flex h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Holy Pilgrimages &amp; Global Travels</span>
                </div>

                <!-- Main Headline -->
                <h2 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-white font-display tracking-tight leading-snug">
                    Umrah, Hajj &amp; International Travel Packages
                </h2>

                <!-- Balanced Subtitle -->
                <p class="mt-1 text-xs sm:text-sm text-sky-100/90 leading-relaxed max-w-xl">
                    Exclusive holy pilgrimages to Makkah &amp; Madinah and curated global vacation tours with visa processing, direct flights, and star hotel accommodation.
                </p>
            </div>

            <!-- Right Action Block (Balanced Buttons) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0 pt-1 md:pt-0">
                <a href="{{ route('travel.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-300 hover:from-amber-300 hover:to-amber-200 text-slate-950 font-bold text-xs sm:text-sm shadow-sm transition-all transform active:scale-95 no-underline text-center">
                    <i data-lucide="plane" class="w-4 h-4 text-slate-950"></i>
                    <span>Explore Packages &amp; Book</span>
                </a>
                <a href="{{ route('travel.tracking') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3.5 sm:px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 font-semibold text-xs sm:text-sm backdrop-blur-sm transition-all no-underline text-center">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-sky-200"></i>
                    <span>Track My Request</span>
                </a>
            </div>

        </div>
    </div>
</div>
