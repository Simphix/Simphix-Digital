<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SimPhix') }}</title>
    <meta name="description" content="SimPhix — secure SIM services, affordable data and a smarter wallet.">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo/favicon.png') }}" type="image/png">
    @include('layouts.partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full bg-white font-sans text-[#14243a] antialiased">
    <main class="min-h-screen lg:grid lg:grid-cols-2">
        <section class="relative hidden min-h-screen flex-col bg-white px-9 py-6 lg:flex" aria-label="SimPhix introduction">
            <a href="/" class="relative z-10 inline-flex w-fit items-center">
                <img src="{{ asset('assets/images/logo/logo1.png') }}" alt="SimPhix" class="h-auto w-[170px]">
            </a>

            <div class="flex flex-1 flex-col items-center justify-center pb-16">
                <img
                    src="{{ asset('assets/images/login-sim-wallet.png') }}"
                    alt="SimPhix wallet and SIM illustration"
                    class="h-[700px] w-[700px] object-contain"
                >
                <h2 class="mt-3 max-w-md text-center text-[26px] font-bold leading-[1.25] tracking-[-0.025em] text-[#15253a]">
                    SimPhix Solutions.<br>Empowering Connections.
                </h2>
            </div>
        </section>

        <section class="relative flex min-h-screen items-center justify-center bg-[#f4f5f7] px-5 py-16 sm:px-10 sm:py-20">
            <div class="absolute left-5 top-6 sm:left-8 sm:top-8 lg:hidden">
                <a href="/">
                    <img src="{{ asset('assets/images/logo/logo1.png') }}" alt="SimPhix" class="h-auto w-[145px]">
                </a>
            </div>

            <div class="mt-16 w-full max-w-[450px] rounded-xl bg-white px-6 py-8 shadow-[0_2px_5px_rgba(15,23,42,0.14)] sm:px-8 sm:py-10 lg:mt-0">
                {{ $slot }}
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', function (event) {
                const button = this.querySelector('button[type="submit"]');
                if (!button) return;

                if (button.dataset.processing === 'true') {
                    event.preventDefault();
                    return;
                }

                button.dataset.processing = 'true';
                button.classList.add('opacity-80');

                const text = button.querySelector('[data-submit-text]');
                if (text) text.textContent = 'Signing in...';
            });
        });
    </script>

    @include('layouts.partials.pwa-install-banner')
</body>
</html>
