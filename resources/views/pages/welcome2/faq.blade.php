@php
    $faqs = [
        [
            'q' => 'How fast is SIM activation?',
            'a' => 'Activation is fully online. Register, verify your details, and your SIM is typically active in under 2 minutes — no paperwork or store visit required.',
        ],
        [
            'q' => 'What SIM types are available?',
            'a' => 'POS, CCTV Camera, Router, and GPS Tracking SIMs — each priced for the device it\'s built for. See the pricing section above for current rates.',
        ],
        [
            'q' => 'Can I become a wholesale distribution agent?',
            'a' => 'Yes. Agents get wholesale SIM pricing, bulk allocation tools, and a live dashboard to track activations and earnings. Reach out via the support section below to get set up.',
        ],
        [
            'q' => 'Which networks does SimPhix support?',
            'a' => 'SimPhix works across MTN, Airtel, Glo, and 9mobile, so you can pick the network that fits your coverage needs.',
        ],
    ];
@endphp

<section id="faq" class="mx-auto max-w-4xl px-6 py-20 lg:px-8 lg:py-28">
    <h2 class="text-3xl font-bold tracking-tight text-[var(--lp-text)] sm:text-4xl">Frequently asked questions</h2>

    <div class="mt-10">
        @foreach ($faqs as $index => $faq)
            <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" class="border-b" style="border-color: var(--lp-border);">
                <h3>
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="faq-panel-{{ $index }}"
                        class="flex w-full items-center justify-between gap-4 py-5 text-left text-base font-semibold text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <span>{{ $faq['q'] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--lp-text-faint)] transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </h3>
                <div
                    id="faq-panel-{{ $index }}"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    class="pb-5 text-sm leading-7 text-[var(--lp-text-soft)]"
                >
                    {{ $faq['a'] }}
                </div>
            </div>
        @endforeach
    </div>
</section>
