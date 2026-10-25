@push('styles')
    <!-- Bootstrap 5 GRID ONLY for premium responsiveness without Reboot/Reset overrides -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css" rel="stylesheet" />
    <style>
        .dash-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            transition: border-color 0.2s ease;
        }
        .dash-card:hover {
            border-color: rgba(0, 86, 210, 0.3);
        }
        .service-btn {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            text-decoration: none;
            background: #ffffff;
            height: 96px;
        }
        .service-btn:hover {
            background: #f8fafc;
            border-color: rgba(0, 86, 210, 0.3);
        }
        .service-btn h4 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }
        @media (min-width: 768px) {
            .service-btn {
                height: 136px;
            }
        }
    </style>
@endpush

<x-app-layout>
    <div class="container-fluid py-2 px-0">

        <!-- Welcome Greeting & Date Header -->
        <div class="row align-items-center mb-4 gy-3">
            <div class="col-12 col-md-8">
                <h1 class="text-2xl font-bold font-display text-slate-800 mb-1 flex flex-wrap items-center gap-2">
                    Welcome back, {{ Auth::user()->first_name ?: Auth::user()->email }}!
                    <span class="text-xs font-semibold text-primary bg-primary/10 border border-primary/20 rounded-md py-1 px-2.5">
                        {{ Auth::user()->role }}
                    </span>
                    @if (Auth::user()->hasJurisdictionRole())
                        @if (Auth::user()->jurisdictionLabel())
                            <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-md py-1 px-2.5 flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                {{ Auth::user()->jurisdictionLabel() }}
                            </span>
                        @else
                            <span class="text-xs font-semibold text-amber-600 bg-amber-50 border border-amber-100 rounded-md py-1 px-2.5 flex items-center gap-1">
                                <i data-lucide="map-pin-off" class="w-3 h-3"></i>
                                Jurisdiction unassigned
                            </span>
                        @endif
                    @endif
                </h1>
                <p class="text-slate-500 text-sm mb-0">Here is your account overview and recent activity for today, {{ now()->format('F d, Y') }}.</p>
            </div>

            <div class="col-12 col-md-4 flex justify-start justify-content-md-end">
                <div class="flex items-center gap-2 text-slate-500 py-2 px-3 bg-white border border-slate-200 rounded-lg text-sm">
                    <i data-lucide="clock" class="text-primary w-4 h-4"></i>
                    <span>Last login: {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->format('M d, h:i A') : now()->format('M d, h:i A') }}</span>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="p-4 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3" role="alert">
                <i data-lucide="check-circle" class="text-emerald-500 shrink-0 w-5 h-5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 flex items-center gap-3" role="alert">
                <i data-lucide="alert-circle" class="text-rose-500 shrink-0 w-5 h-5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- TOP FINANCIAL GRID (Balances & Virtual Accounts using Bootstrap grid) -->
        <div class="row g-3 mb-4">

            <!-- Card 1: Main Wallet Balance -->
            <div class="col-12 col-md-4">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="wallet" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">Wallet Balance</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">
                            ₦{{ number_format($walletData['balance'] ?? 0.00, 2) }}
                        </p>
                    </div>
                    @if (auth()->user()->role !== 'agent')
                        <a href="{{ route('transfer') }}" class="shrink-0 text-xs font-semibold text-primary no-underline hover:underline">
                            Send
                        </a>
                    @endif
                </div>
            </div>

            <!-- Card 2: Rewards & Bonus -->
            <div class="col-12 col-md-4">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="gift" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">{{ \App\Support\SimAccess::canBrowseCatalog(auth()->user()) ? 'Commission' : 'Referral & Bonuses' }}</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">
                            ₦{{ number_format($walletData['bonus'] ?? 0.00, 2) }}
                        </p>
                    </div>
                    @if(isset($walletData) && $walletData['bonus'] > 0)
                        <form method="POST" action="{{ route('wallet.claimBonus') }}" class="m-0 shrink-0">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-vibrant hover:underline">
                                {{ \App\Support\SimAccess::canBrowseCatalog(auth()->user()) ? 'Withdraw' : 'Claim' }}
                            </button>
                        </form>
                    @else
                        <span class="shrink-0 text-xs font-medium text-slate-300">Locked</span>
                    @endif
                </div>
            </div>

            <!-- Partner Virtual Promo Wallet -->
            @if (\App\Support\SimAccess::canBrowseCatalog(auth()->user()))
            <div class="col-12 col-md-4">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="badge-percent" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-400 mb-0.5">Virtual Promo Wallet</p>
                        <p class="text-lg font-bold font-display text-slate-800 tracking-tight mb-0 truncate">
                            ₦{{ number_format($walletData['virtual_promo'] ?? 0.00, 2) }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Virtual Account Details -->
            @unless (\App\Support\SimAccess::canBrowseCatalog(auth()->user()))
            <div class="col-12 col-md-4">
                <div class="dash-card p-3.5 h-100 flex items-center gap-3">
                    <div class="rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center w-10 h-10 shrink-0">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                    @if($virtualAccount)
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-slate-400 mb-0.5 truncate">{{ $virtualAccount->bank_name ?? ($virtualAccount->bankName ?? 'PalmPay') }} &middot; Instant Funding</p>
                            <p class="text-lg font-bold font-display text-primary tracking-wide mb-0 truncate">
                                {{ $virtualAccount->account_number ?? ($virtualAccount->accountNo ?? 'N/A') }}
                            </p>
                        </div>
                        <button onclick="copyToClipboard('{{ $virtualAccount->account_number ?? ($virtualAccount->accountNo ?? '') }}', 'Account number')"
                                type="button"
                                class="shrink-0 text-xs font-semibold text-primary hover:underline">
                            Copy
                        </button>
                    @else
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-slate-400 mb-0.5">Instant Funding</p>
                            <p class="text-sm font-semibold text-slate-800 mb-0">No account yet</p>
                        </div>
                        <a href="{{ route('wallet') }}" class="shrink-0 text-xs font-semibold text-primary no-underline hover:underline">
                            Set up
                        </a>
                    @endif
                </div>
            </div>
            @endunless

        </div>

        <!-- QUICK SERVICES PANEL (4 items per row on desktop) -->
        <div class="dash-card p-4 mb-4">
            <h3 class="text-sm font-semibold text-slate-800 font-display mb-3 flex items-center gap-2">
                <i data-lucide="grid" class="text-primary w-5 h-5"></i>
                Quick Transaction Services
            </h3>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-2 g-md-4">

                @if (auth()->user()->role !== 'agent')
                    <!-- Service 3: SIM Services -->
                    <div class="col">
                        <a href="{{ route('sims.index') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10">
                                <i data-lucide="cpu" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">SIM Plan</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">Activations & SIMs</p>
                            </div>
                        </a>
                    </div>

                    @if (auth()->user()->role !== 'personal' && \App\Models\CashoutSettings::enabled())
                    <!-- Service 4: Cash Out -->
                    <div class="col">
                        <a href="{{ route('withdraw') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10">
                                <i data-lucide="banknote" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">Cash Out</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">Pay out to bank</p>
                            </div>
                        </a>
                    </div>

                    @endif

                    <!-- Service 5: P2P Transfer -->
                    <div class="col">
                        <a href="{{ route('transfer') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10">
                                <i data-lucide="send" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">P2P Send</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">Free P2P transfer</p>
                            </div>
                        </a>
                    </div>
                @endif

                @if (\App\Support\UtilityAccess::canVerify(auth()->user()))
                    <!-- Service 6: NIN -->
                    <div class="col">
                        <a href="{{ route('nin.verification.index') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10">
                                <i data-lucide="id-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">NIN Verification</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">Verify your identity</p>
                            </div>
                        </a>
                    </div>

                    <!-- Service 7: BVN -->
                    <div class="col">
                        <a href="{{ route('bvn.verification.index') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10">
                                <i data-lucide="id-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">BVN Verification</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">BVN verification service</p>
                            </div>
                        </a>
                    </div>

                    <!-- Service 8: NIN by Phone -->
                    <div class="col">
                        <a href="{{ route('nin.phone.index') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10">
                                <i data-lucide="id-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">NIN by Phone</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">NIN retrieval by phone</p>
                            </div>
                        </a>
                    </div>

                    <!-- Service 9: NIN by Demo -->
                    <div class="col">
                        <a href="{{ route('nin.phone.index') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                            <div class="p-2 rounded-lg bg-vibrant/10 text-vibrant flex items-center justify-center w-10 h-10">
                                <i data-lucide="id-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">NIN by Demo</h4>
                                <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">NIN retrieval by demo</p>
                            </div>
                        </a>
                    </div>
                @endif

                <!-- Service 11: CAC Registration -->
                <div class="col">
                    <a href="{{ route('cac.overview') }}" class="service-btn p-3 p-md-4 text-center flex flex-col items-center justify-center gap-2">
                        <div class="p-2 rounded-lg bg-primary/10 text-primary flex items-center justify-center w-10 h-10">
                            <i data-lucide="briefcase" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-800 mb-0 font-display text-sm">CAC Registration</h4>
                            <p class="text-slate-400 hidden md:block mb-0 mt-1 text-xs">Register your business</p>
                        </div>
                    </a>
                </div>

            </div>
        </div>

        @if ($epSims)
            @include('enterprise-partner.partials.sims-panel', ['epSims' => $epSims])
        @endif

        <!-- SPLIT LAYOUT: STATISTICS (Left) & RECENT TRANSACTIONS (Right) -->
        <div class="row g-4">

            <!-- Left Side: Transaction Statistics (5 cols out of 12) -->
            <div class="col-12 col-lg-5 flex flex-col">
                <div class="dash-card p-4 flex flex-col justify-between h-100 flex-grow">

                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-semibold text-slate-800 font-display mb-0 flex items-center gap-2">
                                <i data-lucide="bar-chart-2" class="text-primary w-5 h-5"></i>
                                Account Activity Statistics
                            </h3>
                            <span class="text-slate-400 text-xs font-medium">Last 7 days</span>
                        </div>

                        <!-- Mini stats summary grid -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-3 bg-slate-50 border border-slate-100 rounded-lg">
                                    <div class="flex items-center gap-1.5 text-emerald-600 font-display font-semibold text-xs">
                                        <i data-lucide="arrow-down-left" class="w-3.5 h-3.5"></i>
                                        Total Funded
                                    </div>
                                    <div class="text-lg font-bold text-slate-800 font-display mb-0 mt-1">
                                        ₦{{ number_format($totalCredits, 2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="p-3 bg-slate-50 border border-slate-100 rounded-lg">
                                    <div class="flex items-center gap-1.5 text-rose-600 font-display font-semibold text-xs">
                                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                        Total Spent
                                    </div>
                                    <div class="text-lg font-bold text-slate-800 font-display mb-0 mt-1">
                                        ₦{{ number_format($totalDebits, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SVG-based Custom Dual-Bar Chart -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-lg p-3">
                            <div class="flex justify-between items-center text-slate-400 mb-3 text-xs font-semibold">
                                <span>Volume Trend</span>
                                <div class="flex items-center gap-2">
                                    <span class="flex items-center gap-1 text-emerald-600"><span class="bg-emerald-500 rounded-sm inline-block w-2.5 h-2.5"></span> Cash-In</span>
                                    <span class="flex items-center gap-1 text-rose-600"><span class="bg-rose-500 rounded-sm inline-block w-2.5 h-2.5"></span> Cash-Out</span>
                                </div>
                            </div>

                            @php
                                $maxVal = 1000;
                                foreach ($chartData as $day) {
                                    if ($day['credits'] > $maxVal) $maxVal = $day['credits'];
                                    if ($day['debits'] > $maxVal) $maxVal = $day['debits'];
                                }
                            @endphp

                            <!-- Responsive SVG Chart container -->
                            <div class="position-relative w-100" style="height: 140px;">
                                <svg class="w-full h-full" viewBox="0 0 350 140" preserveAspectRatio="none">
                                    <!-- horizontal gridlines -->
                                    <line x1="0" y1="35" x2="350" y2="35" stroke="#e2e8f0" stroke-width="1" />
                                    <line x1="0" y1="70" x2="350" y2="70" stroke="#e2e8f0" stroke-width="1" />
                                    <line x1="0" y1="105" x2="350" y2="105" stroke="#e2e8f0" stroke-width="1" />

                                    <!-- Bars mapping -->
                                    @foreach ($chartData as $index => $day)
                                        @php
                                            $colWidth = 50;
                                            $startX = ($index * $colWidth) + 12;

                                            $creditHeight = ($day['credits'] / $maxVal) * 105;
                                            $debitHeight = ($day['debits'] / $maxVal) * 105;

                                            if ($day['credits'] > 0 && $creditHeight < 4) $creditHeight = 4;
                                            if ($day['debits'] > 0 && $debitHeight < 4) $debitHeight = 4;

                                            $creditY = 115 - $creditHeight;
                                            $debitY = 115 - $debitHeight;
                                        @endphp

                                        <!-- Credit Bar -->
                                        <rect x="{{ $startX }}" y="{{ $creditY }}" width="10" height="{{ $creditHeight }}" rx="2" fill="#10b981">
                                            <title>{{ $day['day'] }}: Funded ₦{{ number_format($day['credits'], 2) }}</title>
                                        </rect>

                                        <!-- Debit Bar -->
                                        <rect x="{{ $startX + 12 }}" y="{{ $debitY }}" width="10" height="{{ $debitHeight }}" rx="2" fill="#f43f5e">
                                            <title>{{ $day['day'] }}: Spent ₦{{ number_format($day['debits'], 2) }}</title>
                                        </rect>

                                        <!-- Day Labels -->
                                        <text x="{{ $startX + 11 }}" y="132" fill="#64748b" font-size="11" font-weight="600" text-anchor="middle">
                                            {{ $day['day'] }}
                                        </text>
                                    @endforeach

                                    <!-- Bottom axis line -->
                                    <line x1="0" y1="118" x2="350" y2="118" stroke="#cbd5e1" stroke-width="1" />
                                </svg>
                            </div>
                        </div>

                    </div>

                    <!-- Milestone card -->
                    <div class="p-3 rounded-lg relative overflow-hidden bg-primary/5 border border-primary/10">
                        <div class="relative space-y-2">
                            <div class="flex justify-between items-center mb-1 text-xs font-semibold text-slate-700">
                                <span>Monthly Spend Milestone</span>
                                <span class="text-primary">{{ $bonusProgress }}%</span>
                            </div>
                            <div class="w-full bg-primary/10 rounded-full" style="height: 6px;">
                                <div class="bg-primary rounded-full" style="height: 6px; width: {{ $bonusProgress }}%;"></div>
                            </div>
                            <p class="mb-0 mt-2 text-slate-500 text-xs" style="line-height: 1.4;">
                                Spend ₦{{ number_format($currentSpend, 2) }} of ₦{{ number_format($nextBonusTarget, 2) }} limit. Reach target to claim a cashback coupon!
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right Side: Recent Transactions (7 cols out of 12) -->
            <div class="col-12 col-lg-7 flex flex-col">
                <div class="dash-card p-4 flex flex-col justify-between h-100 flex-grow">
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-semibold text-slate-800 font-display mb-0 flex items-center gap-2">
                                <i data-lucide="history" class="text-primary w-5 h-5"></i>
                                Recent Transactions
                            </h3>
                            <a href="{{ route('transactions') }}" class="no-underline px-3 py-1.5 bg-primary/10 hover:bg-primary/15 text-primary text-xs font-semibold rounded-md transition">
                                View All
                            </a>
                        </div>

                        <!-- Transactions List -->
                        <div class="overflow-y-auto pr-1" style="max-height: 380px;">
                            @forelse($recentTransactions as $tx)
                                <div class="p-3 bg-slate-50 border border-slate-200/60 rounded-lg flex items-center justify-between mb-2 transition hover:bg-slate-100/70">
                                    <div class="flex items-center gap-2.5 min-w-0 flex-grow">
                                        <!-- Type icon -->
                                        @if($tx->type === 'credit')
                                            <div class="rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 shrink-0 flex items-center justify-center w-8 h-8">
                                                <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                                            </div>
                                        @else
                                            <div class="rounded-md bg-rose-50 text-rose-600 border border-rose-100 shrink-0 flex items-center justify-center w-8 h-8">
                                                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                                            </div>
                                        @endif

                                        <!-- Details -->
                                        <div class="min-w-0 flex-grow">
                                            <h4 class="font-semibold text-slate-800 mb-0 truncate block text-xs" title="{{ $tx->description }}">
                                                {{ $tx->description }}
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-slate-400 mt-0.5 text-xs">
                                                <span class="truncate block max-w-[80px] sm:max-w-none" title="{{ $tx->transaction_ref }}">{{ $tx->transaction_ref }}</span>
                                                <span>•</span>
                                                <span class="text-nowrap">{{ $tx->created_at->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Amount & Status Badge -->
                                    <div class="text-end shrink-0 ml-3">
                                        <span class="block font-display font-semibold text-slate-800 text-nowrap text-xs">
                                            {{ $tx->type === 'credit' ? '+' : '-' }}₦{{ number_format($tx->amount, 2) }}
                                        </span>

                                        @if($tx->status === 'completed')
                                            <span class="text-emerald-600 mt-1 inline-block text-xs font-semibold">
                                                Completed
                                            </span>
                                        @elseif($tx->status === 'pending')
                                            <span class="text-amber-600 mt-1 inline-block text-xs font-semibold">
                                                Pending
                                            </span>
                                        @else
                                            <span class="text-rose-600 mt-1 inline-block text-xs font-semibold">
                                                Failed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 bg-slate-50 border border-slate-200/60 rounded-lg">
                                    <div class="p-3 bg-white border border-slate-200 rounded-full inline-flex items-center justify-center text-slate-400 mb-3 w-12 h-12">
                                        <i data-lucide="receipt" class="w-5 h-5"></i>
                                    </div>
                                    <h4 class="font-semibold text-slate-800 text-xs">No Transactions Yet</h4>
                                    <p class="text-slate-400 text-xs mt-1 max-w-xs mx-auto mb-0">Fund your wallet or buy packages to see transaction statements here.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Footer Help -->
                    <div class="border-t border-slate-100 pt-3 flex items-center justify-between mt-3 text-slate-400 text-xs font-medium">
                        <span>Need a receipt? Click transaction in history.</span>
                        <a href="{{ route('support') }}" class="text-primary no-underline hover:underline">Get Help</a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
