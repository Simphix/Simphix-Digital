<x-app-layout>
    <title>SimPhix - All SIMs</title>

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="database" class="w-5 h-5"></i>
                    </div>
                    All SIMs
                </h1>
                <p class="text-sm text-slate-500 mt-1">SIM numbers currently and previously assigned to you.</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 text-amber-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('warning') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Stock Summary Metrics Cells (Single Line) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex flex-row divide-x divide-slate-100 overflow-x-auto">
                @forelse ($categories as $cat)
                    <div class="flex-1 min-w-[130px] p-4 sm:p-5 transition-colors hover:bg-slate-50/50">
                        <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate" title="{{ $cat['name'] }}">
                            {{ $cat['name'] }}
                        </span>
                        <span class="text-xl sm:text-2xl font-extrabold text-slate-800 font-display mt-1 block">
                            {{ $stockCounts[$cat['name']] ?? 0 }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">SIMs with you</span>
                    </div>
                @empty
                    <div class="w-full text-center text-slate-400 text-sm py-5">No SIM categories configured yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Filter Form -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <form action="{{ route('sims.inventory') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <x-select-input name="category" class="rounded-xl !text-xs">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat['name'] }}" {{ request('category') === $cat['name'] ? 'selected' : '' }}>{{ $cat['name'] }}</option>
                    @endforeach
                </x-select-input>
                <x-select-input name="provider" class="rounded-xl !text-xs">
                    <option value="">All Networks</option>
                    @foreach ($providers as $prov)
                        <option value="{{ $prov }}" {{ request('provider') === $prov ? 'selected' : '' }}>{{ strtoupper($prov) }}</option>
                    @endforeach
                </x-select-input>
                <div class="flex gap-2">
                    <x-primary-button type="submit" class="flex-1 !text-xs">Filter</x-primary-button>
                    @if (request()->filled('category') || request()->filled('provider'))
                        <a href="{{ route('sims.inventory') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-white border border-slate-200 font-semibold text-xs text-slate-700 shadow-sm transition-all hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Your SIMs -->
        <div x-data="{
            currentTab: 'activated',
            detailsModalOpen: false,
            selectedSim: null,
            copiedKey: null,
            isEditingCustomer: false,
            editForm: {
                full_name: '',
                phone_number: '',
                email: '',
                address: ''
            },
            isSavingCustomer: false,
            isPushingEmail: false,
            editError: '',

            copy(text, key) {
                if (!text || text === 'N/A') return;
                navigator.clipboard.writeText(text);
                this.copiedKey = key;
                setTimeout(() => this.copiedKey = null, 2000);
            },
            openDetails(data) {
                this.selectedSim = data;
                this.isEditingCustomer = false;
                this.editError = '';
                this.detailsModalOpen = true;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },
            openEditModal(data) {
                this.selectedSim = data;
                this.startEditCustomer();
                this.detailsModalOpen = true;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },
            startEditCustomer() {
                this.editForm = {
                    full_name: this.selectedSim.customerName || '',
                    phone_number: this.selectedSim.customerPhone || '',
                    email: this.selectedSim.customerEmail || '',
                    address: this.selectedSim.customerAddress || ''
                };
                this.editError = '';
                this.isEditingCustomer = true;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },
            cancelEditCustomer() {
                this.isEditingCustomer = false;
                this.editError = '';
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            },
            async pushFromEdit() {
                this.editError = '';
                if (!this.editForm.full_name.trim()) {
                    this.editError = 'Customer full name is required.';
                    return;
                }
                if (!this.editForm.address.trim()) {
                    this.editError = 'Full address is required.';
                    return;
                }
                if (!this.editForm.email || !this.editForm.email.trim()) {
                    this.editError = 'Customer email address is required to push activation details.';
                    return;
                }

                this.isSavingCustomer = true;
                this.isPushingEmail = true;
                try {
                    const saveRes = await fetch(this.selectedSim.updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.editForm)
                    });
                    const saveData = await saveRes.json();
                    if (!saveRes.ok || !saveData.success) {
                        this.editError = saveData.message || (saveData.errors ? Object.values(saveData.errors).flat().join(' ') : 'Failed to save customer details before pushing.');
                        this.isSavingCustomer = false;
                        this.isPushingEmail = false;
                        return;
                    }

                    // Update selectedSim state
                    this.selectedSim.customerName = saveData.customer.full_name;
                    this.selectedSim.customerPhone = saveData.customer.phone_number;
                    this.selectedSim.customerEmail = saveData.customer.email;
                    this.selectedSim.customerAddress = saveData.customer.address;

                    // Update table row DOM
                    const rowNameEl = document.getElementById('cust_name_row_' + this.selectedSim.id);
                    if (rowNameEl) rowNameEl.textContent = saveData.customer.full_name;
                    const rowPhoneEl = document.getElementById('cust_phone_row_' + this.selectedSim.id);
                    if (rowPhoneEl) rowPhoneEl.textContent = saveData.customer.phone_number || '';

                    this.isSavingCustomer = false;

                    // Send email
                    const pushRes = await fetch(this.selectedSim.pushUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const pushData = await pushRes.json();
                    if (!pushRes.ok || !pushData.success) {
                        if (window.Swal) {
                            Swal.fire('Error', pushData.message || 'Failed to push activation email.', 'error');
                        } else {
                            alert(pushData.message || 'Failed to push activation email.');
                        }
                        return;
                    }

                    this.isEditingCustomer = false;

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Details Saved & Email Pushed',
                            text: pushData.message || 'Activation details successfully sent to customer email.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                } catch (err) {
                    this.editError = err.message || 'An unexpected error occurred.';
                } finally {
                    this.isSavingCustomer = false;
                    this.isPushingEmail = false;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                }
            },
            async saveCustomer() {
                if (!this.editForm.full_name.trim()) {
                    this.editError = 'Customer full name is required.';
                    return;
                }
                if (!this.editForm.address.trim()) {
                    this.editError = 'Full address is required.';
                    return;
                }
                this.isSavingCustomer = true;
                this.editError = '';

                try {
                    const res = await fetch(this.selectedSim.updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.editForm)
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.editError = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to update customer details.');
                        this.isSavingCustomer = false;
                        return;
                    }

                    // Update selectedSim state
                    this.selectedSim.customerName = data.customer.full_name;
                    this.selectedSim.customerPhone = data.customer.phone_number;
                    this.selectedSim.customerEmail = data.customer.email;
                    this.selectedSim.customerAddress = data.customer.address;
                    this.isEditingCustomer = false;

                    // Update table row DOM if present
                    const rowNameEl = document.getElementById('cust_name_row_' + this.selectedSim.id);
                    if (rowNameEl) rowNameEl.textContent = data.customer.full_name;
                    const rowPhoneEl = document.getElementById('cust_phone_row_' + this.selectedSim.id);
                    if (rowPhoneEl) rowPhoneEl.textContent = data.customer.phone_number || '';

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Customer Updated',
                            text: 'Customer details updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } catch (err) {
                    this.editError = err.message || 'An unexpected network error occurred.';
                } finally {
                    this.isSavingCustomer = false;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                }
            },
            async pushEmail(sim) {
                const targetSim = sim || this.selectedSim;
                if (!targetSim) return;

                if (!targetSim.customerEmail) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Email Address Required',
                            text: 'This customer does not have an email address on file. Please update customer details with an email address first.',
                            confirmButtonText: 'Edit Details',
                            showCancelButton: true,
                            confirmButtonColor: '#0056D2'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (!this.detailsModalOpen) {
                                    this.openDetails(targetSim);
                                }
                                this.startEditCustomer();
                            }
                        });
                    } else {
                        alert('Customer has no email address. Please update details with an email first.');
                    }
                    return;
                }

                this.isPushingEmail = true;
                try {
                    const res = await fetch(targetSim.pushUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        if (window.Swal) {
                            Swal.fire('Error', data.message || 'Failed to send activation email.', 'error');
                        } else {
                            alert(data.message || 'Failed to send activation email.');
                        }
                        return;
                    }

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Push Sent',
                            text: data.message || 'Activation details pushed to customer email.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    } else {
                        alert(data.message || 'Activation details sent!');
                    }
                } catch (err) {
                    if (window.Swal) {
                        Swal.fire('Error', err.message || 'Failed to push activation email.', 'error');
                    } else {
                        alert(err.message || 'Failed to push activation email.');
                    }
                } finally {
                    this.isPushingEmail = false;
                }
            },
            downlineUsersMap: {{ \Illuminate\Support\Js::from($downlineUsers->mapWithKeys(fn ($du) => [trim($du->first_name . ' ' . $du->last_name) . ' · ' . ($du->phone ?? '') . ' #' . $du->id => $du->id]))->toHtml() }}
        }" class="space-y-4">
            <!-- Navigation Tabs -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 flex gap-1">
                <button type="button" @click="currentTab = 'activated'" :class="currentTab === 'activated' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                    <i data-lucide="zap" class="w-4 h-4"></i> Activated
                </button>
                <button type="button" @click="currentTab = 'mine'" :class="currentTab === 'mine' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                    <i data-lucide="inbox" class="w-4 h-4"></i> Assigned to Me
                </button>
                @if ($downlineUsers->count() > 0)
                    <button type="button" @click="currentTab = 'others'" :class="currentTab === 'others' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                        <i data-lucide="git-branch" class="w-4 h-4"></i> Assigned to Others
                    </button>
                @endif
            </div>

            <!-- Tab: Activated -->
            <div x-show="currentTab === 'activated'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 font-display">Activated SIMs</h3>
                    <span class="text-xs text-slate-400">Click any row to view customer and SIM details</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                <th class="py-2.5">Number</th>
                                <th class="py-2.5">Customer</th>
                                <th class="py-2.5">Category</th>
                                <th class="py-2.5">Network</th>
                                <th class="py-2.5">Status</th>
                                <th class="py-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activatedSims as $sim)
                                @php
                                    $customerName = $sim->customer?->full_name ?? ($sim->user && $sim->user_id !== $sim->partner_id ? trim($sim->user->first_name . ' ' . $sim->user->last_name) : null);
                                    $customerPhone = $sim->customer?->phone_number ?? ($sim->user && $sim->user_id !== $sim->partner_id ? $sim->user->phone : null);
                                    $customerEmail = $sim->customer?->email ?? ($sim->user && $sim->user_id !== $sim->partner_id ? $sim->user->email : null);
                                    $customerAddress = $sim->customer?->address ?? null;
                                    $activatedAt = $sim->customer?->created_at?->format('M d, Y · h:i A') ?? $sim->updated_at?->format('M d, Y · h:i A');
                                    $simPayload = [
                                        'id' => $sim->id,
                                        'number' => $sim->number,
                                        'category' => $sim->category,
                                        'provider' => strtoupper($sim->provider),
                                        'status' => \App\Support\SimStatus::label($sim->status),
                                        'statusClass' => \App\Support\SimStatus::badgeClasses($sim->status),
                                        'activatedAt' => $activatedAt,
                                        'customerName' => $customerName,
                                        'customerPhone' => $customerPhone,
                                        'customerEmail' => $customerEmail,
                                        'customerAddress' => $customerAddress,
                                        'updateUrl' => route('sims.activated.update-customer', $sim),
                                        'pushUrl' => route('sims.activated.push-email', $sim),
                                    ];
                                @endphp
                                <tr class="border-b border-slate-50 hover:bg-slate-50/80 cursor-pointer transition-colors group"
                                    @click="openDetails(@js($simPayload))">
                                    <td class="py-3 font-bold text-slate-800">
                                        <button type="button" class="text-primary group-hover:underline flex items-center gap-1 font-mono text-left">
                                            {{ $sim->number }}
                                            <i data-lucide="eye" class="w-3 h-3 opacity-60"></i>
                                        </button>
                                    </td>
                                    <td class="py-3 text-slate-700">
                                        @if ($customerName)
                                            <span class="font-semibold text-slate-800 block" id="cust_name_row_{{ $sim->id }}">{{ $customerName }}</span>
                                            @if ($customerPhone)
                                                <span class="text-[11px] text-slate-400 font-mono" id="cust_phone_row_{{ $sim->id }}">{{ $customerPhone }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400" id="cust_name_row_{{ $sim->id }}">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 font-semibold text-slate-700">{{ $sim->category }}</td>
                                    <td class="py-3 text-slate-500 uppercase">{{ $sim->provider }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">
                                            {{ \App\Support\SimStatus::label($sim->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    @click.stop="openEditModal(@js($simPayload))"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors shadow-sm"
                                                    title="Edit customer details">
                                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-400"></i>
                                                Edit
                                            </button>
                                            <button type="button"
                                                    @click.stop="openDetails(@js($simPayload))"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 group-hover:bg-primary/10 group-hover:text-primary text-slate-600 text-xs font-semibold transition-colors">
                                                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                                Details
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">No activated SIMs yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $activatedSims->links('vendor.pagination.custom') }}
            </div>

            <!-- Tab: Assigned to Me -->
            <div x-show="currentTab === 'mine'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
                 @if ($downlineUsers->count() > 0)
                 x-data="{
                    selectedSims: [],
                    maxSelect: 50,
                    toggleAll(checked) {
                        if (checked) {
                            let ids = Array.from(document.querySelectorAll('.sim-checkbox:not(:disabled)')).map(el => parseInt(el.value));
                            this.selectedSims = ids.slice(0, this.maxSelect);
                        } else {
                            this.selectedSims = [];
                        }
                    },
                    toggleSim(id, checked) {
                        if (checked) {
                            if (this.selectedSims.length >= this.maxSelect) {
                                Swal.fire('Limit Exceeded', 'You can only select up to 50 numbers at a time.', 'warning');
                                document.getElementById('sim_cb_' + id).checked = false;
                                return;
                            }
                            if (!this.selectedSims.includes(id)) {
                                this.selectedSims.push(id);
                            }
                        } else {
                            this.selectedSims = this.selectedSims.filter(x => x !== id);
                        }
                    }
                 }"
                 @endif>
                <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">SIMs Assigned to Me</h3>

                @if ($downlineUsers->count() > 0)
                    <div x-show="selectedSims.length > 0"
                         style="display: none;"
                         class="flex items-center justify-between bg-indigo-50/80 border border-indigo-100 rounded-2xl p-3.5 animate-in slide-in-from-top duration-200">
                        <span class="text-xs font-semibold text-slate-700">
                            Selected: <span class="font-extrabold text-indigo-700" x-text="selectedSims.length"></span> / 50 SIMs
                        </span>
                        <button type="button" @click="Swal.fire({
                            title: 'Bulk Assign SIMs',
                            html: `
                                <div class='text-left space-y-2'>
                                    <label class='text-xs font-bold text-slate-500 uppercase block'>Search by Name or Phone</label>
                                    <input type='text' id='bulk_user_id' list='bulk_user_id_list' autocomplete='off' placeholder='Start typing a name or phone…' class='w-full py-2.5 px-3 border rounded-xl font-medium text-slate-700'>
                                    <datalist id='bulk_user_id_list'>
                                        @foreach ($downlineUsers as $du)
                                            <option value='{{ trim($du->first_name . " " . $du->last_name) }} · {{ $du->phone }} #{{ $du->id }}'></option>
                                        @endforeach
                                    </datalist>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#0056D2',
                            confirmButtonText: 'Assign Selected',
                            preConfirm: () => {
                                const val = downlineUsersMap[document.getElementById('bulk_user_id').value.trim()];
                                if (!val) {
                                    Swal.showValidationMessage('Please select a valid user from the list');
                                }
                                return val;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                let f = document.createElement('form');
                                f.action = '{{ route('sims.bulk-assign') }}';
                                f.method = 'POST';
                                let inputs = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'><input type=\'hidden\' name=\'user_id\' value=\'' + result.value + '\'>';
                                selectedSims.forEach(id => {
                                    inputs += '<input type=\'hidden\' name=\'sim_ids[]\' value=\'' + id + '\'>';
                                });
                                f.innerHTML = inputs;
                                document.body.appendChild(f);
                                f.submit();
                            }
                        })" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-xl flex items-center gap-1.5 transition-colors shadow-sm">
                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                            Assign Checked
                        </button>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                @if ($downlineUsers->count() > 0)
                                    <th class="py-2.5 pl-1 w-8">
                                        <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded text-[#0056D2] focus:ring-[#0056D2]">
                                    </th>
                                @endif
                                <th class="py-2.5">Number</th>
                                <th class="py-2.5">Category</th>
                                <th class="py-2.5">Network</th>
                                <th class="py-2.5">Status</th>
                                @if ($downlineUsers->count() > 0)
                                    <th class="py-2.5 text-right pr-2">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignedToMeSims as $sim)
                                <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                    @if ($downlineUsers->count() > 0)
                                        <td class="py-3 pl-1">
                                            <input type="checkbox" id="sim_cb_{{ $sim->id }}" value="{{ $sim->id }}"
                                                   :checked="selectedSims.includes({{ $sim->id }})"
                                                   @change="toggleSim({{ $sim->id }}, $event.target.checked)"
                                                   class="sim-checkbox rounded text-[#0056D2] focus:ring-[#0056D2]">
                                        </td>
                                    @endif
                                    <td class="py-3 font-bold text-slate-800">{{ $sim->number }}</td>
                                    <td class="py-3 font-semibold text-slate-700">{{ $sim->category }}</td>
                                    <td class="py-3 text-slate-500 uppercase">{{ $sim->provider }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">
                                            {{ \App\Support\SimStatus::label($sim->status) }}
                                        </span>
                                    </td>
                                    @if ($downlineUsers->count() > 0)
                                        <td class="py-3 text-right pr-2">
                                            <button type="button" @click="selectedSims = [{{ $sim->id }}]; $nextTick(() => { document.querySelector('[x-show=\'selectedSims.length > 0\'] button')?.click(); })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition-colors">
                                                <i data-lucide="user-plus" class="w-3 h-3"></i>
                                                Assign
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $downlineUsers->count() > 0 ? 6 : 4 }}" class="py-8 text-center text-slate-400 font-semibold">You don't have any SIMs assigned to you right now.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $assignedToMeSims->links('vendor.pagination.custom') }}
            </div>

            @if ($downlineUsers->count() > 0)
                <!-- Tab: Assigned to Others -->
                <div x-show="currentTab === 'others'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">SIMs Assigned to Others in Your Downline</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5">Number</th>
                                    <th class="py-2.5">Category</th>
                                    <th class="py-2.5">Network</th>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5">Held By</th>
                                    <th class="py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assignedToOthersSims as $sim)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                        <td class="py-3 font-bold text-slate-800">{{ $sim->number }}</td>
                                        <td class="py-3 font-semibold text-slate-700">{{ $sim->category }}</td>
                                        <td class="py-3 text-slate-500 uppercase">{{ $sim->provider }}</td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ \App\Support\SimStatus::badgeClasses($sim->status) }}">
                                                {{ \App\Support\SimStatus::label($sim->status) }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            @if ($sim->current_holder)
                                                <span class="font-semibold text-slate-700">{{ $sim->current_holder->first_name }} {{ $sim->current_holder->last_name }}</span>
                                                <span class="block text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', $sim->current_holder->role) }}</span>
                                            @else
                                                <span class="text-slate-400 italic">Unknown</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-right whitespace-nowrap">
                                            @if ($sim->swap_eligible)
                                                <button type="button" @click="Swal.fire({
                                                    title: 'Swap SIM Holder',
                                                    html: `
                                                        <div class='text-left space-y-2'>
                                                            <p class='text-xs text-slate-500'>Currently with <strong>{{ $sim->current_holder->first_name ?? '' }} {{ $sim->current_holder->last_name ?? '' }}</strong>. This swap requires review from our team before it takes effect.</p>
                                                            <label class='text-xs font-bold text-slate-500 uppercase block'>Search by Name or Phone</label>
                                                            <input type='text' id='swap_to_id_{{ $sim->id }}' list='swap_to_id_list_{{ $sim->id }}' autocomplete='off' placeholder='Start typing a name or phone…' class='w-full py-2.5 px-3 border rounded-xl font-medium text-slate-700'>
                                                            <datalist id='swap_to_id_list_{{ $sim->id }}'>
                                                                @foreach ($downlineUsers as $du)
                                                                    @continue($sim->current_holder && $du->id === $sim->current_holder->id)
                                                                    <option value='{{ trim($du->first_name . " " . $du->last_name) }} · {{ $du->phone }} #{{ $du->id }}'></option>
                                                                @endforeach
                                                            </datalist>
                                                        </div>
                                                    `,
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#0056D2',
                                                    confirmButtonText: 'Request Swap',
                                                    preConfirm: () => {
                                                        const swapMap_{{ $sim->id }} = {{ \Illuminate\Support\Js::from($downlineUsers->filter(fn ($du) => !($sim->current_holder && $du->id === $sim->current_holder->id))->mapWithKeys(fn ($du) => [trim($du->first_name . ' ' . $du->last_name) . ' · ' . ($du->phone ?? '') . ' #' . $du->id => $du->id]))->toHtml() }};
                                                        const val = swapMap_{{ $sim->id }}[document.getElementById('swap_to_id_{{ $sim->id }}').value.trim()];
                                                        if (!val) {
                                                            Swal.showValidationMessage('Please select a valid account from the list');
                                                        }
                                                        return val;
                                                    }
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        let f = document.createElement('form');
                                                        f.action = '{{ route('sims.request-swap') }}';
                                                        f.method = 'POST';
                                                        f.innerHTML = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'><input type=\'hidden\' name=\'sim_id\' value=\'{{ $sim->id }}\'><input type=\'hidden\' name=\'to_holder_id\' value=\'' + result.value + '\'>';
                                                        document.body.appendChild(f);
                                                        f.submit();
                                                    }
                                                })" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all duration-150 shadow-sm">
                                                    <i data-lucide="repeat" class="w-3.5 h-3.5"></i>
                                                    Swap
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">No SIMs assigned further down your downline right now.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $assignedToOthersSims->links('vendor.pagination.custom') }}
                </div>
            @endif

            <!-- Activated SIM & Customer Details Modal -->
            <div x-show="detailsModalOpen"
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto"
                 aria-labelledby="modal-sim-details-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="detailsModalOpen"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
                         @click="detailsModalOpen = false" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="detailsModalOpen"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">

                        <template x-if="selectedSim">
                            <div class="p-6 space-y-5">
                                <!-- Modal Header -->
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold text-slate-800 font-display" id="modal-sim-details-title">
                                                SIM & Customer Details
                                            </h3>
                                            <p class="text-xs text-slate-400">Activated profile and onboarding details</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="detailsModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <!-- SIM Details Card -->
                                <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-100 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">SIM Information</span>
                                        <span :class="selectedSim.statusClass" class="px-2 py-0.5 rounded-full text-[11px] font-bold border" x-text="selectedSim.status"></span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Phone Number</span>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="font-bold text-slate-800 font-mono text-sm" x-text="selectedSim.number"></span>
                                                <button type="button"
                                                        @click="copy(selectedSim.number, 'sim_number')"
                                                        class="text-slate-400 hover:text-primary transition-colors p-0.5"
                                                        title="Copy number">
                                                    <i data-lucide="copy" class="w-3.5 h-3.5" x-show="copiedKey !== 'sim_number'"></i>
                                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600" x-show="copiedKey === 'sim_number'"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Network Provider</span>
                                            <span class="font-bold text-slate-800 uppercase mt-0.5 block" x-text="selectedSim.provider"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 block text-[11px]">SIM Category</span>
                                            <span class="font-semibold text-slate-800 mt-0.5 block" x-text="selectedSim.category"></span>
                                        </div>

                                        <div>
                                            <span class="text-slate-400 block text-[11px]">Activation Date</span>
                                            <span class="font-medium text-slate-600 mt-0.5 block" x-text="selectedSim.activatedAt || 'N/A'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Details Card -->
                                <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-100 space-y-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Customer Information</span>
                                        <div class="flex items-center gap-1.5" x-show="!isEditingCustomer">
                                            <button type="button"
                                                    @click="startEditCustomer()"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-sm">
                                                <i data-lucide="edit-3" class="w-3 h-3 text-slate-400"></i>
                                                Edit Details
                                            </button>
                                        </div>
                                    </div>

                                    <!-- VIEW MODE -->
                                    <template x-if="!isEditingCustomer">
                                        <div>
                                            <template x-if="selectedSim.customerName || selectedSim.customerPhone || selectedSim.customerAddress">
                                                <div class="space-y-2.5 text-xs">
                                                    <div class="flex items-start gap-2.5">
                                                        <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <span class="text-[11px] text-slate-400 block">Full Name</span>
                                                            <span class="font-bold text-slate-800" x-text="selectedSim.customerName || 'N/A'"></span>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-start gap-2.5">
                                                        <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <span class="text-[11px] text-slate-400 block">Full Address</span>
                                                            <span class="font-medium text-slate-700" x-text="selectedSim.customerAddress || 'Not provided'"></span>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-start gap-2.5">
                                                        <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                                            <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <span class="text-[11px] text-slate-400 block">Phone Number</span>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="font-semibold text-slate-800 font-mono" x-text="selectedSim.customerPhone || 'Not provided'"></span>
                                                                <button type="button"
                                                                        x-show="selectedSim.customerPhone"
                                                                        @click="copy(selectedSim.customerPhone, 'cust_phone')"
                                                                        class="text-slate-400 hover:text-primary transition-colors p-0.5"
                                                                        title="Copy phone">
                                                                    <i data-lucide="copy" class="w-3.5 h-3.5" x-show="copiedKey !== 'cust_phone'"></i>
                                                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600" x-show="copiedKey === 'cust_phone'"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-start gap-2.5">
                                                        <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                                            <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <span class="text-[11px] text-slate-400 block">Email Address</span>
                                                            <div class="flex items-center gap-2">
                                                                <span class="font-medium text-slate-700 break-all" x-text="selectedSim.customerEmail || 'Not provided'"></span>
                                                                <span x-show="!selectedSim.customerEmail" class="text-[10px] text-amber-600 font-semibold">(Add email to enable push)</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="!selectedSim.customerName && !selectedSim.customerPhone && !selectedSim.customerAddress">
                                                <div class="py-3 px-3 bg-white rounded-lg border border-slate-200 text-slate-400 text-center text-xs space-y-2">
                                                    <div>No customer profile was recorded for this SIM.</div>
                                                    <button type="button" @click="startEditCustomer()" class="text-primary font-bold hover:underline">Add Customer Details</button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- EDIT MODE -->
                                    <template x-if="isEditingCustomer">
                                        <div class="space-y-3 pt-1">
                                            <div x-show="editError" x-text="editError" class="p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>

                                            <div class="space-y-1">
                                                <label class="block text-[11px] font-bold text-slate-600 uppercase">Customer Full Name <span class="text-rose-500">*</span></label>
                                                <input type="text" x-model="editForm.full_name" placeholder="e.g. Adebayo Ogunlesi" class="w-full text-xs rounded-xl border-slate-200 p-2.5 focus:border-primary focus:ring-primary">
                                            </div>

                                            <div class="space-y-1">
                                                <label class="block text-[11px] font-bold text-slate-600 uppercase">Full Address <span class="text-rose-500">*</span></label>
                                                <textarea rows="2" x-model="editForm.address" placeholder="Physical Address" class="w-full text-xs rounded-xl border-slate-200 p-2.5 focus:border-primary focus:ring-primary"></textarea>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div class="space-y-1">
                                                    <label class="block text-[11px] font-bold text-slate-600 uppercase">Phone Number <span class="text-slate-400 font-normal lowercase">(optional)</span></label>
                                                    <input type="tel" x-model="editForm.phone_number" placeholder="e.g. 08012345678" class="w-full text-xs rounded-xl border-slate-200 p-2.5 focus:border-primary focus:ring-primary">
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="block text-[11px] font-bold text-slate-600 uppercase">Email Address <span class="text-slate-400 font-normal lowercase">(optional)</span></label>
                                                    <input type="email" x-model="editForm.email" placeholder="customer@example.com" class="w-full text-xs rounded-xl border-slate-200 p-2.5 focus:border-primary focus:ring-primary">
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-200/60">
                                                <button type="button"
                                                        @click="pushFromEdit()"
                                                        :disabled="isPushingEmail || isSavingCustomer"
                                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors shadow-sm disabled:opacity-50"
                                                        title="Save details and email SIM activation info to customer">
                                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                                    <span x-text="isPushingEmail ? 'Pushing...' : 'Push'"></span>
                                                </button>

                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                            @click="cancelEditCustomer()"
                                                            :disabled="isSavingCustomer || isPushingEmail"
                                                            class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-100 transition-colors">
                                                        Cancel
                                                    </button>
                                                    <button type="button"
                                                            @click="saveCustomer()"
                                                            :disabled="isSavingCustomer || isPushingEmail"
                                                            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-colors shadow-sm disabled:opacity-50">
                                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                        <span x-text="isSavingCustomer ? 'Saving...' : 'Save'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                                    <button type="button"
                                            @click="detailsModalOpen = false"
                                            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition-colors">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        @if (in_array(auth()->user()->role, ['regional_manager', 'coordinator', 'business_sales_manager'], true))
            <!-- Floating bulk-actions button -->
            <div class="fixed bottom-6 right-6 z-40" x-data="{ open: false }">
                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-2"
                     class="absolute bottom-16 right-0 w-56 bg-white rounded-xl border border-slate-200 shadow-xl p-2 space-y-1" style="display: none;" x-cloak>
                    <a href="{{ route('sims.bulk-assign.page') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="user-plus" class="w-4 h-4 text-emerald-600"></i> Bulk Assign SIM
                    </a>
                    <a href="{{ route('sims.bulk-collect') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="user-minus" class="w-4 h-4 text-rose-600"></i> Bulk Collect SIM
                    </a>
                </div>
                <button type="button" @click="open = !open" aria-label="Bulk actions"
                        class="w-14 h-14 rounded-full bg-[#0056D2] hover:bg-[#0049b8] text-white shadow-xl flex items-center justify-center transition-transform"
                        :class="open ? 'rotate-45' : ''">
                    <i data-lucide="plus" class="w-6 h-6"></i>
                </button>
            </div>
        @endif

    </div>
</x-app-layout>
