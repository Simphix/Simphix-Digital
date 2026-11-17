@php
    /**
     * International Passport Balanced Landscape Banner
     * Matches exact size and responsive layout of the Travel & Tours banner.
     */
@endphp

<!-- Balanced Landscape Ad Banner for International Passport -->
<div class="mb-4 w-full">
    <style>
        .passport-banner-graphic-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-shrink: 0;
            width: 220px;
            height: 120px;
            pointer-events: none;
        }
        .passport-banner-badge-box {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 140px;
            height: 110px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.4) 100%);
            border: 1px solid rgba(52, 211, 153, 0.5);
            backdrop-filter: blur(8px);
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.35);
        }
        @media (min-width: 1024px) {
            .passport-banner-graphic-wrapper {
                width: 260px;
                height: 130px;
            }
            .passport-banner-badge-box {
                width: 160px;
                height: 118px;
            }
        }
        @media (min-width: 1280px) {
            .passport-banner-graphic-wrapper {
                width: 300px;
                height: 140px;
            }
            .passport-banner-badge-box {
                width: 180px;
                height: 122px;
            }
        }
        @media (max-width: 767.98px) {
            .passport-banner-content {
                padding: 10px 14px !important;
                gap: 8px !important;
            }
            .passport-banner-pill {
                padding: 2px 8px !important;
                margin-bottom: 4px !important;
                font-size: 10px !important;
            }
            .passport-banner-pill span {
                font-size: 10px !important;
            }
            .passport-banner-title {
                font-size: 0.875rem !important;
                line-height: 1.25 !important;
                margin-bottom: 4px !important;
            }
            .passport-banner-desc {
                font-size: 0.6875rem !important;
                line-height: 1.35 !important;
                display: -webkit-box !important;
                -webkit-line-clamp: 1 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
                margin-bottom: 6px !important;
            }
            .passport-banner-actions {
                flex-direction: row !important;
                align-items: center !important;
                gap: 8px !important;
                padding-top: 2px !important;
            }
            .passport-banner-btn {
                padding: 6px 12px !important;
                font-size: 11px !important;
            }
            .passport-banner-btn i {
                width: 14px !important;
                height: 14px !important;
            }
            .passport-banner-graphic-wrapper {
                display: none !important;
            }
        }
    </style>

    <div class="relative overflow-hidden rounded-2xl shadow-md"
         style="background-color: #06281e; background-image: linear-gradient(90deg, rgba(6, 40, 30, 0.96) 0%, rgba(9, 64, 48, 0.92) 55%, rgba(13, 110, 82, 0.85) 100%); background-size: cover; background-position: center; border: 1px solid rgba(16, 185, 129, 0.55);">
        
        <!-- Subtle Emerald/Gold Glow Element -->
        <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
             style="background: radial-gradient(circle, rgba(52, 211, 153, 0.35) 0%, rgba(5, 150, 105, 0) 70%); filter: blur(24px);" aria-hidden="true"></div>

        <!-- Banner Content Container -->
        <div class="passport-banner-content relative z-10 w-full p-4 sm:p-5 lg:p-6 flex flex-row items-center justify-between gap-4 lg:gap-6">
            
            <!-- Left Information Block -->
            <div class="min-w-0 flex-1">
                <!-- Tag / Category Pill -->
                <div class="passport-banner-pill"
                     style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background: rgba(16, 185, 129, 0.22); border: 1px solid rgba(52, 211, 153, 0.5); margin-bottom: 8px;">
                    <span style="width: 7px; height: 7px; border-radius: 9999px; background-color: #34d399; display: inline-block;"></span>
                    <span style="font-size: 11px; font-weight: 700; color: #a7f3d0; letter-spacing: 0.3px;">Immigration Services Desk &bull; Standard &amp; Fast-Track</span>
                </div>

                <!-- Main Headline -->
                <h2 class="passport-banner-title"
                    style="font-size: 1.25rem; font-weight: 800; color: #ffffff !important; line-height: 1.3; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    International Passport Registration (New &amp; Renewal)
                </h2>

                <!-- Balanced Subtitle -->
                <p class="passport-banner-desc"
                   style="font-size: 0.8125rem; color: #e2e8f0 !important; line-height: 1.5; margin: 0 0 14px 0; max-width: 580px; font-weight: 400;">
                    Complete hassle-free application for fresh passports and renewal endorsements. Upload required e-copies, get verified, track progress, and download official A4 dossiers.
                </p>

                <!-- Action Buttons -->
                <div class="passport-banner-actions flex flex-row items-center gap-2.5 pt-0.5">
                    <a href="{{ route('passport.create') }}"
                       class="passport-banner-btn"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background-color: #10b981; color: #022c22 !important; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.25); white-space: nowrap;"
                       onmouseover="this.style.backgroundColor='#34d399'"
                       onmouseout="this.style.backgroundColor='#10b981'">
                        <i data-lucide="book-open" style="width: 16px; height: 16px; stroke: #022c22; stroke-width: 2.5;"></i>
                        <span style="color: #022c22 !important;">Apply for Passport</span>
                    </a>
                    <a href="{{ route('passport.tracking') }}"
                       class="passport-banner-btn"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 16px; border-radius: 12px; background: rgba(255, 255, 255, 0.12); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.3); font-size: 13px; font-weight: 600; text-decoration: none; backdrop-filter: blur(4px); white-space: nowrap;"
                       onmouseover="this.style.background='rgba(255, 255, 255, 0.22)'"
                       onmouseout="this.style.background='rgba(255, 255, 255, 0.12)'">
                        <i data-lucide="search" style="width: 15px; height: 15px; stroke: #6ee7b7; stroke-width: 2;"></i>
                        <span style="color: #ffffff !important;">Track Application</span>
                    </a>
                </div>
            </div>

            <!-- Far Right Graphic: Passport Emblem Box -->
            <div class="passport-banner-graphic-wrapper hidden md:flex">
                <div class="passport-banner-badge-box">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(52, 211, 153, 0.25); display: flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                        <i data-lucide="globe-2" style="width: 26px; height: 26px; stroke: #34d399; stroke-width: 2;"></i>
                    </div>
                    <span style="font-size: 11px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">E-PASSPORT</span>
                    <span style="font-size: 9px; font-weight: 600; color: #a7f3d0; text-transform: uppercase;">Standard / Express</span>
                </div>
            </div>

        </div>
    </div>
</div>
