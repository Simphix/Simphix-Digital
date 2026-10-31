<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-5xl mx-auto space-y-6">

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

        <!-- Back Button & Tracking Link -->
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.loan-requests.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-[#00266B] transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to All Loan Applications</span>
            </a>

            <a href="{{ route('loans.tracking', ['ref' => $loanRequest->reference]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#00266B] hover:underline">
                <span>View User Tracking Page</span>
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <!-- Main Card Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left 2 Cols: Application Details -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Header Overview -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-mono text-slate-400">Application Reference</span>
                            <h1 class="text-xl sm:text-2xl font-mono font-extrabold text-slate-900 mt-0.5">
                                {{ $loanRequest->reference }}
                            </h1>
                            <p class="text-xs text-slate-500 mt-1">
                                Submitted on {{ $loanRequest->created_at->format('M d, Y \a\t h:i A') }} ({{ $loanRequest->created_at->diffForHumans() }})
                            </p>
                        </div>

                        <div>
                            <span class="inline-block px-3 py-1.5 rounded-full text-xs font-bold border {{ $loanRequest->status_badge_class }}">
                                {{ $loanRequest->status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Underwriting Highlight Box -->
                    <div class="mt-4 p-4 rounded-xl bg-blue-50/70 border border-blue-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#00266B] block">Requested Financing Facility</span>
                            <span class="text-2xl font-black text-emerald-700 mt-0.5 block">
                                &#8358;{{ number_format($loanRequest->amount_requested, 2) }}
                            </span>
                            <span class="text-xs text-slate-600">Preferred Tenure: <strong>{{ $loanRequest->tenure_label }}</strong></span>
                        </div>

                        <div class="sm:text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Moniepoint Account Number</span>
                            <span class="text-lg font-mono font-black text-[#00266B] block bg-white px-3 py-1 rounded-lg border border-blue-200 inline-block mt-1">
                                {{ $loanRequest->moniepoint_account_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Applicant Credentials Grid -->
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Applicant Full Name</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $loanRequest->name }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Phone Number</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 font-mono mt-1 block">{{ $loanRequest->phone }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Email Address</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $loanRequest->email ?? 'Not provided' }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Registered User Account</span>
                            @if ($loanRequest->user)
                                <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">
                                    {{ $loanRequest->user->name }} (ID #{{ $loanRequest->user->id }})
                                </span>
                            @else
                                <span class="text-xs text-slate-400 mt-1 block">Guest / Unlinked</span>
                            @endif
                        </div>
                    </div>

                    @if ($loanRequest->business_name)
                        <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Business / Store Trade Name</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $loanRequest->business_name }}</span>
                        </div>
                    @endif

                    @if ($loanRequest->purpose)
                        <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Loan Purpose & Business Statement</span>
                            <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ $loanRequest->purpose }}</p>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Right Col: Underwriting & Status Management -->
            <div class="space-y-6">

                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                        <i data-lucide="sliders" class="w-5 h-5 text-[#00266B]"></i>
                        <h2 class="text-base font-bold text-slate-900 font-display">Underwriting &amp; Status</h2>
                    </div>

                    <form method="POST" action="{{ route('admin.loan-requests.update-status', $loanRequest) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Status Selection -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Application Status <span class="text-rose-500">*</span>
                            </label>
                            <select name="status" required
                                    class="w-full py-2.5 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B] bg-white font-semibold">
                                @foreach (\App\Models\LoanRequest::STATUSES as $sKey => $sLabel)
                                    <option value="{{ $sKey }}" {{ $loanRequest->status === $sKey ? 'selected' : '' }}>
                                        {{ $sLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Customer Facing Notes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Customer Facing Update
                                <span class="text-[10px] text-slate-400 font-normal block">Displayed to merchant on the tracking page</span>
                            </label>
                            <textarea name="customer_notes" rows="3"
                                      placeholder="e.g. Loan approved! Funds have been scheduled for credit to your Moniepoint wallet..."
                                      class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('customer_notes', $loanRequest->customer_notes) }}</textarea>
                        </div>

                        <!-- Internal Staff Notes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Internal Credit Assessment Notes
                                <span class="text-[10px] text-slate-400 font-normal block">Only visible to administrators</span>
                            </label>
                            <textarea name="admin_notes" rows="3"
                                      placeholder="e.g. 30-day average inflow verified at N3.2M. Credit facility cleared for approval..."
                                      class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('admin_notes', $loanRequest->admin_notes) }}</textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full py-3 px-4 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-[#ffcc00]"></i>
                                <span>Save Assessment &amp; Status</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- History Audit Card -->
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-2 text-xs text-slate-600">
                    <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px] block mb-2">Audit Timestamp</span>
                    <div class="flex items-center justify-between">
                        <span>Submitted:</span>
                        <span class="font-mono text-slate-800">{{ $loanRequest->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Last Updated:</span>
                        <span class="font-mono text-slate-800">{{ $loanRequest->updated_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
