<x-app-layout>
    <title>SimPhix - Requests</title>

    <div x-data="{ currentTab: '{{ request('tab', 'incoming') }}', requestModalOpen: false }" class="max-w-5xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-primary shrink-0">
                    <i data-lucide="inbox" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold font-display text-slate-900">SIM Requests</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage incoming requests from your downline and track your own SIM requests.</p>
                </div>
            </div>

            <button type="button"
                    @click="requestModalOpen = true"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-sm transition-all hover:bg-primary/90 shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Request SIMs</span>
            </button>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Tab Controls -->
        <div class="flex items-center gap-2 border-b border-slate-200">
            <button type="button"
                    @click="currentTab = 'incoming'"
                    :class="currentTab === 'incoming' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex items-center gap-2 px-4 py-3 border-b-2 text-xs transition-colors">
                <i data-lucide="arrow-down-left" class="w-4 h-4"></i>
                <span>Incoming Requests</span>
                @if ($incomingRequests->total() > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                          :class="currentTab === 'incoming' ? 'bg-primary text-white' : 'bg-slate-200 text-slate-700'">
                        {{ $incomingRequests->total() }}
                    </span>
                @endif
            </button>

            <button type="button"
                    @click="currentTab = 'my'"
                    :class="currentTab === 'my' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex items-center gap-2 px-4 py-3 border-b-2 text-xs transition-colors">
                <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                <span>My Requests</span>
                @if ($myRequests->total() > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                          :class="currentTab === 'my' ? 'bg-primary text-white' : 'bg-slate-200 text-slate-700'">
                        {{ $myRequests->total() }}
                    </span>
                @endif
            </button>
        </div>

        <!-- Tab 1: Incoming Requests -->
        <div x-show="currentTab === 'incoming'" class="space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 font-display text-sm">Incoming Downline Requests</h3>
                    <span class="text-xs text-slate-400">Fulfill directly from your held stock</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                <th class="py-2.5">User</th>
                                <th class="py-2.5">Requested</th>
                                <th class="py-2.5">Category/Provider</th>
                                <th class="py-2.5">Your Stock</th>
                                <th class="py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($incomingRequests as $req)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3">
                                        <span class="font-bold text-slate-700">{{ $req->user->first_name }} {{ $req->user->last_name }}</span>
                                        <span class="block text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', $req->user->role) }}</span>
                                    </td>
                                    <td class="py-3 font-semibold text-slate-800">Qty: {{ $req->quantity }}</td>
                                    <td class="py-3">
                                        <span class="font-medium text-slate-600 block">{{ $req->category }}</span>
                                        <span class="text-xs text-slate-400 block uppercase font-mono">{{ $req->provider }}</span>
                                    </td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $req->held_count >= $req->quantity ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                            {{ $req->held_count }} on hand
                                        </span>
                                    </td>
                                    <td class="py-3 text-right whitespace-nowrap">
                                        @if ($req->held_count >= $req->quantity)
                                            <button type="button" @click="openFulfillRequestModal(
                                                '{{ $req->category }}',
                                                '{{ $req->provider }}',
                                                {{ $req->quantity }},
                                                '{{ route('sims.downline-requests.available-sims') }}',
                                                '{{ route('sims.downline-requests.fulfill', $req->id) }}',
                                                '{{ route('sims.downline-requests.resolve-numbers', $req->id) }}'
                                            )" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg transition-colors text-xs shadow-sm">
                                                Fulfill
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Not enough stock — our team will review</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No pending requests from your downline.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $incomingRequests->links('vendor.pagination.custom') }}
            </div>
        </div>

        <!-- Tab 2: My Requests -->
        <div x-show="currentTab === 'my'" x-cloak class="space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 font-display text-sm">My Outgoing SIM Requests</h3>
                    <span class="text-xs text-slate-400">Track SIM orders you've placed</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                <th class="py-2.5">Date</th>
                                <th class="py-2.5">Category</th>
                                <th class="py-2.5">Network</th>
                                <th class="py-2.5">Quantity</th>
                                <th class="py-2.5">Amount</th>
                                <th class="py-2.5">Routed To</th>
                                <th class="py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myRequests as $req)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 text-slate-500 font-medium whitespace-nowrap">
                                        {{ $req->created_at->format('M d, Y') }}
                                        <span class="block text-[11px] text-slate-400">{{ $req->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td class="py-3 font-semibold text-slate-800">
                                        {{ $req->category }}
                                    </td>
                                    <td class="py-3 text-slate-600 uppercase font-mono font-bold">
                                        {{ $req->provider }}
                                    </td>
                                    <td class="py-3 font-bold text-slate-800">
                                        {{ $req->quantity }} SIM(s)
                                    </td>
                                    <td class="py-3 font-semibold text-slate-700">
                                        ₦{{ number_format((float) ($req->amount ?? 0), 2) }}
                                    </td>
                                    <td class="py-3 text-slate-600">
                                        @if ($req->upline)
                                            <span class="font-medium text-slate-800">{{ $req->upline->first_name }} {{ $req->upline->last_name }}</span>
                                            <span class="block text-[10px] text-slate-400 capitalize">{{ str_replace('_', ' ', $req->upline->role) }}</span>
                                        @else
                                            <span class="font-medium text-slate-500">Platform Admin</span>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @if ($req->status === 'approved')
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Fulfilled
                                            </span>
                                        @elseif ($req->status === 'rejected')
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Rejected
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-slate-400 space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                                            <i data-lucide="inbox" class="w-5 h-5"></i>
                                        </div>
                                        <p class="font-semibold text-sm text-slate-600">No requests placed yet</p>
                                        <p class="text-xs">You haven't requested any SIM cards yet.</p>
                                        <button type="button" @click="requestModalOpen = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition-colors">
                                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                            Request Now
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $myRequests->links('vendor.pagination.custom') }}
            </div>
        </div>

        <!-- Request SIM Modal -->
        <div x-show="requestModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="requestModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
                     @click="requestModalOpen = false" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="requestModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">

                    <form action="{{ route('sims.request') }}" method="POST" class="p-6 space-y-5">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 font-display" id="modal-title">Request SIM Cards</h3>
                            </div>
                            <button type="button" @click="requestModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <!-- Category -->
                            <div class="space-y-1.5">
                                <label for="req_category" class="font-bold text-slate-700 uppercase tracking-wider block">SIM Category</label>
                                <select id="req_category" name="category" required class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat['name'] }}">{{ $cat['label'] ?? $cat['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Provider -->
                            <div class="space-y-1.5">
                                <label for="req_provider" class="font-bold text-slate-700 uppercase tracking-wider block">Network Provider</label>
                                <select id="req_provider" name="provider" required class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    @foreach ($providers as $prov)
                                        <option value="{{ $prov }}">{{ strtoupper($prov) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Quantity -->
                            <div class="space-y-1.5">
                                <label for="req_quantity" class="font-bold text-slate-700 uppercase tracking-wider block">
                                    Quantity <span class="text-slate-400 font-normal lowercase">(max: {{ $maxQuantity }})</span>
                                </label>
                                <input type="number" id="req_quantity" name="quantity" min="1" max="{{ $maxQuantity }}" value="1" required
                                       class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="requestModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-colors shadow">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openFulfillRequestModal(category, provider, quantity, availableSimsUrl, fulfillUrl, resolveNumbersUrl) {
                fetch(`${availableSimsUrl}?category=${encodeURIComponent(category)}&provider=${encodeURIComponent(provider)}`)
                    .then(r => r.json())
                    .then(sims => {
                        if (sims.length === 0) {
                            Swal.fire('Not Enough Stock', `No SIM(s) available for ${category} / ${provider.toUpperCase()}.`, 'warning');
                            return;
                        }

                        const optionsHtml = sims.map(s => `
                            <label class="flex items-center gap-2 text-xs font-medium py-1">
                                <input type="checkbox" class="fulfill-sim-cb" value="${s.id}"> ${s.number}
                            </label>
                        `).join('');

                        let activeTab = 'list';

                        Swal.fire({
                            title: `Select up to ${quantity} SIM(s) to Hand Over`,
                            html: `
                                <div class="text-left">
                                    <div class="flex gap-2 mb-3 border-b border-slate-200">
                                        <button type="button" id="fulfill-tab-list" class="px-3 py-1.5 text-xs font-bold border-b-2 border-[#0056D2] text-[#0056D2]">Pick from List</button>
                                        <button type="button" id="fulfill-tab-numbers" class="px-3 py-1.5 text-xs font-bold border-b-2 border-transparent text-slate-400">Enter Numbers</button>
                                    </div>
                                    <div id="fulfill-panel-list" class="max-h-64 overflow-y-auto space-y-1">${optionsHtml}</div>
                                    <div id="fulfill-panel-numbers" class="hidden">
                                        <textarea id="fulfill-numbers-input" rows="6" class="w-full text-xs border border-slate-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#0056D2]" placeholder="08030000000, 08031111111&#10;or one per line"></textarea>
                                        <p class="text-[11px] text-slate-400 mt-1">Separate numbers by comma or new line. Each must be an existing, unassigned ${category} / ${provider.toUpperCase()} SIM currently in your held stock.</p>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#0056D2',
                            confirmButtonText: 'Fulfill Selected',
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
                                    if (checked.length !== quantity) {
                                        Swal.showValidationMessage(`Please select exactly ${quantity} SIM(s).`);
                                        return false;
                                    }
                                    return { ids: checked, errors: [] };
                                }

                                const numbers = document.getElementById('fulfill-numbers-input').value;
                                if (!numbers.trim()) {
                                    Swal.showValidationMessage('Please enter at least one SIM number.');
                                    return false;
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
                                        if (data.resolved.length === 0) {
                                            Swal.showValidationMessage(data.errors.length ? data.errors.join('<br>') : 'No valid SIM numbers found.');
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
                            const submit = () => {
                                const f = document.createElement('form');
                                f.action = fulfillUrl;
                                f.method = 'POST';
                                let inputs = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
                                ids.forEach(id => { inputs += `<input type="hidden" name="sim_ids[]" value="${id}">`; });
                                f.innerHTML = inputs;
                                document.body.appendChild(f);
                                f.submit();
                            };
                            if (errors && errors.length) {
                                Swal.fire('Some numbers were rejected', errors.join('<br>'), 'warning').then(submit);
                            } else {
                                submit();
                            }
                        });
                    });
            }
        </script>
    @endpush
</x-app-layout>
