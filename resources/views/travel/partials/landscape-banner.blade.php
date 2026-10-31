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

        <!-- Banner Content Container -->
        <div class="relative z-10 w-full p-4 sm:p-5 lg:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 lg:gap-6">
            
            <!-- Left Information Block -->
            <div class="min-w-0 flex-1">
                <!-- Tag / Category Pill -->
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background: rgba(245, 158, 11, 0.18); border: 1px solid rgba(245, 158, 11, 0.45); margin-bottom: 8px;">
                    <span style="width: 7px; height: 7px; border-radius: 9999px; background-color: #fbbf24; display: inline-block;"></span>
                    <span style="font-size: 11px; font-weight: 700; color: #fef08a; letter-spacing: 0.3px;">Holy Pilgrimages &amp; Global Travels</span>
                </div>

                <!-- Main Headline -->
                <h2 style="font-size: 1.25rem; font-weight: 800; color: #ffffff !important; line-height: 1.3; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    Umrah, Hajj &amp; International Travel Packages
                </h2>

                <!-- Balanced Subtitle -->
                <p style="font-size: 0.8125rem; color: #f1f5f9 !important; line-height: 1.5; margin: 0; max-width: 620px; font-weight: 400;">
                    Exclusive holy pilgrimages to Makkah &amp; Madinah and curated global vacation tours with visa processing, direct flights, and star hotel accommodation.
                </p>
            </div>

            <!-- Right Action Block (Balanced Buttons) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0 pt-1 md:pt-0">
                <a href="{{ route('travel.index') }}"
                   style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background-color: #f59e0b; color: #0f172a !important; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.25); white-space: nowrap;"
                   onmouseover="this.style.backgroundColor='#fbbf24'"
                   onmouseout="this.style.backgroundColor='#f59e0b'">
                    <i data-lucide="plane" style="width: 16px; height: 16px; stroke: #0f172a; stroke-width: 2.5;"></i>
                    <span style="color: #0f172a !important;">Explore Packages &amp; Book</span>
                </a>
                <a href="{{ route('travel.tracking') }}"
                   style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); font-size: 13px; font-weight: 600; text-decoration: none; backdrop-filter: blur(4px); white-space: nowrap;"
                   onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                   onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                    <i data-lucide="search" style="width: 15px; height: 15px; stroke: #bae6fd; stroke-width: 2;"></i>
                    <span style="color: #ffffff !important;">Track My Request</span>
                </a>
            </div>

        </div>
    </div>
</div>
