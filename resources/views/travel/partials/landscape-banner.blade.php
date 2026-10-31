@php
    /**
     * Travel & Tours Compact Ad-Like Banner
     * 
     * To set or change your background image:
     * 1. Place your image at: public/assets/images/travel-banner-bg.jpg (or .png / .webp)
     * 2. Or change the $customBannerImage variable below to any local or remote image URL.
     */
    $customBannerImage = asset('assets/images/travel-banner-bg.jpg');
    $hasLocalImage = file_exists(public_path('assets/images/travel-banner-bg.jpg'));
@endphp

<!-- Compact Ad-Style Landscape Banner for Travel & Tours -->
<div class="mb-4 w-full">
    <div class="relative overflow-hidden rounded-xl border border-sky-800/40 shadow-sm bg-slate-950 text-white min-h-[72px] sm:min-h-[80px] flex items-center"
         @if($hasLocalImage) style="background-image: url('{{ $customBannerImage }}'); background-size: cover; background-position: center;" @endif>
        
        <!-- Background Image layer (falls back to rich gradient if no file is present yet) -->
        <div class="absolute inset-0 bg-cover bg-center pointer-events-none"
             style="background-image: url('{{ $customBannerImage }}');"></div>

        <!-- Dark Gradient Overlay: Keeps text and buttons 100% visible & high-contrast over any background photo -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-[#0056D2]/80 pointer-events-none" aria-hidden="true"></div>

        <!-- Subtle Light Ambient Accent -->
        <div class="pointer-events-none absolute -top-12 -right-12 h-36 w-36 rounded-full bg-amber-400/15 blur-2xl" aria-hidden="true"></div>

        <!-- Content Strip -->
        <div class="relative z-10 w-full px-4 sm:px-6 py-2.5 sm:py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Left Info Area -->
            <div class="min-w-0 flex-1 flex flex-col justify-center">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-400 text-slate-950 text-[10px] font-black uppercase tracking-wider shadow-xs shrink-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-950 animate-pulse"></span>
                        Promo
                    </span>
                    <span class="text-[11px] font-semibold text-amber-300 truncate">
                        Holy Pilgrimages &amp; Global Travels
                    </span>
                </div>

                <div class="mt-1 flex items-baseline gap-2 flex-wrap">
                    <h2 class="text-sm sm:text-base font-extrabold text-white font-display tracking-tight leading-tight">
                        Umrah, Hajj &amp; International Travel Packages
                    </h2>
                    <span class="hidden md:inline text-xs text-sky-200/90 font-medium">
                        &bull; Flights, Visas &amp; Hotels Included
                    </span>
                </div>
            </div>

            <!-- Right Action Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('travel.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow-xs transition-all transform active:scale-95 no-underline shrink-0">
                    <i data-lucide="plane" class="w-3.5 h-3.5 text-slate-950"></i>
                    <span>Explore &amp; Book</span>
                </a>
                <a href="{{ route('travel.tracking') }}"
                   class="inline-flex items-center justify-center px-2.5 sm:px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-semibold backdrop-blur-sm transition-all no-underline shrink-0"
                   title="Track My Request">
                    <i data-lucide="search" class="w-3.5 h-3.5 sm:mr-1 text-sky-200"></i>
                    <span class="hidden sm:inline">Track</span>
                </a>
            </div>

        </div>
    </div>
</div>
