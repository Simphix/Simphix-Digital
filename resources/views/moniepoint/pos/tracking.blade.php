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
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-[#00266B] text-xs font-bold mb-2">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                        <span>POS Dispatch Tracking</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 font-display tracking-tight">
                        Track POS Terminal Request
                    </h1>
                </div>

                <div class="shrink-0">
                    <a href="{{ route('moniepoint.pos.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-[#ffcc00]"></i>
                        <span>New POS Request</span>
                    </a>
                </div>
            </div>

            <!-- Lookup Search Bar -->
            <form method="GET" action="{{ route('moniepoint.pos.tracking') }}" class="mt-6 pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="ref" value="{{ $queryRef }}"
                           placeholder="Enter Tracking Reference (e.g. MPOS-202610-ABC12)..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition shrink-0">
                    Track POS Order
                </button>
            </form>
        </div>

        @if ($matchedRequest)
            <!-- Matched Order Detail Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Status Header Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        @php
                            $matchedPosImg = match($matchedRequest->pos_type) {
                                'mini_pos' => asset('assets/images/moniepoint-mini-pos.png'),
                                'traditional_pos' => asset('assets/images/moniepoint-traditional-pos.png'),
                                default => asset('assets/images/moniepoint-pos.png'),
                            };
                        @endphp
                        <div class="w-14 h-14 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center p-1.5 shrink-0">
                            <img src="{{ $matchedPosImg }}" alt="{{ $matchedRequest->pos_type_label }}" class="max-h-full max-w-full object-contain">
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-xs text-slate-400 font-mono">Reference</span>
                                <span class="text-lg font-bold font-mono text-slate-900">{{ $matchedRequest->reference }}</span>
                            </div>
                            <h2 class="text-xl font-bold text-slate-900 font-display mt-0.5">{{ $matchedRequest->pos_type_label }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Requested on {{ $matchedRequest->created_at->format('M d, Y \a\t h:i A') }}</p>
                        </div>
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
                        'pending'      => ['label' => 'Order Received', 'desc' => 'Request logged in dispatch queue'],
                        'under_review' => ['label' => 'Processing', 'desc' => 'Hardware configured & serialized'],
                        'contacted'    => ['label' => 'Customer Will Be Contacted', 'desc' => 'Merchant officer will reach out'],
                        'dispatched'   => ['label' => 'Dispatched', 'desc' => 'In courier transit to address'],
                        'delivered'    => ['label' => 'Delivered & Active', 'desc' => 'Activated and ready for transactions'],
                    ];

                    $rankMap = [
                        'pending'      => 1,
                        'under_review' => 2,
                        'contacted'    => 3,
                        'dispatched'   => 4,
                        'delivered'    => 5,
                        'declined'     => 0,
                    ];

                    $currentRank = $rankMap[$matchedRequest->status] ?? 1;
                    $isDeclined = $matchedRequest->status === 'declined';
                @endphp

                @if ($isDeclined)
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3">
                        <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs sm:text-sm font-bold">This POS terminal request was declined or cancelled.</p>
                            @if ($matchedRequest->customer_notes)
                                <p class="text-xs mt-1 text-rose-700 leading-relaxed">{{ $matchedRequest->customer_notes }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Fulfillment Pipeline</p>
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                            @foreach ($steps as $key => $info)
                                @php
                                    $stepRank = $rankMap[$key];
                                    $isPast = $currentRank >= $stepRank;
                                    $isCurrent = $currentRank === $stepRank;
                                @endphp
                                <div class="p-3 rounded-xl border {{ $isCurrent ? 'bg-blue-50/70 border-[#00266B] text-[#00266B]' : ($isPast ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800' : 'bg-slate-50 border-slate-200 text-slate-400') }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $isPast ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                                            @if ($isPast && !$isCurrent)
                                                ✓
                                            @else
                                                {{ $stepRank }}
                                            @endif
                                        </div>
                                        <span class="text-xs font-bold font-display {{ $isCurrent ? 'text-[#00266B]' : ($isPast ? 'text-slate-900' : 'text-slate-400') }}">
                                            {{ $info['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] {{ $isCurrent ? 'text-[#00266B]/80' : ($isPast ? 'text-slate-600' : 'text-slate-400') }} leading-tight">
                                        {{ $info['desc'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Order Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Applicant &amp; Phone</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $matchedRequest->name }}</span>
                        <span class="text-slate-500 font-mono">{{ $matchedRequest->phone }}</span>
                        <span class="text-slate-500 block truncate">{{ $matchedRequest->email }}</span>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Terminal Model &amp; Quantity</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $matchedRequest->pos_type_label }}</span>
                        <span class="text-slate-500 font-medium">{{ $matchedRequest->quantity }} Unit(s)</span>
                        @if ($matchedRequest->business_name)
                            <span class="text-slate-500 block">Business: {{ $matchedRequest->business_name }}</span>
                        @endif
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Delivery Destination</span>
                        <p class="text-slate-700 mt-0.5 leading-relaxed">{{ $matchedRequest->delivery_address ?: 'Contact officer will confirm address' }}</p>
                    </div>
                </div>

                @if ($matchedRequest->customer_notes)
                    <!-- Updates / Response from Dispatch Team -->
                    <div class="bg-blue-50/70 rounded-xl p-4 border border-blue-200 text-xs">
                        <span class="text-[10px] font-bold text-[#00266B] uppercase tracking-wider flex items-center gap-1.5 mb-1">
                            <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                            <span>Update from Moniepoint Logistics Desk</span>
                        </span>
                        <p class="text-slate-800 leading-relaxed font-medium">{{ $matchedRequest->customer_notes }}</p>
                    </div>
                @endif
            </div>
        @elseif ($queryRef !== '')
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <i data-lucide="file-question" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                <h3 class="text-base font-bold text-slate-800">No POS request found for reference "{{ $queryRef }}"</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Please ensure the reference code was typed correctly, or view your history below.
                </p>
            </div>
        @endif

        @if ($user && $myRequests->isNotEmpty())
            <!-- All Requests Belonging to User -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Your Moniepoint POS Requests</h2>
                        <p class="text-xs text-slate-500">History of all terminal orders logged under your account.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                                <th class="py-3 px-3">Reference</th>
                                <th class="py-3 px-3">Terminal Type</th>
                                <th class="py-3 px-3">Qty</th>
                                <th class="py-3 px-3">Date</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($myRequests as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $item->reference }}</td>
                                    <td class="py-3 px-3 font-medium text-slate-800">{{ $item->pos_type_label }}</td>
                                    <td class="py-3 px-3 text-slate-700">{{ $item->quantity }}</td>
                                    <td class="py-3 px-3 text-slate-500">{{ $item->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $item->status_badge_class }}">
                                            {{ $item->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="{{ route('moniepoint.pos.tracking', ['ref' => $item->reference]) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                            <span>Track Order</span>
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
