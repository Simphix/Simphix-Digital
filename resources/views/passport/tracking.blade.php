<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-5xl mx-auto space-y-6">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <p class="text-sm font-semibold">{{ session('success') }}</p>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <p class="text-sm font-semibold">{{ session('error') }}</p>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        <!-- Navigation Bar -->
        <div class="flex items-center justify-between">
            <a href="{{ route('passport.index') }}"
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-emerald-700 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Passport Portal</span>
            </a>

            <a href="{{ route('passport.create') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-xs hover:bg-emerald-700 transition">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>New Application</span>
            </a>
        </div>

        <!-- Search Card -->
        <div class="dash-card p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display">
                    Track International Passport Application
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 font-medium">
                    Enter your application reference code (e.g. <span class="font-mono text-emerald-700 font-bold">PASS-XXXXXXXX</span>), NIN, or applicant phone number.
                </p>
            </div>

            <form action="{{ route('passport.tracking') }}" method="GET" autocomplete="off" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text"
                           name="ref"
                           value="{{ $queryRef }}"
                           autocomplete="off"
                           placeholder="Enter Reference (e.g. PASS-ABC12345), NIN, or Phone Number"
                           class="w-full pl-10 text-xs sm:text-sm font-semibold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                </div>
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Track Status</span>
                </button>
            </form>
        </div>

        <!-- Search Result (if matched) -->
        @if ($matchedRequest)
            <div class="dash-card p-6 sm:p-8 rounded-3xl bg-white border border-emerald-200 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs uppercase font-extrabold text-slate-700">Application Reference</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $matchedRequest->application_type === 'new' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $matchedRequest->application_type === 'new' ? 'New Passport' : 'Renewal' }}
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black font-mono text-emerald-800 mt-1">
                            {{ $matchedRequest->reference }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $matchedRequest->status_badge_class }}">
                            {{ $matchedRequest->status_label }}
                        </span>
                        <a href="{{ route('passport.pdf', $matchedRequest) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span>Download A4 PDF</span>
                        </a>
                    </div>
                </div>

                @php
                    $isRejected = in_array($matchedRequest->status, ['rejected', 'cancelled']);

                    $stepRank = match($matchedRequest->status) {
                        'pending'             => 1,
                        'under_review'        => 2,
                        'contacted'           => 2,
                        'capturing_scheduled' => 3,
                        'processing'          => 3,
                        'ready_for_pickup'    => 4,
                        'completed'           => 4,
                        default               => 1,
                    };
                @endphp

                @if ($isRejected)
                    <!-- Rejected Notice -->
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3">
                        <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-bold text-rose-900">Application Declined / Rejected</p>
                            <p class="text-xs text-rose-800 mt-0.5 font-medium leading-relaxed mb-0">
                                {{ $matchedRequest->customer_notes ?: 'This passport request could not be processed. Please contact customer support or re-apply with verified credentials.' }}
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Status Progress Pipeline -->
                    <div class="space-y-2">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">Processing Timeline</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                            <!-- Step 1: Submitted -->
                            <div class="p-3 rounded-xl border {{ $stepRank >= 1 ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-500' }}">
                                <div class="w-7 h-7 mx-auto rounded-full {{ $stepRank >= 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                    ✓
                                </div>
                                <span class="text-xs font-bold block">1. Submitted</span>
                                <span class="text-[10px] text-slate-600 font-medium">{{ $matchedRequest->created_at->format('M d, Y') }}</span>
                            </div>

                            <!-- Step 2: Under Review -->
                            <div class="p-3 rounded-xl border {{ $stepRank >= 2 ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-500' }}">
                                <div class="w-7 h-7 mx-auto rounded-full {{ $stepRank >= 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                    {{ $stepRank > 2 ? '✓' : '2' }}
                                </div>
                                <span class="text-xs font-bold block">2. Under Review</span>
                                <span class="text-[10px] text-slate-600 font-medium">{{ $matchedRequest->reviewed_at ? $matchedRequest->reviewed_at->format('M d, Y') : ($stepRank >= 2 ? 'In Review' : 'Pending') }}</span>
                            </div>

                            <!-- Step 3: Biometrics Capturing -->
                            <div class="p-3 rounded-xl border {{ $stepRank >= 3 ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-500' }}">
                                <div class="w-7 h-7 mx-auto rounded-full {{ $stepRank >= 3 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                    {{ $stepRank > 3 ? '✓' : '3' }}
                                </div>
                                <span class="text-xs font-bold block">3. Biometrics</span>
                                <span class="text-[10px] text-slate-600 font-medium">{{ $matchedRequest->capturing_date ? $matchedRequest->capturing_date->format('M d, Y') : ($stepRank >= 3 ? 'Capturing Scheduled' : 'Awaiting Schedule') }}</span>
                            </div>

                            <!-- Step 4: Completed -->
                            <div class="p-3 rounded-xl border {{ $stepRank >= 4 ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-500' }}">
                                <div class="w-7 h-7 mx-auto rounded-full {{ $stepRank >= 4 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                    {{ $stepRank >= 4 ? '✓' : '4' }}
                                </div>
                                <span class="text-xs font-bold block">4. Ready / Issued</span>
                                <span class="text-[10px] text-slate-600 font-medium">{{ $matchedRequest->resolved_at ? $matchedRequest->resolved_at->format('M d, Y') : ($stepRank >= 4 ? 'Ready' : 'Pending') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Applicant Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-800">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <span class="text-[10px] font-extrabold text-slate-700 uppercase tracking-wider block">Applicant Biodata</span>
                        <div><strong>Name:</strong> {{ $matchedRequest->full_name }}</div>
                        <div><strong>NIN:</strong> <span class="font-mono font-bold">{{ $matchedRequest->nin }}</span></div>
                        <div><strong>Date of Birth:</strong> {{ $matchedRequest->date_of_birth ? $matchedRequest->date_of_birth->format('d/m/Y') : 'N/A' }} ({{ $matchedRequest->sex }})</div>
                        <div><strong>Origin:</strong> {{ $matchedRequest->state_of_origin }}, {{ $matchedRequest->lga }} LGA</div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                        <span class="text-[10px] font-extrabold text-slate-700 uppercase tracking-wider block">Contact &amp; Capturing</span>
                        <div><strong>Mobile:</strong> {{ $matchedRequest->mobile_number }}</div>
                        <div><strong>Email:</strong> {{ $matchedRequest->email }}</div>
                        <div><strong>Capturing Center:</strong> {{ $matchedRequest->capturing_center ?? 'To be communicated' }}</div>
                        @if ($matchedRequest->capturing_date)
                            <div><strong>Capturing Date:</strong> <span class="text-emerald-700 font-bold">{{ $matchedRequest->capturing_date->format('M d, Y') }}</span></div>
                        @endif
                    </div>
                </div>

                @if ($matchedRequest->customer_notes && !$isRejected)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs">
                        <strong class="block mb-1 font-bold">Passport Desk Update:</strong>
                        {{ $matchedRequest->customer_notes }}
                    </div>
                @endif

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <a href="{{ route('passport.show', $matchedRequest) }}"
                       class="text-xs font-bold text-emerald-700 hover:underline flex items-center gap-1">
                        <span>View Complete Application Dossier &amp; Documents</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>

                    <a href="{{ route('passport.pdf', $matchedRequest) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Download A4 PDF</span>
                    </a>
                </div>
            </div>
        @elseif($queryRef)
            <div class="dash-card p-8 rounded-3xl bg-white border border-slate-200 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mx-auto">
                    <i data-lucide="file-question" class="w-6 h-6"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Passport Application Found</h3>
                <p class="text-xs text-slate-700 font-medium max-w-sm mx-auto">
                    We could not find an application matching "<span class="font-mono font-bold text-emerald-800">{{ $queryRef }}</span>". Please double-check your reference code, NIN, or phone number.
                </p>
            </div>
        @endif

        <!-- My Recent Applications -->
        @if ($user && $myRequests->isNotEmpty())
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                            My Passport Applications ({{ method_exists($myRequests, 'total') ? $myRequests->total() : $myRequests->count() }})
                        </h3>
                        <p class="text-xs text-slate-600 font-medium mb-0">Click any reference to track status directly</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 text-slate-800 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Reference</th>
                                <th class="py-3 px-4">Applicant</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4">NIN</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($myRequests as $req)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-emerald-700">
                                        <a href="{{ route('passport.tracking', ['ref' => $req->reference]) }}" class="hover:underline">
                                            {{ $req->reference }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900">{{ $req->full_name }}</td>
                                    <td class="py-3 px-4 uppercase text-[10px] font-bold text-slate-800">{{ $req->application_type }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-700">{{ $req->nin }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $req->status_badge_class }}">
                                            {{ $req->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 whitespace-nowrap">{{ $req->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                        <a href="{{ route('passport.tracking', ['ref' => $req->reference]) }}" class="text-emerald-700 hover:underline font-bold">
                                            Track
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <a href="{{ route('passport.show', $req) }}" class="text-slate-700 hover:text-emerald-700 font-bold">
                                            Dossier
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <a href="{{ route('passport.pdf', $req) }}" class="text-emerald-700 hover:underline font-bold">
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (method_exists($myRequests, 'links'))
                    <div class="pt-3">
                        {{ $myRequests->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>
</x-app-layout>
