<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-7xl mx-auto space-y-6">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <p class="text-sm font-bold">Please correct the following errors:</p>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Prominent Moniepoint Business Owner Disclaimer (Kept Intact) -->
        <div class="rounded-2xl bg-amber-500/10 border-2 border-amber-500/40 p-4 sm:p-5 flex items-start gap-4 shadow-xs">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-900 flex items-center justify-center shrink-0 font-bold shadow-xs">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">
                        Important Eligibility Disclaimer
                    </span>
                    <span class="text-xs font-bold text-amber-900 dark:text-amber-300">Moniepoint Business Account Required</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 mt-1 font-medium leading-relaxed">
                    <strong>Notice:</strong> Only Moniepoint business owners can request for loan (merchants with an active Moniepoint business account / POS terminal). All loan requests are audited against transaction turnover history on your Moniepoint business account.
                </p>
            </div>
        </div>

        <!-- Prominent Top Banner with Apply Button (Visible at Top Like Before) -->
        <div class="relative w-full rounded-2xl shadow-md border border-slate-200 overflow-hidden p-5 sm:p-7 min-h-[170px] flex items-center"
             style="background: linear-gradient(135deg, #001233 0%, #002875 50%, #0056D2 100%); color: #ffffff;">

            <!-- Decorative Ambient Accents -->
            <div class="pointer-events-none absolute -top-16 -right-16 h-56 w-56 rounded-full"
                 style="background: radial-gradient(circle, rgba(255, 204, 0, 0.28) 0%, rgba(255, 204, 0, 0) 70%); filter: blur(25px);" aria-hidden="true"></div>

            <div class="relative z-10 w-full flex flex-col md:flex-row md:items-center justify-between gap-5 lg:gap-8">
                <!-- Left Content Block -->
                <div class="min-w-0 flex-1 text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-[#bae6fd] text-xs font-bold mb-2.5 backdrop-blur-xs">
                        <i data-lucide="banknote" class="w-3.5 h-3.5 text-[#ffcc00]"></i>
                        <span>SME &amp; Merchant Growth Financing</span>
                    </div>

                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white font-display tracking-tight leading-tight mb-2">
                        Business Working Capital Loans
                    </h1>

                    <p class="text-xs sm:text-sm text-blue-100 max-w-2xl leading-relaxed mb-4">
                        Expand your retail operations or agent liquidity with fast, low-interest working capital loans up to &#8358;50,000,000. Verified turnover evaluation, fast disbursement within 24&ndash;48 hours, and zero physical collateral.
                    </p>

                    <!-- Feature Tags -->
                    <div class="hidden sm:flex flex-wrap items-center gap-2 mb-4">
                        <span class="text-xs px-2.5 py-1 rounded-lg bg-white/10 border border-white/15 text-emerald-300 font-semibold">
                            &#10003; 24&ndash;48h Quick Disbursement
                        </span>
                        <span class="text-xs px-2.5 py-1 rounded-lg bg-white/10 border border-white/15 text-emerald-300 font-semibold">
                            &#10003; Up to &#8358;50,000,000
                        </span>
                        <span class="text-xs px-2.5 py-1 rounded-lg bg-white/10 border border-white/15 text-emerald-300 font-semibold">
                            &#10003; Zero Physical Collateral
                        </span>
                        <span class="text-xs px-2.5 py-1 rounded-lg bg-white/10 border border-white/15 text-emerald-300 font-semibold">
                            &#10003; Flexible Repayment
                        </span>
                    </div>

                    <!-- Action Buttons Visible at Top -->
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('loans.create') }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-[#ffcc00] hover:bg-[#ffe066] text-[#001F5C] text-xs sm:text-sm font-extrabold shadow-lg transition transform hover:-translate-y-0.5">
                            <i data-lucide="banknote" class="w-4 h-4 text-[#001F5C]"></i>
                            <span>Apply for Business Loan</span>
                        </a>
                        <a href="{{ route('loans.tracking') }}"
                           class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white/15 hover:bg-white/25 text-white border border-white/30 text-xs sm:text-sm font-semibold backdrop-blur-xs transition">
                            <i data-lucide="search" class="w-4 h-4 text-blue-200"></i>
                            <span>Track My Loan Request</span>
                        </a>
                    </div>
                </div>

                <!-- Right Side Graphic / Illustration -->
                <div class="shrink-0 hidden md:flex items-center justify-center">
                    <div class="relative p-3 rounded-2xl bg-white/10 backdrop-blur-sm border border-emerald-400/30 shadow-xl">
                        <img src="{{ asset('assets/images/business-loan.png') }}"
                             alt="Business Loan"
                             class="h-28 lg:h-36 w-auto object-contain filter drop-shadow-lg transform hover:scale-105 transition-transform duration-300">
                    </div>
                </div>
            </div>
        </div>

        @if ($userRequests->isNotEmpty())
            <!-- Recent Loan Requests Quick Bar -->
            <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-[#00266B]"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 font-display">Your Recent Loan Applications</h3>
                    </div>
                    <a href="{{ route('loans.tracking') }}" class="text-xs font-semibold text-[#00266B] hover:underline">
                        View All Applications &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($userRequests as $req)
                        <div class="bg-white rounded-xl border border-blue-200/60 p-3 shadow-xs flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold font-mono text-slate-900 truncate">{{ $req->reference }}</p>
                                <p class="text-xs font-bold text-emerald-600 truncate">&#8358;{{ number_format($req->amount_requested, 2) }}</p>
                                <p class="text-[11px] text-slate-500 truncate">Acct: {{ $req->moniepoint_account_number }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $req->status_badge_class }}">
                                    {{ $req->status_label }}
                                </span>
                                <a href="{{ route('loans.tracking', ['ref' => $req->reference]) }}" class="block text-[10px] font-semibold text-primary hover:underline mt-1">
                                    Track &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Benefits & Terms Feature Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Card 1 -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-[#00266B] flex items-center justify-center mb-4">
                        <i data-lucide="zap" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 font-display">Fast 24 - 48h Disbursement</h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Once your transaction history is evaluated and approved, funds are credited directly to your business wallet account.
                    </p>
                    <ul class="space-y-2 mt-4 text-xs text-slate-600">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>No extensive paperwork</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Verified merchant turnover advantage</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-6">
                    <span class="text-xs font-bold text-[#00266B]">Up to &#8358;50,000,000 Volume</span>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 font-display">Zero Physical Collateral</h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Qualifying business accounts qualify for collateral-free financing based strictly on your historical POS transactions and turnover.
                    </p>
                    <ul class="space-y-2 mt-4 text-xs text-slate-600">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Turnover-backed assessment</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>No land titles or vehicle guarantees</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-6">
                    <span class="text-xs font-bold text-emerald-700">Fair &amp; Transparent Rates</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center mb-4">
                        <i data-lucide="calendar" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 font-display">Flexible Repayment Structures</h3>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Repay comfortably with flexible 1 to 12 month tenures or automated small daily deductions from incoming card sales to protect your working cashflow.
                    </p>
                    <ul class="space-y-2 mt-4 text-xs text-slate-600">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>1 to 12 month tenures available</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Daily, weekly or monthly deductions</span>
                        </li>
                    </ul>
                </div>
                <div class="pt-6">
                    <span class="text-xs font-bold text-purple-700">Merchant Friendly Terms</span>
                </div>
            </div>

        </div>

        <!-- Call to Action Banner (Bottom Link to Dedicated Apply Form) -->
        <div class="bg-gradient-to-r from-[#00266B] via-[#003893] to-[#001F5C] rounded-2xl p-6 sm:p-10 shadow-md text-white flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center sm:text-left">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-[#bae6fd] backdrop-blur-xs">
                    Quick Online Application
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold font-display">
                    Ready to Scale Your Business Operations?
                </h2>
                <p class="text-xs sm:text-sm text-blue-100 max-w-xl">
                    Apply for up to &#8358;50,000,000 in working capital. Click the button below to complete the application form on our secured loan portal.
                </p>
            </div>
            <div class="shrink-0">
                <a href="{{ route('loans.create') }}"
                   class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-[#ffcc00] hover:bg-[#ffe066] text-[#001F5C] text-sm font-extrabold shadow-lg transition transform hover:-translate-y-0.5">
                    <i data-lucide="banknote" class="w-5 h-5 text-[#001F5C]"></i>
                    <span>Apply for Business Loan</span>
                </a>
            </div>
        </div>

    </div>
</x-app-layout>