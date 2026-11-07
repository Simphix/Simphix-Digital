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

        <!-- Disclaimer Header -->
        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-slate-800 text-xs flex items-center gap-3">
            <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0"></i>
            <span><strong>Disclaimer:</strong> Only Moniepoint business owners are eligible for business loans. Loan statuses reflect verification against Moniepoint transaction records.</span>
        </div>

        <!-- Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-[#00266B] text-xs font-bold mb-2">
                        <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                        <span>Business Loan Tracking Desk</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 font-display tracking-tight">
                        Track Business Loan Application
                    </h1>
                </div>

                <div class="shrink-0">
                    <a href="{{ route('loans.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-[#ffcc00]"></i>
                        <span>New Loan Request</span>
                    </a>
                </div>
            </div>

            <!-- Lookup Search Bar -->
            <form method="GET" action="{{ route('loans.tracking') }}" class="mt-6 pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="ref" value="{{ $queryRef }}"
                           placeholder="Enter Tracking Reference (e.g. MPLOAN-202610-XYZ12)..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                </div>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition shrink-0">
                    Track Application
                </button>
            </form>
        </div>

        @if ($matchedRequest)
            <!-- Matched Order Detail Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Status Header Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs text-slate-400 font-mono">Reference</span>
                            <span class="text-lg font-bold font-mono text-slate-900">{{ $matchedRequest->reference }}</span>
                        </div>
                        <h2 class="text-2xl font-extrabold text-emerald-700 font-display mt-1">
                            &#8358;{{ number_format($matchedRequest->amount_requested, 2) }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Applied on {{ $matchedRequest->created_at->format('M d, Y \a\t h:i A') }}</p>
                    </div>

                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $matchedRequest->status_badge_class }}">
                            <span class="w-2 h-2 rounded-full bg-current"></span>
                            <span>{{ $matchedRequest->status_label }}</span>
                        </span>
                    </div>
                </div>

                <!-- Progress Stepper -->
                @php
                    $steps = [
                        'pending'      => ['label' => 'Processing', 'desc' => 'Logged into assessment pipeline'],
                        'under_review' => ['label' => 'Credit Review', 'desc' => 'Turnover analysis in progress'],
                        'contacted'    => ['label' => 'Customer Will Be Contacted', 'desc' => 'Credit officer will reach out'],
                        'approved'     => ['label' => 'Approved', 'desc' => 'Loan facility sanctioned'],
                        'disbursed'    => ['label' => 'Disbursed', 'desc' => 'Funds credited to Moniepoint wallet'],
                    ];

                    $rankMap = [
                        'pending'      => 1,
                        'under_review' => 2,
                        'contacted'    => 3,
                        'approved'     => 4,
                        'disbursed'    => 5,
                        'declined'     => 0,
                    ];

                    $currentRank = $rankMap[$matchedRequest->status] ?? 1;
                    $isDeclined = $matchedRequest->status === 'declined';
                @endphp

                @if ($isDeclined)
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-start gap-3">
                        <i data-lucide="x-circle" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-bold">Application Declined</p>
                            <p class="mt-0.5 text-xs text-rose-700">
                                This loan request could not be approved at this time. Please ensure your Moniepoint POS account maintains active turnover, or contact support for reconsideration.
                            </p>
                        </div>
                    </div>
                @else
                    <div class="py-4">
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 sm:gap-2 relative">
                            @php $i = 1; @endphp
                            @foreach ($steps as $key => $meta)
                                @php
                                    $isPassed = $currentRank >= $i;
                                    $isCurrent = $currentRank === $i;
                                @endphp
                                <div class="flex sm:flex-col items-center sm:text-center gap-3 sm:gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition
                                        {{ $isPassed ? 'bg-[#00266B] text-white shadow-xs' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                        @if ($isPassed && !$isCurrent)
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        @else
                                            <span>{{ $i }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold {{ $isCurrent ? 'text-[#00266B]' : ($isPassed ? 'text-slate-800' : 'text-slate-400') }}">
                                            {{ $meta['label'] }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 hidden sm:block mt-0.5 leading-tight">
                                            {{ $meta['desc'] }}
                                        </p>
                                    </div>
                                </div>
                                @php $i++; @endphp
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Application Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-slate-100">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Applicant Name</span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $matchedRequest->name }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Phone Number</span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $matchedRequest->phone }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Email Address</span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $matchedRequest->email ?? 'Not provided' }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200/60">
                        <span class="text-[11px] font-semibold text-[#00266B] uppercase tracking-wider block">Moniepoint Account</span>
                        <span class="text-xs sm:text-sm font-black font-mono text-[#00266B] mt-1 block">{{ $matchedRequest->moniepoint_account_number }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Business Name</span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $matchedRequest->business_name ?? 'Not specified' }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Preferred Tenure</span>
                        <span class="text-xs sm:text-sm font-bold text-slate-800 mt-1 block">{{ $matchedRequest->tenure_label }}</span>
                    </div>
                </div>

                @if ($matchedRequest->purpose)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-700">
                        <span class="font-bold text-slate-900 block mb-1">Business Loan Purpose:</span>
                        <p class="leading-relaxed">{{ $matchedRequest->purpose }}</p>
                    </div>
                @endif

                @if ($matchedRequest->customer_notes)
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs">
                        <div class="flex items-center gap-2 font-bold mb-1">
                            <i data-lucide="message-square" class="w-4 h-4 text-[#00266B]"></i>
                            <span>Update from Credit Operations Desk:</span>
                        </div>
                        <p class="leading-relaxed">{{ $matchedRequest->customer_notes }}</p>
                    </div>
                @endif
            </div>
        @elseif ($queryRef !== '')
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center shadow-sm">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="search-x" class="w-6 h-6"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Application Found</h3>
                <p class="text-xs text-slate-500 mt-1">We couldn't find any loan application with reference "{{ $queryRef }}". Please verify your reference and try again.</p>
            </div>
        @endif

        @if ($myRequests && $myRequests->isNotEmpty())
            <!-- My Previous Applications Table -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-[#00266B]"></i>
                        <h2 class="text-base font-bold text-slate-900 font-display">Your Loan Applications History</h2>
                    </div>
                    <span class="text-xs text-slate-400">{{ $myRequests->total() }} Total Applications</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-y border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Reference</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Moniepoint Account</th>
                                <th class="py-3 px-4">Tenure</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($myRequests as $req)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-800">{{ $req->reference }}</td>
                                    <td class="py-3 px-4 font-bold text-emerald-700">&#8358;{{ number_format($req->amount_requested, 2) }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-600">{{ $req->moniepoint_account_number }}</td>
                                    <td class="py-3 px-4 text-slate-600">{{ $req->tenure_label }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $req->status_badge_class }}">
                                            {{ $req->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">{{ $req->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ route('loans.tracking', ['ref' => $req->reference]) }}"
                                           class="text-[#00266B] hover:underline font-bold text-xs">
                                            View &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $myRequests->links() }}
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
