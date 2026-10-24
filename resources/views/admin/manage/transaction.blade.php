<x-app-layout>
    <title>SimPhix Admin - System Transactions</title>

    <div x-data="{ selectedTx: null }" class="max-w-7xl mx-auto space-y-6 animate-in fade-in duration-300">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="receipt" class="w-6 h-6"></i>
                    </div>
                    Transactions
                </h1>
                <p class="text-sm text-slate-500 mt-1">Monitor, search, and audit all transaction activities across the entire SimPhix platform.</p>
            </div>
        </div>

        <!-- Transaction Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Credits Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative overflow-hidden flex flex-col justify-between min-h-[145px] hover:shadow-md transition-all duration-300 border-t-4 border-emerald-500 group">
                <div class="absolute -top-10 -right-10 w-28 h-28 bg-emerald-500/5 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100/50 flex items-center justify-center text-emerald-600 shadow-inner">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total  Credits</span>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold font-display text-emerald-600">
                        ₦{{ number_format($totalCredits, 2) }}
                    </div>
                    <span class="text-xs font-semibold text-slate-400 block mt-1">Aggregated platform inflows</span>
                </div>
            </div>

            <!-- Total Debits Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative overflow-hidden flex flex-col justify-between min-h-[145px] hover:shadow-md transition-all duration-300 border-t-4 border-rose-500 group">
                <div class="absolute -top-10 -right-10 w-28 h-28 bg-rose-500/5 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100/50 flex items-center justify-center text-rose-600 shadow-inner">
                            <i data-lucide="trending-down" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total  Debits</span>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold font-display text-rose-600">
                        ₦{{ number_format($totalDebits, 2) }}
                    </div>
                    <span class="text-xs font-semibold text-slate-400 block mt-1">Aggregated platform outflows</span>
                </div>
            </div>

            <!-- Total Records Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative overflow-hidden flex flex-col justify-between min-h-[145px] hover:shadow-md transition-all duration-300 border-t-4 border-indigo-500 group">
                <div class="absolute -top-10 -right-10 w-28 h-28 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100/50 flex items-center justify-center text-[#0056D2] shadow-inner">
                            <i data-lucide="activity" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Transactions</span>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold font-display text-slate-800">
                        {{ number_format($totalCount) }}
                    </div>
                    <span class="text-xs font-semibold text-slate-400 block mt-1">Grand total of database logs</span>
                </div>
            </div>
        </div>

        <!-- Filter Form Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 bg-slate-50/30">
            <form method="GET" action="{{ route('admin.transactions') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                <!-- Search Input -->
                <div class="relative flex-grow">
                    <x-text-input type="text" name="search" :value="request('search')"
                           class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                           placeholder="Search by reference, description, amount, name, or email..." />
                    <div class="absolute left-4 top-3.5 text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                </div>

                <!-- Type Filter -->
                <div class="w-full md:w-44">
                    <x-select-input name="type" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                        <option value="">All Types</option>
                        <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Credit</option>
                        <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Debit</option>
                        <option value="refund" {{ request('type') == 'refund' ? 'selected' : '' }}>Refund</option>
                        <option value="bonus" {{ request('type') == 'bonus' ? 'selected' : '' }}>Bonus</option>
                        <option value="manual_credit" {{ request('type') == 'manual_credit' ? 'selected' : '' }}>Manual Credit</option>
                        <option value="manual_debit" {{ request('type') == 'manual_debit' ? 'selected' : '' }}>Manual Debit</option>
                    </x-select-input>
                </div>

                <!-- Status Filter -->
                <div class="w-full md:w-44">
                    <x-select-input name="status" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                        <option value="">All Statuses</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </x-select-input>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <x-primary-button type="submit" class="flex-1 md:flex-none !text-xs">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        Filter
                    </x-primary-button>
                    @if(request('search') || request('type') || request('status'))
                        <a href="{{ route('admin.transactions') }}" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Transaction Records Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 font-display flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4.5 h-4.5 text-[#0056D2]"></i>
                    All Transactions
                </h3>
                <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-indigo-50 text-[#0056D2] border border-indigo-100/50 uppercase tracking-wider">
                    Super Admin Console
                </span>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6 text-center hidden md:table-cell">S/N</th>
                            <th class="py-4 px-6 whitespace-nowrap">Date</th>
                            <th class="py-4 px-6 hidden lg:table-cell">Reference</th>
                            <th class="py-4 px-6 hidden md:table-cell">User / Performed By</th>
                            <th class="py-4 px-6">Description</th>
                            <th class="py-4 px-6 text-center hidden sm:table-cell">Type</th>
                            <th class="py-4 px-6 text-right">Amount</th>
                            <th class="py-4 px-6 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-semibold text-slate-700">
                        @forelse ($transactions as $transaction)
                            @php
                                $metadata = $transaction->metadata;
                                $purchasedPin = $metadata['purchased_code'] ?? $metadata['purchased_pin'] ?? $metadata['pin'] ?? '';
                                $escapedDescription = str_replace(["'", '"'], ["\\'", '\\"'], $transaction->description);
                                
                                // User display info
                                $userName = $transaction->user ? trim($transaction->user->first_name . ' ' . $transaction->user->last_name) : 'System';
                                $userEmail = $transaction->user ? $transaction->user->email : 'N/A';
                                $userUrl = $transaction->user ? route('admin.manage.users.show', $transaction->user->id) : '#';
                            @endphp
                            <tr style="cursor: pointer;" 
                                @click="selectedTx = { 
                                    id: '{{ $transaction->id }}', 
                                    ref: '{{ $transaction->transaction_ref }}', 
                                    amount: '{{ number_format($transaction->amount, 2) }}', 
                                    fee: '{{ number_format($transaction->fee, 2) }}',
                                    net_amount: '{{ number_format($transaction->net_amount, 2) }}',
                                    type: '{{ $transaction->type }}', 
                                    status: '{{ $transaction->status }}', 
                                    date: '{{ $transaction->created_at->format('d M Y, h:i A') }}', 
                                    description: '{{ $escapedDescription }}', 
                                    purchased_pin: '{{ $purchasedPin }}',
                                    performed_by: '{{ $transaction->performed_by ?? $userName }}',
                                    user_name: '{{ $userName }}',
                                    user_email: '{{ $userEmail }}',
                                    user_url: '{{ $userUrl }}',
                                    meta_json: '{{ json_encode($metadata) }}'
                                }"
                                class="hover:bg-slate-50/50 hover:shadow-inner transition-all duration-150">
                                <td class="py-4 px-6 text-center text-slate-400 font-mono text-xs hidden md:table-cell">
                                    {{ ($transactions->currentPage() - 1) * $transactions->perPage() + $loop->iteration }}
                                </td>
                                <td class="py-4 px-6 text-slate-900 font-bold whitespace-nowrap">
                                    {{ $transaction->created_at->format('d M Y') }}
                                    <span class="block text-xs text-slate-400 font-normal mt-0.5">{{ $transaction->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="py-4 px-6 font-mono text-xs text-slate-500 hidden lg:table-cell">
                                    {{ Str::limit($transaction->transaction_ref, 15) }}
                                </td>
                                <td class="py-4 px-6 hidden md:table-cell">
                                    @if($transaction->user)
                                        <a href="{{ $userUrl }}" class="font-bold text-slate-800 hover:text-[#0056D2] hover:underline block">
                                            {{ $userName }}
                                        </a>
                                        <span class="block text-xs text-slate-400 font-normal font-mono mt-0.5">{{ $userEmail }}</span>
                                    @else
                                        <span class="font-bold text-slate-500 block">System</span>
                                        <span class="block text-xs text-slate-400 font-normal font-mono mt-0.5">internal</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-700 max-w-[200px] truncate" title="{{ $transaction->description }}">
                                    {{ $transaction->description }}
                                </td>
                                <td class="py-4 px-6 text-center hidden sm:table-cell">
                                    @if(in_array($transaction->type, ['credit', 'refund', 'bonus', 'manual_credit']))
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100/50 rounded-full uppercase tracking-wider">
                                            {{ $transaction->type == 'manual_credit' ? 'Credit' : ucfirst($transaction->type) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold bg-slate-100 text-slate-700 border border-slate-200/50 rounded-full uppercase tracking-wider">
                                            {{ $transaction->type == 'manual_debit' ? 'Debit' : 'Debit' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right font-extrabold {{ in_array($transaction->type, ['credit', 'refund', 'bonus', 'manual_credit']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ in_array($transaction->type, ['credit', 'refund', 'bonus', 'manual_credit']) ? '+' : '-' }}₦{{ number_format($transaction->amount, 2) }}
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    @if(in_array($transaction->status, ['completed', 'successful']))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Success
                                        </span>
                                    @elseif($transaction->status == 'failed')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-extrabold bg-rose-50 text-rose-700 border border-rose-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span> Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> {{ ucfirst($transaction->status) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-16 text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-4 max-w-sm mx-auto">
                                        <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100 shadow-inner">
                                            <i data-lucide="inbox" class="w-8 h-8"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-extrabold text-slate-800 font-display">No transactions found</h6>
                                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">We couldn't find any transaction matches in the system database. Try clearing filters or refining your search query.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="p-6 border-t border-slate-100 bg-slate-50/30">
                    {{ $transactions->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Alpine.js Transaction Detail Modal Overlay -->
        <div x-show="selectedTx" 
             class="fixed inset-0 z-[99999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
             
            <div @click.away="selectedTx = null" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-2xl w-full overflow-hidden relative">
                 
                <!-- Modal Header -->
                <div class="py-4 px-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/35 shadow-sm">
                            <i data-lucide="receipt" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 font-display text-sm">System Transaction Detail</h5>
                            <small class="text-slate-500 font-mono text-xs block mt-0.5" x-text="'Ref: ' + selectedTx?.ref"></small>
                        </div>
                    </div>
                    <button type="button" @click="selectedTx = null" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-lg hover:bg-slate-100">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6 bg-white space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Primary Details Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-100">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gross Amount</span>
                            <span class="text-base font-extrabold" 
                                  :class="['credit', 'refund', 'bonus', 'manual_credit'].includes(selectedTx?.type) ? 'text-emerald-600' : 'text-rose-600'"
                                  x-text="(['credit', 'refund', 'bonus', 'manual_credit'].includes(selectedTx?.type) ? '+' : '-') + '₦' + selectedTx?.amount">
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Fee</span>
                            <span class="text-sm font-bold text-slate-800 block mt-0.5" x-text="'₦' + selectedTx?.fee"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Net Amount</span>
                            <span class="text-sm font-bold text-slate-800 block mt-0.5" x-text="'₦' + selectedTx?.net_amount"></span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Status</span>
                            <div>
                                <template x-if="['completed', 'successful'].includes(selectedTx?.status)">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full uppercase tracking-wider mt-0.5">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Success
                                    </span>
                                </template>
                                <template x-if="selectedTx?.status === 'failed'">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-extrabold bg-rose-50 text-rose-700 border border-rose-100 rounded-full uppercase tracking-wider mt-0.5">
                                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span> Failed
                                    </span>
                                </template>
                                <template x-if="!['completed', 'successful', 'failed'].includes(selectedTx?.status)">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-100 rounded-full uppercase tracking-wider mt-0.5">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> <span x-text="selectedTx?.status"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- User and Context Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200/80">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">User / Account Info</span>
                            <div class="space-y-1.5">
                                <div class="text-sm font-semibold text-slate-800">
                                    Name: <a :href="selectedTx?.user_url" class="font-bold text-[#0056D2] hover:underline" x-text="selectedTx?.user_name"></a>
                                </div>
                                <div class="text-xs text-slate-600 font-mono">
                                    Email: <span x-text="selectedTx?.user_email"></span>
                                </div>
                                <div class="text-xs text-slate-600">
                                    Performed By: <span class="font-bold" x-text="selectedTx?.performed_by"></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200/80">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Timestamp & Metadata</span>
                            <div class="space-y-1.5">
                                <div class="text-xs text-slate-700">
                                    Created At: <span class="font-bold" x-text="selectedTx?.date"></span>
                                </div>
                                <div class="text-xs text-slate-700">
                                    Transaction Type: <span class="font-mono uppercase font-bold" x-text="selectedTx?.type"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Card -->
                    <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100/50 space-y-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Description</span>
                            <p class="text-sm font-semibold text-slate-700 mt-1 leading-relaxed" x-text="selectedTx?.description"></p>
                        </div>
                        
                        <!-- Pin Copy Area -->
                        <div x-show="selectedTx?.purchased_pin" class="p-4 bg-indigo-50/50 border border-indigo-100/60 rounded-xl flex items-center justify-between shadow-inner">
                            <div>
                                <span class="block text-xs font-bold text-indigo-500 uppercase tracking-wider">Purchased PIN/Token</span>
                                <span class="font-mono font-bold text-slate-900 text-sm mt-1 block select-all" x-text="selectedTx?.purchased_pin"></span>
                            </div>
                            <button class="flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-indigo-50 border border-indigo-200 text-indigo-600 rounded-lg text-xs font-bold shadow-sm transition-all duration-150 hover:-translate-y-0.5 active:translate-y-0"
                                    @click="copyToClipboard(selectedTx?.purchased_pin, 'PIN')">
                                <i class="w-3.5 h-3.5" data-lucide="copy"></i> Copy PIN
                            </button>
                        </div>
                    </div>

                    <!-- Raw JSON Metadata Block -->
                    <div class="space-y-2">
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Raw Metadata Logs</span>
                        <pre class="p-4 bg-slate-900 text-slate-300 rounded-2xl border border-slate-800 text-xs font-mono overflow-x-auto max-h-48"
                             x-text="JSON.stringify(JSON.parse(selectedTx?.meta_json || '{}'), null, 4)"></pre>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer py-4 px-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <x-secondary-button type="button" @click="selectedTx = null" class="!text-xs">
                        Close
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
            // Ensure icons render after modal operations/refresh
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        </script>
    @endpush
</x-app-layout>
