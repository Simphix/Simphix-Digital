<x-app-layout>
    <title>SimPhix - Receipt</title>

    @php
        $isPending = $status === 'pending';
        $isFailed = $status === 'failed';
        $isWithdrawal = $serviceName === 'Wallet Withdrawal';
    @endphp

    <div class="max-w-lg mx-auto space-y-6">
        <!-- Status Header -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <div class="w-16 h-16 rounded-full mx-auto flex items-center justify-center
                {{ $isFailed ? 'bg-rose-50 text-rose-500' : ($isPending ? 'bg-amber-50 text-amber-500' : 'bg-emerald-50 text-emerald-500') }}">
                <i data-lucide="{{ $isFailed ? 'x-circle' : ($isPending ? 'clock' : 'check-circle') }}" class="w-9 h-9"></i>
            </div>

            <h1 class="text-xl font-extrabold font-display text-slate-900 mt-4">
                @if ($isFailed)
                    Transaction Failed
                @elseif ($isPending)
                    Request Submitted
                @else
                    {{ $serviceName }} Successful
                @endif
            </h1>

            <p class="text-sm text-slate-500 mt-1.5 max-w-sm mx-auto">
                @if ($isWithdrawal && $isPending)
                    Your cash-out request has been submitted and is awaiting review from our team. You'll be notified once it's processed.
                @elseif ($isPending)
                    Your request has been submitted and is being processed.
                @elseif ($isFailed)
                    This transaction could not be completed. Contact support if you were charged.
                @else
                    Your {{ strtolower($serviceName) }} has been completed successfully.
                @endif
            </p>

            <span class="inline-flex items-center gap-1.5 mt-4 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide
                {{ $isFailed ? 'bg-rose-50 text-rose-600 border border-rose-100' : ($isPending ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100') }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $isFailed ? 'bg-rose-500' : ($isPending ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                {{ ucfirst($status) }}
            </span>
        </div>

        <!-- Receipt Details -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 font-display">Transaction Details</h3>
            </div>
            <div class="divide-y divide-slate-50 text-sm">
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-slate-400 font-medium">Reference</span>
                    <span class="font-bold text-slate-800 font-mono text-xs">{{ $ref }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-slate-400 font-medium">Service</span>
                    <span class="font-semibold text-slate-700">{{ $serviceName }}</span>
                </div>

                @if ($isWithdrawal)
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-slate-400 font-medium">Bank</span>
                        <span class="font-semibold text-slate-700">{{ $network }}</span>
                    </div>
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-slate-400 font-medium">Account Number</span>
                        <span class="font-semibold text-slate-700">{{ $mobile }}</span>
                    </div>
                    @if ($receiverName)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-slate-400 font-medium">Account Name</span>
                            <span class="font-semibold text-slate-700">{{ $receiverName }}</span>
                        </div>
                    @endif
                @else
                    @if ($network && $network !== 'N/A')
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-slate-400 font-medium">Network</span>
                            <span class="font-semibold text-slate-700 uppercase">{{ $network }}</span>
                        </div>
                    @endif
                    @if ($mobile && $mobile !== 'N/A')
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-slate-400 font-medium">Recipient</span>
                            <span class="font-semibold text-slate-700">{{ $mobile }}</span>
                        </div>
                    @endif
                    @if ($token)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-slate-400 font-medium">Token / PIN</span>
                            <span class="font-bold text-slate-800 font-mono text-xs">{{ $token }}</span>
                        </div>
                    @endif
                    @if ($serial)
                        <div class="flex items-center justify-between px-6 py-3.5">
                            <span class="text-slate-400 font-medium">Serial Number</span>
                            <span class="font-semibold text-slate-700 font-mono text-xs">{{ $serial }}</span>
                        </div>
                    @endif
                @endif

                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-slate-400 font-medium">Amount</span>
                    <span class="font-semibold text-slate-700">₦{{ number_format($amount, 2) }}</span>
                </div>
                @if ($fee > 0)
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-slate-400 font-medium">Fee</span>
                        <span class="font-semibold text-slate-700">₦{{ number_format($fee, 2) }}</span>
                    </div>
                @endif
                @if ($tax > 0)
                    <div class="flex items-center justify-between px-6 py-3.5">
                        <span class="text-slate-400 font-medium">Tax</span>
                        <span class="font-semibold text-slate-700">₦{{ number_format($tax, 2) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between px-6 py-3.5 bg-slate-50/60">
                    <span class="text-slate-500 font-bold">{{ $isWithdrawal ? 'Total Debited' : 'Total Paid' }}</span>
                    <span class="font-extrabold text-slate-900">₦{{ number_format($paid, 2) }}</span>
                </div>
                <div class="flex items-center justify-between px-6 py-3.5">
                    <span class="text-slate-400 font-medium">Date</span>
                    <span class="font-semibold text-slate-700">{{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y \a\t h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('dashboard') }}"
               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white font-semibold text-sm shadow-sm transition-all hover:bg-primary/90">
                <i data-lucide="home" class="w-4 h-4"></i>
                Back to Dashboard
            </a>
            <a href="{{ route('transactions') }}"
               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold text-sm shadow-sm transition-all">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                View All Transactions
            </a>
        </div>
    </div>
</x-app-layout>
