<x-app-layout>
    <title>SmartSIM - Enterprise Partner Fulfillment</title>

    <div class="max-w-6xl mx-auto space-y-6" x-data="{ currentTab: 'pending' }">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="package-check" class="w-5 h-5"></i>
                    </div>
                    Enterprise Partner Fulfillment
                </h1>
                <p class="text-sm text-slate-500 mt-1">A paid order is a request — decide how many of each SIM to actually grant, up to what was ordered and what's in stock.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0 shadow-inner">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0 shadow-inner">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 flex gap-1">
            <button type="button" @click="currentTab = 'pending'" :class="currentTab === 'pending' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="inbox" class="w-4 h-4"></i> Pending Orders ({{ count($pending) }})
            </button>
            <button type="button" @click="currentTab = 'resolved'" :class="currentTab === 'resolved' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="check-square" class="w-4 h-4"></i> Fulfilled Orders
            </button>
        </div>

        <!-- Tab: Pending -->
        <div x-show="currentTab === 'pending'" class="space-y-4">
            @forelse ($pending as $application)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ $application->user->business_name }}</p>
                            <p class="text-xs text-slate-400">{{ $application->user->email }} &middot; paid {{ $application->paid_at?->diffForHumans() }}</p>
                        </div>
                        <span class="text-sm font-bold text-primary">Total paid: ₦{{ number_format($application->total_amount_snapshot, 2) }}</span>
                    </div>

                    <form method="POST" action="{{ route('admin.enterprise-partners.fulfillment.fulfill', $application) }}" class="space-y-3">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                        <th class="py-2.5">Item</th>
                                        <th class="py-2.5">Requested</th>
                                        <th class="py-2.5">Live Stock</th>
                                        <th class="py-2.5">Grant Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($application->items as $item)
                                        @php $stock = $stockMatrix[$item->sim_category . '|' . $item->provider] ?? 0; @endphp
                                        <tr class="border-b border-slate-50">
                                            <td class="py-3 font-semibold text-slate-700">{{ $item->sim_category }} ({{ strtoupper($item->provider) }})</td>
                                            <td class="py-3">{{ $item->quantity }}</td>
                                            <td class="py-3 {{ $stock < $item->quantity ? 'text-rose-600 font-bold' : 'text-slate-600' }}">{{ $stock }}</td>
                                            <td class="py-3">
                                                <input type="number" name="quantities[{{ $item->id }}]" min="0"
                                                       max="{{ min($item->quantity, $stock) }}"
                                                       value="{{ min($item->quantity, $stock) }}"
                                                       class="w-20 rounded-lg border-slate-200 text-xs font-semibold py-1.5">
                                            </td>
                                        </tr>
                                        @if ($stock < $item->quantity)
                                            <tr>
                                                <td colspan="4" class="pb-2 text-[11px] text-rose-500">Only {{ $stock }} in stock — this EP will receive less than requested unless more stock is added first.</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors">Fulfill Order</button>
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400 font-semibold text-sm">No orders awaiting fulfillment.</div>
            @endforelse
        </div>

        <!-- Tab: Resolved -->
        <div x-show="currentTab === 'resolved'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Fulfillment History</h3>
            <div class="space-y-4">
                @forelse ($resolved as $application)
                    <div class="border border-slate-100 rounded-lg p-4 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="font-bold text-slate-700">{{ $application->user->business_name }} ({{ $application->user->email }})</span>
                            <span class="text-slate-400">Fulfilled by {{ $application->fulfilledBy?->first_name ?? '—' }} &middot; {{ $application->fulfilled_at?->format('M d, Y H:i') }}</span>
                        </div>
                        <table class="w-full text-left border-collapse text-xs">
                            <tbody>
                                @foreach ($application->items as $item)
                                    <tr class="border-t border-slate-50">
                                        <td class="py-1.5 font-semibold text-slate-700">{{ $item->sim_category }} ({{ strtoupper($item->provider) }})</td>
                                        <td class="py-1.5 {{ $item->fulfilled_quantity < $item->quantity ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold' }}">
                                            {{ $item->fulfilled_quantity }} / {{ $item->quantity }} granted
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="py-8 text-center text-slate-400 font-semibold text-sm">No fulfilled orders yet.</p>
                @endforelse
            </div>
            {{ $resolved->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
