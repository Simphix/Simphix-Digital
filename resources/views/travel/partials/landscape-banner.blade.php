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
    <div class="relative overflow-hidden rounded-2xl shadow-md"
         style="background-color: #07152f; background-image: linear-gradient(90deg, rgba(7, 21, 47, 0.94) 0%, rgba(12, 35, 75, 0.88) 55%, rgba(0, 86, 210, 0.78) 100%), url('{{ $customBannerImage }}'); background-size: cover; background-position: center; border: 1px solid rgba(30, 58, 138, 0.6);">
        
        <!-- Subtle Glow Element -->
        <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
             style="background: radial-gradient(circle, rgba(251, 191, 36, 0.2) 0%, rgba(251, 191, 36, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

        <!-- Banner Content Container: Mobile height halved (~115px), exact original size preserved on large screens (md+) -->
        <div class="relative z-10 w-full py-2.5 px-3 md:p-5 lg:p-6 flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-6">
            
            <!-- Left Information Block -->
            <div class="min-w-0 flex-1">
                <!-- Tag / Category Pill -->
                <div class="inline-flex items-center gap-1.5 md:gap-2 py-0.5 px-2 md:py-1 md:px-3 rounded-full mb-1 md:mb-2"
                     style="background: rgba(245, 158, 11, 0.18); border: 1px solid rgba(245, 158, 11, 0.45);">
                    <span class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full inline-block shrink-0" style="background-color: #fbbf24;"></span>
                    <span class="text-[10px] md:text-[11px] font-bold text-amber-200 tracking-wide uppercase">Holy Pilgrimages &amp; Global Travels</span>
                </div>

                <!-- Main Headline -->
                <h2 class="text-sm md:text-xl lg:text-[1.25rem] font-bold md:font-extrabold text-white leading-tight md:leading-snug mb-1 md:mb-1.5 tracking-tight"
                    style="color: #ffffff !important;">
                    Umrah, Hajj &amp; International Travel Packages
                </h2>

                <!-- Subtitle: 1 line on mobile screen, full original text on large screens -->
                <p class="text-[11px] md:text-[0.8125rem] text-slate-100 line-clamp-1 md:line-clamp-none font-normal max-w-2xl leading-normal md:leading-relaxed"
                   style="color: #f1f5f9 !important; margin: 0;">
                    Exclusive holy pilgrimages to Makkah &amp; Madinah and curated global vacation tours with visa processing, direct flights, and star hotel accommodation.
                </p>
            </div>

            <!-- Right Action Block: Compact side-by-side on mobile, exact original button size on large screen -->
            <div class="flex flex-row items-center gap-2 md:gap-2.5 shrink-0 pt-0.5 md:pt-0">
                <a href="{{ route('travel.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 md:gap-2 py-1.5 px-3 md:py-2.5 md:px-4.5 rounded-xl font-bold text-xs md:text-[13px] whitespace-nowrap shadow-sm transition"
                   style="background-color: #f59e0b; color: #0f172a !important; text-decoration: none;"
                   onmouseover="this.style.backgroundColor='#fbbf24'"
                   onmouseout="this.style.backgroundColor='#f59e0b'">
                    <i data-lucide="plane" class="w-3.5 h-3.5 md:w-4 md:h-4 stroke-[2.5]" style="stroke: #0f172a;"></i>
                    <span style="color: #0f172a !important;">Explore Packages &amp; Book</span>
                </a>
                <a href="{{ route('travel.tracking') }}"
                   class="inline-flex items-center justify-center gap-1.5 md:gap-2 py-1.5 px-2.5 md:py-2.5 md:px-4 rounded-xl font-semibold text-xs md:text-[13px] whitespace-nowrap transition"
                   style="background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); text-decoration: none; backdrop-filter: blur(4px);"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                    <i data-lucide="search" class="w-3.5 h-3.5 md:w-4 md:h-4" style="stroke: #bae6fd;"></i>
                    <span style="color: #ffffff !important;">Track My Request</span>
                </a>
            </div>

        </div>
    </div>
</div>
