<!-- Moniepoint POS & Business Loans Banners (Decoupled List) -->
<div class="mb-4 w-full flex flex-col gap-4">

    <style>
        @media (max-width: 767.98px) {
            .slide-banner-card {
                padding: 10px 14px !important;
                min-height: auto !important;
            }
            .slide-banner-row {
                gap: 8px !important;
            }
            .slide-banner-pill {
                padding: 2px 8px !important;
                margin-bottom: 4px !important;
                font-size: 10px !important;
            }
            .slide-banner-pill span {
                font-size: 10px !important;
            }
            .slide-banner-title {
                font-size: 0.875rem !important;
                line-height: 1.25 !important;
                margin-bottom: 4px !important;
            }
            .slide-banner-desc {
                font-size: 0.6875rem !important;
                line-height: 1.35 !important;
                display: -webkit-box !important;
                -webkit-line-clamp: 1 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
                margin-bottom: 6px !important;
            }
            .slide-banner-actions {
                flex-direction: row !important;
                align-items: center !important;
                gap: 8px !important;
                padding-top: 2px !important;
            }
            .slide-banner-btn {
                padding: 6px 12px !important;
                font-size: 11px !important;
            }
            .slide-banner-btn i {
                width: 14px !important;
                height: 14px !important;
            }
            .slide-banner-graphic {
                display: none !important;
            }
            .slide-feature-tags {
                display: none !important;
            }
        }
    </style>

    <!-- ========================================== -->
    <!-- 1. MONIEPOINT POS TERMINAL REQUEST BANNER  -->
    <!-- ========================================== -->
    <div class="slide-banner-card relative w-full rounded-2xl shadow-md border border-slate-200 overflow-hidden p-4 sm:p-5 lg:p-6 min-h-[160px] flex items-center"
         style="background: linear-gradient(135deg, #001233 0%, #002875 50%, #0056D2 100%); color: #ffffff;">

        <!-- Decorative Ambient Accents -->
        <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
             style="background: radial-gradient(circle, rgba(0, 163, 255, 0.28) 0%, rgba(0, 86, 210, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

        <div class="slide-banner-row relative z-10 w-full flex flex-col md:flex-row md:items-center justify-between gap-4 lg:gap-6">
            
            <!-- Left Content Block -->
            <div class="min-w-0 flex-1 text-left">
                <!-- Partner Badge -->
                <div class="slide-banner-pill"
                     style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background: rgba(0, 102, 246, 0.25); border: 1px solid rgba(0, 163, 255, 0.45); margin-bottom: 8px;">
                    <i data-lucide="smartphone" style="width: 14px; height: 14px; stroke: #38bdf8;"></i>
                    <span style="font-size: 11px; font-weight: 700; color: #bae6fd; letter-spacing: 0.3px; text-transform: uppercase;">POS Terminal Hardware Desk</span>
                </div>

                <!-- Title -->
                <h2 class="slide-banner-title"
                    style="font-size: 1.25rem; font-weight: 800; color: #ffffff !important; line-height: 1.3; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    Request Your POS Terminal Today
                </h2>

                <!-- Subtitle -->
                <p class="slide-banner-desc"
                    style="font-size: 0.8125rem; color: #e0e7ff !important; line-height: 1.5; margin: 0 0 10px 0; max-width: 580px; font-weight: 400;">
                    Get high-speed Smart (Android) or Traditional POS terminals for your business. Enjoy instant card settlement, 99.9% uptime, dual SIM, and long battery life.
                </p>

                <!-- Feature Tags (Desktop/Tablet) -->
                <div class="slide-feature-tags hidden sm:flex items-center gap-2 mb-3">
                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #bae6fd; font-weight: 600;">
                        ✓ Instant Wallet Settlement
                    </span>
                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #bae6fd; font-weight: 600;">
                        ✓ Android &amp; Traditional
                    </span>
                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #bae6fd; font-weight: 600;">
                        ✓ Nationwide Delivery
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="slide-banner-actions flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                    <a href="{{ route('moniepoint.pos.index') }}"
                       class="slide-banner-btn"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background-color: #ffcc00; color: #001F5C !important; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.2); white-space: nowrap;"
                       onmouseover="this.style.backgroundColor='#ffe066'"
                       onmouseout="this.style.backgroundColor='#ffcc00'">
                        <i data-lucide="smartphone" style="width: 16px; height: 16px; stroke: #001F5C; stroke-width: 2.5;"></i>
                        <span style="color: #001F5C !important;">Request POS Terminal</span>
                    </a>
                    <a href="{{ route('moniepoint.pos.tracking') }}"
                       class="slide-banner-btn"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 15px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); font-size: 13px; font-weight: 600; text-decoration: none; backdrop-filter: blur(4px); white-space: nowrap;"
                       onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                       onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                        <i data-lucide="search" style="width: 15px; height: 15px; stroke: #bae6fd; stroke-width: 2;"></i>
                        <span style="color: #ffffff !important;">Track POS Request</span>
                    </a>
                </div>
            </div>

            <!-- Right Side: Official POS Hardware Image (Desktop/Tablet) -->
            <div class="slide-banner-graphic shrink-0 hidden md:flex items-center justify-center pt-2 md:pt-0">
                <div class="relative flex items-center justify-center p-3 rounded-2xl bg-white/10 backdrop-blur-sm border border-sky-400/30 shadow-xl">
                    <img src="{{ asset('assets/images/moniepoint-traditional-pos.png') }}"
                         alt="POS Terminal"
                         class="h-28 lg:h-36 w-auto object-contain filter drop-shadow-lg transform hover:scale-105 transition-transform duration-300">
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. BUSINESS LOAN REQUEST BANNER (BELOW POS)-->
    <!-- ========================================== -->
    <div class="slide-banner-card relative w-full rounded-2xl shadow-md border border-slate-200 overflow-hidden p-4 sm:p-5 lg:p-6 min-h-[160px] flex items-center"
         style="background: linear-gradient(135deg, #063d2e 0%, #0b4a3a 50%, #0d2f5a 100%); color: #ffffff;">

        <!-- Decorative Ambient Accents -->
        <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
             style="background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, rgba(52, 211, 153, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

        <div class="slide-banner-row relative z-10 w-full flex flex-col md:flex-row md:items-center justify-between gap-4 lg:gap-6">
            
            <!-- Left Content Block -->
            <div class="min-w-0 flex-1 text-left">
                <!-- Prominent Disclaimer Badge -->
                <div class="slide-banner-pill"
                     style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background: rgba(245, 158, 11, 0.22); border: 1px solid rgba(245, 158, 11, 0.55); margin-bottom: 8px;">
                    <i data-lucide="alert-triangle" style="width: 14px; height: 14px; stroke: #fbbf24; stroke-width: 2.5;"></i>
                    <span style="font-size: 11px; font-weight: 700; color: #fef08a; letter-spacing: 0.3px; text-transform: uppercase;">
                        Disclaimer: Moniepoint Business Owners Only
                    </span>
                </div>

                <!-- Title -->
                <h2 class="slide-banner-title"
                    style="font-size: 1.25rem; font-weight: 800; color: #ffffff !important; line-height: 1.3; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                    Instant Working Capital &amp; Business Loans
                </h2>

                <!-- Subtitle -->
                <p class="slide-banner-desc"
                   style="font-size: 0.8125rem; color: #d1fae5 !important; line-height: 1.5; margin: 0 0 10px 0; max-width: 580px; font-weight: 400;">
                    Expand your retail operations or agent liquidity. Fast, low-interest working capital loans disbursed directly into your active Moniepoint business wallet.
                </p>

                <!-- Feature Tags -->
                <div class="slide-feature-tags hidden sm:flex items-center gap-2 mb-3">
                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                        ✓ 24-48h Quick Disbursement
                    </span>
                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                        ✓ Up to &#8358;50,000,000
                    </span>
                    <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                        ✓ Flexible Repayment
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="slide-banner-actions flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                    <a href="{{ route('loans.create') }}"
                       class="slide-banner-btn"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background-color: #10b981; color: #022c22 !important; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.2); white-space: nowrap;"
                       onmouseover="this.style.backgroundColor='#34d399'"
                       onmouseout="this.style.backgroundColor='#10b981'">
                        <i data-lucide="banknote" style="width: 16px; height: 16px; stroke: #022c22; stroke-width: 2.5;"></i>
                        <span style="color: #022c22 !important;">Apply for Business Loan</span>
                    </a>
                    <a href="{{ route('loans.tracking') }}"
                       class="slide-banner-btn"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 15px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); font-size: 13px; font-weight: 600; text-decoration: none; backdrop-filter: blur(4px); white-space: nowrap;"
                       onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                       onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                        <i data-lucide="search" style="width: 15px; height: 15px; stroke: #a7f3d0; stroke-width: 2;"></i>
                        <span style="color: #ffffff !important;">Track Loan Status</span>
                    </a>
                </div>
            </div>

            <!-- Right Side: Business Loan Graphic (Desktop/Tablet) -->
            <div class="slide-banner-graphic shrink-0 hidden md:flex items-center justify-center pt-2 md:pt-0">
                <div class="relative flex items-center justify-center p-3 rounded-2xl bg-white/10 backdrop-blur-sm border border-emerald-400/30 shadow-xl">
                    <img src="{{ asset('assets/images/business-loan.png') }}"
                         alt="Business Loan"
                         class="h-28 lg:h-36 w-auto object-contain filter drop-shadow-lg transform hover:scale-105 transition-transform duration-300">
                </div>
            </div>

        </div>
    </div>

</div>
