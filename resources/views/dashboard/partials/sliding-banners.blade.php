<!-- Sliding Banners: 1. Moniepoint POS | 2. Business Loans (Below Quick Services) -->
<div class="mb-5 w-full"
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
             class="relative p-5 sm:p-6 lg:p-7 min-h-[170px] flex items-center">

            <!-- Decorative Ambient Accents -->
            <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
                 style="background: radial-gradient(circle, rgba(251, 191, 36, 0.25) 0%, rgba(251, 191, 36, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

            <div class="relative z-10 w-full flex flex-col md:flex-row items-center justify-between gap-5">
                
                <!-- Left Content Block -->
                <div class="min-w-0 flex-1 text-left">
                    <!-- Partner Badge with Logo -->
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background: rgba(255, 204, 0, 0.15); border: 1px solid rgba(255, 204, 0, 0.4); margin-bottom: 8px;">
                        <img src="{{ asset('assets/images/moniepoint-logo.png') }}" alt="Moniepoint" style="height: 14px; width: auto; object-fit: contain;">
                        <span style="font-size: 11px; font-weight: 800; color: #fef08a; letter-spacing: 0.3px; text-transform: uppercase;">Official Moniepoint POS Partner</span>
                    </div>

                    <!-- Title -->
                    <h2 style="font-size: 1.35rem; font-weight: 800; color: #ffffff !important; line-height: 1.25; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                        Request Your Moniepoint POS Terminal Today
                    </h2>

                    <!-- Subtitle -->
                    <p style="font-size: 0.8125rem; color: #e0e7ff !important; line-height: 1.45; margin: 0 0 12px 0; max-width: 580px;">
                        Get high-speed Moniepoint Smart (Android) or Traditional POS terminals for your business. Enjoy instant card settlement, 99.9% uptime, dual SIM, and long battery life.
                    </p>

                    <!-- Feature Tags -->
                    <div class="hidden sm:flex items-center gap-2 mb-3">
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
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('moniepoint.pos.index') }}"
                           style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background-color: #ffcc00; color: #001F5C !important; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.2); white-space: nowrap;"
                           onmouseover="this.style.backgroundColor='#ffe066'"
                           onmouseout="this.style.backgroundColor='#ffcc00'">
                            <i data-lucide="smartphone" style="width: 16px; height: 16px; stroke: #001F5C; stroke-width: 2.5;"></i>
                            <span style="color: #001F5C !important;">Request Moniepoint POS</span>
                        </a>
                        <a href="{{ route('moniepoint.pos.tracking') }}"
                           style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 15px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); font-size: 13px; font-weight: 600; text-decoration: none; backdrop-filter: blur(4px); white-space: nowrap;"
                           onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                           onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                            <i data-lucide="search" style="width: 15px; height: 15px; stroke: #bae6fd; stroke-width: 2;"></i>
                            <span style="color: #ffffff !important;">Track POS Request</span>
                        </a>
                    </div>
                </div>

                <!-- Right Side: Official POS Hardware Image -->
                <div class="shrink-0 flex items-center justify-center pt-2 md:pt-0">
                    <div class="relative flex items-center justify-center p-2 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 shadow-lg">
                        <img src="{{ asset('assets/images/moniepoint-pos.png') }}"
                             alt="Moniepoint POS Terminal"
                             class="h-28 sm:h-36 lg:h-40 w-auto object-contain filter drop-shadow-md transform hover:scale-105 transition-transform duration-300">
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
             class="relative p-5 sm:p-6 lg:p-7 min-h-[170px] flex items-center"
             style="display: none;">

            <!-- Decorative Ambient Accents -->
            <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full"
                 style="background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, rgba(52, 211, 153, 0) 70%); filter: blur(20px);" aria-hidden="true"></div>

            <div class="relative z-10 w-full flex flex-col md:flex-row items-center justify-between gap-5">
                
                <!-- Left Content Block -->
                <div class="min-w-0 flex-1 text-left">
                    <!-- Prominent Disclaimer Badge -->
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background: rgba(245, 158, 11, 0.22); border: 1px solid rgba(245, 158, 11, 0.55); margin-bottom: 8px;">
                        <i data-lucide="alert-triangle" style="width: 14px; height: 14px; stroke: #fbbf24; stroke-width: 2.5;"></i>
                        <span style="font-size: 11px; font-weight: 800; color: #fef08a; letter-spacing: 0.3px; text-transform: uppercase;">
                            Disclaimer: Moniepoint Business Owners Only
                        </span>
                    </div>

                    <!-- Title -->
                    <h2 style="font-size: 1.35rem; font-weight: 800; color: #ffffff !important; line-height: 1.25; margin: 0 0 6px 0; letter-spacing: -0.02em;">
                        Instant Working Capital &amp; Business Loans
                    </h2>

                    <!-- Subtitle with Disclaimer emphasis -->
                    <p style="font-size: 0.8125rem; color: #d1fae5 !important; line-height: 1.45; margin: 0 0 12px 0; max-width: 580px;">
                        Expand your retail operations or agent liquidity. Fast, low-interest working capital loans disbursed directly into your active Moniepoint business wallet.
                    </p>

                    <!-- Feature Tags -->
                    <div class="hidden sm:flex items-center gap-2 mb-3">
                        <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                            ✓ 24-48h Quick Disbursement
                        </span>
                        <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                            ✓ Up to ₦5,000,000+
                        </span>
                        <span style="font-size: 11px; padding: 2px 8px; border-radius: 6px; background: rgba(255, 255, 255, 0.12); color: #a7f3d0; font-weight: 600;">
                            ✓ Flexible Repayment
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('loans.index') }}"
                           style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: 12px; background-color: #10b981; color: #022c22 !important; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.2); white-space: nowrap;"
                           onmouseover="this.style.backgroundColor='#34d399'"
                           onmouseout="this.style.backgroundColor='#10b981'">
                            <i data-lucide="banknote" style="width: 16px; height: 16px; stroke: #022c22; stroke-width: 2.5;"></i>
                            <span style="color: #022c22 !important;">Apply for Business Loan</span>
                        </a>
                        <a href="{{ route('loans.tracking') }}"
                           style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 15px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.35); font-size: 13px; font-weight: 600; text-decoration: none; backdrop-filter: blur(4px); white-space: nowrap;"
                           onmouseover="this.style.background='rgba(255, 255, 255, 0.25)'"
                           onmouseout="this.style.background='rgba(255, 255, 255, 0.15)'">
                            <i data-lucide="search" style="width: 15px; height: 15px; stroke: #a7f3d0; stroke-width: 2;"></i>
                            <span style="color: #ffffff !important;">Track Loan Status</span>
                        </a>
                    </div>
                </div>

                <!-- Right Side: Loan / Growth Illustration Badge -->
                <div class="shrink-0 flex items-center justify-center pt-2 md:pt-0">
                    <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-emerald-400/30 text-center shadow-lg">
                        <div class="w-14 h-14 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center mx-auto mb-2 border border-emerald-400/40">
                            <i data-lucide="trending-up" class="w-7 h-7"></i>
                        </div>
                        <span class="block text-xs font-bold text-white uppercase tracking-wider">Business Credit</span>
                        <span class="block text-[10px] text-emerald-300 font-medium">Moniepoint Verified</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Navigation Chevrons (Previous / Next) -->
        <button type="button" @click="prev()"
                class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-slate-900/40 hover:bg-slate-900/70 text-white flex items-center justify-center backdrop-blur-sm transition z-20"
                aria-label="Previous Slide">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </button>
        <button type="button" @click="next()"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-slate-900/40 hover:bg-slate-900/70 text-white flex items-center justify-center backdrop-blur-sm transition z-20"
                aria-label="Next Slide">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>

        <!-- Slide Indicators / Switcher Tabs (Bottom Center) -->
        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center gap-1.5 z-20 bg-slate-950/50 backdrop-blur-md py-1 px-2.5 rounded-full border border-white/15">
            <button type="button" @click="setSlide(0)"
                    :class="currentSlide === 0 ? 'bg-amber-400 text-slate-950 font-bold px-3 py-0.5' : 'bg-white/20 text-white px-2 py-0.5'"
                    class="rounded-full text-[10px] transition-all flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full" :class="currentSlide === 0 ? 'bg-slate-950' : 'bg-white'"></span>
                <span>Moniepoint POS</span>
            </button>
            <button type="button" @click="setSlide(1)"
                    :class="currentSlide === 1 ? 'bg-emerald-400 text-slate-950 font-bold px-3 py-0.5' : 'bg-white/20 text-white px-2 py-0.5'"
                    class="rounded-full text-[10px] transition-all flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full" :class="currentSlide === 1 ? 'bg-slate-950' : 'bg-white'"></span>
                <span>Business Loans</span>
            </button>
        </div>

    </div>
</div>
