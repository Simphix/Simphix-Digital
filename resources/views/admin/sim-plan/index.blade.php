<x-app-layout>
    <title>SmartSIM - Admin SIM Management</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header + Admin SIM Activation: assign + activate a SIM
             directly to a personal user, bypassing any partner/agent — no
             commission is paid to anyone on this SIM. The user's own wallet
             is still debited the normal price; activation is refused if
             their balance is short. -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
             x-data="{
                open: false,
                userQuery: '',
                userResults: [],
                selectedUser: null,
                category: '{{ $categories[0] ?? '' }}',
                provider: '{{ $providers[0] ?? '' }}',
                submitting: false,

                async searchUsers() {
                    if (this.userQuery.trim().length < 2) { this.userResults = []; return; }
                    const res = await fetch('{{ route('admin.sim-plan.users.search') }}?role=personal&q=' + encodeURIComponent(this.userQuery));
                    this.userResults = await res.json();
                },

                selectUser(u) {
                    this.selectedUser = u;
                    this.userResults = [];
                    this.userQuery = u.name + ' · ' + (u.phone || u.email);
                },

                submit() {
                    if (!this.selectedUser || this.submitting) return;
                    this.submitting = true;
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '{{ route('admin.sim-plan.direct-assign') }}';
                    let inputs = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'>';
                    inputs += '<input type=\'hidden\' name=\'user_id\' value=\'' + this.selectedUser.id + '\'>';
                    inputs += '<input type=\'hidden\' name=\'category\' value=\'' + this.category + '\'>';
                    inputs += '<input type=\'hidden\' name=\'provider\' value=\'' + this.provider + '\'>';
                    f.innerHTML = inputs;
                    document.body.appendChild(f);
                    f.submit();
                }
             }">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                    </div>
                    SIM Cards & Plans Management
                </h1>
                <p class="text-sm text-slate-500 mt-1">Upload available SIM numbers, assign numbers directly to roles, and approve user requests.</p>
            </div>
            <div>
                <button type="button" @click="open = true"
                        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                    Admin SIM Activation
                </button>
            </div>

            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="open = false">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-extrabold font-display text-slate-900">Admin SIM Activation</h3>
                        <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500">Assign and activate a SIM directly to a personal user — the user's wallet is debited the normal price, but no partner/agent is involved, so no one earns a commission on it.</p>

                    <div class="space-y-1.5 relative">
                        <x-input-label value="Personal User" />
                        <x-text-input type="text" x-model="userQuery" @input.debounce.400ms="searchUsers()"
                                      placeholder="Search by name, email, or phone…" autocomplete="off" class="rounded-xl font-medium" />
                        <div x-show="userResults.length > 0" x-cloak
                             class="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                            <template x-for="u in userResults" :key="u.id">
                                <button type="button" @click="selectUser(u)"
                                        class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-50 last:border-0">
                                    <span class="block text-xs font-bold text-slate-700" x-text="u.name"></span>
                                    <span class="block text-[11px] text-slate-400" x-text="(u.phone || '') + ' · ' + u.email"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <x-input-label value="Category" />
                            <select x-model="category" class="w-full rounded-xl border-slate-200 text-xs font-semibold">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <x-input-label value="Network" />
                            <select x-model="provider" class="w-full rounded-xl border-slate-200 text-xs font-semibold">
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}">{{ strtoupper($prov) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="button" @click="submit()" :disabled="!selectedUser || submitting"
                            class="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-colors">
                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                        Assign &amp; Activate — No Commission
                    </button>
                </div>
            </div>
        </div>

        <!-- Header Statistics Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Card 1: Total Uploaded -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2] shrink-0">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Uploaded</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $totalUploaded }}</p>
                </div>
            </div>

            <!-- Card 2: Total Available -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100/50 flex items-center justify-center text-blue-600 shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Not Assigned</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $totalAvailable }}</p>
                </div>
            </div>

            <!-- Card 3: Total Assigned -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 border border-purple-100/50 flex items-center justify-center text-purple-600 shrink-0">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Assigned</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $totalAssigned }}</p>
                </div>
            </div>

            <!-- Card 4: Total Activated -->
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100/50 flex items-center justify-center text-emerald-600 shrink-0">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-slate-400 mb-0.5">Total Activated</p>
                    <p class="text-lg font-bold font-display text-slate-800 truncate">{{ $totalActivated }}</p>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-amber-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('warning') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Side: Upload Numbers Form -->
            <div class="lg:col-span-4 space-y-6" x-data="{ uploadTab: 'manual' }">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-slate-800 font-display">Add Available Numbers</h3>
                            <p class="text-xs text-slate-400">Add inventory numbers for users to request.</p>
                        </div>
                    </div>

                    <!-- Upload Toggle Tabs -->
                    <div class="flex bg-slate-50 p-1 rounded-xl mb-4 text-xs font-semibold">
                        <button type="button" @click="uploadTab = 'manual'" :class="uploadTab === 'manual' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-1.5 rounded-lg transition-all text-center">Manual Text</button>
                        <button type="button" @click="uploadTab = 'excel'" :class="uploadTab === 'excel' ? 'bg-[#0056D2] text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="flex-1 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1">
                            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Excel Sheet
                        </button>
                    </div>

                    <!-- Manual Text Form -->
                    <form x-show="uploadTab === 'manual'" action="{{ route('admin.sim-plan.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <x-input-label for="add_category" value="SIM Category" />
                            <x-select-input id="add_category" name="category" required class="rounded-xl font-medium">
                                <option value="">Select Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </x-select-input>
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="add_provider" value="Network Operator" />
                            <x-select-input id="add_provider" name="provider" required class="rounded-xl font-medium">
                                <option value="">Select Network</option>
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}">{{ strtoupper($prov) }}</option>
                                @endforeach
                            </x-select-input>
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="add_numbers" value="SIM Numbers" />
                            <x-textarea-input id="add_numbers" name="numbers" rows="6" required placeholder="08030000000&#10;08031111111&#10;08032222222"
                                      class="rounded-xl font-semibold placeholder:font-normal" />
                            <p class="text-xs text-slate-400">Separate numbers by comma or new lines.</p>
                        </div>

                        <x-primary-button type="submit" class="w-full !text-xs !bg-indigo-600 hover:!bg-indigo-700">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            Upload Numbers
                        </x-primary-button>
                    </form>

                    <!-- Excel Upload Form -->
                    <form x-show="uploadTab === 'excel'" style="display: none;" action="{{ route('admin.sim-plan.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="bg-indigo-50/50 rounded-2xl p-4 border border-indigo-100/50 space-y-2">
                            <h4 class="text-xs font-bold text-[#0056D2] flex items-center gap-1.5">
                                <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i> Excel Upload Instructions
                            </h4>
                            <p class="text-[11px] text-slate-600 leading-relaxed">
                                Columns in your Excel file must include exactly: <strong class="text-indigo-800">number</strong>, <strong class="text-indigo-800">category</strong>, and <strong class="text-indigo-800">provider</strong> headers in row 1.
                            </p>
                            <a href="{{ route('admin.sim-plan.download-sample') }}" class="text-[11px] font-bold text-indigo-600 hover:underline flex items-center gap-1 mt-1">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download Sample Excel Template
                            </a>
                        </div>

                        <div class="space-y-2">
                            <label for="excel_file" class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Choose Excel/CSV file</label>
                            <div class="border-2 border-dashed border-slate-200 hover:border-[#0056D2]/50 transition-colors rounded-2xl p-4 text-center cursor-pointer relative">
                                <input type="file" id="excel_file" name="excel_file" required accept=".xlsx,.xls,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="document.getElementById('excel-file-name').innerText = this.files[0].name;">
                                <div class="space-y-1.5">
                                    <i data-lucide="file-up" class="w-8 h-8 text-slate-400 mx-auto"></i>
                                    <p class="text-xs font-semibold text-slate-600" id="excel-file-name">Click or drag Excel file here</p>
                                    <p class="text-xs text-slate-400">Supports .xlsx, .xls, .csv up to 5MB</p>
                                </div>
                            </div>
                        </div>

                        <x-primary-button type="submit" class="w-full !text-xs !bg-emerald-600 hover:!bg-emerald-700">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            Import Excel SIMs
                        </x-primary-button>
                    </form>
                </div>

                <!-- Activation Controls -->
                <div id="activation-controls" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 scroll-mt-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100 mb-4">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100/50 flex items-center justify-center text-rose-600">
                            <i data-lucide="power-off" class="w-4 h-4"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-slate-800 font-display">Activation Controls</h3>
                            <p class="text-xs text-slate-400">Block new activations per SIM category.</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        @forelse ($categoryFields as $field)
                            <div class="flex items-center justify-between px-3 py-2.5 rounded-xl {{ $field->activation_disabled ? 'bg-rose-50' : 'bg-slate-50' }}">
                                <span class="text-xs font-semibold text-slate-700">{{ $field->field_name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider w-14 text-right {{ $field->activation_disabled ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $field->activation_disabled ? 'Blocked' : 'Active' }}
                                    </span>
                                    <form method="POST" action="{{ route('admin.sim-plan.categories.toggle-activation', $field) }}">
                                        @csrf
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer" {{ $field->activation_disabled ? 'checked' : '' }} onchange="this.form.submit()">
                                            <div class="w-9 h-5 bg-slate-300 peer-checked:bg-rose-500 rounded-full transition-colors"></div>
                                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                                        </label>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">No SIM categories configured yet.</p>
                        @endforelse
                    </div>
                    <p class="text-[11px] text-slate-400 mt-3">Toggling ON blocks new activations for that category — existing activated SIMs are unaffected.</p>
                </div>
            </div>

            <!-- Right Side: Requests & Inventory Tabs -->
            <div class="lg:col-span-8 space-y-6" x-data="{ currentTab: 'requests' }">
                <!-- Navigation Tabs (horizontally scrollable so they stay relaxed on small screens) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 overflow-x-auto">
                    <div class="flex gap-1 w-max min-w-full">
                        <button type="button" @click="currentTab = 'requests'" :class="currentTab === 'requests' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="shrink-0 py-2 px-3.5 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="inbox" class="w-4 h-4"></i> Pending Requests ({{ count($pendingRequests) }})
                        </button>
                        <button type="button" @click="currentTab = 'resolved'" :class="currentTab === 'resolved' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="shrink-0 py-2 px-3.5 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="check-square" class="w-4 h-4"></i> Resolved Requests
                        </button>
                        <button type="button" @click="currentTab = 'inventory'" :class="currentTab === 'inventory' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="shrink-0 py-2 px-3.5 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="database" class="w-4 h-4"></i> SIM Inventory
                        </button>
                        <button type="button" @click="currentTab = 'swaps'" :class="currentTab === 'swaps' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="shrink-0 py-2 px-3.5 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="repeat" class="w-4 h-4"></i> Pending Swaps ({{ count($pendingSwaps) }})
                        </button>
                        <button type="button" @click="currentTab = 'failed'" :class="currentTab === 'failed' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="shrink-0 py-2 px-3.5 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <i data-lucide="alert-octagon" class="w-4 h-4"></i> Failed Activations ({{ count($failedActivations) }})
                        </button>
                    </div>
                </div>

                <!-- Tab: Pending Requests -->
                <div x-show="currentTab === 'requests'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Pending Actions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5">User</th>
                                    <th class="py-2.5">Number</th>
                                    <th class="py-2.5">Type</th>
                                    <th class="py-2.5">Category/Provider</th>
                                    <th class="py-2.5">Amount</th>
                                    <th class="py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pendingRequests as $req)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3">
                                            <span class="font-bold text-slate-700">{{ $req->user->first_name }} {{ $req->user->last_name }}</span>
                                            <span class="block text-xs text-slate-400">{{ $req->user->role }}</span>
                                        </td>
                                        <td class="py-3 font-semibold text-slate-800">
                                            @if ($req->request_type === 'purchase')
                                                Qty: {{ $req->quantity }}
                                            @else
                                                {{ $req->number }}
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $req->request_type === 'activation' ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-[#0056D2]' }}">
                                                {{ $req->request_type }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="font-medium text-slate-600 block">{{ $req->category }}</span>
                                            <span class="text-xs text-slate-400 block uppercase">{{ $req->provider }}</span>
                                        </td>
                                        <td class="py-3">
                                            <span class="font-bold text-slate-700">₦{{ number_format($req->amount, 2) }}</span>
                                            @if ($req->upline)
                                                <span class="block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 w-fit">
                                                    Routed to: {{ $req->upline->first_name }} {{ $req->upline->last_name }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-right space-x-1 whitespace-nowrap">
                                            @if ($req->request_type === 'activation')
                                                <form action="{{ route('admin.sim-plan.requests.approve', $req->id) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">Approve</button>
                                                </form>
                                            @else
                                                <button type="button" @click="openApproveRequestModal(
                                                    '{{ $req->category }}',
                                                    '{{ $req->provider }}',
                                                    {{ $req->quantity }},
                                                    '{{ route('admin.sim-plan.available-sims') }}',
                                                    '{{ route('admin.sim-plan.requests.approve', $req->id) }}',
                                                    '{{ route('admin.sim-plan.requests.resolve-numbers', $req->id) }}'
                                                )" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">
                                                    Approve
                                                </button>
                                            @endif
                                            <button type="button" @click="Swal.fire({
                                                title: 'Reject Request',
                                                input: 'text',
                                                inputLabel: 'Rejection Reason',
                                                inputPlaceholder: 'Enter reason here...',
                                                showCancelButton: true,
                                                confirmButtonColor: '#e11d48',
                                                cancelButtonColor: '#64748b',
                                                confirmButtonText: 'Reject'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    let f = document.createElement('form');
                                                    f.action = '{{ route('admin.sim-plan.requests.reject', $req->id) }}';
                                                    f.method = 'POST';
                                                    f.innerHTML = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'><input type=\'hidden\' name=\'admin_notes\' value=\'' + (result.value || '') + '\'>';
                                                    document.body.appendChild(f);
                                                    f.submit();
                                                }
                                            })" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">
                                                Reject
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No pending requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Resolved Requests -->
                <div x-show="currentTab === 'resolved'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Resolved Requests Log</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5">User</th>
                                    <th class="py-2.5">Number</th>
                                    <th class="py-2.5">Type</th>
                                    <th class="py-2.5">Amount</th>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($resolvedRequests as $req)
                                    <tr class="border-b border-slate-50">
                                        <td class="py-3">
                                            <span class="font-bold text-slate-700">{{ $req->user->first_name }} {{ $req->user->last_name }}</span>
                                            <span class="block text-xs text-slate-400">{{ $req->user->role }}</span>
                                        </td>
                                        <td class="py-3 font-semibold text-slate-800">
                                            @if ($req->request_type === 'purchase')
                                                Qty: {{ $req->quantity }}
                                            @else
                                                {{ $req->number }}
                                            @endif
                                        </td>
                                        <td class="py-3 font-semibold capitalize">{{ $req->request_type }}</td>
                                        <td class="py-3 font-bold text-slate-700">₦{{ number_format($req->amount, 2) }}</td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $req->status === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                                {{ $req->status }}
                                            </span>
                                            @if ($req->admin_notes)
                                                <span class="block text-xs text-slate-400 italic mt-0.5">{{ $req->admin_notes }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-slate-400 font-medium">{{ $req->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No resolved requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $resolvedRequests->withQueryString()->links('vendor.pagination.custom') }}
                </div>

                <!-- Tab: SIM Inventory -->
                <div x-show="currentTab === 'inventory'" 
                     x-data="{
                        selectedSims: [],
                        maxSelect: 50,
                        assignableUsersMap: {{ \Illuminate\Support\Js::from($assignableUsers->mapWithKeys(fn ($au) => [trim($au->first_name . ' ' . $au->last_name) . ' · ' . ($au->phone ?? '') . ' (' . $au->role . ') #' . $au->id => $au->id]))->toHtml() }},
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
                     class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-800 font-display">SIM Inventory pool</h3>
                    </div>

                    <!-- Filter Form -->
                    <form action="{{ route('admin.sim-plan.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-3 bg-slate-50 p-3 rounded-2xl">
                        <div>
                            <x-text-input type="text" name="search" :value="request('search')"
                                   class="rounded-xl !text-xs" placeholder="Search Number..." />
                        </div>
                        <div>
                            <x-select-input name="category" class="rounded-xl !text-xs">
                                <option value="">Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div>
                            <x-select-input name="provider" class="rounded-xl !text-xs">
                                <option value="">Network</option>
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}" {{ request('provider') === $prov ? 'selected' : '' }}>{{ strtoupper($prov) }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div>
                            <x-select-input name="status" class="rounded-xl !text-xs">
                                <option value="">Status</option>
                                <option value="UNASSIGNED" {{ request('status') === 'UNASSIGNED' ? 'selected' : '' }}>NOT ASSIGNED</option>
                                <option value="ASSIGNED_TO_RM" {{ request('status') === 'ASSIGNED_TO_RM' ? 'selected' : '' }}>ASSIGNED TO REGIONAL MANAGER</option>
                                <option value="ASSIGNED_TO_COORDINATOR" {{ request('status') === 'ASSIGNED_TO_COORDINATOR' ? 'selected' : '' }}>ASSIGNED TO COORDINATOR</option>
                                <option value="ASSIGNED_TO_PARTNER" {{ request('status') === 'ASSIGNED_TO_PARTNER' ? 'selected' : '' }}>ASSIGNED TO PARTNER</option>
                                <option value="ACTIVATED" {{ request('status') === 'ACTIVATED' ? 'selected' : '' }}>ACTIVATED</option>
                                <option value="DEACTIVATED" {{ request('status') === 'DEACTIVATED' ? 'selected' : '' }}>DEACTIVATED</option>
                                <option value="SUSPENDED" {{ request('status') === 'SUSPENDED' ? 'selected' : '' }}>SUSPENDED</option>
                            </x-select-input>
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button type="submit" class="flex-1 !py-2 !px-3 !text-xs">Filter</x-primary-button>
                            <a href="{{ route('admin.sim-plan.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs py-2 px-3 rounded-xl flex items-center justify-center">Reset</a>
                        </div>
                    </form>

                    <!-- Bulk Assign Action Bar -->
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
                                        @foreach ($assignableUsers as $au)
                                            <option value='{{ trim($au->first_name . " " . $au->last_name) }} · {{ $au->phone }} ({{ $au->role }}) #{{ $au->id }}'></option>
                                        @endforeach
                                    </datalist>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#0056D2',
                            confirmButtonText: 'Assign Selected',
                            preConfirm: () => {
                                const val = assignableUsersMap[document.getElementById('bulk_user_id').value.trim()];
                                if (!val) {
                                    Swal.showValidationMessage('Please select a valid user from the list');
                                }
                                return val;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                let f = document.createElement('form');
                                f.action = '{{ route('admin.sim-plan.assign') }}';
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

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5 pl-3 w-8">
                                        <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded text-[#0056D2] focus:ring-[#0056D2]">
                                    </th>
                                    <th class="py-2.5">Number</th>
                                    <th class="py-2.5">Category/Network</th>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5">Assigned To</th>
                                    <th class="py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sims as $sim)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                        <td class="py-3 pl-3">
                                            @if ($sim->status === 'UNASSIGNED')
                                                <input type="checkbox" id="sim_cb_{{ $sim->id }}" value="{{ $sim->id }}"
                                                       :checked="selectedSims.includes({{ $sim->id }})"
                                                       @change="toggleSim({{ $sim->id }}, $event.target.checked)"
                                                       class="sim-checkbox rounded text-[#0056D2] focus:ring-[#0056D2]">
                                            @else
                                                <input type="checkbox" disabled class="rounded bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed">
                                            @endif
                                        </td>
                                        <td class="py-3 font-bold text-slate-800">{{ $sim->number }}</td>
                                        <td class="py-3">
                                            <span class="font-semibold text-slate-700 block">{{ $sim->category }}</span>
                                            <span class="text-xs text-slate-400 block uppercase">{{ $sim->provider }}</span>
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase
                                                {{ $sim->status === 'ACTIVATED' ? 'bg-emerald-50 text-emerald-600' : (str_starts_with($sim->status, 'ASSIGNED_TO') ? 'bg-blue-50 text-blue-600' : ($sim->status === 'UNASSIGNED' ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600')) }}">
                                                @if($sim->status === 'UNASSIGNED')
                                                    NOT ASSIGNED
                                                @else
                                                    {{ str_replace('_', ' ', $sim->status) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            @if ($sim->regionalManager || $sim->coordinator || $sim->user)
                                                @php $holder = $sim->regionalManager ?? $sim->coordinator ?? $sim->user; @endphp
                                                <span class="font-semibold text-slate-700">{{ $holder->first_name }} {{ $holder->last_name }}</span>
                                                <span class="block text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', $holder->role) }}</span>
                                            @else
                                                <span class="text-slate-400 font-semibold italic">None</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-right space-x-1 whitespace-nowrap">
                                            @if (in_array($sim->status, ['ASSIGNED_TO_RM', 'ASSIGNED_TO_COORDINATOR', 'ASSIGNED_TO_PARTNER']))
                                                <form action="{{ route('admin.sim-plan.activate', $sim->id) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-all duration-150">
                                                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                                        Activate
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($sim->status === 'UNASSIGNED')
                                                <button type="button" @click="Swal.fire({
                                                    title: 'Assign SIM Card',
                                                    html: `
                                                        <div class='text-left space-y-2'>
                                                            <label class='text-xs font-bold text-slate-500 uppercase'>Search by Name or Phone</label>
                                                            <input type='text' id='swal_user_id' list='swal_user_id_list' autocomplete='off' placeholder='Start typing a name or phone…' class='w-full py-2.5 px-3 border rounded-xl font-medium text-slate-700'>
                                                            <datalist id='swal_user_id_list'>
                                                                @foreach ($assignableUsers as $au)
                                                                    <option value='{{ trim($au->first_name . " " . $au->last_name) }} · {{ $au->phone }} ({{ $au->role }}) #{{ $au->id }}'></option>
                                                                @endforeach
                                                            </datalist>
                                                        </div>
                                                    `,
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#0056D2',
                                                    confirmButtonText: 'Assign Now',
                                                    preConfirm: () => {
                                                        const val = assignableUsersMap[document.getElementById('swal_user_id').value.trim()];
                                                        if (!val) {
                                                            Swal.showValidationMessage('Please select a valid user from the list');
                                                        }
                                                        return val;
                                                    }
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        let f = document.createElement('form');
                                                        f.action = '{{ route('admin.sim-plan.assign') }}';
                                                        f.method = 'POST';
                                                        f.innerHTML = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'><input type=\'hidden\' name=\'sim_ids[]\' value=\'{{ $sim->id }}\'><input type=\'hidden\' name=\'user_id\' value=\'' + result.value + '\'>';
                                                        document.body.appendChild(f);
                                                        f.submit();
                                                    }
                                                })" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-[#0056D2] hover:bg-[#354062] text-white rounded-xl transition-all duration-150 shadow-sm">
                                                    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                                    Assign
                                                </button>
                                            @else
                                                <form action="{{ route('admin.sim-plan.unassign', $sim->id) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                                        <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                                        Unassign
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">No SIM records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $sims->withQueryString()->links('vendor.pagination.custom') }}
                </div>

                <!-- Tab: Pending Swaps -->
                <div x-show="currentTab === 'swaps'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Pending Swap Requests</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5">SIM Number</th>
                                    <th class="py-2.5">Requested By</th>
                                    <th class="py-2.5">From</th>
                                    <th class="py-2.5">To</th>
                                    <th class="py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pendingSwaps as $swap)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3 font-semibold text-slate-800">
                                            {{ $swap->sim->number ?? 'N/A' }}
                                            <span class="block text-xs text-slate-400">{{ $swap->sim->category ?? '' }} / {{ strtoupper($swap->sim->provider ?? '') }}</span>
                                        </td>
                                        <td class="py-3">
                                            <span class="font-bold text-slate-700">{{ $swap->requester->first_name ?? '' }} {{ $swap->requester->last_name ?? '' }}</span>
                                            <span class="block text-xs text-slate-400">{{ $swap->requester->role ?? '' }}</span>
                                        </td>
                                        <td class="py-3">
                                            <span class="font-semibold text-slate-700">{{ $swap->fromHolder->first_name ?? '' }} {{ $swap->fromHolder->last_name ?? '' }}</span>
                                            <span class="block text-xs text-slate-400 capitalize">{{ $swap->holder_role }}</span>
                                        </td>
                                        <td class="py-3">
                                            <span class="font-semibold text-slate-700">{{ $swap->toHolder->first_name ?? '' }} {{ $swap->toHolder->last_name ?? '' }}</span>
                                            <span class="block text-xs text-slate-400 capitalize">{{ $swap->holder_role }}</span>
                                        </td>
                                        <td class="py-3 text-right space-x-1 whitespace-nowrap">
                                            <form action="{{ route('admin.sim-plan.swaps.approve', $swap->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">Approve</button>
                                            </form>
                                            <button type="button" @click="Swal.fire({
                                                title: 'Reject Swap Request',
                                                input: 'text',
                                                inputLabel: 'Rejection Reason',
                                                inputPlaceholder: 'Enter reason here...',
                                                showCancelButton: true,
                                                confirmButtonColor: '#e11d48',
                                                cancelButtonColor: '#64748b',
                                                confirmButtonText: 'Reject'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    let f = document.createElement('form');
                                                    f.action = '{{ route('admin.sim-plan.swaps.reject', $swap->id) }}';
                                                    f.method = 'POST';
                                                    f.innerHTML = '<input type=\'hidden\' name=\'_token\' value=\'{{ csrf_token() }}\'><input type=\'hidden\' name=\'admin_notes\' value=\'' + (result.value || '') + '\'>';
                                                    document.body.appendChild(f);
                                                    f.submit();
                                                }
                                            })" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">
                                                Reject
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No pending swap requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($resolvedSwaps->total() > 0)
                        <h3 class="font-bold text-slate-800 font-display pb-3 pt-4 border-b border-t border-slate-100">Resolved Swap Requests</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                        <th class="py-2.5">SIM Number</th>
                                        <th class="py-2.5">From</th>
                                        <th class="py-2.5">To</th>
                                        <th class="py-2.5">Status</th>
                                        <th class="py-2.5">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($resolvedSwaps as $swap)
                                        <tr class="border-b border-slate-50">
                                            <td class="py-3 font-semibold text-slate-800">{{ $swap->sim->number ?? 'N/A' }}</td>
                                            <td class="py-3 text-slate-600">{{ $swap->fromHolder->first_name ?? '' }} {{ $swap->fromHolder->last_name ?? '' }}</td>
                                            <td class="py-3 text-slate-600">{{ $swap->toHolder->first_name ?? '' }} {{ $swap->toHolder->last_name ?? '' }}</td>
                                            <td class="py-3">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $swap->status === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                                    {{ $swap->status }}
                                                </span>
                                                @if ($swap->admin_notes)
                                                    <span class="block text-xs text-slate-400 italic mt-0.5">{{ $swap->admin_notes }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 text-slate-400 font-medium">{{ $swap->created_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $resolvedSwaps->withQueryString()->links('vendor.pagination.custom') }}
                    @endif
                </div>

                <!-- Tab: Failed Activations -->
                <div x-show="currentTab === 'failed'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Failed Activation Bonus Top-Ups</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5">Phone Number</th>
                                    <th class="py-2.5">Network</th>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5">Reason</th>
                                    <th class="py-2.5">Failed At</th>
                                    <th class="py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($failedActivations as $failed)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                        <td class="py-3 font-semibold text-slate-800">{{ $failed->phone_number }}</td>
                                        <td class="py-3 text-slate-600 uppercase">{{ $failed->network }}</td>
                                        <td class="py-3">
                                            @if ($failed->status === 'retrying')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-700">Retrying…</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-100 text-red-700">Failed</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-slate-600 max-w-xs">{{ $failed->description }}</td>
                                        <td class="py-3 text-slate-400 font-medium">{{ $failed->created_at->format('M d, Y H:i') }}</td>
                                        <td class="py-3 text-right whitespace-nowrap">
                                            @if ($failed->status === 'failed')
                                                <form action="{{ route('admin.sim-plan.failed-activations.retry', $failed) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">Retry</button>
                                                </form>
                                            @else
                                                <span class="text-slate-300 font-semibold">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">No failed activation bonus top-ups found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $failedActivations->withQueryString()->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>

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
            <a href="{{ route('admin.sim-plan.bulk-assign') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4 text-emerald-600"></i> Bulk Assign SIM
            </a>
            <a href="{{ route('admin.sim-plan.bulk-collect') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                <i data-lucide="user-minus" class="w-4 h-4 text-rose-600"></i> Bulk Collect SIM
            </a>
        </div>
        <button type="button" @click="open = !open" aria-label="Bulk actions"
                class="w-14 h-14 rounded-full bg-[#0056D2] hover:bg-[#0049b8] text-white shadow-xl flex items-center justify-center transition-transform"
                :class="open ? 'rotate-45' : ''">
            <i data-lucide="plus" class="w-6 h-6"></i>
        </button>
    </div>

    @push('scripts')
        <script>
            function openApproveRequestModal(category, provider, quantity, availableSimsUrl, approveUrl, resolveNumbersUrl) {
                fetch(`${availableSimsUrl}?category=${encodeURIComponent(category)}&provider=${encodeURIComponent(provider)}`)
                    .then(r => r.json())
                    .then(sims => {
                        if (sims.length === 0) {
                            Swal.fire('Not Enough Stock', `No SIM(s) available for ${category} / ${provider.toUpperCase()}.`, 'warning');
                            return;
                        }

                        const optionsHtml = sims.map(s => `
                            <label class="flex items-center gap-2 text-xs font-medium py-1">
                                <input type="checkbox" class="approve-sim-cb" value="${s.id}"> ${s.number}
                            </label>
                        `).join('');

                        let activeTab = 'list';

                        Swal.fire({
                            title: `Select up to ${quantity} SIM(s) to Approve`,
                            html: `
                                <div class="text-left">
                                    <div class="flex gap-2 mb-3 border-b border-slate-200">
                                        <button type="button" id="approve-tab-list" class="px-3 py-1.5 text-xs font-bold border-b-2 border-[#0056D2] text-[#0056D2]">Pick from List</button>
                                        <button type="button" id="approve-tab-numbers" class="px-3 py-1.5 text-xs font-bold border-b-2 border-transparent text-slate-400">Enter Numbers</button>
                                    </div>
                                    <div id="approve-panel-list" class="max-h-64 overflow-y-auto space-y-1">${optionsHtml}</div>
                                    <div id="approve-panel-numbers" class="hidden">
                                        <textarea id="approve-numbers-input" rows="6" class="w-full text-xs border border-slate-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#0056D2]" placeholder="08030000000, 08031111111&#10;or one per line"></textarea>
                                        <p class="text-[11px] text-slate-400 mt-1">Separate numbers by comma or new line. Each must be an existing, unassigned ${category} / ${provider.toUpperCase()} SIM.</p>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#0056D2',
                            confirmButtonText: 'Approve Selected',
                            didOpen: () => {
                                const listBtn = document.getElementById('approve-tab-list');
                                const numbersBtn = document.getElementById('approve-tab-numbers');
                                const listPanel = document.getElementById('approve-panel-list');
                                const numbersPanel = document.getElementById('approve-panel-numbers');

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
                                    const checked = Array.from(document.querySelectorAll('.approve-sim-cb:checked')).map(el => el.value);
                                    if (checked.length !== quantity) {
                                        Swal.showValidationMessage(`Please select exactly ${quantity} SIM(s).`);
                                        return false;
                                    }
                                    return { ids: checked, errors: [] };
                                }

                                const numbers = document.getElementById('approve-numbers-input').value;
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
                                f.action = approveUrl;
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
