<x-app-layout>
    <title>SimPhix - Inspect Fintava Account ({{ $virtualAccount->account_number }})</title>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{ transferAmount: '{{ $liveBalance !== null && $liveBalance > 0 ? $liveBalance : '' }}' }">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="{{ route('admin.fintava-accounts.index') }}" class="hover:text-[#0056D2] transition">Fintava Accounts</a>
                    <span>/</span>
                    <span class="text-slate-600">{{ $virtualAccount->account_number }}</span>
                </div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="landmark" class="w-5 h-5"></i>
                    </div>
                    <span>{{ $virtualAccount->account_name }} &middot; Loma Bank</span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Account: <span class="font-mono font-bold text-slate-700">{{ $virtualAccount->account_number }}</span>
                    @if ($virtualAccount->user)
                        &bull; User: <a href="{{ route('admin.manage.users.show', $virtualAccount->user->id) }}" class="text-[#0056D2] font-semibold hover:underline">{{ $virtualAccount->user->first_name }} {{ $virtualAccount->user->last_name }} ({{ $virtualAccount->user->email }})</a>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.fintava-accounts.export', $virtualAccount) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-xs">
                    <i data-lucide="download" class="w-4 h-4 text-slate-500"></i>
                    Export Transactions CSV
                </a>
                @if ($virtualAccount->user)
                    <a href="{{ route('admin.manage.users.show', $virtualAccount->user->id) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-600 hover:bg-slate-200 transition">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        View User Profile
                    </a>
                @endif
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Live Balance & Account Metadata Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Live Available Balance Card -->
            <div class="bg-gradient-to-br from-[#0056D2] to-indigo-700 rounded-2xl p-6 text-white shadow-md flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-extrabold tracking-wider text-blue-100">Live Fintava Balance</span>
                    <button onclick="window.location.reload()" title="Refresh Balance" class="p-1 rounded-lg bg-white/10 hover:bg-white/20 transition text-white">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <div class="my-4">
                    @if ($liveBalance !== null)
                        <div class="text-3xl font-black font-display tracking-tight">₦{{ number_format($liveBalance, 2) }}</div>
                        <p class="text-xs text-blue-100/80 mt-1 font-medium">Available balance at provider (Loma Bank)</p>
                    @else
                        <div class="text-xl font-bold text-rose-200">Unavailable</div>
                        <p class="text-xs text-rose-100/90 mt-1">{{ $balanceError ?: 'Could not fetch balance' }}</p>
                    @endif
                </div>

                <div class="pt-3 border-t border-white/15 flex items-center justify-between text-xs text-blue-100">
                    <span>Ledger Balance:</span>
                    <span class="font-bold">₦{{ number_format($ledgerBalance ?? 0, 2) }}</span>
                </div>
            </div>

            <!-- Provider Credentials Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                        <span class="text-xs uppercase font-extrabold tracking-wider text-slate-500">Provider Metadata</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                        {{ $virtualAccount->bank_name }}
                    </span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Account Number</span>
                        <span class="font-mono font-bold text-slate-800">{{ $virtualAccount->account_number }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Wallet ID</span>
                        <span class="font-mono text-slate-600 truncate max-w-[160px]" title="{{ $virtualAccount->provider_wallet_id }}">{{ $virtualAccount->provider_wallet_id ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-slate-400 font-medium">Customer ID</span>
                        <span class="font-mono text-slate-600 truncate max-w-[160px]" title="{{ $virtualAccount->provider_customer_id }}">{{ $virtualAccount->provider_customer_id ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="text-[11px] text-slate-400">
                    Account Status: <span class="font-bold text-emerald-600">{{ $virtualAccount->is_active ? 'Active' : 'Inactive' }}</span> &bull; Provider: <span class="font-bold text-slate-700 uppercase">Fintava</span>
                </div>
            </div>

            <!-- Transfer Balance Action Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-extrabold tracking-wider text-slate-500">Transfer / Sweep Balance</span>
                        <p class="text-[11px] text-slate-400">Move funds to merchant wallet</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.fintava-accounts.transfer', $virtualAccount) }}" class="space-y-3" onsubmit="return confirm('Initiate wallet-to-wallet transfer from this customer account to the merchant wallet?');">
                    @csrf

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Receiver (Merchant Account)</label>
                        <input type="text"
                               name="receiver_account"
                               value="{{ old('receiver_account', $merchantAccount) }}"
                               required
                               placeholder="Merchant account number"
                               class="w-full px-3 py-1.5 text-xs font-mono font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:bg-white focus:border-[#0056D2] transition shadow-xs" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Amount (₦)</label>
                            @if ($liveBalance !== null && $liveBalance > 0)
                                <button type="button"
                                        @click="transferAmount = '{{ $liveBalance }}'"
                                        class="text-[10px] font-bold text-[#0056D2] hover:underline">
                                    All (₦{{ number_format($liveBalance, 2) }})
                                </button>
                            @endif
                        </div>
                        <input type="number"
                               name="amount"
                               x-model="transferAmount"
                               step="0.01"
                               min="1"
                               required
                               placeholder="e.g. 5000.00"
                               class="w-full px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-800 focus:border-[#0056D2] transition shadow-xs" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Narration (Optional)</label>
                        <input type="text"
                               name="narration"
                               value="{{ old('narration') }}"
                               placeholder="Reason for transfer"
                               class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 text-slate-800 focus:border-[#0056D2] transition shadow-xs" />
                    </div>

                    <button type="submit"
                            class="w-full py-2 px-4 rounded-xl bg-[#0056D2] hover:bg-[#0046ad] text-white text-xs font-bold transition shadow-xs flex items-center justify-center gap-1.5">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        Transfer to Merchant Wallet
                    </button>
                    <p class="text-[10px] text-slate-400 text-center">Platform user balance is not debited.</p>
                </form>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-[#0056D2]"></i>
                        Customer Fintava Transactions ({{ count($transactions) }})
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Live transaction log from Fintava BaaS API.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.fintava-accounts.export', $virtualAccount) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition shadow-xs">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i>
                        Download CSV
                    </a>
                </div>
            </div>

            @if ($txnError)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0"></i>
                    <span>{{ $txnError }}</span>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">Date</th>
                            <th class="py-2.5">Reference</th>
                            <th class="py-2.5">Type</th>
                            <th class="py-2.5">Amount</th>
                            <th class="py-2.5">Sender / Receiver</th>
                            <th class="py-2.5">Narration</th>
                            <th class="py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $tx)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors align-top">
                                <td class="py-3 text-slate-500 whitespace-nowrap">{{ $tx['created_at'] }}</td>
                                <td class="py-3 font-mono font-bold text-slate-700">{{ $tx['reference'] }}</td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-[11px] font-bold uppercase',
                                        'bg-emerald-50 text-emerald-600' => in_array(strtolower($tx['type']), ['credit', 'inflow', 'fund']),
                                        'bg-rose-50 text-rose-600' => in_array(strtolower($tx['type']), ['debit', 'outflow', 'transfer']),
                                        'bg-slate-100 text-slate-600' => !in_array(strtolower($tx['type']), ['credit', 'inflow', 'fund', 'debit', 'outflow', 'transfer']),
                                    ])>
                                        {{ $tx['type'] }}
                                    </span>
                                </td>
                                <td class="py-3 font-bold text-slate-800">₦{{ number_format($tx['amount'], 2) }}</td>
                                <td class="py-3 font-mono text-[11px] text-slate-600">
                                    @if ($tx['sender'])
                                        <div>From: {{ $tx['sender'] }}</div>
                                    @endif
                                    @if ($tx['receiver'])
                                        <div>To: {{ $tx['receiver'] }}</div>
                                    @endif
                                    @if (!$tx['sender'] && !$tx['receiver'])
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-600 max-w-xs">{{ $tx['narration'] }}</td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-[11px] font-bold',
                                        'bg-emerald-50 text-emerald-600' => in_array(strtolower($tx['status']), ['success', 'successful', 'completed', 'paid']),
                                        'bg-rose-50 text-rose-600' => in_array(strtolower($tx['status']), ['failed', 'rejected', 'reversed']),
                                        'bg-amber-50 text-amber-600' => in_array(strtolower($tx['status']), ['pending', 'processing']),
                                        'bg-slate-100 text-slate-600' => !in_array(strtolower($tx['status']), ['success', 'successful', 'completed', 'paid', 'failed', 'rejected', 'reversed', 'pending', 'processing']),
                                    ])>
                                        {{ ucfirst($tx['status']) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                    No transactions recorded on Fintava for this customer yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Wallet Sweeps / Transfers Audit History -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="arrow-left-right" class="w-4 h-4 text-emerald-600"></i>
                        Wallet Sweeps & Transfers ({{ count($sweeps) }})
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Automated and manual customer-to-merchant transfers recorded on SimPhix.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">Date</th>
                            <th class="py-2.5">Customer Ref</th>
                            <th class="py-2.5">Deposit Ref</th>
                            <th class="py-2.5">Receiver</th>
                            <th class="py-2.5">Amount</th>
                            <th class="py-2.5">Fee Deducted</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Provider Ref</th>
                            <th class="py-2.5">Payload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sweeps as $sweep)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors align-top">
                                <td class="py-3 text-slate-500 whitespace-nowrap">{{ $sweep->created_at->format('M j, Y g:ia') }}</td>
                                <td class="py-3 font-mono font-bold text-slate-700">{{ $sweep->customer_reference }}</td>
                                <td class="py-3 font-mono text-slate-500">{{ $sweep->deposit_reference }}</td>
                                <td class="py-3 font-mono text-slate-600">{{ $sweep->receiver_account }}</td>
                                <td class="py-3 font-bold text-slate-800">₦{{ number_format((float) $sweep->amount, 2) }}</td>
                                <td class="py-3 text-slate-500">₦{{ number_format((float) $sweep->fee_charged, 2) }}</td>
                                <td class="py-3">
                                    <span @class([
                                        'px-2 py-0.5 rounded-full text-xs font-bold',
                                        'bg-emerald-50 text-emerald-600' => $sweep->status === 'completed',
                                        'bg-rose-50 text-rose-600' => $sweep->status === 'failed',
                                        'bg-amber-50 text-amber-600' => $sweep->status === 'pending',
                                    ])>
                                        {{ ucfirst($sweep->status) }}
                                    </span>
                                    @if ($sweep->status === 'failed' && $sweep->error_message)
                                        <p class="text-[11px] text-rose-500 mt-1 max-w-xs">{{ $sweep->error_message }}</p>
                                    @endif
                                </td>
                                <td class="py-3 font-mono text-[11px] text-slate-600">{{ $sweep->provider_reference ?? '—' }}</td>
                                <td class="py-3">
                                    <details class="max-w-xs">
                                        <summary class="cursor-pointer text-[#0056D2] font-semibold text-[11px]">Payload</summary>
                                        <div class="mt-2 space-y-1.5">
                                            @if ($sweep->request_payload)
                                                <div>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase">Request</p>
                                                    <pre class="text-[10px] bg-slate-50 border border-slate-100 rounded p-1.5 overflow-x-auto max-h-28 overflow-y-auto">{{ json_encode($sweep->request_payload, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if ($sweep->response_payload)
                                                <div>
                                                    <p class="text-[9px] font-bold text-slate-400 uppercase">Response</p>
                                                    <pre class="text-[10px] bg-slate-50 border border-slate-100 rounded p-1.5 overflow-x-auto max-h-28 overflow-y-auto">{{ json_encode($sweep->response_payload, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-slate-400">
                                    No sweeps recorded for this account.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
