<x-app-layout>
    <title>SmartSIM - Cash Out Approvals</title>

    <div class="max-w-6xl mx-auto space-y-6" x-data="{ currentTab: 'pending' }">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                        <i data-lucide="banknote" class="w-5 h-5"></i>
                    </div>
                    Cash Out Approvals
                </h1>
                <p class="text-sm text-slate-500 mt-1">Review pending cash-out requests. Approving pays the user's saved bank account directly via PalmPay.</p>
            </div>
        </div>

        @include('admin.partials.cashout-switcher', ['platformPendingCount' => $platformPendingCount])

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="p-4 bg-emerald-50/70 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 flex-shrink-0 shadow-inner">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50/70 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0 shadow-inner">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 flex gap-1">
            <button type="button" @click="currentTab = 'pending'" :class="currentTab === 'pending' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="inbox" class="w-4 h-4"></i> Pending Requests ({{ count($pending) }})
            </button>
            <button type="button" @click="currentTab = 'resolved'" :class="currentTab === 'resolved' ? 'bg-[#0056D2] text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="check-square" class="w-4 h-4"></i> Resolved Requests
            </button>
        </div>

        <!-- Tab: Pending Requests -->
        <div x-show="currentTab === 'pending'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">Awaiting Approval</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">User</th>
                            <th class="py-2.5">Bank</th>
                            <th class="py-2.5">Account</th>
                            <th class="py-2.5">Amount</th>
                            <th class="py-2.5">Requested</th>
                            <th class="py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $req)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                <td class="py-3">
                                    <span class="font-bold text-slate-700">{{ $req->user->first_name }} {{ $req->user->last_name }}</span>
                                    <span class="block text-xs text-slate-400">{{ $req->user->role }}</span>
                                </td>
                                <td class="py-3 font-semibold text-slate-700">{{ $req->bank_name }}</td>
                                <td class="py-3">
                                    <span class="font-semibold text-slate-800 block">{{ $req->account_name }}</span>
                                    <span class="text-xs text-slate-400 block font-mono">{{ $req->account_no }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="font-bold text-slate-700">₦{{ number_format($req->total_charge, 2) }}</span>
                                    @if ($req->tax > 0)
                                        <span class="block text-[11px] text-slate-400">+ ₦{{ number_format($req->tax, 2) }} tax on approval</span>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-400 font-medium">{{ $req->created_at->diffForHumans() }}</td>
                                <td class="py-3 text-right space-x-1 whitespace-nowrap">
                                    <form action="{{ route('admin.cash-out.approve', $req->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg transition-colors">Approve</button>
                                    </form>
                                    <button type="button" @click="Swal.fire({
                                        title: 'Reject Cash Out',
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
                                            f.action = '{{ route('admin.cash-out.reject', $req->id) }}';
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
                                <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">No pending cash-out requests.</td>
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
                            <th class="py-2.5">Account</th>
                            <th class="py-2.5">Amount</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Resolved By</th>
                            <th class="py-2.5">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resolved as $req)
                            <tr class="border-b border-slate-50">
                                <td class="py-3">
                                    <span class="font-bold text-slate-700">{{ $req->user->first_name }} {{ $req->user->last_name }}</span>
                                    <span class="block text-xs text-slate-400">{{ $req->user->role }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="font-semibold text-slate-800 block">{{ $req->bank_name }}</span>
                                    <span class="text-xs text-slate-400 block font-mono">{{ $req->account_no }}</span>
                                </td>
                                <td class="py-3 font-bold text-slate-700">₦{{ number_format($req->total_charge, 2) }}</td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $req->status === 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                        {{ $req->status }}
                                    </span>
                                    @if ($req->admin_notes)
                                        <span class="block text-xs text-slate-400 italic mt-0.5">{{ $req->admin_notes }}</span>
                                    @endif
                                </td>
                                <td class="py-3 text-slate-500 font-medium">{{ $req->approvedBy?->first_name ?? '—' }}</td>
                                <td class="py-3 text-slate-400 font-medium">{{ $req->resolved_at?->format('M d, Y H:i') ?? $req->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">No resolved cash-out requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $resolved->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
