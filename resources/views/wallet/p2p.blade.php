<x-app-layout>
    <title>SimPhix - {{ $title ?? 'P2P Wallet Transfer' }}</title>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </div>
                    P2P Transfer
                </h1>
                <p class="text-sm text-slate-500 mt-1">Send funds instantly to any registered SimPhix user's wallet with zero fees.</p>
            </div>
            
            <div class="flex items-center gap-3">
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

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 space-y-2 shadow-sm animate-in fade-in duration-300">
                <div class="flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                    <div class="text-sm font-bold">Please correct the following errors:</div>
                </div>
                <ul class="text-xs list-disc pl-8 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Grid Container -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Left Card: Transfer Form -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-full flex flex-col justify-between">
                    
                    <div>
                        <!-- Card Header -->
                        <div class="bg-white px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/10 text-primary">
                                    <i data-lucide="send" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold font-display text-slate-800">Transfer Funds</h3>
                                    <p class="text-xs text-slate-400 mt-0.5 font-medium">Instantly credit another wallet.</p>
                                </div>
                            </div>
                            <span class="inline-block text-xs font-extrabold text-primary bg-primary/10 px-2.5 py-1 rounded-full uppercase tracking-wider">P2P</span>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 space-y-6">
                            
                            <!-- Welcome Subtitle -->
                            <div class="text-center max-w-sm mx-auto space-y-1.5">
                                <div class="w-12 h-12 rounded-full bg-indigo-50 text-[#0056D2] flex items-center justify-center mx-auto shadow-inner">
                                    <i data-lucide="users" class="w-6 h-6"></i>
                                </div>
                                <h4 class="font-bold text-slate-800 text-sm">Instant Peer-to-Peer Transfer</h4>
                                <p class="text-xs text-slate-400">Enter recipient's Wallet ID, registered Email, or Phone number.</p>
                            </div>

                            <!-- Form -->
                            <form id="transferForm" method="POST" action="{{ route('transfer.process') }}" class="space-y-4">
                                @csrf

                                {{-- Recipient Identifier --}}
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Recipient Identifier</label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-grow">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                            </div>
                                            <x-text-input type="text" id="wallet_id" name="wallet_id"
                                                   class="pl-10 pr-4"
                                                   placeholder="Wallet ID, Email, or Phone"
                                                   required />
                                        </div>
                                        <x-primary-button type="button" id="verifyBtn" onclick="verifyUser()" class="shrink-0">
                                            Verify
                                        </x-primary-button>
                                    </div>
                                    
                                    {{-- Recipient Info Card (Photo + Name) --}}
                                    <div id="verifiedUserCard" class="hidden mt-3 p-4 bg-emerald-50/50 border border-emerald-100 rounded-2xl shadow-sm animate-in fade-in duration-200">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <img id="recipientPhoto" src="{{ asset('assets/img/avatars/1.png') }}" alt="User" class="w-11 h-11 rounded-full border-2 border-white shadow-sm object-cover">
                                                <div class="absolute -bottom-0.5 -right-0.5 bg-emerald-500 text-white rounded-full p-0.5 border border-white shadow-sm">
                                                    <i data-lucide="check" class="w-2.5 h-2.5"></i>
                                                </div>
                                            </div>
                                            <div class="space-y-0.5">
                                                <h6 id="recipientName" class="font-bold text-xs text-slate-800"></h6>
                                                <span class="block text-xs text-slate-400 font-semibold uppercase tracking-wider">Verified SimPhix User</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-1">
                                        <small id="userErrorDisplay" class="text-rose-600 font-bold text-xs flex items-center gap-1"></small>
                                    </div>
                                </div>

                                {{-- Amount --}}
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        <label for="amount">Amount</label>
                                        <span class="text-slate-400 lowercase">Balance: 
                                            <strong class="text-emerald-600 font-extrabold uppercase">
                                                ₦{{ number_format(auth()->user()->wallet->balance ?? 0, 2) }}
                                            </strong>
                                        </span>
                                    </div>
                                    
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-sm">
                                            ₦
                                        </div>
                                        <x-text-input type="number" id="amount" name="amount"
                                               class="pl-8 pr-4"
                                               placeholder="0.00"
                                               min="0.01" step="0.01"
                                               required />
                                    </div>
                                </div>

                                {{-- Description --}}
                                <div class="space-y-2">
                                    <label for="description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Description (Optional)</label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3 pointer-events-none text-slate-400">
                                            <i data-lucide="text" class="w-4 h-4"></i>
                                        </div>
                                        <x-textarea-input id="description" name="description"
                                                  class="pl-10 pr-4"
                                                  rows="2" placeholder="What is this transfer for?"></x-textarea-input>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <x-primary-button type="button" id="proceedBtn" disabled class="w-full mt-4">
                                    Proceed to Transfer
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </x-primary-button>
                            </form>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-center gap-1.5 text-xs text-slate-400 font-semibold">
                        <i data-lucide="shield-check" class="w-4 h-4 text-[#0056D2]/80"></i>
                        <span>Protected by Multi-Factor Authentication</span>
                    </div>

                </div>
            </div>

            <!-- Right Card: Recent Recipients -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden h-full flex flex-col justify-between">
                    
                    <div>
                        <!-- Header -->
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-inner">
                                <i data-lucide="clock-3" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800 font-display">Recent Recipients</h3>
                                <p class="text-xs text-slate-400 mt-0.5 font-medium">Tap a recipient to auto-fill the transfer details.</p>
                            </div>
                        </div>

                        <!-- Body (Scrollable Container) -->
                        <div class="p-6 overflow-y-auto max-h-[460px] space-y-3" id="recentRecipientsBody">
                            @if(isset($recentRecipients) && count($recentRecipients) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($recentRecipients as $recipient)
                                        <div class="flex items-center gap-3 p-3 bg-white border border-slate-200 hover:border-[#0056D2]/30 hover:bg-[#0056D2]/5 rounded-2xl shadow-sm transition-all duration-200 cursor-pointer"
                                             onclick="selectRecentBank('{{ $recipient['bank_code'] }}', '{{ $recipient['account_no'] }}', '{{ $recipient['account_name'] }}')">

                                            <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0">
                                                @if(!empty($recipient['bank_url']))
                                                    <img src="{{ $recipient['bank_url'] }}" alt="{{ $recipient['bank_name'] }}"
                                                         class="w-6 h-6 object-contain"
                                                         onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                                                    <i data-lucide="user" class="w-4 h-4 text-[#0056D2]" style="display:none;"></i>
                                                @else
                                                    <i data-lucide="user" class="w-4 h-4 text-[#0056D2]"></i>
                                                @endif
                                            </div>

                                            <div class="flex-grow min-w-0">
                                                <span class="block text-xs font-bold text-slate-900 truncate leading-snug">{{ $recipient['account_name'] }}</span>
                                                <span class="block text-xs text-slate-400 font-medium truncate mt-0.5">
                                                    {{ $recipient['bank_name'] }} &bull; {{ $recipient['account_no'] }}
                                                </span>
                                            </div>

                                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 shrink-0"></i>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12 text-slate-400 max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto text-slate-300">
                                        <i data-lucide="users-2" class="w-6 h-6"></i>
                                    </div>
                                    <h5 class="font-bold text-slate-800 text-xs">No Recent Transfers</h5>
                                    <p class="text-[11px] text-slate-400 leading-normal px-6">
                                        Your trusted recipients will appear here automatically after your first successful transfer.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-center text-xs text-slate-400 font-semibold gap-1">
                        <span>PCI-DSS Secured System Gateway</span>
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- PIN Modal --}}
    @include('pages.pin')

    <script>
        let verifiedRecipient = null;

        // Custom recipient helper
        function selectRecentBank(bankCode, accountNo, accountName) {
            document.getElementById('wallet_id').value = accountNo;
            verifyUser();
        }

        function verifyUser() {
            const walletId = document.getElementById('wallet_id').value;
            const userErrorDisplay = document.getElementById('userErrorDisplay');
            const verifiedUserCard = document.getElementById('verifiedUserCard');
            const recipientName = document.getElementById('recipientName');
            const recipientPhoto = document.getElementById('recipientPhoto');
            const proceedBtn = document.getElementById('proceedBtn');
            const verifyBtn = document.getElementById('verifyBtn');

            if (!walletId) {
                userErrorDisplay.innerHTML = '<i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 inline-block"></i> Please enter a Wallet ID, Phone, or Email.';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                verifiedUserCard.classList.add('hidden');
                return;
            }

            // UI Feedback
            userErrorDisplay.innerHTML = "";
            verifyBtn.innerHTML = '<span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin inline-block"></span>';
            verifyBtn.disabled = true;

            fetch("{{ route('transfer.verify') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ wallet_id: walletId })
            })
            .then(response => response.json())
            .then(data => {
                verifyBtn.innerHTML = 'Verify';
                verifyBtn.disabled = false;

                if (data.success) {
                    verifiedRecipient = data;
                    recipientName.textContent = data.user_name;
                    recipientPhoto.src = data.photo || "{{ asset('assets/img/avatars/1.png') }}";
                    verifiedUserCard.classList.remove('hidden');
                    userErrorDisplay.innerHTML = "";
                    proceedBtn.disabled = false;
                    
                    // If the user used email/phone, update the field to the canonical Wallet ID
                    document.getElementById('wallet_id').value = data.wallet_id;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                } else {
                    userErrorDisplay.innerHTML = '<i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-500 inline-block"></i> User not found.';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    verifiedUserCard.classList.add('hidden');
                    proceedBtn.disabled = true;
                    verifiedRecipient = null;
                }
            })
            .catch(err => {
                console.error("Verification failed:", err);
                verifyBtn.innerHTML = 'Verify';
                verifyBtn.disabled = false;
                userErrorDisplay.innerHTML = '<i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500 inline-block"></i> Verification failed.';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                verifiedUserCard.classList.add('hidden');
                proceedBtn.disabled = true;
            });
        }

        // Logic for Proceed Button (Populate Modal & Show)
        document.getElementById('proceedBtn').addEventListener('click', function() {
            const amount = document.getElementById('amount').value;
            if (!amount || amount <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Please enter a valid amount.',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            if (!verifiedRecipient) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Recipient Not Verified',
                    text: 'Please verify the recipient first.',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            // Populate Modal Fields (from pages.pin)
            document.getElementById('confirmAccountName').textContent = verifiedRecipient.user_name;
            document.getElementById('confirmBankName').textContent = 'SimPhix Wallet';
            document.getElementById('confirmAccountNo').textContent = verifiedRecipient.wallet_id;
            document.getElementById('confirmAmount').textContent = '₦' + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});

            // Show Modal
            const pinModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('pinModal'));
            pinModal.show();
        });

        // Modal Step Navigation (Summary -> PIN)
        const btnGoToPin = document.getElementById('btnGoToPin');
        if (btnGoToPin) {
            btnGoToPin.addEventListener('click', () => {
                const confStep = document.getElementById('confirmationStep');
                const pinStep = document.getElementById('pinStep');
                if (confStep) confStep.classList.add('hidden');
                if (pinStep) pinStep.classList.remove('hidden');
                
                // Update headers if they exist
                const mt = document.getElementById('modalTitle');
                const ms = document.getElementById('modalSubtitle');
                if (mt) mt.textContent = 'Authorize Payout';
                if (ms) ms.textContent = 'Step 2 of 2 — Security PIN';
                
                setTimeout(() => document.getElementById('pinInput_1')?.focus(), 100);
            });
        }

        // PIN Confirmation Logic
        document.getElementById('confirmPinBtn').addEventListener('click', function() {
            const confirmBtn = this;
            const loader = document.getElementById('pinLoader');
            const confirmText = document.getElementById('confirmPinText');
            const pinError = document.getElementById('pinError');
            const pinErrorText = document.getElementById('pinErrorText');
            const pin = document.getElementById('pinInput').value.trim();

            if (!pin || pin.length !== 4) {
                pinErrorText.textContent = 'Please enter a valid 4-digit PIN.';
                pinError.classList.remove('hidden');
                return;
            }

            confirmBtn.disabled = true;
            loader.classList.remove('hidden');
            confirmText.textContent = "Verifying...";
            pinError.classList.add('hidden');

            // Verify PIN via AJAX first
            fetch("{{ route('verify.pin') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ pin })
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    // Append PIN to the form and submit
                    const form = document.getElementById('transferForm');
                    const pinHiddenInput = document.createElement('input');
                    pinHiddenInput.type = 'hidden';
                    pinHiddenInput.name = 'pin';
                    pinHiddenInput.value = pin;
                    form.appendChild(pinHiddenInput);
                    
                    form.submit();
                } else {
                    pinErrorText.textContent = 'Incorrect PIN. Please try again.';
                    pinError.classList.remove('hidden');
                    confirmBtn.disabled = false;
                    loader.classList.add('hidden');
                    confirmText.textContent = "Authorize Now";
                    
                    // Clear input
                    document.getElementById('pinInput_wrap').dispatchEvent(new CustomEvent('pin-reset'));
                    document.getElementById('pinInput_1').focus();
                }
            })
            .catch(err => {
                console.error("PIN check failed:", err);
                pinErrorText.textContent = 'Network error. Please try again.';
                pinError.classList.remove('hidden');
                confirmBtn.disabled = false;
                loader.classList.add('hidden');
                confirmText.textContent = "Authorize Now";
            });
        });
    </script>
</x-app-layout>