<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-7xl mx-auto space-y-6" x-data="{
        showRequestModal: false,
        amount: '{{ old('amount_requested', '500000') }}',
        tenure: '{{ old('loan_tenure', '3_months') }}',
        openModal() {
            this.showRequestModal = true;
        }
    }">

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

        <!-- Prominent Moniepoint Business Owner Disclaimer -->
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
                    <strong>Notice:</strong> Only registered and active <strong>Moniepoint business owners</strong> (merchants with an active Moniepoint business account / POS terminal) can request for working capital loans. All loan requests are audited against transaction turnover history on your Moniepoint business account.
                </p>
            </div>
        </div>

        <!-- Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#00266B] flex items-center justify-center p-2.5 shrink-0 shadow-sm">
                        <img src="{{ asset('assets/images/moniepoint-logo.png') }}" alt="Moniepoint" class="w-full h-auto object-contain brightness-0 invert">
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-[#00266B] text-xs font-bold mb-2">
                            <span>SME & Merchant Growth Financing</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-display tracking-tight">
                            Moniepoint Business Working Capital Loans
                        </h1>
                        <p class="text-slate-500 text-sm mt-1 max-w-2xl">
                            Fast-track your inventory expansion, shop restocking, or operational cashflow with competitive interest rates and flexible daily turnover deductions.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('loans.tracking') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-sm transition">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        <span>Track My Loan Request</span>
                    </a>
                    <button type="button" @click="openModal()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001b4c] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="banknote" class="w-4 h-4 text-[#ffcc00]"></i>
                        <span>Apply for Loan Now</span>
                    </button>
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
                        Once your Moniepoint transaction history is evaluated and approved, funds are credited directly to your Moniepoint business wallet.
                    </p>
                    <ul class="space-y-2 mt-4 text-xs text-slate-600">
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>No extensive paperwork</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Verified Moniepoint merchant advantage</span>
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
                        Qualifying Moniepoint business accounts qualify for collateral-free financing based strictly on your historical POS transactions and turnover.
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
                    <span class="text-xs font-bold text-emerald-700">Fair & Transparent Rates</span>
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

        <!-- Inline Application Form Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-8">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-[#00266B]/10 text-[#00266B] mb-2">
                        Online Application
                    </span>
                    <h2 class="text-2xl font-extrabold text-slate-900 font-display">
                        Apply for Moniepoint Business Loan
                    </h2>
                    <p class="text-slate-500 text-xs sm:text-sm mt-1">
                        Fill in your business and account credentials. Note that this facility is strictly for Moniepoint merchants.
                    </p>
                </div>

                <form method="POST" action="{{ route('loans.store') }}" class="space-y-6">
                    @csrf

                    <!-- Disclaimer Notice inside Form -->
                    <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                        <span><strong>Disclaimer:</strong> Only Moniepoint business owners can request for loan. Your Moniepoint account number will be verified.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Full Name (as on Moniepoint Account) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" required
                                   value="{{ old('name', $user->name ?? '') }}"
                                   placeholder="e.g. Adebayo Ibrahim"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Phone Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="phone" required
                                   value="{{ old('phone', $user->phone ?? '') }}"
                                   placeholder="e.g. 08012345678"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        </div>

                        <!-- Email (Optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Email Address <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="email" name="email"
                                   value="{{ old('email', $user->email ?? '') }}"
                                   placeholder="e.g. adebayo@example.com"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        </div>

                        <!-- Moniepoint Account Number (Required) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Moniepoint Wallet / Account Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="moniepoint_account_number" required
                                   value="{{ old('moniepoint_account_number') }}"
                                   placeholder="e.g. 8123456789 (Moniepoint MFB)"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                            <p class="text-[11px] text-slate-400 mt-1">Must be your active Moniepoint business account number.</p>
                        </div>

                        <!-- Amount Requesting (Required) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Loan Amount Requesting (&#8358;) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" name="amount_requested" min="1000" max="50000000" step="1000" required
                                   value="{{ old('amount_requested', '500000') }}"
                                   placeholder="e.g. 500000"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                            <p class="text-[11px] text-slate-400 mt-1">Range: &#8358;1,000 to &#8358;50,000,000</p>
                        </div>

                        <!-- Loan Tenure -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Preferred Repayment Tenure
                            </label>
                            <select name="loan_tenure"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                                @foreach (\App\Models\LoanRequest::TENURES as $k => $v)
                                    <option value="{{ $k }}" {{ old('loan_tenure', '3_months') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Business Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Registered Business / Store Name <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" name="business_name"
                               value="{{ old('business_name') }}"
                               placeholder="e.g. Adebayo Supermarket & POS Venture"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Loan Purpose -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Purpose of Loan / Notes <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <textarea name="purpose" rows="3"
                                  placeholder="e.g. Inventory restocking for festive season and purchasing additional shop stock..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('purpose') }}</textarea>
                    </div>

                    <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                        <div class="text-xs text-slate-500">
                            By clicking submit, you confirm that you are an active Moniepoint business owner.
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-md transition">
                            <i data-lucide="send" class="w-4 h-4 text-[#ffcc00]"></i>
                            <span>Submit Loan Application</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
