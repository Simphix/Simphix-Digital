<x-app-layout>
    <title>SimPhix - {{ $title ?? 'Cash Out' }}</title>

    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="banknote" class="w-5 h-5"></i>
                    </div>
                    Cash Out
                </h1>
                <p class="text-sm text-slate-500 mt-1">Request a payout to your saved withdrawal account — every request is reviewed by our team before funds are sent.</p>
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

        @unless ($withdrawalAccount)
            <!-- Empty state: no saved withdrawal account -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <i data-lucide="landmark" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-sm">No withdrawal account saved yet</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Add the bank account you want to cash out to from Settings before you can submit a request.</p>
                <a href="{{ route('profile.edit') }}"
                   class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-xs shadow-sm transition-all hover:bg-primary/90">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Withdrawal Account
                </a>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-white px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/10 text-primary">
                            <i data-lucide="banknote" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold font-display text-slate-800">Request Cash Out</h3>
                            <p class="text-xs text-slate-400 mt-0.5 font-medium">Sent for review by our team before payout.</p>
                        </div>
                    </div>
                    <span class="inline-block text-xs font-extrabold text-primary bg-primary/10 px-2.5 py-1 rounded-full uppercase tracking-wider">Manual Review</span>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Eligibility Banner --}}
                    @if($totalVolume < $eligibilityAmount)
                        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-amber-800 flex items-start gap-3 shadow-sm">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                            <div class="text-xs leading-normal">
                                <strong>You are not eligible for payouts yet</strong><br>
                                Complete transactions to unlock cash-out requests.
                            </div>
                        </div>
                    @else
                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                            <i data-lucide="shield-check" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                            <div class="text-xs leading-normal">
                                <strong>Account Verified</strong><br>
                                Your account is active and qualified for cash-out requests.
                            </div>
                        </div>
                    @endif

                    {{-- Saved account summary --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Cashing Out To</label>
                            <a href="{{ route('profile.edit') }}" class="text-xs font-bold text-primary hover:underline">Change account</a>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-center gap-3.5 shadow-inner">
                            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm text-[#0056D2]">
                                <i data-lucide="landmark" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0 flex-grow">
                                <p class="font-bold text-xs text-slate-800 truncate">{{ $withdrawalAccount->account_name }}</p>
                                <p class="text-xs text-slate-400 font-semibold tracking-wide">{{ $withdrawalAccount->bank_name }} &bull; {{ substr($withdrawalAccount->account_no, 0, 3) }}****{{ substr($withdrawalAccount->account_no, -3) }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Cash Out Form --}}
                    <form id="withdrawForm" method="POST" action="{{ route('withdraw.process') }}" class="space-y-4">
                        @csrf

                        {{-- Amount --}}
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <label for="amount_display">Cash Out Amount <span class="text-rose-500">*</span></label>
                                <span class="text-slate-400 lowercase">Balance:
                                    <strong class="text-emerald-600 font-extrabold uppercase">
                                        ₦{{ number_format(auth()->user()->wallet->balance ?? 0, 2) }}
                                    </strong>
                                </span>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">
                                    ₦
                                </div>
                                <x-text-input type="text" id="amount_display"
                                       class="pl-8 pr-4"
                                       placeholder="0.00"
                                       required />
                                <input type="hidden" id="amount" name="amount" required>
                            </div>

                            <div id="amount_in_words" class="text-xs font-bold text-[#0056D2] bg-[#0056D2]/5 px-3 py-2 rounded-xl mt-1.5 hidden leading-normal"></div>

                            <div class="flex justify-between items-center text-xs text-slate-400 font-semibold pt-1">
                                <span>Min: ₦{{ number_format($roleLimits['min'] ?? 100, 2) }}</span>
                                <span>Max Limit: ₦{{ number_format($roleLimits['max'] ?? 500000, 2) }}</span>
                            </div>
                        </div>

                        {{-- Real-time Cash Out Charges Breakdown --}}
                        <div id="charges_breakdown_card" class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-3 hidden transition-all duration-200">
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                                    <i data-lucide="receipt" class="w-3.5 h-3.5 text-[#0056D2]"></i>
                                    Cash Out Breakdown
                                </span>
                                <span id="charges_badge" class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                    No Extra Charges
                                </span>
                            </div>

                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center text-slate-600">
                                    <span>Payout to Bank Account:</span>
                                    <span class="font-bold text-slate-800 font-mono" id="breakdown_payout">₦0.00</span>
                                </div>

                                <div id="breakdown_fee_row" class="flex justify-between items-center text-slate-600 hidden">
                                    <span class="flex items-center gap-1 text-slate-500">
                                        <span id="breakdown_fee_desc">Withdrawal Fee</span>:
                                    </span>
                                    <span class="font-bold text-rose-600 font-mono" id="breakdown_fee">+₦0.00</span>
                                </div>

                                <div id="breakdown_threshold_row" class="flex justify-between items-center text-slate-600 hidden">
                                    <span class="flex items-center gap-1 text-slate-500">
                                        <span id="breakdown_threshold_desc">Transaction Levy</span>:
                                    </span>
                                    <span class="font-bold text-rose-600 font-mono" id="breakdown_threshold">+₦0.00</span>
                                </div>

                                <div id="breakdown_threshold_hint" class="text-[11px] text-slate-400 italic hidden pt-0.5"></div>

                                <div class="flex justify-between items-center pt-2.5 border-t border-slate-200/80 font-bold text-slate-800">
                                    <span>Total Wallet Deduction:</span>
                                    <span class="text-[#0056D2] font-display font-extrabold text-sm sm:text-base font-mono" id="breakdown_total_debit">₦0.00</span>
                                </div>
                            </div>
                        </div>

                        {{-- Warning --}}
                        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-amber-800 flex gap-3 shadow-sm">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                            <div class="text-[11px] leading-relaxed">
                                <strong>Manual Review Required:</strong> Your wallet is debited immediately, but the payout only happens once our team approves this request. Approved requests are non-reversible.
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <x-primary-button type="button" id="proceedBtn" class="w-full"
                                :disabled="$totalVolume < $eligibilityAmount">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            Request Cash Out
                        </x-primary-button>

                        @if(auth()->user()->role === 'super_admin')
                            <div class="text-center pt-2">
                                <a href="{{ route('withdraw.syncBanks') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors bg-slate-50 border border-slate-200/60 px-3 py-1.5 rounded-full">
                                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> Sync Bank Infrastructure
                                </a>
                            </div>
                        @endif
                    </form>
                </div>

                <!-- Footer Info -->
                <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-center gap-1.5 text-xs text-slate-400 font-semibold">
                    <i data-lucide="shield-check" class="w-4 h-4 text-[#0056D2]/80"></i>
                    <span>PCI-DSS Secured Bank Gateway</span>
                </div>
            </div>
        @endunless
    </div>

    {{-- PIN Modal --}}
    @if ($withdrawalAccount)
        @include('pages.pin')
    @endif

    @if ($withdrawalAccount)
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const proceedBtn = document.getElementById('proceedBtn');
        if (!proceedBtn) return; // no saved account, form not rendered

        const amountInput       = document.getElementById('amount');
        const amountDisplay     = document.getElementById('amount_display');
        const amountInWordsDisp = document.getElementById('amount_in_words');

        /* ── Cash Out Charges Configuration ─────────── */
        const userWalletBalance = {{ (float) (auth()->user()->wallet->balance ?? 0) }};
        const cashoutFee = {{ (float) ($cashoutSettings->cashout_fee ?? 0) }};
        const cashoutFeeType = @json($cashoutSettings->cashout_fee_type ?? 'fixed');
        const cashoutFeeDesc = @json($cashoutSettings->cashout_fee_description ?: 'Withdrawal Processing Fee');
        const cashoutThresholdAmount = {{ $cashoutSettings->cashout_threshold_amount !== null ? (float) $cashoutSettings->cashout_threshold_amount : 'null' }};
        const cashoutThresholdFee = {{ (float) ($cashoutSettings->cashout_threshold_fee ?? 0) }};
        const cashoutThresholdDesc = @json($cashoutSettings->cashout_threshold_description ?: 'Transaction Levy / Stamp Duty');

        const breakdownCard        = document.getElementById('charges_breakdown_card');
        const breakdownPayout      = document.getElementById('breakdown_payout');
        const breakdownFeeRow      = document.getElementById('breakdown_fee_row');
        const breakdownFeeDesc     = document.getElementById('breakdown_fee_desc');
        const breakdownFeeVal      = document.getElementById('breakdown_fee');
        const breakdownThreshRow   = document.getElementById('breakdown_threshold_row');
        const breakdownThreshDesc  = document.getElementById('breakdown_threshold_desc');
        const breakdownThreshVal   = document.getElementById('breakdown_threshold');
        const breakdownThreshHint  = document.getElementById('breakdown_threshold_hint');
        const breakdownTotalDebit  = document.getElementById('breakdown_total_debit');
        const chargesBadge         = document.getElementById('charges_badge');

        function formatNaira(num) {
            return '₦' + parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calculateCharges(amount) {
            let baseFee = 0;
            if (cashoutFee > 0) {
                if (cashoutFeeType === 'percent') {
                    baseFee = Math.round((amount * cashoutFee) / 100 * 100) / 100;
                } else {
                    baseFee = cashoutFee;
                }
            }
            let thresholdFee = 0;
            let thresholdMet = false;
            if (cashoutThresholdAmount !== null && cashoutThresholdFee > 0) {
                if (amount > cashoutThresholdAmount) {
                    thresholdFee = cashoutThresholdFee;
                    thresholdMet = true;
                }
            }
            let totalCharges = baseFee + thresholdFee;
            let totalDebit = amount + totalCharges;
            return {
                amount: amount,
                baseFee: baseFee,
                thresholdFee: thresholdFee,
                thresholdMet: thresholdMet,
                totalCharges: totalCharges,
                totalDebit: totalDebit
            };
        }

        function updateBreakdown(rawValue) {
            if (!breakdownCard) return;

            let val = parseFloat(rawValue);
            if (isNaN(val) || val <= 0) {
                breakdownCard.classList.add('hidden');
                return;
            }

            let calc = calculateCharges(val);

            breakdownPayout.textContent = formatNaira(calc.amount);

            if (calc.baseFee > 0) {
                breakdownFeeRow.classList.remove('hidden');
                breakdownFeeDesc.textContent = cashoutFeeDesc + (cashoutFeeType === 'percent' ? ` (${cashoutFee}%)` : '');
                breakdownFeeVal.textContent = '+' + formatNaira(calc.baseFee);
            } else {
                breakdownFeeRow.classList.add('hidden');
            }

            if (calc.thresholdFee > 0) {
                breakdownThreshRow.classList.remove('hidden');
                breakdownThreshDesc.textContent = cashoutThresholdDesc;
                breakdownThreshVal.textContent = '+' + formatNaira(calc.thresholdFee);
            } else {
                breakdownThreshRow.classList.add('hidden');
            }

            if (cashoutThresholdAmount !== null && cashoutThresholdFee > 0 && !calc.thresholdMet) {
                breakdownThreshHint.textContent = `Note: A ${formatNaira(cashoutThresholdFee)} ${cashoutThresholdDesc} applies to cash outs exceeding ${formatNaira(cashoutThresholdAmount)}.`;
                breakdownThreshHint.classList.remove('hidden');
            } else {
                breakdownThreshHint.classList.add('hidden');
            }

            breakdownTotalDebit.textContent = formatNaira(calc.totalDebit);

            if (calc.totalCharges > 0) {
                chargesBadge.className = 'text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-700';
                chargesBadge.textContent = 'Charges: +' + formatNaira(calc.totalCharges);
            } else {
                chargesBadge.className = 'text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700';
                chargesBadge.textContent = 'No Extra Charges';
            }

            breakdownCard.classList.remove('hidden');
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        /* ── Amount Input Styling Config ─────────────── */
        if (amountDisplay) {
            amountDisplay.addEventListener('input', function() {
                let cursorPosition = this.selectionStart;
                let originalValue = this.value;
                let rawValue = originalValue.replace(/,/g, '');

                if (rawValue === '') {
                    amountInput.value = '';
                    updateBreakdown(0);
                    if (amountInWordsDisp) {
                        amountInWordsDisp.textContent = '';
                        amountInWordsDisp.classList.add('hidden');
                    }
                    return;
                }

                if (isNaN(rawValue)) {
                    this.value = rawValue.slice(0, -1);
                    return;
                }

                amountInput.value = rawValue;
                updateBreakdown(rawValue);

                if (amountInWordsDisp) {
                    let w = numberToWords(rawValue);
                    if (w) {
                        amountInWordsDisp.textContent = w;
                        amountInWordsDisp.classList.remove('hidden');
                    } else {
                        amountInWordsDisp.textContent = '';
                        amountInWordsDisp.classList.add('hidden');
                    }
                }

                let parts = rawValue.split('.');
                let integerPart = parts[0];
                let decimalPart = parts[1];

                let formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                let formattedValue = formattedInteger;
                if (decimalPart !== undefined) {
                    formattedValue += '.' + decimalPart;
                }

                this.value = formattedValue;

                let lengthDiff = formattedValue.length - originalValue.length;
                let newSelection = cursorPosition + lengthDiff;
                newSelection = Math.max(0, Math.min(newSelection, formattedValue.length));
                this.setSelectionRange(newSelection, newSelection);
            });

            amountDisplay.addEventListener('blur', function() {
                if (amountInput.value) {
                    let val = parseFloat(amountInput.value);
                    if (!isNaN(val)) {
                        this.value = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        amountInput.value = val.toFixed(2);
                    }
                }
            });

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

        // Modal
        const confirmationStep = document.getElementById('confirmationStep');
        const pinStep          = document.getElementById('pinStep');
        const btnGoToPin       = document.getElementById('btnGoToPin');
        const btnBackToConfirm = document.getElementById('btnBackToConfirm');
        const modalTitle       = document.getElementById('modalTitle');
        const modalSubtitle    = document.getElementById('modalSubtitle');

        let pinModal;
        try { pinModal = new bootstrap.Modal(document.getElementById('pinModal')); }
        catch (e) { console.error('Modal init failed:', e); }

        const roleMinAmount = {{ (float) ($roleLimits['min'] ?? 100) }};
        const roleMaxAmount = {{ (float) ($roleLimits['max'] ?? 500000) }};

        /* ── Proceed Button → Open Modal ──────────────── */
        proceedBtn.addEventListener('click', function () {
            const amount = amountInput.value;
            const numAmount = parseFloat(amount);

            if (!amount || isNaN(numAmount) || numAmount < roleMinAmount) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Amount',
                    text: 'Please enter a valid amount (Min ₦' + roleMinAmount.toLocaleString(undefined, { minimumFractionDigits: 2 }) + ').',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            if (numAmount > roleMaxAmount) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Exceeded',
                    text: 'Amount exceeds the maximum cash out limit of ₦' + roleMaxAmount.toLocaleString(undefined, { minimumFractionDigits: 2 }) + ' for your role.',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            const calc = calculateCharges(numAmount);
            if (userWalletBalance < calc.totalDebit) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Insufficient Balance',
                    text: 'Your wallet balance is ₦' + userWalletBalance.toLocaleString(undefined, { minimumFractionDigits: 2 }) + ', but total required deduction (including charges) is ₦' + calc.totalDebit.toLocaleString(undefined, { minimumFractionDigits: 2 }) + '.',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            document.getElementById('confirmAccountName').textContent = @json($withdrawalAccount->account_name);
            document.getElementById('confirmBankName').textContent    = @json($withdrawalAccount->bank_name);
            document.getElementById('confirmAccountNo').textContent   = @json($withdrawalAccount->account_no);

            const confirmAmountEl     = document.getElementById('confirmAmount');
            const confirmAmountLabel  = document.getElementById('confirmAmountLabel');
            const confirmBreakdown    = document.getElementById('confirmBreakdownContainer');
            const confirmFeeRow       = document.getElementById('confirmFeeRow');
            const confirmFeeDesc      = document.getElementById('confirmFeeDesc');
            const confirmFeeVal       = document.getElementById('confirmFeeVal');
            const confirmThreshRow    = document.getElementById('confirmThresholdRow');
            const confirmThreshDesc   = document.getElementById('confirmThresholdDesc');
            const confirmThreshVal    = document.getElementById('confirmThresholdVal');
            const confirmTotalDebit   = document.getElementById('confirmTotalDebit');

            confirmAmountEl.textContent = formatNaira(numAmount);

            if (confirmBreakdown) {
                if (calc.totalCharges > 0) {
                    if (confirmAmountLabel) confirmAmountLabel.textContent = 'Payout Amount';
                    confirmBreakdown.classList.remove('hidden');

                    if (calc.baseFee > 0 && confirmFeeRow) {
                        confirmFeeRow.classList.remove('hidden');
                        if (confirmFeeDesc) confirmFeeDesc.textContent = cashoutFeeDesc + (cashoutFeeType === 'percent' ? ` (${cashoutFee}%)` : '');
                        if (confirmFeeVal) confirmFeeVal.textContent = '+' + formatNaira(calc.baseFee);
                    } else if (confirmFeeRow) {
                        confirmFeeRow.classList.add('hidden');
                    }

                    if (calc.thresholdFee > 0 && confirmThreshRow) {
                        confirmThreshRow.classList.remove('hidden');
                        if (confirmThreshDesc) confirmThreshDesc.textContent = cashoutThresholdDesc;
                        if (confirmThreshVal) confirmThreshVal.textContent = '+' + formatNaira(calc.thresholdFee);
                    } else if (confirmThreshRow) {
                        confirmThreshRow.classList.add('hidden');
                    }

                    if (confirmTotalDebit) confirmTotalDebit.textContent = formatNaira(calc.totalDebit);
                } else {
                    if (confirmAmountLabel) confirmAmountLabel.textContent = 'Total Amount';
                    confirmBreakdown.classList.add('hidden');
                }
            }

            confirmationStep.classList.remove('hidden');
            pinStep.classList.add('hidden');
            modalTitle.textContent    = 'Confirm Cash Out Request';
            modalSubtitle.textContent = 'Please review details carefully';

            window.dispatchEvent(new CustomEvent('open-modal'));
            try { (pinModal || new bootstrap.Modal(document.getElementById('pinModal'))).show(); } catch (e) {}
        });

        /* ── Modal Step Navigation ────────────────────── */
        btnGoToPin.addEventListener('click', () => {
            confirmationStep.classList.add('hidden');
            pinStep.classList.remove('hidden');
            modalTitle.textContent    = 'Authorize Cash Out';
            modalSubtitle.textContent = 'Step 2 of 2 — Security PIN';
            document.getElementById('pinInput_1').focus();
        });

        btnBackToConfirm?.addEventListener('click', () => {
            pinStep.classList.add('hidden');
            confirmationStep.classList.remove('hidden');
            modalTitle.textContent    = 'Confirm Cash Out Request';
            modalSubtitle.textContent = 'Please review details carefully';
        });

        /* ── PIN Submit ───────────────────────────────── */
        document.getElementById('confirmPinBtn').addEventListener('click', function () {
            const confirmBtn   = this;
            const loader       = document.getElementById('pinLoader');
            const confirmText  = document.getElementById('confirmPinText');
            const pinError     = document.getElementById('pinError');
            const pinErrorText = document.getElementById('pinErrorText');
            const pin          = document.getElementById('pinInput').value.trim();

            function setPinError(msg) {
                if (pinErrorText) pinErrorText.textContent = msg;
                pinError?.classList.remove('hidden');
            }

            if (!pin || pin.length !== 4) { setPinError('Please enter your 4-digit PIN.'); return; }

            confirmBtn.disabled = true;
            loader.classList.remove('hidden');
            confirmText.textContent = 'Verifying...';
            pinError?.classList.add('hidden');

            fetch("{{ route('verify.pin') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ pin })
            })
            .then(r => r.json())
            .then(data => {
                if (data.valid) {
                    const form = document.getElementById('withdrawForm');
                    const h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'pin'; h.value = pin;
                    form.appendChild(h);
                    form.submit();
                } else {
                    setPinError('Incorrect PIN. Please try again.');
                    confirmBtn.disabled = false;
                    loader.classList.add('hidden');
                    confirmText.textContent = 'Authorize Now';
                    document.getElementById('pinInput_wrap').dispatchEvent(new CustomEvent('pin-reset'));
                    document.getElementById('pinInput_1').focus();
                }
            })
            .catch(() => {
                setPinError('Connection error. Please try again.');
                confirmBtn.disabled = false;
                loader.classList.add('hidden');
                confirmText.textContent = 'Authorize Now';
            });
        });

    });
    </script>
    @endif
</x-app-layout>
