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
        <section class="relative hidden min-h-screen flex-col items-center justify-center bg-white px-8 py-12 lg:flex border-r border-slate-100" aria-label="SimPhix introduction">
            <div class="flex flex-col items-center justify-center text-center max-w-lg w-full my-auto">
                <a href="/" class="inline-block transition hover:opacity-95">
                    <img
                        src="{{ asset('assets/images/logo/simphix-banner.png') }}"
                        alt="SimPhix"
                        class="h-auto w-full max-w-[440px] object-contain mx-auto"
                    >
                </a>
                <h2 class="mt-8 max-w-md text-center text-[26px] font-bold leading-[1.25] tracking-[-0.025em] text-[#15253a]">
                    SimPhix Solutions.<br>Empowering Connections.
                </h2>
                <p class="mt-3 max-w-sm text-center text-sm text-slate-500 leading-relaxed">
                    Next-generation telecom infrastructure engineered for seamless connectivity, real-time access, and boundless scale.
                </p>
            </div>
        </section>

        <section class="relative flex min-h-screen flex-col items-center justify-center bg-[#f4f5f7] px-5 py-12 sm:px-10">
            <div class="mb-8 flex justify-center lg:hidden">
                <a href="/" class="inline-flex items-center justify-center">
                    <img src="{{ asset('assets/images/logo/simphix-banner.png') }}" alt="SimPhix" class="h-auto w-[200px] object-contain mx-auto">
                </a>
            </div>

            <div class="w-full max-w-[450px] rounded-xl bg-white px-6 py-8 shadow-[0_2px_5px_rgba(15,23,42,0.14)] sm:px-8 sm:py-10">
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
