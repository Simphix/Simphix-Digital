<section class="mx-auto grid max-w-7xl items-center gap-14 px-6 pb-20 pt-16 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12 lg:px-8 lg:pb-28 lg:pt-20">
    <div>
        <p class="text-sm font-semibold text-primary">Smart connectivity</p>

        <h1 class="mt-4 text-4xl font-bold leading-[1.15] tracking-tight text-[var(--lp-text)] sm:text-5xl lg:text-[3.25rem]">
            Connect your world. Supercharge your business.
        </h1>

        <p class="mt-6 max-w-xl text-md leading-8 text-[var(--lp-text-soft)]">
            SimPhix solutions for POS terminals, cameras, routers, and trackers with airtime, data, KYC verification, and rewards all in one platform.
        </p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="#pricing" class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#0049b8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                See our pricing
            </a>
            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg border px-6 py-3 text-sm font-semibold text-[var(--lp-text)] transition hover:bg-[var(--lp-surface-alt)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary" style="border-color: var(--lp-border-strong);">
                Create an account
            </a>
        </div>

    </div>

    <!-- Visual: network illustration -- one SIM platform, many connected devices -->
    <div class="relative mx-auto w-full max-w-md lg:max-w-none" aria-hidden="true">
        <div class="absolute inset-0 -z-10 animate-pulse rounded-full bg-primary/10 blur-[90px]"></div>

        <svg viewBox="0 0 500 500" class="h-auto w-full">
            <!-- slow rotating scan ring -->
            <circle cx="250" cy="250" r="220" fill="none" stroke="var(--lp-border)" stroke-width="1" stroke-dasharray="2 10" stroke-linecap="round">
                <animateTransform attributeName="transform" type="rotate" from="0 250 250" to="360 250 250" dur="60s" repeatCount="indefinite" />
            </circle>

            <!-- connecting lines: hub -> each device node -->
            <path id="lp-line-1" d="M250,250 L250,75" stroke="var(--lp-border-strong)" stroke-width="1.5" fill="none" />
            <path id="lp-line-2" d="M250,250 L416,196" stroke="var(--lp-border-strong)" stroke-width="1.5" fill="none" />
            <path id="lp-line-3" d="M250,250 L353,392" stroke="var(--lp-border-strong)" stroke-width="1.5" fill="none" />
            <path id="lp-line-4" d="M250,250 L147,392" stroke="var(--lp-border-strong)" stroke-width="1.5" fill="none" />
            <path id="lp-line-5" d="M250,250 L84,196" stroke="var(--lp-border-strong)" stroke-width="1.5" fill="none" />

            <!-- animated data pulses travelling along each line -->
            <circle r="4" class="fill-vibrant">
                <animateMotion dur="2.4s" repeatCount="indefinite" begin="0s"><mpath href="#lp-line-1" /></animateMotion>
            </circle>
            <circle r="4" class="fill-primary">
                <animateMotion dur="2.4s" repeatCount="indefinite" begin="0.5s"><mpath href="#lp-line-2" /></animateMotion>
            </circle>
            <circle r="4" class="fill-vibrant">
                <animateMotion dur="2.4s" repeatCount="indefinite" begin="1s"><mpath href="#lp-line-3" /></animateMotion>
            </circle>
            <circle r="4" class="fill-primary">
                <animateMotion dur="2.4s" repeatCount="indefinite" begin="1.5s"><mpath href="#lp-line-4" /></animateMotion>
            </circle>
            <circle r="4" class="fill-vibrant">
                <animateMotion dur="2.4s" repeatCount="indefinite" begin="2s"><mpath href="#lp-line-5" /></animateMotion>
            </circle>

            <!-- device nodes -->
            @php
                $nodes = [
                    ['x' => 250, 'y' => 75, 'label' => 'POS', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
                    ['x' => 416, 'y' => 196, 'label' => 'Camera', 'icon' => 'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316zM16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z'],
                    ['x' => 353, 'y' => 392, 'label' => 'CCTV', 'icon' => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z'],
                    ['x' => 147, 'y' => 392, 'label' => 'Router', 'icon' => 'M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z'],
                    ['x' => 84, 'y' => 196, 'label' => 'GPS', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
                ];
            @endphp

            @foreach ($nodes as $i => $node)
                <g>
                    <circle cx="{{ $node['x'] }}" cy="{{ $node['y'] }}" r="30" style="fill: var(--lp-surface);" stroke="var(--lp-border-strong)" stroke-width="1.5" />
                    <svg x="{{ $node['x'] - 11 }}" y="{{ $node['y'] - 11 }}" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="1.6" class="stroke-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $node['icon'] }}" />
                    </svg>
                    <text x="{{ $node['x'] }}" y="{{ $node['y'] + 46 }}" text-anchor="middle" font-size="13" font-weight="600" style="fill: var(--lp-text-faint);">{{ $node['label'] }}</text>
                </g>
            @endforeach

            <!-- central hub: radar pulses -->
            <circle cx="250" cy="250" r="52" fill="none" class="stroke-primary" stroke-width="1.5" opacity="0.5">
                <animate attributeName="r" values="52;95;52" dur="3s" repeatCount="indefinite" />
                <animate attributeName="opacity" values="0.5;0;0.5" dur="3s" repeatCount="indefinite" />
            </circle>
            <circle cx="250" cy="250" r="52" fill="none" class="stroke-primary" stroke-width="1.5" opacity="0.5">
                <animate attributeName="r" values="52;95;52" dur="3s" begin="1.5s" repeatCount="indefinite" />
                <animate attributeName="opacity" values="0.5;0;0.5" dur="3s" begin="1.5s" repeatCount="indefinite" />
            </circle>

            <!-- central hub: SIM chip -->
            <circle cx="250" cy="250" r="52" class="fill-primary" />
            <rect x="230" y="230" width="40" height="40" rx="8" fill="white" opacity="0.95" />
            <rect x="237" y="238" width="14" height="10" rx="2" class="fill-primary" />
            <line x1="237" y1="256" x2="263" y2="256" stroke-width="2" class="stroke-primary" />
            <line x1="237" y1="262" x2="257" y2="262" stroke-width="2" class="stroke-primary" />
        </svg>

        <p class="mt-2 text-center text-xs font-medium text-[var(--lp-text-faint)]">One SIM platform, every connected device</p>
    </div>
</section>
