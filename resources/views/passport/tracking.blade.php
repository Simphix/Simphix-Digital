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

        <!-- Navigation Bar -->
        <div class="flex items-center justify-between">
            <a href="{{ route('passport.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-700 transition">
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
                <p class="text-xs sm:text-sm text-slate-500">
                    Enter your application reference code (e.g. <span class="font-mono text-emerald-700 font-bold">PASS-XXXXXXXX</span>) or applicant phone number.
                </p>
            </div>

            <form action="{{ route('passport.tracking') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text"
                           name="ref"
                           value="{{ $queryRef }}"
                           placeholder="Enter Reference (e.g. PASS-ABC12345) or Phone Number"
                           class="w-full pl-10 text-xs sm:text-sm rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
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
                            <span class="text-xs uppercase font-bold text-slate-400">Application Reference</span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $matchedRequest->application_type === 'new' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $matchedRequest->application_type }}
                            </span>
                        </div>
                        <h2 class="text-xl font-black font-mono text-emerald-800 mt-1">
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

                <!-- Status Progress Pipeline -->
                <div class="space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Processing Timeline</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="p-3 rounded-xl border {{ in_array($matchedRequest->status, ['pending', 'under_review', 'capturing_scheduled', 'completed']) ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-400' }}">
                            <div class="w-7 h-7 mx-auto rounded-full {{ in_array($matchedRequest->status, ['pending', 'under_review', 'capturing_scheduled', 'completed']) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                1
                            </div>
                            <span class="text-xs font-bold block">Submitted</span>
                            <span class="text-[10px] text-slate-500">{{ $matchedRequest->created_at->format('M d, Y') }}</span>
                        </div>

                        <div class="p-3 rounded-xl border {{ in_array($matchedRequest->status, ['under_review', 'capturing_scheduled', 'completed']) ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-400' }}">
                            <div class="w-7 h-7 mx-auto rounded-full {{ in_array($matchedRequest->status, ['under_review', 'capturing_scheduled', 'completed']) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                2
                            </div>
                            <span class="text-xs font-bold block">Under Review</span>
                            <span class="text-[10px] text-slate-500">{{ $matchedRequest->reviewed_at ? $matchedRequest->reviewed_at->format('M d, Y') : 'Processing' }}</span>
                        </div>

                        <div class="p-3 rounded-xl border {{ in_array($matchedRequest->status, ['capturing_scheduled', 'completed']) ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-400' }}">
                            <div class="w-7 h-7 mx-auto rounded-full {{ in_array($matchedRequest->status, ['capturing_scheduled', 'completed']) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                3
                            </div>
                            <span class="text-xs font-bold block">Capturing</span>
                            <span class="text-[10px] text-slate-500">{{ $matchedRequest->capturing_date ? $matchedRequest->capturing_date->format('M d, Y') : 'Pending Schedule' }}</span>
                        </div>

                        <div class="p-3 rounded-xl border {{ $matchedRequest->status === 'completed' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 text-slate-400' }}">
                            <div class="w-7 h-7 mx-auto rounded-full {{ $matchedRequest->status === 'completed' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }} flex items-center justify-center text-xs font-bold mb-1">
                                4
                            </div>
                            <span class="text-xs font-bold block">Completed</span>
                            <span class="text-[10px] text-slate-500">{{ $matchedRequest->resolved_at ? $matchedRequest->resolved_at->format('M d, Y') : 'Awaiting Completion' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Applicant Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-700">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Applicant Biodata</span>
                        <div><strong>Name:</strong> {{ $matchedRequest->full_name }}</div>
                        <div><strong>NIN:</strong> <span class="font-mono">{{ $matchedRequest->nin }}</span></div>
                        <div><strong>Date of Birth:</strong> {{ $matchedRequest->date_of_birth ? $matchedRequest->date_of_birth->format('d/m/Y') : 'N/A' }} ({{ $matchedRequest->sex }})</div>
                        <div><strong>Origin:</strong> {{ $matchedRequest->state_of_origin }}, {{ $matchedRequest->lga }} LGA</div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Contact &amp; Capturing</span>
                        <div><strong>Mobile:</strong> {{ $matchedRequest->mobile_number }}</div>
                        <div><strong>Email:</strong> {{ $matchedRequest->email }}</div>
                        <div><strong>Capturing Center:</strong> {{ $matchedRequest->capturing_center ?? 'To be communicated' }}</div>
                        @if ($matchedRequest->capturing_date)
                            <div><strong>Capturing Date:</strong> <span class="text-emerald-700 font-bold">{{ $matchedRequest->capturing_date->format('M d, Y') }}</span></div>
                        @endif
                    </div>
                </div>

                @if ($matchedRequest->customer_notes)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs">
                        <strong class="block mb-1 font-bold">Immigration Desk Update:</strong>
                        {{ $matchedRequest->customer_notes }}
                    </div>
                @endif

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('passport.show', $matchedRequest) }}"
                       class="text-xs font-bold text-emerald-700 hover:underline flex items-center gap-1">
                        <span>View Complete Application Dossier &amp; Documents</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        @elseif($queryRef)
            <div class="dash-card p-8 rounded-3xl bg-white border border-slate-200 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <i data-lucide="file-question" class="w-6 h-6"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">No Passport Application Found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    We could not find an application matching "<span class="font-mono font-bold">{{ $queryRef }}</span>". Please double-check your reference code or phone number.
                </p>
            </div>
        @endif

        <!-- My Recent Applications -->
        @if ($myRequests && $myRequests->isNotEmpty())
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display">
                        My Passport Applications ({{ $myRequests->total() }})
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
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
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        <a href="{{ route('passport.show', $req) }}" class="text-emerald-700 hover:underline">
                                            {{ $req->reference }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-800">{{ $req->full_name }}</td>
                                    <td class="py-3 px-4 uppercase text-[10px] font-bold">{{ $req->application_type }}</td>
                                    <td class="py-3 px-4 font-mono text-slate-600">{{ $req->nin }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $req->status_badge_class }}">
                                            {{ $req->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $req->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                        <a href="{{ route('passport.show', $req) }}" class="text-slate-600 hover:text-emerald-700 font-bold">
                                            View
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <a href="{{ route('passport.pdf', $req) }}" class="text-emerald-700 hover:underline font-bold">
                                            A4 PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-3">
                    {{ $myRequests->links() }}
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
