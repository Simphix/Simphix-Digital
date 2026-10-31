<!-- Sliding Banners: 1. Moniepoint POS | 2. Business Loans (Balanced Landscape Size matching Travel & Tours Banner) -->
<div class="mb-4 w-full"
     x-data="{
        currentSlide: 0,
        totalSlides: 2,
        autoSlideTimer: null,
        init() {
            this.startTimer();
        },
        startTimer() {
            this.autoSlideTimer = setInterval(() => {
                this.next();
            }, 6000);
        },
        stopTimer() {
            clearInterval(this.autoSlideTimer);
        },
        next() {
            this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
        },
        prev() {
            this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
        },
        setSlide(index) {
            this.currentSlide = index;
            this.stopTimer();
            this.startTimer();
        }
     }"
     @mouseenter="stopTimer()"
     @mouseleave="startTimer()">

    <div class="relative overflow-hidden rounded-2xl shadow-md border border-slate-200">

        <!-- ========================================== -->
        <!-- SLIDE 1: MONIEPOINT POS TERMINAL REQUEST  -->
        <!-- ========================================== -->
        <div x-show="currentSlide === 0"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 -translate-x-4"
             style="background: linear-gradient(135deg, #001F5C 0%, #002D80 50%, #0056D2 100%); color: #ffffff;"
             class="relative py-2.5 px-3 md:p-5 lg:p-6 min-h-[115px] md:min-h-[160px] flex items-center">

            <!-- Decorative Ambient Accents -->
            <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
                 style="background: radial-gradient(circle, rgba(251, 191, 36, 0.25) 0%, rgba(251, 191, 36, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

            <div class="relative z-10 w-full flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-6">
                
                <!-- Left Content Block -->
                <div class="min-w-0 flex-1 text-left">
                    <!-- Partner Badge with Logo -->
                    <div class="inline-flex items-center gap-1.5 md:gap-2 py-0.5 px-2 md:py-1 md:px-3 rounded-full mb-1 md:mb-2"
                         style="background: rgba(255, 204, 0, 0.15); border: 1px solid rgba(255, 204, 0, 0.4);">
                        <img src="{{ asset('assets/images/moniepoint-logo.png') }}" alt="Moniepoint" class="h-3 md:h-3.5 w-auto object-contain">
                        <span class="text-[10px] md:text-[11px] font-bold text-amber-200 tracking-wide uppercase">Official Moniepoint POS Partner</span>
                    </div>

                    <!-- Title -->
                    <h2 class="text-sm md:text-xl lg:text-[1.25rem] font-bold md:font-extrabold text-white leading-tight md:leading-snug mb-1 md:mb-1.5 tracking-tight"
                        style="color: #ffffff !important;">
                        Request Your Moniepoint POS Terminal Today
                    </h2>

                    <!-- Subtitle (1 line on mobile screen, full text on large screens) -->
                    <p class="text-[11px] md:text-[0.8125rem] text-slate-100 line-clamp-1 md:line-clamp-none font-normal max-w-2xl leading-normal md:leading-relaxed"
                       style="color: #e0e7ff !important; margin: 0;">
                        Get high-speed Moniepoint Smart (Android) or Traditional POS terminals with instant settlement, dual SIM, and nationwide delivery.
                    </p>

                    <!-- Feature Tags (Desktop/Tablet Only) -->
                    <div class="hidden lg:flex items-center gap-2 mt-2">
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #bae6fd; font-weight: 600;">
                            ✓ Instant Wallet Settlement
                        </span>
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #bae6fd; font-weight: 600;">
                            ✓ Smart Android &amp; Traditional
                        </span>
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #bae6fd; font-weight: 600;">
                            ✓ Fast Dispatch
                        </span>
                    </div>
                </div>

                <!-- Right Action Block + Hardware Image -->
                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-0.5 md:pt-0">
                    <!-- Action Buttons -->
                    <div class="flex flex-row items-center gap-2 md:gap-2.5">
                        <a href="{{ route('moniepoint.pos.index') }}"
                           class="inline-flex items-center justify-center gap-1.5 md:gap-2 py-1.5 px-3 md:py-2.5 md:px-4.5 rounded-xl font-bold text-xs md:text-[13px] whitespace-nowrap shadow-sm transition"
                           style="background-color: #ffcc00; color: #001F5C !important; text-decoration: none;"
                           onmouseover="this.style.backgroundColor='#ffe066'"
                           onmouseout="this.style.backgroundColor='#ffcc00'">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5 md:w-4 md:h-4 stroke-[2.5]" style="stroke: #001F5C;"></i>
                            <span style="color: #001F5C !important;">Request Moniepoint POS</span>
                        </a>
                        <a href="{{ route('moniepoint.pos.tracking') }}"
                           class="inline-flex items-center justify-center gap-1.5 md:gap-2 py-1.5 px-2.5 md:py-2.5 md:px-4 rounded-xl font-semibold text-xs md:text-[13px] whitespace-nowrap transition"
                           style="background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); text-decoration: none; backdrop-filter: blur(4px);"
                           onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                           onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                            <i data-lucide="search" class="w-3.5 h-3.5 md:w-4 md:h-4" style="stroke: #bae6fd;"></i>
                            <span style="color: #ffffff !important;">Track POS Request</span>
                        </a>
                    </div>

                    <!-- POS Terminal Graphic (Tablet/Desktop) -->
                    <div class="hidden md:flex shrink-0 items-center justify-center">
                        <div class="relative flex items-center justify-center p-1.5 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 shadow-sm">
                            <img src="{{ asset('assets/images/moniepoint-pos.png') }}"
                                 alt="Moniepoint POS"
                                 class="h-16 lg:h-20 w-auto object-contain filter drop-shadow-md">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- SLIDE 2: BUSINESS LOAN REQUEST BANNER     -->
        <!-- ========================================== -->
        <div x-show="currentSlide === 1"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 -translate-x-4"
             style="background: linear-gradient(135deg, #063d2e 0%, #0b4a3a 50%, #0d2f5a 100%); color: #ffffff;"
             class="relative py-2.5 px-3 md:p-5 lg:p-6 min-h-[115px] md:min-h-[160px] flex items-center"
             style="display: none;">

            <!-- Decorative Ambient Accents -->
            <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
                 style="background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, rgba(52, 211, 153, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

            <div class="relative z-10 w-full flex flex-col md:flex-row md:items-center justify-between gap-2.5 md:gap-6">
                
                <!-- Left Content Block -->
                <div class="min-w-0 flex-1 text-left">
                    <!-- Prominent Disclaimer Badge -->
                    <div class="inline-flex items-center gap-1.5 md:gap-2 py-0.5 px-2 md:py-1 md:px-3 rounded-full mb-1 md:mb-2"
                         style="background: rgba(245, 158, 11, 0.22); border: 1px solid rgba(245, 158, 11, 0.55);">
                        <i data-lucide="alert-triangle" class="w-3 md:w-3.5 h-3 md:h-3.5 stroke-[2.5]" style="stroke: #fbbf24;"></i>
                        <span class="text-[10px] md:text-[11px] font-bold text-amber-200 tracking-wide uppercase">
                            Disclaimer: Moniepoint Business Owners Only
                        </span>
                    </div>

                    <!-- Title -->
                    <h2 class="text-sm md:text-xl lg:text-[1.25rem] font-bold md:font-extrabold text-white leading-tight md:leading-snug mb-1 md:mb-1.5 tracking-tight"
                        style="color: #ffffff !important;">
                        Instant Working Capital &amp; Business Loans
                    </h2>

                    <!-- Subtitle (1 line on mobile screen, full text on large screens) -->
                    <p class="text-[11px] md:text-[0.8125rem] text-slate-100 line-clamp-1 md:line-clamp-none font-normal max-w-2xl leading-normal md:leading-relaxed"
                       style="color: #d1fae5 !important; margin: 0;">
                        Expand your retail operations or agent liquidity with fast working capital disbursed directly into your active Moniepoint business wallet.
                    </p>

                    <!-- Feature Tags (Desktop/Tablet Only) -->
                    <div class="hidden lg:flex items-center gap-2 mt-2">
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                            ✓ 24-48h Quick Disbursement
                        </span>
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                            ✓ Up to &#8358;50,000,000
                        </span>
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                            ✓ Flexible Repayment
                        </span>
                    </div>
                </div>

                <!-- Right Action Block + Badge Graphic -->
                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-0.5 md:pt-0">
                    <!-- Action Buttons -->
                    <div class="flex flex-row items-center gap-2 md:gap-2.5">
                        <a href="{{ route('loans.index') }}"
                           class="inline-flex items-center justify-center gap-1.5 md:gap-2 py-1.5 px-3 md:py-2.5 md:px-4.5 rounded-xl font-bold text-xs md:text-[13px] whitespace-nowrap shadow-sm transition"
                           style="background-color: #10b981; color: #022c22 !important; text-decoration: none;"
                           onmouseover="this.style.backgroundColor='#34d399'"
                           onmouseout="this.style.backgroundColor='#10b981'">
                            <i data-lucide="banknote" class="w-3.5 h-3.5 md:w-4 md:h-4 stroke-[2.5]" style="stroke: #022c22;"></i>
                            <span style="color: #022c22 !important;">Apply for Loan</span>
                        </a>
                        <a href="{{ route('loans.tracking') }}"
                           class="inline-flex items-center justify-center gap-1.5 md:gap-2 py-1.5 px-2.5 md:py-2.5 md:px-4 rounded-xl font-semibold text-xs md:text-[13px] whitespace-nowrap transition"
                           style="background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); text-decoration: none; backdrop-filter: blur(4px);"
                           onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                           onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                            <i data-lucide="search" class="w-3.5 h-3.5 md:w-4 md:h-4" style="stroke: #a7f3d0;"></i>
                            <span style="color: #ffffff !important;">Track Loan</span>
                        </a>
                    </div>

                    <!-- Business Credit Graphic (Tablet/Desktop) -->
                    <div class="hidden md:flex shrink-0 items-center justify-center">
                        <div class="p-2 rounded-xl bg-white/10 backdrop-blur-sm border border-emerald-400/30 text-center shadow-sm">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-300 flex items-center justify-center mx-auto mb-1 border border-emerald-400/40">
                                <i data-lucide="trending-up" class="w-4 h-4"></i>
                            </div>
                            <span class="block text-[9px] font-bold text-white uppercase tracking-wider">Moniepoint Credit</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- SLIDER CONTROLS: Dots Navigation          -->
        <!-- ========================================== -->
        <div class="absolute bottom-2 right-3 z-20 flex items-center gap-1.5 bg-black/25 px-2.5 py-1 rounded-full backdrop-blur-xs">
            <button type="button" @click="prev()" aria-label="Previous Banner"
                    class="text-white/70 hover:text-white transition p-0.5">
                <i data-lucide="chevron-left" class="w-3 h-3"></i>
            </button>

            <!-- Dot Indicators -->
            <button type="button" @click="setSlide(0)" aria-label="Go to Moniepoint POS Banner"
                    :class="currentSlide === 0 ? 'w-4 bg-amber-400' : 'w-1.5 bg-white/50 hover:bg-white/80'"
                    class="h-1.5 rounded-full transition-all duration-200"></button>
            <button type="button" @click="setSlide(1)" aria-label="Go to Business Loan Banner"
                    :class="currentSlide === 1 ? 'w-4 bg-emerald-400' : 'w-1.5 bg-white/50 hover:bg-white/80'"
                    class="h-1.5 rounded-full transition-all duration-200"></button>

            <button type="button" @click="next()" aria-label="Next Banner"
                    class="text-white/70 hover:text-white transition p-0.5">
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </button>
        </div>

    </div>
</div>
