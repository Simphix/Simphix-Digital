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
                                        <th class="py-2.5">Selected SIMs</th>
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
                                                <button type="button"
                                                        onclick="openFulfillmentItemPicker({{ $item->id }}, '{{ $item->sim_category }}', '{{ $item->provider }}', {{ $item->quantity }}, '{{ route('admin.sim-plan.available-sims') }}', '{{ route('admin.enterprise-partners.fulfillment.items.resolve-numbers', $item) }}')"
                                                        class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold px-3 py-1.5 rounded-lg transition-colors">
                                                    <i data-lucide="list-checks" class="w-3.5 h-3.5"></i> Select SIMs
                                                </button>
                                                <span id="item-{{ $item->id }}-count" class="block text-[11px] text-slate-400 mt-1">0 selected</span>
                                                <div id="item-{{ $item->id }}-sim-ids"></div>
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

    @push('scripts')
        <script>
            // Per-item selections live only in the DOM (hidden inputs inside
            // #item-{id}-sim-ids) until the whole application's "Fulfill
            // Order" form is submitted — this modal never submits on its own,
            // unlike SimPlanController's openApproveRequestModal().
            function openFulfillmentItemPicker(itemId, category, provider, maxSelectable, availableSimsUrl, resolveNumbersUrl) {
                fetch(`${availableSimsUrl}?category=${encodeURIComponent(category)}&provider=${encodeURIComponent(provider)}`)
                    .then(r => r.json())
                    .then(sims => {
                        const optionsHtml = sims.length
                            ? sims.map(s => `
                                <label class="flex items-center gap-2 text-xs font-medium py-1">
                                    <input type="checkbox" class="fulfill-sim-cb" value="${s.id}"> ${s.number}
                                </label>
                            `).join('')
                            : '<p class="text-xs text-slate-400">No SIMs currently available for this category/network.</p>';

                        let activeTab = 'list';

                        Swal.fire({
                            title: `Select up to ${maxSelectable} SIM(s)`,
                            html: `
                                <div class="text-left">
                                    <div class="flex gap-2 mb-3 border-b border-slate-200">
                                        <button type="button" id="fulfill-tab-list" class="px-3 py-1.5 text-xs font-bold border-b-2 border-[#0056D2] text-[#0056D2]">Pick from List</button>
                                        <button type="button" id="fulfill-tab-numbers" class="px-3 py-1.5 text-xs font-bold border-b-2 border-transparent text-slate-400">Enter Numbers</button>
                                    </div>
                                    <div id="fulfill-panel-list" class="max-h-64 overflow-y-auto space-y-1">${optionsHtml}</div>
                                    <div id="fulfill-panel-numbers" class="hidden">
                                        <textarea id="fulfill-numbers-input" rows="6" class="w-full text-xs border border-slate-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#0056D2]" placeholder="08030000000, 08031111111&#10;or one per line"></textarea>
                                        <p class="text-[11px] text-slate-400 mt-1">Separate numbers by comma or new line. Each must be an existing, unassigned ${category} / ${provider.toUpperCase()} SIM.</p>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#0056D2',
                            confirmButtonText: 'Use Selection',
                            didOpen: () => {
                                const listBtn = document.getElementById('fulfill-tab-list');
                                const numbersBtn = document.getElementById('fulfill-tab-numbers');
                                const listPanel = document.getElementById('fulfill-panel-list');
                                const numbersPanel = document.getElementById('fulfill-panel-numbers');

                                listBtn.addEventListener('click', () => {
                                    activeTab = 'list';
                                    listBtn.classList.add('border-[#0056D2]', 'text-[#0056D2]');
                                    listBtn.classList.remove('border-transparent', 'text-slate-400');
                                    numbersBtn.classList.add('border-transparent', 'text-slate-400');
                                    numbersBtn.classList.remove('border-[#0056D2]', 'text-[#0056D2]');
                                    listPanel.classList.remove('hidden');
                                    numbersPanel.classList.add('hidden');
                                });

                                numbersBtn.addEventListener('click', () => {
                                    activeTab = 'numbers';
                                    numbersBtn.classList.add('border-[#0056D2]', 'text-[#0056D2]');
                                    numbersBtn.classList.remove('border-transparent', 'text-slate-400');
                                    listBtn.classList.add('border-transparent', 'text-slate-400');
                                    listBtn.classList.remove('border-[#0056D2]', 'text-[#0056D2]');
                                    numbersPanel.classList.remove('hidden');
                                    listPanel.classList.add('hidden');
                                });
                            },
                            preConfirm: () => {
                                if (activeTab === 'list') {
                                    const checked = Array.from(document.querySelectorAll('.fulfill-sim-cb:checked')).map(el => el.value);
                                    if (checked.length > maxSelectable) {
                                        Swal.showValidationMessage(`You can select at most ${maxSelectable} SIM(s) for this item.`);
                                        return false;
                                    }
                                    // Unlike SimRequest approval, 0 is a valid
                                    // selection here — partial/zero fulfillment
                                    // is an intentional, supported outcome.
                                    return { ids: checked, errors: [] };
                                }

                                const numbers = document.getElementById('fulfill-numbers-input').value;
                                if (!numbers.trim()) {
                                    // An empty textarea on this tab just means
                                    // "grant nothing for this item" — allow it.
                                    return { ids: [], errors: [] };
                                }

                                return fetch(resolveNumbersUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ numbers }),
                                })
                                    .then(r => r.json())
                                    .then(data => {
                                        if (data.resolved.length === 0 && data.errors.length) {
                                            Swal.showValidationMessage(data.errors.join('<br>'));
                                            return false;
                                        }
                                        return { ids: data.resolved.map(s => s.id), errors: data.errors };
                                    })
                                    .catch(() => {
                                        Swal.showValidationMessage('Could not validate numbers. Please try again.');
                                        return false;
                                    });
                            }
                        }).then((result) => {
                            if (!result.isConfirmed) return;
                            const { ids, errors } = result.value;

                            const applySelection = () => {
                                const container = document.getElementById(`item-${itemId}-sim-ids`);
                                container.innerHTML = ids.map(id => `<input type="hidden" name="sim_ids[${itemId}][]" value="${id}">`).join('');
                                document.getElementById(`item-${itemId}-count`).textContent = `${ids.length} selected`;
                            };

                            if (errors && errors.length) {
                                Swal.fire('Some numbers were rejected', errors.join('<br>'), 'warning').then(applySelection);
                            } else {
                                applySelection();
                            }
                        });
                    });
            }
        </script>
    @endpush
</x-app-layout>
