<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-3xl mx-auto space-y-6">

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

        <!-- Back Button -->
        <div class="flex items-center justify-between">
            <a href="{{ route('loans.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-[#00266B] transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Business Loans Overview</span>
            </a>

            <a href="{{ route('loans.tracking') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#00266B] hover:underline">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                <span>Track Application</span>
            </a>
        </div>

        <!-- Prominent Disclaimer (Kept Intact) -->
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

        <!-- Application Form Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="border-b border-slate-100 pb-5 mb-6 flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#00266B] flex items-center justify-center shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display tracking-tight">
                        Apply for Business Loan
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Complete your business and account credentials for underwriting assessment.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('loans.store') }}" autocomplete="off" class="space-y-6">
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
                            Full Name (as on Account) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" required autocomplete="off"
                               value="{{ old('name', $user?->name) }}"
                               placeholder="e.g. Adebayo Ibrahim"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="phone" required autocomplete="off"
                               value="{{ old('phone', $user?->phone) }}"
                               placeholder="e.g. 08012345678"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Email Address (Optional) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Email Address <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="email" name="email" autocomplete="off"
                               value="{{ old('email', $user?->email) }}"
                               placeholder="e.g. merchant@example.com"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Moniepoint Account Number (Required) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Moniepoint Account Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="moniepoint_account_number" required autocomplete="off"
                               value="{{ old('moniepoint_account_number') }}"
                               placeholder="10-digit Moniepoint account"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        <p class="text-[10px] text-slate-400 mt-1">Must be an active Moniepoint merchant account.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Amount Requested -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Amount Requested (&#8358;) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="amount_requested" required min="1000" max="50000000" step="1000"
                               value="{{ old('amount_requested', '500000') }}"
                               placeholder="e.g. 500000"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        <p class="text-[10px] text-slate-400 mt-1">Min: &#8358;1,000 &bull; Max: &#8358;50,000,000</p>
                    </div>

                    <!-- Loan Tenure -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Preferred Repayment Tenure
                        </label>
                        <select name="loan_tenure"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B] bg-white">
                            @foreach (\App\Models\LoanRequest::TENURES as $k => $label)
                                <option value="{{ $k }}" {{ old('loan_tenure', '3_months') === $k ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Business Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Registered Business / Store Name <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input type="text" name="business_name" autocomplete="off"
                           value="{{ old('business_name') }}"
                           placeholder="e.g. Adebayo Enterprise"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                </div>

                <!-- Loan Purpose / Business Summary -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Purpose of Facility / Working Capital Needs <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <textarea name="purpose" rows="3"
                              placeholder="Briefly describe what inventory, expansion or liquidity need this facility is for..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('purpose') }}</textarea>
                </div>

                <!-- Submission Action -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-slate-400">
                        Submitting generates a tracking reference code.
                    </p>
                    <button type="submit"
                            class="w-full sm:w-auto px-8 py-3 rounded-xl bg-[#00266B] hover:bg-[#001b4c] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        Submit Loan Application
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
