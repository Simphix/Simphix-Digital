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

        <!-- Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold mb-2">
                        <i data-lucide="compass" class="w-3.5 h-3.5"></i>
                        <span>Travel Request Tracking</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 font-display tracking-tight">
                        Track Your Travel Application
                    </h1>
                </div>

                <div class="shrink-0">
                    <a href="{{ route('travel.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-[#0056D2] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>New Travel Request</span>
                    </a>
                </div>
            </div>

            <!-- Lookup Search Bar -->
            <form method="GET" action="{{ route('travel.tracking') }}" class="mt-6 pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="ref" value="{{ $queryRef }}"
                           placeholder="Enter your Tracking Reference Code (e.g. TRV-202610-ABC12)..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition shrink-0">
                    Track Application
                </button>
            </form>
        </div>

        @if ($matchedRequest)
            <!-- Matched Request Detailed Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Status Header Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs text-slate-400 font-mono">Reference</span>
                            <span class="text-lg font-bold font-mono text-slate-900">{{ $matchedRequest->reference }}</span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 font-display mt-1">{{ $matchedRequest->package_name }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Submitted on {{ $matchedRequest->created_at->format('M d, Y \a\t h:i A') }}</p>
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
                        'pending'   => ['label' => 'Submitted', 'desc' => 'Request received by desk'],
                        'reviewing' => ['label' => 'Under Review', 'desc' => 'Officer reviewing requirements'],
                        'contacted' => ['label' => 'Contacted', 'desc' => 'Travel specialist in touch'],
                        'approved'  => ['label' => 'Approved / Processing', 'desc' => 'Visa & flights processing'],
                        'completed' => ['label' => 'Completed', 'desc' => 'Travel confirmed & ready'],
                    ];

                    $statusRank = [
                        'pending'   => 1,
                        'reviewing' => 2,
                        'contacted' => 3,
                        'approved'  => 4,
                        'completed' => 5,
                        'cancelled' => 0,
                    ];

                    $currentRank = $statusRank[$matchedRequest->status] ?? 1;
                    $isCancelled = $matchedRequest->status === 'cancelled';
                @endphp

                @if ($isCancelled)
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3">
                        <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs sm:text-sm font-bold">This travel request was cancelled or declined.</p>
                            @if ($matchedRequest->customer_notes)
                                <p class="text-xs mt-1 text-rose-700 leading-relaxed">{{ $matchedRequest->customer_notes }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Application Progress</p>
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                            @foreach ($steps as $key => $stepInfo)
                                @php
                                    $stepRank = $statusRank[$key];
                                    $isPast = $currentRank >= $stepRank;
                                    $isCurrent = $currentRank === $stepRank;
                                @endphp
                                <div class="p-3 rounded-xl border {{ $isCurrent ? 'bg-primary/5 border-primary text-primary' : ($isPast ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-400') }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $isPast ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                                            @if ($isPast && !$isCurrent)
                                                ✓
                                            @else
                                                {{ $stepRank }}
                                            @endif
                                        </div>
                                        <span class="text-xs font-bold font-display {{ $isCurrent ? 'text-primary' : ($isPast ? 'text-slate-900' : 'text-slate-400') }}">
                                            {{ $stepInfo['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] {{ $isCurrent ? 'text-primary/80' : ($isPast ? 'text-slate-600' : 'text-slate-400') }} leading-tight">
                                        {{ $stepInfo['desc'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Application Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Lead Traveler</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $matchedRequest->name }}</span>
                        <span class="text-slate-500 font-mono">{{ $matchedRequest->phone }}</span>
                        @if ($matchedRequest->email)
                            <span class="text-slate-500 block truncate">{{ $matchedRequest->email }}</span>
                        @endif
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Package &amp; Category</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $matchedRequest->package_type_label }}</span>
                        <span class="text-slate-500">{{ $matchedRequest->package_name }}</span>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Travel Party &amp; Date</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $matchedRequest->number_of_travelers }} Traveler(s)</span>
                        <span class="text-slate-500">Departure: {{ $matchedRequest->departure_date ?: 'Flexible' }}</span>
                    </div>
                </div>

                @if ($matchedRequest->notes)
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-xs">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Your Special Instructions</span>
                        <p class="text-slate-600 leading-relaxed">{{ $matchedRequest->notes }}</p>
                    </div>
                @endif

                @if ($matchedRequest->customer_notes)
                    <!-- Updates / Response from Travel Officer -->
                    <div class="bg-sky-50 rounded-xl p-4 border border-sky-100 text-xs">
                        <span class="text-[10px] font-bold text-[#0056D2] uppercase tracking-wider flex items-center gap-1.5 mb-1">
                            <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                            <span>Message from Travel Officer</span>
                        </span>
                        <p class="text-slate-700 leading-relaxed font-medium">{{ $matchedRequest->customer_notes }}</p>
                    </div>
                @endif
            </div>
        @elseif ($queryRef !== '')
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <i data-lucide="file-question" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                <h3 class="text-base font-bold text-slate-800">No application found for reference "{{ $queryRef }}"</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Please ensure the reference code was typed correctly, or check your registered travel applications below.
                </p>
            </div>
        @endif

        @if ($user && $myRequests->isNotEmpty())
            <!-- All Requests Belonging to User -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Your Travel Application History</h2>
                        <p class="text-xs text-slate-500">List of all travel requests submitted under your account.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                                <th class="py-3 px-3">Reference</th>
                                <th class="py-3 px-3">Package</th>
                                <th class="py-3 px-3">Category</th>
                                <th class="py-3 px-3">Travelers</th>
                                <th class="py-3 px-3">Date Submitted</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($myRequests as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $item->reference }}</td>
                                    <td class="py-3 px-3 font-medium text-slate-800">{{ $item->package_name }}</td>
                                    <td class="py-3 px-3 text-slate-600">{{ $item->package_type_label }}</td>
                                    <td class="py-3 px-3 text-slate-700">{{ $item->number_of_travelers }}</td>
                                    <td class="py-3 px-3 text-slate-500">{{ $item->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $item->status_badge_class }}">
                                            {{ $item->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="{{ route('travel.tracking', ['ref' => $item->reference]) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                            <span>View Details</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    {{ $myRequests->links() }}
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
