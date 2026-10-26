<x-app-layout>
<div class="space-y-6" x-data="{
    showReassignModal: false,
    selectedSimId: '',
    selectedSimNumber: '',
    currentHolderName: '',
    currentHolderRole: ''
}">

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <p class="text-sm font-medium">{{ session('success') }}</p>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                <p class="text-sm font-medium">{{ session('error') }}</p>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center p-2 rounded-lg bg-rose-50 text-rose-600">
                <i data-lucide="zap" class="w-6 h-6"></i>
            </span>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 font-display">Sales Acceleration &amp; Interventions Desk</h1>
                <p class="text-sm text-slate-500">Unblock bottlenecked stock, approve purchase requests, and resolve failed activation bonuses</p>
            </div>
        </div>
    </div>

    <!-- 1. Stalled Field Stock Alert Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Stalled Field Stock (≥ 7 Days)</h2>
                <p class="text-xs text-slate-500">Unactivated SIMs sitting with partners/agents — swap or recall to accelerate turnover</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                {{ $dormantSims->total() }} stuck
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">SIM Number</th>
                        <th class="py-2.5 px-3">Current Holder</th>
                        <th class="py-2.5 px-3">Days In Field</th>
                        <th class="py-2.5 px-3 text-right">Quick Intervention</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($dormantSims as $dSim)
                        @php
                            $dHolder = $dSim->user ?? $dSim->partner ?? $dSim->coordinator ?? $dSim->regionalManager;
                            $dDays = $dSim->daysHeld();
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-900 text-xs">{{ $dSim->number }}</td>
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                <p class="font-medium text-slate-900">{{ $dHolder?->name ?? 'Unknown' }}</p>
                                <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $dHolder?->role ?? '')) }} &bull; {{ $dHolder?->phone ?? '—' }}</p>
                            </td>
                            <td class="py-2.5 px-3 text-xs font-bold text-rose-600">
                                {{ $dDays }} days
                            </td>
                            <td class="py-2.5 px-3 text-right">
                                <button type="button" @click="selectedSimId = '{{ $dSim->id }}'; selectedSimNumber = '{{ $dSim->number }}'; currentHolderName = '{{ $dHolder?->name }}'; currentHolderRole = '{{ $dHolder?->role }}'; showReassignModal = true" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold">
                                    Reassign
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-xs text-slate-400">No dormant stock detected. Velocity is healthy!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            {{ $dormantSims->links() }}
        </div>
    </div>

    <!-- 2. Pending Partner Stock Requests -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Pending Stock Requests</h2>
                <p class="text-xs text-slate-500">Fulfill incoming inventory requests to accelerate partner deployment</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                {{ $pendingRequests->total() }} pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Requester</th>
                        <th class="py-2.5 px-3">Item Requested</th>
                        <th class="py-2.5 px-3">Quantity</th>
                        <th class="py-2.5 px-3">Requested At</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendingRequests as $req)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                <p class="font-medium text-slate-900">{{ $req->user?->name ?? 'User #' . $req->user_id }}</p>
                                <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $req->user?->role ?? '')) }} &bull; {{ $req->user?->phone }}</p>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-slate-700">
                                {{ $req->category }} ({{ strtoupper($req->provider) }})
                            </td>
                            <td class="py-2.5 px-3 text-xs font-bold text-slate-900">{{ $req->quantity }}</td>
                            <td class="py-2.5 px-3 text-xs text-slate-400">{{ $req->created_at->diffForHumans() }}</td>
                            <td class="py-2.5 px-3 text-right">
                                <form method="POST" action="{{ route('bsm.requests.approve', $req) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">
                                        Approve &amp; Dispatch
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-slate-400">All inventory requests are up to date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            {{ $pendingRequests->links() }}
        </div>
    </div>

    <!-- 3. Failed Activation Bonus Retries -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">Failed Activation Bonuses</h2>
                <p class="text-xs text-slate-500">Retry vendor data top-ups that stalled during SIM activation</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                {{ $failedBonusReports->total() }} stalled
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">Target Number</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3">Attempt Date</th>
                        <th class="py-2.5 px-3">Vendor Response</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($failedBonusReports as $rep)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2.5 px-3 font-mono font-semibold text-slate-900 text-xs">{{ $rep->phone ?? $rep->account_number ?? '—' }}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800">
                                    {{ $rep->status }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-slate-400">{{ $rep->created_at->format('M d, H:i') }}</td>
                            <td class="py-2.5 px-3 text-xs text-slate-500 max-w-xs truncate">{{ $rep->description ?? $rep->api_response ?? 'No error payload' }}</td>
                            <td class="py-2.5 px-3 text-right">
                                <form method="POST" action="{{ route('bsm.failed-activations.retry', $rep) }}" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-[#0056D2] hover:bg-blue-700 text-white text-xs font-semibold">
                                        Retry Bonus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-xs text-slate-400">No failed activation bonus attempts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100">
            {{ $failedBonusReports->links() }}
        </div>
    </div>

    <!-- Reassign Modal -->
    <div x-show="showReassignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="showReassignModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display">Reassign Stalled Stock</h3>
            <p class="text-xs text-slate-500">Reassign SIM <strong class="font-mono text-slate-900" x-text="selectedSimNumber"></strong> from <span class="font-semibold text-slate-800" x-text="currentHolderName"></span> to an active partner or coordinator to accelerate sales.</p>

            <form method="POST" action="{{ route('bsm.lifecycle.reassign') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="sim_id" :value="selectedSimId">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Replacement Recipient</label>
                    <select name="new_holder_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                        <option value="">Select recipient...</option>
                        <optgroup label="Coordinators">
                            @foreach ($coordinators as $coord)
                                <option value="{{ $coord->id }}">{{ $coord->name }} (Coordinator) - {{ $coord->phone }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Partners">
                            @foreach ($partners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (Partner) - {{ $p->phone }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showReassignModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-amber-600 hover:bg-amber-700 text-white">Execute Reassignment</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
