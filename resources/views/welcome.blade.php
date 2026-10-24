<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SimPhix - Empowering Businesses Through Smart Connectivity</title>
    <meta name="description" content="SimPhix - Empowering Businesses Through Smart Connectivity. Purchase bulk SIM cards wholesale, manage allocations, and get massive welcome data bonuses for the public.">
    <meta name="keywords" content="telecom, SIM card, wholesale SIM, sell SIMs, data welcome bonus, mobile network, SimPhix">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">

    <!-- Bootstrap 5 GRID ONLY for premium responsiveness without Reboot/Reset overrides -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css" rel="stylesheet" />

    <!-- CSS Assets -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v=1.1">
</head>
<body>

    <!-- Header Navigation -->
    <header class="header">
        <div class="container nav-wrapper">
            <!-- Brand Logo -->
            <a href="#" class="logo-link">
                <img src="{{ asset('assets/images/logo/logo1.png') }}" alt="SimPhix Logo" class="logo-img">
            </a>

            <!-- Nav Links -->
            <nav class="nav-menu">
                <a href="#features" class="nav-link">Features</a>
                <a href="#services" class="nav-link">Services</a>
                <a href="#pricing" class="nav-link">Pricing</a>
                <a href="#calculator" class="nav-link">Earnings Calc</a>
                <a href="#support" class="nav-link">Support</a>
                <div class="mobile-nav-actions">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn-login">Log In</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </nav>

            <!-- Auth Actions -->
            <div class="nav-actions">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-login">Log In</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                        @endif
                    @endauth
                @endif
            </div>

            <!-- Hamburger Toggle for Mobile -->
            <button class="menu-toggle" aria-label="Toggle Navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-12 col-lg-7 hero-content">
                    <span class="badge badge-primary">Empowering Businesses Through Smart Connectivity</span>
                    <h1 class="hero-title">
                        Connect Your World.<br>
                        <span class="blue-highlight">Supercharge</span> Your <span class="green-highlight">Business</span>.
                    </h1>
                    <p class="hero-description">
                        SimPhix is the ultimate high-speed telecom platform. We empower local agents to build thriving distribution businesses with wholesale rates, while offering the public premium SIM cards loaded with massive data packages and airtime bonuses.
                    </p>
                    <div class="public-alert-banner">
                        <span class="pulse-dot"></span>
                        <span><strong>Public Offer:</strong> Get a SIM & enjoy up to <strong>100GB Free Data</strong> + <strong>₦15,000 Bonus</strong>!</span>
                    </div>
                    <div class="hero-buttons">
                        <a href="#pricing" class="btn btn-primary">Get Your SIM Now</a>
                        <a href="#pricing" class="btn btn-outline">Claim Free Data</a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number">200+</span>
                            <span class="stat-label">Active Users</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number green-stat">50K+</span>
                            <span class="stat-label">Public SIMs Activated</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">99.8%</span>
                            <span class="stat-label">Uptime Connectivity</span>
                        </div>
                    </div>
                </div>
    
                <!-- Interactive Illustration/Mockup -->
                <div class="col-12 col-lg-5 hero-image-wrapper">
                    <div class="hero-bg-glow"></div>
                    <div class="hero-illustration">
                        <!-- Glass Floating Card 1 -->
                        <div class="floating-card floating-card-1">
                            <div class="badge badge-primary" style="margin-bottom: 8px;">Data Welcome Bonus</div>
                            <h4 style="margin-bottom: 4px; font-size: 0.95rem;">100GB Active Data</h4>
                            <p style="font-size: 0.8rem; color: var(--text-secondary);">Claimed successfully</p>
                        </div>
    
                        <!-- Main Simulated Mobile App -->
                        <div class="main-phone-mockup">
                            <div class="mockup-inner">
                                <div class="mockup-header"></div>
                                <div class="mockup-screen-title">SimPhix Dashboard</div>
                                
                                <div class="mockup-sim-details">
                                    <div class="mockup-sim-row">
                                        <span>Your SIM:</span>
                                        <span style="font-weight:600;">08034567890</span>
                                    </div>
                                    <div class="mockup-sim-row">
                                        <span>Welcome Bonus:</span>
                                        <span>₦15,000</span>
                                    </div>
                                    <div class="mockup-sim-row">
                                        <span>Status:</span>
                                        <span class="mockup-sim-status">Active</span>
                                    </div>
                                </div>
    
                                <div class="mockup-chart">
                                    <div style="position: absolute; top: 15px; left: 15px; color:#FFF; font-size: 0.75rem; font-weight:700;">
                                        Monthly Savings: ₦12,500
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <!-- Glass Floating Card 2 -->
                        <div class="floating-card floating-card-2">
                            <div class="badge badge-secondary" style="margin-bottom: 8px;">Instant Cashback</div>
                            <h4 style="margin-bottom: 4px; font-size: 0.95rem;">Up to 10% Off</h4>
                            <p style="font-size: 0.8rem; color: var(--text-secondary);">On every data purchase</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('pages.landing.services')

    @include('pages.landing.price')

    @include('pages.landing.support')

    @include('pages.landing.footer')

    <!-- JS Assets -->
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
