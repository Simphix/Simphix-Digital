<x-app-layout>
    <title>SimPhix - My SIM</title>

    <div x-data="{ currentTab: 'sims' }" class="max-w-6xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="smartphone" class="w-5 h-5"></i>
                </div>
                My SIM
            </h1>
            <p class="text-sm text-slate-500 mt-1">The SIM cards you own and the requests you've submitted.</p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-lg border border-slate-100 shadow-sm p-2 flex gap-1">
            <button type="button" @click="currentTab = 'sims'" :class="currentTab === 'sims' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="cpu" class="w-4 h-4"></i> My Registered SIMs
            </button>
            <button type="button" @click="currentTab = 'my_requests'" :class="currentTab === 'my_requests' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-50'" class="flex-1 py-2 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                <i data-lucide="clock" class="w-4 h-4"></i> My Requests
            </button>
        </div>

        <!-- Tab: My Registered SIMs -->
        <div x-show="currentTab === 'sims'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">
                My Registered SIMs
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">Number</th>
                            <th class="py-2.5">Category/Network</th>
                            <th class="py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sims as $sim)
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                <td class="py-3 font-bold text-slate-800">{{ $sim->number }}</td>
                                <td class="py-3">
                                    <span class="font-semibold text-slate-700 block">{{ $sim->category }}</span>
                                    <span class="text-xs text-slate-400 block uppercase">{{ $sim->provider }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase
                                        {{ $sim->status === 'ACTIVATED' ? 'bg-emerald-50 text-emerald-600' : (str_starts_with($sim->status, 'ASSIGNED_TO') ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-600') }}">
                                        @if($sim->status === 'UNASSIGNED')
                                            NOT ASSIGNED
                                        @else
                                            {{ str_replace('_', ' ', $sim->status) }}
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-400 font-semibold">No SIM records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sims->withQueryString()->links('vendor.pagination.custom') }}
        </div>

        <!-- Tab: My Requests -->
        <div x-show="currentTab === 'my_requests'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="font-bold text-slate-800 font-display pb-3 border-b border-slate-100">My SIM Requests</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                            <th class="py-2.5">Number</th>
                            <th class="py-2.5">Request Type</th>
                            <th class="py-2.5">Category/Network</th>
                            <th class="py-2.5">Amount</th>
                            <th class="py-2.5">Status</th>
                            <th class="py-2.5">Date Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr class="border-b border-slate-50">
                                <td class="py-3 font-bold text-slate-800">{{ $req->number }}</td>
                                <td class="py-3 capitalize font-semibold text-slate-700">{{ $req->request_type }}</td>
                                <td class="py-3">
                                    <span class="font-semibold text-slate-600 block">{{ $req->category }}</span>
                                    <span class="text-xs text-slate-400 block uppercase">{{ $req->provider }}</span>
                                </td>
                                <td class="py-3 font-bold text-slate-700">₦{{ number_format($req->amount, 2) }}</td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase
                                        {{ $req->status === 'approved' ? 'bg-emerald-50 text-emerald-600' : ($req->status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-rose-50 text-rose-600') }}">
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
                                <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">You haven't submitted any requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
