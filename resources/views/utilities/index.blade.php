<x-app-layout>
    <title>SimPhix - {{ $title ?? 'Buy Airtime' }}</title>

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
        .amount-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }
        @media (max-width: 640px) {
            .amount-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .amount-btn {
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        .amount-btn:hover {
            border-color: #0056D2 !important;
            color: #0056D2 !important;
            background-color: rgb(243 244 246) !important;
        }
        .amount-btn:active {
            transform: scale(0.95);
        }
        #recentRecipientsBody {
            max-height: 480px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #0056D2 #f8f9fa;
        }
        #recentRecipientsBody::-webkit-scrollbar {
            width: 5px;
        }
        #recentRecipientsBody::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 10px;
        }
        #recentRecipientsBody::-webkit-scrollbar-thumb {
            background-color: #0056D2;
            border-radius: 10px;
        }
    </style>
    @endpush

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="phone" class="w-5 h-5"></i>
                    </div>
                    Buy Airtime
                </h1>
                <p class="text-sm text-slate-500 mt-1">Top up any network operator instantly with zero service charges.</p>
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

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Left Side: Airtime Form -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative flex flex-col justify-between h-full">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">Instant Recharge</h3>
                                    <span class="inline-block text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full mt-0.5">Zero Fee</span>
                                </div>
                            </div>
                        </div>

                        {{-- Airtime Form --}}
                        <form id="buyAirtimeForm" method="POST" action="{{ route('buyairtime') }}" class="space-y-5">
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
                                            'mtn'      => ['name' => 'MTN',    'img' => 'mtn.jpg'],
                                            'airtel'   => ['name' => 'Airtel', 'img' => 'Airtel.png'],
                                            'glo'      => ['name' => 'Glo',    'img' => 'glo.jpg'],
                                            'etisalat' => ['name' => '9Mobile','img' => '9Mobile.jpg'],
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

                            {{-- Amount Input --}}
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <x-input-label for="amount_display" value="Amount" class="!mb-0" />
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
                                    <x-text-input type="text" id="amount_display" required autocomplete="off"
                                           class="pl-10 pr-4 text-center font-extrabold text-lg"
                                           placeholder="0.00" />
                                    <input type="hidden" id="amount" name="amount" value="{{ old('amount') }}" required>
                                </div>

                                <div id="amount_in_words" class="text-xs font-bold text-[#0056D2] text-center hidden italic mt-1.5"></div>

                                <div class="flex justify-between text-xs text-slate-400 font-semibold px-1">
                                    <span>Min: ₦50.00</span>
                                    <span>Max: ₦5,000.00</span>
                                </div>

                                {{-- Quick suggestions --}}
                                <div class="amount-grid mt-2">
                                    @php $amounts = [100, 200, 500, 1000, 2000]; @endphp
                                    @foreach ($amounts as $amt)
                                        <button type="button" class="amount-btn py-2 bg-slate-50 border border-slate-200 text-slate-600 hover:bg-slate-100 rounded-xl text-xs font-bold transition-all" 
                                                data-amount="{{ $amt }}">
                                            ₦{{ $amt }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Purchase Button --}}
                            <div class="pt-4">
                                <x-primary-button type="button" id="buy-airtime" class="w-full">
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

            <!-- Right Side: Recent Airtime Purchases -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative flex flex-col justify-between h-full">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2]">
                                    <i data-lucide="history" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800 font-display">Recent Airtime Purchases</h3>
                                    <p class="text-xs text-slate-400">Tap a recent purchase to re-fill form details</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3" id="recentRecipientsBody">
                            @if(isset($recentRecipients) && count($recentRecipients) > 0)
                                <div class="flex flex-col gap-2.5">
                                    @foreach($recentRecipients as $recipient)
                                        <div class="recipient-item flex items-center justify-between p-3.5 rounded-2xl border border-slate-50 hover:bg-slate-50/70 hover:border-slate-100 cursor-pointer transition-all duration-200"
                                             onclick="selectRecentRecipient('{{ $recipient['account_no'] }}', '{{ $recipient['bank_code'] }}')">

                                            <div class="flex items-center gap-3">
                                                <div class="bg-white rounded-full shadow-sm flex items-center justify-center flex-shrink-0 w-10 h-10 border border-slate-100">
                                                    @if(!empty($recipient['bank_url']))
                                                        <img src="{{ $recipient['bank_url'] }}" alt="{{ $recipient['bank_name'] }}"
                                                             class="w-6 h-6 object-contain"
                                                             onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                                                        <i data-lucide="phone" class="w-4 h-4 text-[#0056D2]" style="display:none;"></i>
                                                    @else
                                                        <i data-lucide="phone" class="w-4 h-4 text-[#0056D2]"></i>
                                                    @endif
                                                </div>

                                                <div>
                                                    <span class="font-bold text-sm text-slate-800 block">{{ $recipient['account_no'] }}</span>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span class="text-xs text-slate-500 font-semibold">{{ $recipient['bank_name'] }}</span>
                                                        <span class="text-slate-300 text-xs">•</span>
                                                        <span class="text-xs text-slate-400 font-semibold">{{ $recipient['date'] }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <span class="font-bold text-sm text-slate-800 block">₦{{ number_format($recipient['amount'], 2) }}</span>
                                                <div class="mt-0.5">
                                                    @if($recipient['status'] === 'successful' || $recipient['status'] === 'completed')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">
                                                            Success
                                                        </span>
                                                    @elseif($recipient['status'] === 'failed')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700">
                                                            Failed
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700">
                                                            Processing
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12 text-slate-400">
                                    <div class="bg-slate-50 rounded-2xl w-14 h-14 flex items-center justify-center mb-3 mx-auto border border-slate-100">
                                        <i data-lucide="phone-off" class="w-6 h-6 text-slate-400"></i>
                                    </div>
                                    <h6 class="font-bold text-slate-800 text-sm">No Recent Purchases</h6>
                                    <p class="text-xs text-slate-400 mt-1 max-w-[240px] mx-auto">
                                        Your recent purchases will appear here after your first airtime transaction.
                                    </p>
                                </div>
                            @endif
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const networkOptions      = document.querySelectorAll('.network-option');
            const selectedNetworkInput = document.getElementById('selectedNetwork');
            const amountInput         = document.getElementById('amount');
            const amountDisplay       = document.getElementById('amount_display');
            const amountInWordsDisp   = document.getElementById('amount_in_words');
            const amountButtons       = document.querySelectorAll('.amount-btn');
            const phoneInput          = document.getElementById('mobileno');
            const networkResultDiv    = document.getElementById('networkResult');
            const buyButton           = document.getElementById('buy-airtime');
            const toggleBalance       = document.getElementById('toggleBalance');
            const walletBalance       = document.getElementById('walletBalance');
            const hiddenBalance       = document.getElementById('hiddenBalance');

            // Helper function: Convert number to English words
            function numberToWords(num) {
                if (isNaN(num) || num === '') return '';
                
                let n = parseFloat(num);
                if (n === 0) return 'Zero Naira Only';
                
                const a = [
                    '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                    'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
                ];
                const b = [
                    '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'
                ];
                const g = [
                    '', 'Thousand', 'Million', 'Billion', 'Trillion'
                ];

                function chunk(n) {
                    if (n === 0) return '';
                    let str = '';
                    if (n >= 100) {
                        str += a[Math.floor(n / 100)] + ' Hundred ';
                        n %= 100;
                    }
                    if (n >= 20) {
                        str += b[Math.floor(n / 10)] + ' ';
                        if (n % 10 > 0) {
                            str += a[n % 10] + ' ';
                        }
                    } else if (n > 0) {
                        str += a[n] + ' ';
                    }
                    return str;
                }

                let integerPart = Math.floor(n);
                let decimalPart = Math.round((n - integerPart) * 100);

                let words = '';
                
                if (integerPart === 0) {
                    words = 'Zero ';
                } else {
                    let parts = [];
                    let groupIdx = 0;
                    while (integerPart > 0) {
                        let rem = integerPart % 1000;
                        if (rem > 0) {
                            let cStr = chunk(rem);
                            let gStr = g[groupIdx];
                            parts.unshift(cStr + (gStr ? gStr + ' ' : ''));
                        }
                        integerPart = Math.floor(integerPart / 1000);
                        groupIdx++;
                    }
                    words = parts.join('').trim() + ' ';
                }

                words += 'Naira';

                if (decimalPart > 0) {
                    words += ' and ' + chunk(decimalPart).trim() + ' Kobo';
                }

                return words.replace(/\s+/g, ' ').trim() + ' Only';
            }

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

            // --- Network selection ---
            networkOptions.forEach(option => {
                option.addEventListener('click', function () {
                    networkOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    selectedNetworkInput.value = this.dataset.network;
                    
                    // Add visual feedback
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => this.style.transform = 'scale(1)', 100);
                });
            });

            /* ── Live Amount Comma Formatting & Synchronizing ── */
            if (amountDisplay && amountInput) {
                amountDisplay.addEventListener('input', function (e) {
                    let selectionStart = this.selectionStart;
                    let originalValue = this.value;
                    
                    // Clean: strip everything except digits and dot
                    let cleanValue = originalValue.replace(/[^0-9.]/g, '');
                    
                    // Keep only first dot
                    const parts = cleanValue.split('.');
                    if (parts.length > 2) {
                        cleanValue = parts[0] + '.' + parts.slice(1).join('');
                    }
                    
                    // Limit to maximum 5,000
                    let parsedVal = parseFloat(cleanValue);
                    if (!isNaN(parsedVal) && parsedVal > 5000) {
                        cleanValue = '5000';
                        const originalParts = originalValue.split('.');
                        if (originalParts.length > 1) {
                            cleanValue = '5000.' + originalParts[1].substring(0, 2);
                        }
                    }
                    
                    // Update raw hidden input
                    amountInput.value = cleanValue;
                    
                    if (cleanValue === '') {
                        this.value = '';
                        if (amountInWordsDisp) {
                            amountInWordsDisp.classList.add('hidden');
                        }
                        return;
                    }
                    
                    const newParts = cleanValue.split('.');
                    let integerPart = newParts[0];
                    let decimalPart = newParts[1];
                    
                    // Add commas to the integer part
                    let formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    
                    let formattedValue = formattedInteger;
                    if (decimalPart !== undefined) {
                        formattedValue += '.' + decimalPart;
                    }
                    
                    this.value = formattedValue;
                    
                    // Update amount in words
                    if (amountInWordsDisp) {
                        const words = numberToWords(cleanValue);
                        if (words && parseFloat(cleanValue) >= 1) {
                            amountInWordsDisp.innerText = words;
                            amountInWordsDisp.classList.remove('hidden');
                        } else {
                            amountInWordsDisp.classList.add('hidden');
                        }
                    }
                    
                    // Correct cursor position
                    let lengthDiff = formattedValue.length - originalValue.length;
                    let newSelection = selectionStart + lengthDiff;
                    newSelection = Math.max(0, Math.min(newSelection, formattedValue.length));
                    this.setSelectionRange(newSelection, newSelection);
                });

                // Format to standard 2 decimal places on blur
                amountDisplay.addEventListener('blur', function() {
                    if (amountInput.value) {
                        let val = parseFloat(amountInput.value);
                        if (!isNaN(val)) {
                            this.value = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            amountInput.value = val.toFixed(2);
                        }
                    }
                });

                // Strip trailing .00 on focus for easier editing of whole numbers
                amountDisplay.addEventListener('focus', function() {
                    if (amountInput.value) {
                        let val = parseFloat(amountInput.value);
                        if (!isNaN(val)) {
                            if (val % 1 === 0) {
                                this.value = val.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                            }
                        }
                    }
                });
            }

            // --- Quick amount selection ---
            amountButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const rawVal = this.dataset.amount;
                    amountInput.value = rawVal;
                    if (amountDisplay) {
                        const val = parseFloat(rawVal);
                        amountDisplay.value = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        amountDisplay.classList.add('ring-2', 'ring-emerald-500/20');
                        setTimeout(() => amountDisplay.classList.remove('ring-2', 'ring-emerald-500/20'), 500);
                    }
                    if (amountInWordsDisp) {
                        const words = numberToWords(rawVal);
                        if (words && parseFloat(rawVal) >= 1) {
                            amountInWordsDisp.innerText = words;
                            amountInWordsDisp.classList.remove('hidden');
                        } else {
                            amountInWordsDisp.classList.add('hidden');
                        }
                    }
                });
            });

            // --- Auto network detection ---
            const networkPrefixes = {
                'mtn':      ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916','07025','07026','0704','09065'],
                'glo':      ['0805','0807','0705','0811','0815','0905','0915'],
                'airtel':   ['0802','0808','0701','0708','0812','0901','0902','0904','0907','0912'],
                'etisalat': ['0809','0817','0818','0908','0909']
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
                                networkResultDiv.innerHTML = `<span class="inline-flex items-center gap-1.5 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ${network.toUpperCase()} Detected</span>`;
                            }
                            return;
                        }
                    }
                }
                if (val.length < 4) networkResultDiv.textContent = '';
            };

            phoneInput.addEventListener('input', detectNetwork);
            phoneInput.addEventListener('paste', () => setTimeout(detectNetwork, 100));

            // --- Recent Recipient selection ---
            window.selectRecentRecipient = function(number, network) {
                phoneInput.value = number;
                const option = document.querySelector(`.network-option[data-network="${network}"]`);
                if (option) {
                    option.click();
                } else {
                    phoneInput.dispatchEvent(new Event('input'));
                }
                phoneInput.focus();
            };

            // --- Open Modal (Step 1) ---
            if (buyButton) {
                buyButton.addEventListener('click', function () {
                    const amount = amountInput.value;
                    const number = phoneInput.value;
                    const network = selectedNetworkInput.value;

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
                    if (!amount || amount < 50 || amount > 5000) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Amount',
                            text: 'Recharge amount must be between ₦50 and ₦5,000.',
                            confirmButtonColor: '#0056D2',
                        });
                        if (amountDisplay) amountDisplay.focus();
                        else amountInput.focus();
                        return;
                    }

                    // Populate Summary
                    document.getElementById('confirmAccountName').textContent = number;
                    document.getElementById('confirmBankName').textContent = network.toUpperCase() + ' Airtime';
                    document.getElementById('confirmAccountNo').textContent = number;
                    document.getElementById('confirmAmount').textContent = '₦' + parseFloat(amount).toLocaleString(undefined, {minimumFractionDigits: 2});

                    const pinModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('pinModal'));
                    pinModal.show();
                });
            }

            // --- Modal Step Navigation (Summary -> PIN) ---
            const btnGoToPin = document.getElementById('btnGoToPin');
            if (btnGoToPin) {
                btnGoToPin.addEventListener('click', () => {
                    document.getElementById('confirmationStep')?.classList.add('hidden');
                    document.getElementById('pinStep')?.classList.remove('hidden');
                    
                    document.getElementById('modalTitle').textContent = 'Authorize Airtime';
                    document.getElementById('modalSubtitle').textContent = 'Step 2 of 2 — Security PIN';
                    
                    setTimeout(() => document.getElementById('pinInput_1')?.focus(), 100);
                });
            }

            // --- Final Authorization ---
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
                            const form = document.getElementById('buyAirtimeForm');
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
