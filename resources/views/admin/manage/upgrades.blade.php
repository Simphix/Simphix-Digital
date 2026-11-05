<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Account Upgrade Requests</h1>
            <p class="text-xs text-slate-400 mt-1">Review business credentials, CAC registration details, and process role promotions.</p>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Pending Upgrades Section -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-700 font-display flex items-center gap-2">
                <span class="w-2.5 h-2.5 bg-amber-400 rounded-full animate-pulse"></span>
                Pending Requests ({{ count($pendingUpgrades) }})
            </h2>

            @forelse ($pendingUpgrades as $req)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-[#0056D2]/5 text-[#0056D2] rounded-2xl flex items-center justify-center font-extrabold text-sm uppercase">
                                {{ substr($req->first_name, 0, 1) }}{{ substr($req->last_name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800 font-display">{{ $req->first_name }} {{ $req->middle_name }} {{ $req->last_name }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $req->email }} | {{ $req->phone ?? 'No Phone' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400">Request:</span>
                            <span class="px-3 py-1 text-xs font-extrabold bg-[#0056D2]/10 text-[#0056D2] rounded-full uppercase tracking-wider">
                                {{ $req->role }}
                            </span>
                            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300"></i>
                            <span class="px-3 py-1 text-xs font-extrabold bg-indigo-500 text-white rounded-full uppercase tracking-wider">
                                {{ $req->pending_role }}
                            </span>
                        </div>
                    </div>

                    <!-- Business Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 p-5 bg-slate-50 rounded-2xl border border-slate-100/50">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Business Name</span>
                            <span class="text-sm font-bold text-slate-700">{{ $req->business_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Business Type</span>
                            <span class="text-sm font-bold text-slate-700 capitalize">{{ str_replace('_', ' ', $req->business_type) }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">CAC Registration No.</span>
                            <span class="text-sm font-bold text-slate-700">{{ $req->cac_number }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gender / Requested At</span>
                            <span class="text-sm font-bold text-slate-700 block capitalize">{{ $req->gender }}</span>
                            <span class="text-xs text-slate-400 mt-0.5 block">{{ $req->upgrade_requested_at ? $req->upgrade_requested_at->format('M d, Y h:i A') : 'No Date' }}</span>
                        </div>
                    </div>

                    <!-- Actions Form -->
                    <div class="flex justify-end gap-3 pt-2">
                        @if (auth()->user()->role === 'business_sales_manager' && $req->pending_role === 'business_sales_manager')
                            <div class="inline-flex items-center gap-2 px-3.5 py-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-bold">
                                <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600"></i>
                                <span>BSM upgrade requires Super Admin approval</span>
                            </div>
                            <form method="POST" action="{{ route('admin.manage.upgrades.reject', $req) }}">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold text-xs rounded-xl border border-rose-100 transition-all duration-200">
                                    Decline Upgrade
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.manage.upgrades.reject', $req) }}">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold text-xs rounded-xl border border-rose-100 transition-all duration-200">
                                    Decline Upgrade
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.manage.upgrades.approve', $req) }}">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-xs rounded-xl shadow-md shadow-emerald-600/10 transition-all duration-200">
                                    Approve Upgrade
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400 text-sm">
                    No pending upgrade requests found.
                </div>
            @endforelse
        </div>

        <!-- Historical Upgrades Section -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-700 font-display">Upgrade History</h2>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6">User</th>
                                <th class="py-4 px-6">Business Name</th>
                                <th class="py-4 px-6">Requested Tier</th>
                                <th class="py-4 px-6">Review Date</th>
                                <th class="py-4 px-6 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                            @forelse ($historicalUpgrades as $hist)
                                <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                    <td class="py-4 px-6">
                                        <span class="block text-slate-800 font-bold font-display">{{ $hist->first_name }} {{ $hist->last_name }}</span>
                                        <span class="block text-xs text-slate-400">{{ $hist->email }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="block text-slate-700 font-semibold">{{ $hist->business_name ?? 'N/A' }}</span>
                                        <span class="block text-xs text-slate-400">{{ $hist->cac_number ?? 'N/A' }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-slate-100 text-slate-600 border border-slate-200/50 uppercase tracking-wider">
                                            {{ $hist->pending_role ?? $hist->role }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-400 text-xs">
                                        {{ $hist->updated_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        @if ($hist->upgrade_status === 'approved')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                                Approved
                                            </span>
                                        @elseif ($hist->upgrade_status === 'rejected')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-100 rounded-full uppercase tracking-wider">
                                                Rejected
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-slate-400 text-sm">
                                        No request history recorded.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $historicalUpgrades->withQueryString()->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
</x-app-layout>
