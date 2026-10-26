<x-app-layout>
<div class="space-y-6" x-data="{
    showAssignModal: false,
    showReassignModal: false,
    showRevertModal: false,
    showStatusModal: false,
    selectedSimId: '',
    selectedSimNumber: '',
    currentHolderName: '',
    currentHolderRole: '',
    currentStatus: ''
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

    <!-- Top Shared Navigation -->
    @include('bsm.partials.nav')

    <!-- SIM Lifecycle Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900 font-display">SIM Lifecycle Governance</h2>
                <p class="text-xs text-slate-500">Track and govern all platform SIM cards across holding tiers, activation velocity, and customer linkages</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('bsm.lifecycle') }}" class="flex flex-wrap items-center gap-2.5">
                <div class="relative w-full sm:w-64">
                    <input type="text" name="sim_search" value="{{ $simSearch }}" placeholder="Search number, holder, customer…" class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary pl-8 pr-3 py-2 bg-slate-50">
                    <div class="absolute left-2.5 top-2.5 text-slate-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </div>
                </div>

                <select name="lifecycle_status" onchange="this.form.submit()" class="text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3 bg-slate-50">
                    <option value="all" {{ $lifecycleStatus === 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="{{ \App\Support\SimStatus::UNASSIGNED }}" {{ $lifecycleStatus === \App\Support\SimStatus::UNASSIGNED ? 'selected' : '' }}>Unassigned</option>
                    <option value="{{ \App\Support\SimStatus::ASSIGNED_TO_RM }}" {{ $lifecycleStatus === \App\Support\SimStatus::ASSIGNED_TO_RM ? 'selected' : '' }}>Assigned to RM</option>
                    <option value="{{ \App\Support\SimStatus::ASSIGNED_TO_COORDINATOR }}" {{ $lifecycleStatus === \App\Support\SimStatus::ASSIGNED_TO_COORDINATOR ? 'selected' : '' }}>Assigned to Coordinator</option>
                    <option value="{{ \App\Support\SimStatus::ASSIGNED_TO_PARTNER }}" {{ $lifecycleStatus === \App\Support\SimStatus::ASSIGNED_TO_PARTNER ? 'selected' : '' }}>Assigned to Partner</option>
                    <option value="{{ \App\Support\SimStatus::ASSIGNED_TO_BUSINESS }}" {{ $lifecycleStatus === \App\Support\SimStatus::ASSIGNED_TO_BUSINESS ? 'selected' : '' }}>Assigned to Enterprise Business</option>
                    <option value="{{ \App\Support\SimStatus::ASSIGNED_TO_BUSINESS_AGENT }}" {{ $lifecycleStatus === \App\Support\SimStatus::ASSIGNED_TO_BUSINESS_AGENT ? 'selected' : '' }}>Assigned to Business Agent</option>
                    <option value="{{ \App\Support\SimStatus::ACTIVATED }}" {{ $lifecycleStatus === \App\Support\SimStatus::ACTIVATED ? 'selected' : '' }}>Activated</option>
                    <option value="{{ \App\Support\SimStatus::SUSPENDED }}" {{ $lifecycleStatus === \App\Support\SimStatus::SUSPENDED ? 'selected' : '' }}>Suspended</option>
                    <option value="{{ \App\Support\SimStatus::DEACTIVATED }}" {{ $lifecycleStatus === \App\Support\SimStatus::DEACTIVATED ? 'selected' : '' }}>Deactivated</option>
                </select>

                <select name="category" onchange="this.form.submit()" class="text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary py-2 px-3 bg-slate-50">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>

                @if ($simSearch || ($lifecycleStatus && $lifecycleStatus !== 'all') || $selectedCategory || $selectedProvider)
                    <a href="{{ route('bsm.lifecycle') }}" class="p-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto mt-4">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50">
                        <th class="py-2.5 px-3">SIM Number</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Network</th>
                        <th class="py-2.5 px-3">Lifecycle Status</th>
                        <th class="py-2.5 px-3">Current Holder</th>
                        <th class="py-2.5 px-3">Customer Linkage</th>
                        <th class="py-2.5 px-3">Idle Time</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($lifecycleSims as $sim)
                        @php
                            $daysHeld = $sim->daysHeld();
                            $isDormant = $sim->status !== \App\Support\SimStatus::ACTIVATED && $sim->status !== \App\Support\SimStatus::UNASSIGNED && $daysHeld >= 7;
                            $holder = match ($sim->status) {
                                \App\Support\SimStatus::ASSIGNED_TO_RM => $sim->regionalManager,
                                \App\Support\SimStatus::ASSIGNED_TO_COORDINATOR => $sim->coordinator,
                                \App\Support\SimStatus::ASSIGNED_TO_BUSINESS => $sim->business,
                                \App\Support\SimStatus::ASSIGNED_TO_BUSINESS_AGENT => $sim->businessAgent,
                                default => $sim->user ?? $sim->partner,
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $sim->number }}</td>
                            <td class="py-3 px-3 text-xs text-slate-700">{{ $sim->category }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                    {{ $sim->provider }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                @php
                                    $badgeColor = match ($sim->status) {
                                        \App\Support\SimStatus::ACTIVATED => 'bg-emerald-100 text-emerald-800',
                                        \App\Support\SimStatus::UNASSIGNED => 'bg-slate-100 text-slate-700',
                                        \App\Support\SimStatus::SUSPENDED => 'bg-amber-100 text-amber-800',
                                        \App\Support\SimStatus::DEACTIVATED => 'bg-rose-100 text-rose-800',
                                        default => 'bg-blue-100 text-blue-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $sim->status)) }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-xs text-slate-700">
                                @if ($holder)
                                    <p class="font-semibold text-slate-900">{{ $holder->name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ ucfirst(str_replace('_', ' ', $holder->role)) }} &bull; {{ $holder->phone }}</p>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-xs text-slate-700">
                                @if ($sim->customer)
                                    <p class="font-medium text-slate-900">{{ $sim->customer->full_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $sim->customer->phone_number }}</p>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if ($isDormant)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <span>{{ $daysHeld }}d idle</span>
                                    </span>
                                @elseif ($sim->status === \App\Support\SimStatus::ACTIVATED)
                                    <span class="text-xs text-emerald-600 font-semibold">Active</span>
                                @else
                                    <span class="text-xs text-slate-400">{{ $daysHeld }}d</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($sim->status === \App\Support\SimStatus::UNASSIGNED)
                                        <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; showAssignModal = true" class="px-2 py-1 rounded-lg bg-primary hover:bg-primary-dark text-white text-xs font-semibold">
                                            Assign
                                        </button>
                                    @else
                                        <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; currentHolderName = '{{ $holder?->name }}'; currentHolderRole = '{{ $holder?->role }}'; showReassignModal = true" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold" title="Swap Holder">
                                            Swap
                                        </button>

                                        @if ($sim->status !== \App\Support\SimStatus::ACTIVATED)
                                            <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; showRevertModal = true" class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold" title="Recall to Unassigned">
                                                Recall
                                            </button>
                                        @endif
                                    @endif

                                    <button type="button" @click="selectedSimId = '{{ $sim->id }}'; selectedSimNumber = '{{ $sim->number }}'; currentStatus = '{{ $sim->status }}'; showStatusModal = true" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600" title="Change Status">
                                        <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">No SIM cards matched the selected lifecycle filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            {{ $lifecycleSims->links() }}
        </div>
    </div>

    <!-- Modals -->
    <!-- Assign Modal -->
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="showAssignModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display">Assign SIM Stock</h3>
            <p class="text-xs text-slate-500">Allocate SIM <strong class="font-mono text-slate-900" x-text="selectedSimNumber"></strong> to an onboarding manager or partner in the distribution chain.</p>

            <form method="POST" action="{{ route('bsm.lifecycle.assign') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="sim_id" :value="selectedSimId">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Account</label>
                    <select name="target_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                        <option value="">Select recipient...</option>
                        <optgroup label="Regional Managers">
                            @foreach ($regionalManagers as $rm)
                                <option value="{{ $rm->id }}">{{ $rm->name }} (RM) - {{ $rm->phone }}</option>
                            @endforeach
                        </optgroup>
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
                    <button type="button" @click="showAssignModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-primary hover:bg-primary-dark text-white">Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Swap / Reassign Modal -->
    <div x-show="showReassignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="showReassignModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display">Swap / Reassign SIM</h3>
            <p class="text-xs text-slate-500">Lateral swap for SIM <strong class="font-mono text-slate-900" x-text="selectedSimNumber"></strong> away from stagnant or slow holder <span class="font-semibold text-slate-800" x-text="currentHolderName"></span> to accelerate field turnover.</p>

            <form method="POST" action="{{ route('bsm.lifecycle.reassign') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="sim_id" :value="selectedSimId">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">New Holder</label>
                    <select name="new_holder_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                        <option value="">Select replacement holder...</option>
                        <optgroup label="Active Coordinators">
                            @foreach ($coordinators as $coord)
                                <option value="{{ $coord->id }}">{{ $coord->name }} (Coordinator) - {{ $coord->phone }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Active Partners">
                            @foreach ($partners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (Partner) - {{ $p->phone }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showReassignModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-amber-600 hover:bg-amber-700 text-white">Execute Swap</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Revert Modal -->
    <div x-show="showRevertModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="showRevertModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display">Recall SIM to Unassigned Pool</h3>
            <p class="text-xs text-slate-500">Recall SIM <strong class="font-mono text-slate-900" x-text="selectedSimNumber"></strong> back to unassigned inventory. All field holding associations will be cleared.</p>

            <form method="POST" action="{{ route('bsm.lifecycle.revert') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="sim_id" :value="selectedSimId">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Intervention Note</label>
                    <textarea name="note" rows="3" placeholder="e.g. Field agent unresponsive, recalled to central depot..." class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showRevertModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 hover:bg-rose-700 text-white">Confirm Recall</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div x-show="showStatusModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @click.self="showStatusModal = false">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display">Update Lifecycle Status</h3>
            <p class="text-xs text-slate-500">Update administrative state for SIM <strong class="font-mono text-slate-900" x-text="selectedSimNumber"></strong>.</p>

            <form method="POST" :action="'/bsm/lifecycle/' + selectedSimId + '/status'" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Status</label>
                    <select name="status" required class="w-full text-xs rounded-xl border-slate-200 focus:border-primary focus:ring-primary p-2.5">
                        <option value="{{ \App\Support\SimStatus::ACTIVATED }}">Activated</option>
                        <option value="{{ \App\Support\SimStatus::SUSPENDED }}">Suspended</option>
                        <option value="{{ \App\Support\SimStatus::DEACTIVATED }}">Deactivated</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showStatusModal = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-xl bg-primary hover:bg-primary-dark text-white">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
