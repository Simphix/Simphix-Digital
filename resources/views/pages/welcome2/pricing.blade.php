@php
    $simService = \App\Models\Service::where('name', 'simcard')->with(['fields.prices'])->first();
    $fields = $simService ? $simService->fields : collect();
    $currentUser = auth()->user();

    $cardsConfig = [
        [
            'name' => 'POS SIM',
            'desc' => 'For payment terminals',
            'illustration' => 'pages.welcome2.illustrations.pos-sim',
            'match' => ['pos_sim', 'pos sim', 'pos'],
            'fallback_price' => '1,000',
        ],
        [
            'name' => 'CCTV Camera SIM',
            'desc' => 'For surveillance systems',
            'illustration' => 'pages.welcome2.illustrations.cctv-sim',
            'match' => ['cctv_sim', 'cctv', 'camera_sim', 'camera sim'],
            'fallback_price' => '2,000',
        ],
        [
            'name' => 'Router SIM',
            'desc' => 'For mobile routers',
            'illustration' => 'pages.welcome2.illustrations.router-sim',
            'match' => ['router_sim', 'router sim', 'router'],
            'fallback_price' => '2,500',
        ],
        [
            'name' => 'GPS Tracking SIM',
            'desc' => 'For tracking devices',
            'illustration' => 'pages.welcome2.illustrations.gps-sim',
            'match' => ['gps_sim', 'gps sim', 'gps'],
            'fallback_price' => '3,000',
        ],
    ];

    $simTypes = [];
    foreach ($cardsConfig as $card) {
        $field = null;
        foreach ($card['match'] as $matchKey) {
            $matched = $fields->first(function ($f) use ($matchKey) {
                $code = strtolower(trim($f->field_code ?? ''));
                $name = strtolower(trim($f->field_name ?? ''));
                return $code === $matchKey || $name === $matchKey;
            });
            if ($matched) {
                $field = $matched;
                break;
            }
        }

        $isComingSoon = false;
        $price = null;

        if ($field) {
            if (!$field->is_active || (bool) $field->activation_disabled) {
                $isComingSoon = true;
            } else {
                $numericPrice = $currentUser ? $field->priceForUser($currentUser) : $field->getPriceForUserType('personal');
                if ($numericPrice > 0) {
                    $price = number_format($numericPrice);
                } else {
                    $isComingSoon = true;
                }
            }
        } else {
            $price = $card['fallback_price'];
        }

        $simTypes[] = [
            'name' => $card['name'],
            'price' => $price,
            'desc' => $card['desc'],
            'illustration' => $card['illustration'],
            'comingSoon' => $isComingSoon,
        ];
    }
@endphp

<section id="pricing" class="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
    <div class="max-w-2xl">
        <h2 class="text-3xl font-bold tracking-tight text-[var(--lp-text)] sm:text-4xl">SIM pricing, by device</h2>
        <p class="mt-4 text-lg leading-8 text-[var(--lp-text-soft)]">
            One-time activation fee, no contracts. Pick the SIM built for what you're connecting.
        </p>
    </div>

    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($simTypes as $sim)
            <div class="flex flex-col items-center rounded-xl border p-5 text-center" style="border-color: var(--lp-border); background: var(--lp-surface);">
                @include($sim['illustration'])
                <p class="mt-2 text-sm font-semibold text-[var(--lp-text)]">{{ $sim['name'] }}</p>
                <p class="mt-1 text-xs text-[var(--lp-text-faint)]">{{ $sim['desc'] }}</p>
                @if (!empty($sim['comingSoon']) || empty($sim['price']))
                    <p class="mt-4 text-xs font-bold uppercase tracking-wider text-primary">Coming Soon</p>
                @else
                    <p class="mt-4 flex items-baseline gap-1">
                        <span class="text-sm font-semibold text-[var(--lp-text-soft)]">₦</span>
                        <span class="text-2xl font-bold text-[var(--lp-text)]">{{ $sim['price'] }}</span>
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    <p class="mt-8 text-sm text-[var(--lp-text-faint)]">
        Buying in bulk? <a href="#support" class="font-semibold text-primary hover:underline">Ask about wholesale agent rates.</a>
    </p>
</section>
