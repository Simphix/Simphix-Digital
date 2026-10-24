<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SimPhix - 500 Internal Server Error</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0b0f19;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #f8fafc;
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
        }
        
        /* Ambient Background Glows */
        .glow-1 {
            position: absolute;
            top: -20%;
            left: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.08); /* Red/crimson tint for server error */
            filter: blur(120px);
            pointer-events: none;
        }
        .glow-2 {
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(66, 81, 124, 0.05);
            filter: blur(120px);
            pointer-events: none;
        }

        /* Container Card */
        .error-card {
            max-width: 450px;
            width: 100%;
            text-align: center;
            padding: 2.5rem;
            z-index: 10;
            box-sizing: border-box;
        }

        /* Icon Container */
        .icon-container {
            display: inline-flex;
            width: 80px;
            height: 80px;
            border-radius: 24px;
            background-color: #0f172a;
            border: 1px solid rgba(239, 68, 68, 0.2); /* Red tint border */
            align-items: center;
            justify-content: center;
            color: #ef4444; /* Crimson red color */
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            margin-bottom: 2rem;
        }
        .icon-glow {
            position: absolute;
            inset: 0;
            background-color: rgba(239, 68, 68, 0.15);
            border-radius: 24px;
            filter: blur(16px);
            z-index: -1;
        }

        /* 500 Heading */
        .code-title {
            font-size: 7.5rem;
            font-weight: 900;
            line-height: 1;
            margin: 0;
            letter-spacing: -0.05em;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 50%, #64748b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        /* Subheadings */
        .status-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin: 0.75rem 0;
        }
        .message-text {
            font-size: 0.875rem;
            color: #94a3b8;
            line-height: 1.625;
            max-width: 360px;
            margin: 0 auto 2rem auto;
        }

        /* Buttons styling */
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        @media (min-width: 640px) {
            .button-group {
                flex-direction: row;
            }
        }
        
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: #0056D2;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(66, 81, 124, 0.3);
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #354267;
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(51, 65, 85, 0.5);
            color: #cbd5e1;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background-color: rgba(30, 41, 59, 1);
            color: #ffffff;
            transform: translateY(-1px);
        }
        .btn-secondary:active {
            transform: translateY(0);
        }

        /* Footer line */
        .footer-divider {
            border: 0;
            height: 1px;
            background: rgba(30, 41, 59, 0.6);
            margin: 2rem 0 1.5rem 0;
        }
        .footer-text {
            font-size: 0.75rem;
            color: #4b5563;
            margin: 0;
        }

        /* Pulse animation */
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.96); }
        }
        .animate-pulse-slow {
            animation: pulse-slow 3.5s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Ambient Background Glows -->
    <div class="glow-1"></div>
    <div class="glow-2"></div>

    <div class="error-card">
        <!-- Logo / Icon -->
        <div class="icon-container">
            <div class="icon-glow"></div>
            <i data-lucide="server-crash" class="animate-pulse-slow" style="width: 40px; height: 40px;"></i>
        </div>

        <!-- 500 Heading & Text -->
        <div>
            <h1 class="code-title">500</h1>
            <h2 class="status-title">Server Error</h2>
            <p class="message-text">
                Something went wrong on our servers. Rest assured, our team is looking into it. Let's get you back to safety.
            </p>
        </div>

        <!-- Navigation Buttons -->
        <div class="button-group">
            @auth
                <a href="{{ route('wallet') }}" class="btn-primary">
                    <i data-lucide="home" style="width: 16px; height: 16px;"></i>
                    Back to Wallet
                </a>
            @else
                <a href="{{ url('/') }}" class="btn-primary">
                    <i data-lucide="home" style="width: 16px; height: 16px;"></i>
                    Go to Home
                </a>
                <a href="{{ route('login') }}" class="btn-secondary">
                    <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
                    Log In
                </a>
            @endauth
        </div>

        <hr class="footer-divider">
        <p class="footer-text">
            &copy; {{ date('Y') }} SimPhix. All rights reserved.
        </p>
    </div>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
