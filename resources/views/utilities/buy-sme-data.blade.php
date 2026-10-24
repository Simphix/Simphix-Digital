<x-app-layout>
    <title>SimPhix - Buy Data Bundle</title>

    {{-- Custom Styles --}}
    @push('styles')
    <style>
        .network-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .network-option {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .network-option.active {
            border-color: #0056D2 !important;
            background-color: rgb(243 244 246) !important;
            box-shadow: 0 0 0 2px rgba(66, 81, 124, 0.2) !important;
        }
        .network-option .check-mark {
            display: none;
            position: absolute;
            top: -6px;
            right: -6px;
            background: #fff;
            border-radius: 50%;
            z-index: 5;
            line-height: 1;
        }
        .network-option.active .check-mark {
            display: block;
        }
    </style>
    @endpush

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="wifi" class="w-5 h-5"></i>
                    </div>
                    Buy Data Bundle
                </h1>
                <p class="text-sm text-slate-500 mt-1">Purchase high-speed SME/Gifting data plans instantly at the best prices.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('wallet') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                    <i data-lucide="wallet" class="w-3.5 h-3.5 text-slate-400"></i>
                    My Wallet
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm animate-in fade-in duration-300">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Left Side: Purchase Form -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative flex flex-col justify-between h-full">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">Instant Recharge</h3>
                                    <span class="inline-block text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full mt-0.5">Automated Delivery</span>
                                </div>
                            </div>
                        </div>

                        {{-- Buy SME Data Form --}}
                        <form id="buySmeDataForm" method="POST" action="{{ route('buy-sme-data.submit') }}" class="space-y-5">
                            @csrf
                            <input type="hidden" id="selectedNetwork" name="network" value="{{ old('network') }}">

                            {{-- Phone Number Input --}}
                            <div class="space-y-1.5">
                                <x-input-label for="mobileno" value="Recipient Phone Number" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                    </div>
                                    <x-text-input type="tel" id="mobileno" name="mobileno" :value="old('mobileno')" required
                                           class="pl-10 pr-4 text-center font-semibold"
                                           maxlength="11" pattern="\d{11}" placeholder="080 0000 0000" />
                                </div>
                                <div id="networkResult" class="text-xs font-bold text-[#0056D2] text-center min-h-[1.2rem] mt-1"></div>
                            </div>

                            {{-- Network Selection --}}
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block text-center">Select Network Operator</label>
                                <div class="network-grid">
                                    @php
                                        $networks = [
                                            'MTN'      => ['name' => 'MTN',    'img' => 'mtn.jpg'],
                                            'AIRTEL'   => ['name' => 'Airtel', 'img' => 'Airtel.png'],
                                            'GLO'      => ['name' => 'Glo',    'img' => 'glo.jpg'],
                                            '9MOBILE'  => ['name' => '9Mobile','img' => '9Mobile.jpg'],
                                        ];
                                    @endphp

                                    @foreach ($networks as $key => $network)
                                        <div class="network-option flex flex-col items-center justify-center p-2.5 rounded-2xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-all relative" 
                                             data-network="{{ $key }}">
                                            
                                            <!-- Check Mark -->
                                            <div class="check-mark bg-white border border-slate-200 rounded-full p-0.5 text-[#0056D2] shadow-sm">
                                                <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 20 20"><path d="M0 11l2-2 5 5L18 3l2 2L7 18z"/></svg>
                                            </div>

                                            <img src="{{ asset('assets/images/apps/' . $network['img']) }}" alt="{{ $network['name'] }}" 
                                                 class="rounded-full w-9 h-9 object-contain shadow-sm border border-slate-100 mb-1" 
                                                 onerror="this.src='{{ asset('assets/images/apps/default.png') }}'">
                                            
                                            <span class="text-xs font-bold text-slate-700">{{ $network['name'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Data Type subselection --}}
                            <div class="space-y-1.5">
                                <x-input-label for="type" value="Data Plan Type" />
                                <x-select-input name="type" id="type" class="text-center font-semibold" required>
                                    <option value="">Select Type</option>
                                </x-select-input>
                            </div>

                            {{-- Data Plan --}}
                            <div class="space-y-1.5">
                                <x-input-label for="plan" value="Select Bundle Plan" />
                                <x-select-input name="plan" id="plan" class="text-center font-semibold" required>
                                    <option value="">Select Plan</option>
                                </x-select-input>
                            </div>

                            {{-- Amount --}}
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <x-input-label for="amountToPay" value="Amount to Pay" class="!mb-0" />
                                    <div class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
                                        <span>Balance:</span>
                                        <span id="walletBalance" class="font-bold text-emerald-600 hidden">₦{{ number_format($wallet->balance ?? 0, 2) }}</span>
                                        <span id="hiddenBalance" class="font-bold text-emerald-600">₦ * * * *</span>
                                        <button type="button" id="toggleBalance" class="text-[#0056D2] hover:underline focus:outline-none">
                                            <i data-lucide="eye" class="w-3.5 h-3.5 inline"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 font-bold text-lg">
                                        ₦
                                    </div>
                                    <x-text-input type="text" id="amountToPay" name="amount" readonly required autocomplete="off"
                                           class="pl-10 pr-4 text-center font-extrabold text-lg bg-slate-50"
                                           placeholder="0.00" />
                                </div>
                            </div>

                            {{-- Purchase Button --}}
                            <div class="pt-4">
                                <x-primary-button type="button" id="buy-data-btn" class="w-full">
                                    <i data-lucide="zap" class="w-4 h-4"></i>
                                    Purchase Now
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-8 border-t border-slate-100 pt-4 text-center">
                        <span class="text-xs text-slate-400 font-semibold inline-flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                            Secure 256-bit Encrypted Transaction
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Plan Prices Card -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative flex flex-col justify-between h-full">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                                    <i data-lucide="list" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">SME Data Plan Prices</h3>
                                    <p class="text-xs text-slate-400">Current discounted rates for your account tier: <span class="font-bold text-indigo-600">{{ strtoupper(auth()->user()->role ?? 'personal') }}</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Selection -->
                        <div class="flex border-b border-slate-200">
                            <button type="button" class="tab-btn flex-1 py-3 text-sm font-bold border-b-2 transition-all text-[#0056D2] border-[#0056D2]" data-tab="mtn">MTN</button>
                            <button type="button" class="tab-btn flex-1 py-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all" data-tab="glo">GLO</button>
                            <button type="button" class="tab-btn flex-1 py-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all" data-tab="airtel">AIRTEL</button>
                            <button type="button" class="tab-btn flex-1 py-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-slate-600 transition-all" data-tab="mobile9">9MOBILE</button>
                        </div>

                        <!-- Tab Content Panes -->
                        <div class="tab-panes-container">
                            <div class="tab-pane" id="tab-mtn">
                                @include('utilities.partials.pricing-table-content', ['plans' => $mtnPlans])
                            </div>
                            <div class="tab-pane hidden" id="tab-glo">
                                @include('utilities.partials.pricing-table-content', ['plans' => $gloPlans])
                            </div>
                            <div class="tab-pane hidden" id="tab-airtel">
                                @include('utilities.partials.pricing-table-content', ['plans' => $airtelPlans])
                            </div>
                            <div class="tab-pane hidden" id="tab-mobile9">
                                @include('utilities.partials.pricing-table-content', ['plans' => $mobile9Plans])
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-slate-100 pt-4 text-center">
                        <span class="text-xs text-slate-400 font-semibold inline-flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                            Protected by SimPhix Multi-Factor Authentication
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- PIN Modal --}}
    @include('pages.pin')

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    let pendingSelection = null;

    function autoSelectPlan(network, type, planId) {
        pendingSelection = { type: type, planId: planId };
        
        // Find the network option element and click it
        const option = document.querySelector(`.network-option[data-network="${network}"]`);
        if (option) {
            option.click();
        }
    }

    $(document).ready(function () {
        const networkOptions = document.querySelectorAll('.network-option');
        const selectedNetworkInput = document.getElementById('selectedNetwork');
        const phoneInput = document.getElementById('mobileno');
        const networkResultDiv = document.getElementById('networkResult');
        const toggleBalance = document.getElementById('toggleBalance');
        const walletBalance = document.getElementById('walletBalance');
        const hiddenBalance = document.getElementById('hiddenBalance');
        const buyButton = document.getElementById('buy-data-btn');

        // --- Toggle Balance Visibility ---
        if (toggleBalance) {
            toggleBalance.addEventListener('click', function() {
                if (walletBalance.classList.contains('hidden')) {
                    walletBalance.classList.remove('hidden');
                    hiddenBalance.classList.add('hidden');
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.setAttribute('data-lucide', 'eye-off');
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                } else {
                    walletBalance.classList.add('hidden');
                    hiddenBalance.classList.remove('hidden');
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.setAttribute('data-lucide', 'eye');
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }
                }
            });
        }

        // --- Network option selection ---
        networkOptions.forEach(option => {
            option.addEventListener('click', function () {
                networkOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                selectedNetworkInput.value = this.dataset.network;
                
                // Fetch new types
                fetchDataTypes(this.dataset.network);
                
                // Add visual scale feedback
                this.style.transform = 'scale(0.95)';
                setTimeout(() => this.style.transform = 'scale(1)', 100);
            });
        });

        function fetchDataTypes(network) {
            if(!network) return;
            $.ajax({
                type: "get",
                url: "{{ route('sme.fetch.type') }}",
                data: { id: network },
                dataType: "json",
                success: function (response) {
                    var len = response.length;
                    $("#type").empty();
                    $("#type").append("<option value=''>Data Type</option>");

                    for (var i = 0; i < len; i++) {
                        var plan_type = response[i]["plan_type"];
                        $("#type").append("<option value='" + plan_type + "'>" + plan_type + "</option>");
                    }
                    $("#plan").empty().append("<option value=''>Select Plan</option>");
                    $("#amountToPay").val("");

                    // Trigger pending auto-select plan type if exists
                    if (pendingSelection && pendingSelection.type) {
                        $("#type").val(pendingSelection.type).trigger('change');
                    }
                },
                error: function (data) {
                    console.error("Error fetching data types", data);
                },
            });
        }

        $("#type").change(function () {
            let service_id = $("#selectedNetwork").val();
            let type = $(this).val();
            if(!service_id || !type) return;

            $.ajax({
                type: "get",
                url: "{{ route('sme.fetch.plan') }}",
                data: { id: service_id, type: type },
                dataType: "json",
                success: function (response) {
                    var len = response.length;
                    $("#plan").empty();
                    $("#plan").append("<option value=''>Data Plan</option>");

                    const userRole = "{{ auth()->user()->role ?? 'personal' }}";
                    for (var i = 0; i < len; i++) {
                        let price = parseFloat(response[i]["personal_price"]);
                        if (userRole === 'agent') price = parseFloat(response[i]["agent_price"]);
                        else if (userRole === 'partner') price = parseFloat(response[i]["partner_price"]);
                        else if (['business', 'staff', 'checker', 'super_admin'].includes(userRole)) price = parseFloat(response[i]["business_price"]);

                        var plan_text = response[i]["size"] + " - ₦" + price.toFixed(2) + " (" + response[i]["validity"] + " Days)";
                        var id = response[i]["data_id"];
                        $("#plan").append("<option value='" + id + "'>" + plan_text + "</option>");
                    }
                    $("#amountToPay").val("");

                    // Trigger pending auto-select plan ID if exists
                    if (pendingSelection && pendingSelection.planId) {
                        $("#plan").val(pendingSelection.planId).trigger('change');
                        pendingSelection = null; // Clear active auto-select
                    }
                },
                error: function (data) {
                    console.error("Error fetching data plans", data);
                },
            });
        });

        $("#plan").change(function () {
            let plan_id = $(this).val();
            if(!plan_id) {
                $("#amountToPay").val("");
                return;
            }

            $.ajax({
                type: "get",
                url: "{{ route('sme.fetch.price') }}",
                data: { id: plan_id },
                dataType: "json",
                success: function (response) {
                    $("#amountToPay").val(parseFloat(response).toFixed(2));
                },
                error: function (data) {
                    console.error("Error fetching price", data);
                },
            });
        });

        // --- Auto network detection ---
        const networkPrefixes = {
            'MTN':      ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916','07025','07026','0704','09065'],
            'GLO':      ['0805','0807','0705','0811','0815','0905','0915'],
            'AIRTEL':   ['0802','0808','0701','0708','0812','0901','0902','0904','0907','0912'],
            '9MOBILE':  ['0809','0817','0818','0908','0909']
        };

        const detectNetwork = function () {
            const val = phoneInput.value.replace(/\s+/g, '');
            if (val.length >= 4) {
                const prefix = val.substring(0, 4);
                const prefix5 = val.substring(0, 5);
                
                for (const network in networkPrefixes) {
                    if (networkPrefixes[network].includes(prefix) || networkPrefixes[network].includes(prefix5)) {
                        const opt = document.querySelector(`.network-option[data-network="${network}"]`);
                        if (opt && !opt.classList.contains('active')) {
                            opt.click();
                            networkResultDiv.innerHTML = `<span class="inline-flex items-center gap-1.5 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ${network} Detected</span>`;
                        }
                        return;
                    }
                }
            }
            if (val.length < 4) networkResultDiv.textContent = '';
        };

        phoneInput.addEventListener('input', detectNetwork);
        phoneInput.addEventListener('paste', () => setTimeout(detectNetwork, 100));

        // --- Tab Selection Logic ---
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                tabButtons.forEach(b => {
                    b.classList.remove('text-[#0056D2]', 'border-[#0056D2]');
                    b.classList.add('text-slate-400', 'border-transparent');
                });
                this.classList.remove('text-slate-400', 'border-transparent');
                this.classList.add('text-[#0056D2]', 'border-[#0056D2]');

                const tabId = this.dataset.tab;
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
                document.getElementById(`tab-${tabId}`).classList.remove('hidden');
            });
        });

        // Set MTN tab as active initially
        const mtnTabBtn = document.querySelector('.tab-btn[data-tab="mtn"]');
        if(mtnTabBtn) mtnTabBtn.click();

        // --- Open PIN Confirmation Modal ---
        if (buyButton) {
            buyButton.addEventListener('click', function () {
                const number = phoneInput.value;
                const network = selectedNetworkInput.value;
                const type = document.getElementById('type').value;
                const planId = document.getElementById('plan').value;
                const amount = document.getElementById('amountToPay').value;

                if (!number || number.length < 11) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Phone Number',
                        text: 'Please enter a valid 11-digit phone number.',
                        confirmButtonColor: '#0056D2',
                    });
                    phoneInput.focus();
                    return;
                }
                if (!network) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select Operator',
                        text: 'Please select a network operator.',
                        confirmButtonColor: '#0056D2',
                    });
                    return;
                }
                if (!type) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select Plan Type',
                        text: 'Please select a data plan type.',
                        confirmButtonColor: '#0056D2',
                    });
                    return;
                }
                if (!planId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Select Data Plan',
                        text: 'Please select a data bundle.',
                        confirmButtonColor: '#0056D2',
                    });
                    return;
                }

                // Populate Summary in the pages.pin modal
                document.getElementById('confirmAccountName').textContent = number;
                document.getElementById('confirmBankName').textContent = network.toUpperCase() + ' ' + type + ' Data';
                document.getElementById('confirmAccountNo').textContent = number;
                document.getElementById('confirmAmount').textContent = '₦' + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});

                const pinModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('pinModal'));
                
                // Reset to Step 1 (Summary)
                document.getElementById('confirmationStep')?.classList.remove('hidden');
                document.getElementById('pinStep')?.classList.add('hidden');
                document.getElementById('modalTitle').textContent = 'Confirm Transaction Details';
                document.getElementById('modalSubtitle').textContent = 'Step 1 of 2 — Summary';
                document.getElementById('pinInput_wrap')?.dispatchEvent(new CustomEvent('pin-reset'));
                document.getElementById('pinError')?.classList.add('hidden');

                pinModal.show();
            });
        }

        // --- Modal Step Navigation (Summary -> PIN) ---
        const btnGoToPin = document.getElementById('btnGoToPin');
        if (btnGoToPin) {
            btnGoToPin.addEventListener('click', () => {
                document.getElementById('confirmationStep')?.classList.add('hidden');
                document.getElementById('pinStep')?.classList.remove('hidden');
                
                document.getElementById('modalTitle').textContent = 'Authorize Data Purchase';
                document.getElementById('modalSubtitle').textContent = 'Step 2 of 2 — Security PIN';
                
                setTimeout(() => document.getElementById('pinInput_1')?.focus(), 100);
            });
        }

        // --- Final Authorization and Submission ---
        const confirmPinBtn = document.getElementById('confirmPinBtn');
        if (confirmPinBtn) {
            confirmPinBtn.addEventListener('click', function () {
                const pin = document.getElementById('pinInput').value;
                const pinError = document.getElementById('pinError');
                const pinErrorText = document.getElementById('pinErrorText');
                
                if (!pin || pin.length !== 4) {
                    if (pinErrorText) pinErrorText.textContent = 'Please enter your 4-digit PIN.';
                    if (pinError) pinError.classList.remove('hidden');
                    return;
                }

                confirmPinBtn.disabled = true;
                const loader = document.getElementById('pinLoader');
                const btnText = document.getElementById('confirmPinText');
                if (loader) loader.classList.remove('hidden');
                if (btnText) btnText.textContent = 'Verifying...';

                fetch("{{ route('verify.pin') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ pin })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.valid) {
                        const form = document.getElementById('buySmeDataForm');
                        const hiddenPin = document.createElement('input');
                        hiddenPin.type = 'hidden';
                        hiddenPin.name = 'pin';
                        hiddenPin.value = pin;
                        form.appendChild(hiddenPin);
                        form.submit();
                    } else {
                        if (pinErrorText) pinErrorText.textContent = 'Incorrect PIN. Try again.';
                        if (pinError) pinError.classList.remove('hidden');
                        confirmPinBtn.disabled = false;
                        if (loader) loader.classList.add('hidden');
                        if (btnText) btnText.textContent = 'Authorize Now';
                        document.getElementById('pinInput_wrap').dispatchEvent(new CustomEvent('pin-reset'));
                        document.getElementById('pinInput_1').focus();
                    }
                })
                .catch(() => {
                    if (pinErrorText) pinErrorText.textContent = 'Network error. Please try again.';
                    if (pinError) pinError.classList.remove('hidden');
                    confirmPinBtn.disabled = false;
                    if (loader) loader.classList.add('hidden');
                    if (btnText) btnText.textContent = 'Authorize Now';
                });
            });
        }
    });
    </script>
    @endpush
</x-app-layout>
