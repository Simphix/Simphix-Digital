<x-app-layout>
    <title>SimPhix - Downline Requests</title>

    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex items-center gap-4 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2] shrink-0">
                <i data-lucide="inbox" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-extrabold font-display text-slate-900">Downline Requests</h1>
                <p class="text-sm text-slate-500 mt-0.5">SIM requests from users who named you as their upline — fulfill from your own held stock, or leave for our team to review.</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
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
                        @forelse ($requests as $req)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="py-3">
                                    <span class="font-bold text-slate-700">{{ $req->user->first_name }} {{ $req->user->last_name }}</span>
                                    <span class="block text-xs text-slate-400 capitalize">{{ $req->user->role }}</span>
                                </td>
                                <td class="py-3 font-semibold text-slate-800">Qty: {{ $req->quantity }}</td>
                                <td class="py-3">
                                    <span class="font-medium text-slate-600 block">{{ $req->category }}</span>
                                    <span class="text-xs text-slate-400 block uppercase">{{ $req->provider }}</span>
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
                                        )" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1.5 rounded-lg transition-colors">
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
