<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="vapid-public-key" content="{{ config('services.webpush.vapid.public_key') }}">

        <title>{{ config('app.name', 'SimPhix') }}</title>

        <!-- Favicon -->
        <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">

        <!-- PWA -->
        @include('layouts.partials.pwa-head')

        <!-- Bootstrap 5 GRID ONLY for premium responsiveness without Reboot/Reset overrides -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css" rel="stylesheet" />
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Font Awesome 6 Solid CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Lucide Icons CDN -->
        <script src="https://unpkg.com/lucide@latest"></script>
        
        <!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        @stack('styles')
    </head>
    <body class="font-sans antialiased text-slate-800 h-full">
        <!-- Main Layout Container (Alpine.js State) -->
        <div x-data="{ sidebarOpen: false, sidebarCollapsed: false }"
             class="h-full flex overflow-hidden">
            
            <!-- Sidebar Navigation -->
            @include('layouts.partials.sidebar')

            <!-- Content Area Wrapper -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto lg:h-screen lg:overflow-y-auto">
                <!-- Sticky Header -->
                @include('layouts.partials.header')

                <!-- Main Content Slot -->
                <main class="flex-grow py-8 px-8 sm:py-4 sm:px-4 md:py-8 md:px-10 lg:py-10 lg:px-16 xl:px-24 bg-slate-50/50">
                    <div class="mx-auto max-w-[1800px]">
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <script>
            // Initialize Lucide Icons
            lucide.createIcons();

            // Form submission processing state visual handler
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        // Prevent double submission
                        if (submitBtn.classList.contains('is-processing')) {
                            e.preventDefault();
                            return;
                        }
                        
                        submitBtn.classList.add('is-processing');
                        submitBtn.style.opacity = '0.8';
                        
                        // Look for span and icon to replace
                        const textSpan = submitBtn.querySelector('span');
                        const icon = submitBtn.querySelector('[data-lucide]');
                        
                        if (textSpan) {
                            textSpan.textContent = 'Processing...';
                        } else {
                            submitBtn.textContent = 'Processing...';
                        }
                        
                        if (icon) {
                            icon.setAttribute('data-lucide', 'loader-2');
                            icon.classList.add('animate-spin');
                        } else {
                            // Create icon if not present
                            const newIcon = document.createElement('i');
                            newIcon.setAttribute('data-lucide', 'loader-2');
                            newIcon.className = 'w-4 h-4 animate-spin ml-2 inline-block';
                            submitBtn.appendChild(newIcon);
                        }
                        
                        lucide.createIcons();
                    }
                });
            });
        </script>

        @php
            // A newly-registered Enterprise Partner's own registration form
            // never collects state/lga/address (see EnterprisePartnerController::register())
            // — don't block them behind this modal before they've even had
            // the chance to pay and unlock the rest of the app. Mirrors the
            // same exception already carved out in EnsureKycCompleted.
            $isPendingEnterprisePartner = Auth::check() && Auth::user()->role === 'business'
                && in_array(Auth::user()->status, ['pending_eligibility', 'pending_payment'], true);
        @endphp
        @if(Auth::check() && Auth::user()->hasVerifiedEmail() && !$isPendingEnterprisePartner && (empty(Auth::user()->first_name) || empty(Auth::user()->last_name) || empty(Auth::user()->phone) || empty(Auth::user()->state) || empty(Auth::user()->lga) || empty(Auth::user()->address)))
            @include('pages.dashboard.kyc')
        @endif

        @if (session('welcome'))
            <!-- Welcome Celebratory Modal Overlay -->
            <div x-data="{ welcomeOpen: true }" x-show="welcomeOpen" 
                 class="fixed inset-0 z-[99999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div @click.away="welcomeOpen = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-8 text-center relative">

                    <!-- Icon -->
                    <div class="mx-auto w-14 h-14 bg-vibrant/10 rounded-xl flex items-center justify-center text-vibrant mb-6">
                        <i data-lucide="party-popper" class="w-7 h-7"></i>
                    </div>

                    <!-- Header -->
                    <h2 class="text-2xl font-bold text-slate-900 font-display mb-3">
                        Profile Setup Complete!
                    </h2>

                    <p class="text-sm text-slate-600 leading-relaxed mb-6">
                        {{ session('welcome') }}
                    </p>

                    <!-- Core Value Selling Points Grid -->
                    <div class="grid grid-cols-3 gap-3 mb-8">
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-lg">
                            <div class="text-primary mb-1 flex justify-center">
                                <i data-lucide="zap" class="w-5 h-5"></i>
                            </div>
                            <span class="block text-xs font-semibold text-slate-400 mb-0.5">Speed</span>
                            <span class="block text-xs font-semibold text-slate-700">Instant Delivery</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-lg">
                            <div class="text-primary mb-1 flex justify-center">
                                <i data-lucide="tag" class="w-5 h-5"></i>
                            </div>
                            <span class="block text-xs font-semibold text-slate-400 mb-0.5">Rates</span>
                            <span class="block text-xs font-semibold text-slate-700">Cheapest Plans</span>
                        </div>
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-lg">
                            <div class="text-primary mb-1 flex justify-center">
                                <i data-lucide="shield-check" class="w-5 h-5"></i>
                            </div>
                            <span class="block text-xs font-semibold text-slate-400 mb-0.5">Security</span>
                            <span class="block text-xs font-semibold text-slate-700">Fully Protected</span>
                        </div>
                    </div>

                    <!-- Button Actions -->
                    <button type="button" @click="welcomeOpen = false"
                            class="w-full py-3 px-6 bg-primary hover:bg-[#0049b8] text-white font-semibold text-sm rounded-lg transition font-display">
                        Start Exploring Services
                    </button>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
                // Immediate fallback to render icons if page is already loaded
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            </script>
        @endif

        @if (session('pos_sim_notice'))
            <!-- POS SIM Usage Notice Overlay -->
            <div x-data="{ posSimNoticeOpen: true }" x-show="posSimNoticeOpen"
                 class="fixed inset-0 z-[99999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div @click.away="posSimNoticeOpen = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-sm w-full p-5 text-center relative">
                    <img src="{{ asset('assets/images/pos-sim-notice.jpeg') }}" alt="POS SIM is only for POS" class="w-full rounded-xl mb-5">
                    <button type="button" @click="posSimNoticeOpen = false"
                            class="w-full py-3 px-6 bg-primary hover:bg-[#0049b8] text-white font-semibold text-sm rounded-lg transition font-display">
                        Continue
                    </button>
                </div>
            </div>
        @endif

        @include('layouts.partials.pwa-install-banner')

        <!-- Global "Copied!" snackbar — see resources/js/app.js's copyToClipboard() helper -->
        <div x-data class="fixed bottom-5 right-5 z-50">
            <div x-show="$store.toast.show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-xl flex items-center gap-3 border border-slate-800"
                 x-cloak>
                <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <span class="text-xs font-semibold" x-text="$store.toast.message"></span>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
