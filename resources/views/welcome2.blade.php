<!DOCTYPE html>
<html lang="en" class="landing-theme scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0056D2">

    <title>SimPhix — Empowering Businesses Through Smart Connectivity</title>
    <meta name="description" content="SimPhix is a wholesale telecom platform: buy premium SIM cards loaded with free data and airtime, or become a distribution agent with wholesale rates, instant activation and real-time earnings tracking.">
    <meta name="keywords" content="telecom, SIM card, wholesale SIM, sell SIMs, data welcome bonus, mobile network, SimPhix, SimPhix, Nigeria SIM, agent SIM distribution">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SimPhix">
    <meta property="og:title" content="SimPhix — Empowering Businesses Through Smart Connectivity">
    <meta property="og:description" content="Premium SIM cards with massive data bonuses for the public, and wholesale distribution tools for agents.">
    <meta property="og:image" content="{{ asset('assets/images/logo/logo1.png') }}">
    <meta property="og:url" content="{{ url('/') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SimPhix — Empowering Businesses Through Smart Connectivity">
    <meta name="twitter:description" content="Premium SIM cards with massive data bonuses for the public, and wholesale distribution tools for agents.">
    <meta name="twitter:image" content="{{ asset('assets/images/logo/logo1.png') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/icons/icon-192.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "SimPhix",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('assets/images/logo/logo1.png') }}",
        "email": "Support@smartsimsub.com",
        "contactPoint": {
            "@@type": "ContactPoint",
            "email": "Support@smartsimsub.com",
            "contactType": "customer support"
        },
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "Behind Oti Carpet, Opp BMT Garden, Wuse 2",
            "addressLocality": "Abuja",
            "addressRegion": "FCT",
            "addressCountry": "NG"
        }
    }
    </script>

    <!-- Set the color theme before first paint to avoid a light/dark flash.
         With no saved preference, default to dark during Lagos night hours (7pm-6am WAT). -->
    <script>
        (function () {
            var stored = localStorage.getItem('simphix-theme');
            var dark;
            if (stored) {
                dark = stored === 'dark';
            } else {
                var isLagosNight;
                try {
                    var lagosHour = parseInt(new Intl.DateTimeFormat('en-US', {
                        timeZone: 'Africa/Lagos',
                        hour: 'numeric',
                        hour12: false
                    }).format(new Date()), 10);
                    isLagosNight = lagosHour >= 19 || lagosHour < 6;
                } catch (e) {
                    isLagosNight = null;
                }
                dark = isLagosNight !== null ? isLagosNight : window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[var(--lp-page)] font-sans text-[var(--lp-text)] antialiased selection:bg-primary/20 transition-colors duration-300">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-primary focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-white">
        Skip to main content
    </a>

    <div class="relative isolate overflow-hidden">
        <!-- Ambient background: fixed so it spans the whole scroll, not just the hero -->
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[var(--lp-page)] transition-colors duration-300"></div>
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_var(--lp-page-grad-a),_transparent_35%),radial-gradient(circle_at_top_right,_var(--lp-page-grad-b),_transparent_35%)]"></div>

        @include('pages.welcome2.navbar')

        <main id="main-content">
            @include('pages.welcome2.herosection')
            @include('pages.welcome2.networks')
            @include('pages.welcome2.features')
            @include('pages.welcome2.pricing')
            @include('pages.welcome2.faq')
            @include('pages.welcome2.support')
        </main>

        @include('pages.welcome2.footer')
    </div>

    @include('layouts.partials.pwa-install-banner')
</body>
</html>
