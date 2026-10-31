<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto" x-data="downlineBatchShow()">
        <!-- Header & Breadcrumbs -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('admin.manage.downline-batches.index') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Downline Batches
                </a>
                <div class="flex flex-wrap items-center gap-3 mt-1.5">
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">
                        {{ $batch->batch_reference }}
                    </h1>
                    @if ($batch->status === \App\Models\DownlineBatch::STATUS_HELD)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i> Held ({{ $batch->current_count }} pending)
                        </span>
                    @elseif ($batch->status === \App\Models\DownlineBatch::STATUS_PARTIALLY_TRANSFERRED)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Partially Transferred ({{ $batch->current_count }} held, {{ $batch->transferred_count }} transferred)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Fully Transferred
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    {{ $batch->title }} &bull; Created {{ $batch->created_at->format('M d, Y h:i A') }}
                    @if ($batch->creator) by {{ $batch->creator->name }} @endif
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.manage.replace-manager.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold shadow-sm transition">
                    <i data-lucide="repeat" class="w-3.5 h-3.5 text-slate-400"></i>
                    Replace Manager
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300">
                {{ session('error') }}
            </div>
        @endif

        <!-- Batch Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Source / Original Upline -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Original Upline</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wide bg-slate-100 text-slate-600">
                        {{ str_replace('_', ' ', $batch->source_role ?? 'Manager') }}
                    </span>
                </div>
                @if ($batch->sourceUser)
                    <div>
                        <a href="{{ route('admin.manage.users.show', $batch->sourceUser) }}" class="text-base font-bold text-slate-800 hover:text-indigo-600 transition">
                            {{ $batch->sourceUser->name }}
                        </a>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $batch->sourceUser->email }}</div>
                        <div class="text-xs text-slate-500">{{ $batch->sourceUser->phone }}</div>
                    </div>
                @else
                    <div class="text-xs text-slate-400 italic">User #{{ $batch->source_user_id }} (Deleted)</div>
                @endif
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Batch Type:</span>
                    <strong class="text-slate-700 capitalize">{{ str_replace('_', ' ', $batch->source_type ?? 'detached') }}</strong>
                </div>
            </div>

            <!-- Holding Anchor (Master Manager) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-extrabold text-indigo-500 uppercase tracking-wider">Holding Master Manager</span>
                    <i data-lucide="shield-check" class="w-4 h-4 text-indigo-500"></i>
                </div>
                @if ($batch->masterManager)
                    <div>
                        <div class="text-base font-bold text-slate-800">{{ $batch->masterManager->name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $batch->masterManager->email }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">Acts as temporary upline anchor so no users are left orphaned.</div>
                    </div>
                @else
                    <div class="text-xs text-slate-400">Platform Level Master Manager</div>
                @endif
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Current Upline ID:</span>
                    <strong class="font-mono text-slate-700">#{{ $batch->master_manager_id }}</strong>
                </div>
            </div>

            <!-- Counts & Progress -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Transfer Progress</span>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="text-lg font-black text-slate-800">{{ $batch->initial_count }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Initial</div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-100">
                        <div class="text-lg font-black text-amber-600">{{ $batch->current_count }}</div>
                        <div class="text-[10px] font-bold text-amber-600 uppercase">Held</div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-100">
                        <div class="text-lg font-black text-emerald-600">{{ $batch->transferred_count }}</div>
                        <div class="text-[10px] font-bold text-emerald-600 uppercase">Done</div>
                    </div>
                </div>

                @php
                    $percent = $batch->initial_count > 0 ? round(($batch->transferred_count / $batch->initial_count) * 100) : 100;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 mb-1">
                        <span>Transferred</span>
                        <span>{{ $percent }}%</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        @if ($batch->notes)
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-start gap-3">
                <i data-lucide="info" class="w-4 h-4 text-slate-400 shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold text-slate-700">Audit Notes:</span> {{ $batch->notes }}
                </div>
            </div>
        @endif

        <!-- Bulk Transfer Action Panel (Only if held users remain) -->
        @if ($batch->current_count > 0)
            <div class="bg-gradient-to-br from-indigo-50/70 via-white to-slate-50 rounded-2xl border border-indigo-100 p-6 sm:p-7 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-indigo-100/60 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <i data-lucide="send" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Bulk Transfer Held Downlines</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Select a destination manager below to transfer downlines to. Their upline (<code class="font-mono text-indigo-700">referred_by</code>) will be reassigned immediately.
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            <span x-text="selectedUserIds.length > 0 ? selectedUserIds.length + ' of {{ $batch->current_count }} selected' : 'All {{ $batch->current_count }} held users'"></span>
                        </span>
                    </div>
                </div>

                <form id="bulk-transfer-form" method="POST" action="{{ route('admin.manage.downline-batches.transfer', $batch) }}" @submit.prevent="confirmTransfer">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <!-- Destination Manager Select -->
                        <div class="md:col-span-2 space-y-2">
                            <label for="destination_user_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Destination Manager <span class="text-rose-500">*</span>
                            </label>
                            <select name="destination_user_id" id="destination_user_id" required
                                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 px-3.5 text-xs font-medium text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">-- Choose active destination manager --</option>
                                @foreach ($destinationManagers as $destManager)
                                    <option value="{{ $destManager->id }}" {{ old('destination_user_id') == $destManager->id ? 'selected' : '' }}>
                                        {{ $destManager->name }} ({{ ucwords(str_replace('_', ' ', $destManager->role)) }}) &mdash; {{ $destManager->email }} [{{ $destManager->phone }}]
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400">
                                Eligible managers: Regional Managers, Coordinators, Partners, and Business Managers.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <x-primary-button type="submit" class="w-full justify-center !py-2.5 !text-xs !bg-indigo-600 hover:!bg-indigo-700 shadow-sm font-bold">
                                <i data-lucide="arrow-right-left" class="w-4 h-4 mr-1.5"></i>
                                <span x-text="selectedUserIds.length > 0 ? 'Transfer Selected (' + selectedUserIds.length + ')' : 'Transfer All Held ({{ $batch->current_count }})'"></span>
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center gap-4 text-emerald-800">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="check-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold">All Batch Downlines Successfully Transferred</h4>
                    <p class="text-xs text-emerald-700 mt-0.5">
                        There are no more held users pending in this batch. All {{ $batch->initial_count }} users have been transferred to destination managers.
                    </p>
                </div>
            </div>
        @endif

        <!-- Batch Members Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Members Filter & Search -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/40">
                <form method="GET" action="{{ route('admin.manage.downline-batches.show', $batch) }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                    <div class="relative flex-grow">
                        <x-text-input type="text" name="search" :value="request('search')"
                               class="pl-11 pr-4 rounded-xl font-semibold shadow-sm w-full"
                               placeholder="Search member by name, email, or phone..." />
                        <div class="absolute left-4 top-3 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>

                    <div class="w-full md:w-52">
                        <x-select-input name="status" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm w-full">
                            <option value="">All Member Statuses</option>
                            <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>Held Only</option>
                            <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>Transferred Only</option>
                        </x-select-input>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-primary-button type="submit" class="flex-1 md:flex-none !text-xs !py-2.5">
                            <i data-lucide="filter" class="w-3.5 h-3.5 mr-1"></i>
                            Filter
                        </x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('admin.manage.downline-batches.show', $batch) }}"
                               class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Quick Selection Toolbar (when held users are present) -->
            @if ($batch->current_count > 0)
                <div class="px-6 py-3 bg-indigo-50/50 border-b border-indigo-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" @change="toggleSelectAll($event)" :checked="isAllSelected()"
                                   class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span>Select all visible held users</span>
                        </label>
                        <span class="text-slate-400">|</span>
                        <button type="button" @click="clearSelection()" x-show="selectedUserIds.length > 0" class="text-indigo-600 hover:underline font-bold">
                            Clear selection (<span x-text="selectedUserIds.length"></span>)
                        </button>
                    </div>
                    <div class="text-[11px] text-slate-500">
                        <span x-show="selectedUserIds.length === 0" class="italic">
                            No individual users selected: transferring will apply to <strong>ALL {{ $batch->current_count }} held downlines</strong>.
                        </span>
                        <span x-show="selectedUserIds.length > 0" class="font-semibold text-indigo-700">
                            Only the <span x-text="selectedUserIds.length"></span> selected user(s) will be transferred.
                        </span>
                    </div>
                </div>
            @endif

            <!-- Members Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/60">
                            @if ($batch->current_count > 0)
                                <th class="py-4 px-4 text-center w-10">#</th>
                            @endif
                            <th class="py-4 px-6">Member Name</th>
                            <th class="py-4 px-6">Email / Phone</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6">Current Upline</th>
                            <th class="py-4 px-6 text-right">Profile</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($batchUsers as $item)
                            <tr class="hover:bg-slate-50/60 transition-colors {{ $item->status === 'transferred' ? 'bg-slate-50/30 opacity-75' : '' }}">
                                @if ($batch->current_count > 0)
                                    <td class="py-4 px-4 text-center">
                                        @if ($item->status === 'held')
                                            <input type="checkbox" name="user_ids[]" value="{{ $item->user_id }}" form="bulk-transfer-form"
                                                   x-model="selectedUserIds"
                                                   class="batch-user-checkbox rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        @else
                                            <i data-lucide="check" class="w-4 h-4 text-emerald-500 mx-auto" title="Transferred"></i>
                                        @endif
                                    </td>
                                @endif

                                <!-- Member Name -->
                                <td class="py-4 px-6 font-bold text-slate-800">
                                    {{ $item->user?->name ?? 'User #' . $item->user_id }}
                                </td>

                                <!-- Email / Phone -->
                                <td class="py-4 px-6">
                                    <div class="text-slate-700 font-medium">{{ $item->user?->email ?? '-' }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ $item->user?->phone ?? '-' }}</div>
                                </td>

                                <!-- Role -->
                                <td class="py-4 px-6">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-slate-100 text-slate-700">
                                        {{ str_replace('_', ' ', $item->user?->role ?? 'user') }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-6">
                                    @if ($item->status === \App\Models\DownlineBatchUser::STATUS_HELD)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i data-lucide="clock" class="w-3 h-3"></i> Held
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i data-lucide="check-circle" class="w-3 h-3"></i> Transferred
                                        </span>
                                        @if ($item->transferred_at)
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ $item->transferred_at->format('M d, Y') }}
                                            </div>
                                        @endif
                                    @endif
                                </td>

                                <!-- Current Upline -->
                                <td class="py-4 px-6">
                                    @if ($item->status === 'transferred' && $item->transferredToUser)
                                        <div class="font-bold text-slate-800">{{ $item->transferredToUser->name }}</div>
                                        <div class="text-[11px] text-emerald-600 font-semibold">
                                            {{ ucwords(str_replace('_', ' ', $item->transferredToUser->role)) }}
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1 text-slate-700 font-medium">
                                            <i data-lucide="shield" class="w-3 h-3 text-indigo-400"></i>
                                            {{ $batch->masterManager?->name ?? 'Platform Master Manager' }}
                                        </div>
                                        <div class="text-[10px] text-amber-600 font-semibold">Pending Bulk Transfer</div>
                                    @endif
                                </td>

                                <!-- Profile Link -->
                                <td class="py-4 px-6 text-right">
                                    @if ($item->user)
                                        <a href="{{ route('admin.manage.users.show', $item->user) }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 hover:text-indigo-600 transition">
                                            <span>Profile</span>
                                            <i data-lucide="external-link" class="w-3 h-3"></i>
                                        </a>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $batch->current_count > 0 ? '7' : '6' }}" class="py-10 text-center text-slate-400 text-xs">
                                    No members found matching your search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batchUsers->hasPages())
                <div class="p-5 border-t border-slate-100 bg-slate-50/30">
                    {{ $batchUsers->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function downlineBatchShow() {
            return {
                selectedUserIds: [],
                visibleHeldIds: @json($batchUsers->where('status', 'held')->pluck('user_id')->all()),
                
                isAllSelected() {
                    if (this.visibleHeldIds.length === 0) return false;
                    return this.visibleHeldIds.every(id => this.selectedUserIds.includes(String(id)) || this.selectedUserIds.includes(Number(id)));
                },

                toggleSelectAll(event) {
                    if (event.target.checked) {
                        this.visibleHeldIds.forEach(id => {
                            const strId = String(id);
                            if (!this.selectedUserIds.includes(strId)) {
                                this.selectedUserIds.push(strId);
                            }
                        });
                    } else {
                        this.selectedUserIds = this.selectedUserIds.filter(id => !this.visibleHeldIds.includes(Number(id)) && !this.visibleHeldIds.includes(String(id)));
                    }
                },

                clearSelection() {
                    this.selectedUserIds = [];
                },

                confirmTransfer() {
                    const select = document.getElementById('destination_user_id');
                    if (!select || !select.value) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Destination Manager Required',
                                text: 'Please select a destination manager to receive the transferred downlines.',
                                confirmButtonColor: '#4f46e5',
                            });
                        } else {
                            alert('Please select a destination manager to receive the transferred downlines.');
                        }
                        return;
                    }

                    const managerText = select.options[select.selectedIndex].text;
                    const count = this.selectedUserIds.length > 0 ? this.selectedUserIds.length : {{ $batch->current_count }};
                    const message = `Are you sure you want to bulk transfer ${count} downline user(s) to:\n\n${managerText}\n\nTheir upline manager will be updated immediately.`;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Confirm Bulk Downline Transfer',
                            html: `<p class="text-sm text-slate-600 mb-2">You are about to transfer <strong>${count}</strong> downline user(s) to:</p><p class="text-sm font-bold text-slate-800 bg-slate-100 p-2 rounded">${managerText}</p><p class="text-xs text-amber-600 mt-2">Their upline manager will be updated immediately.</p>`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Yes, execute transfer',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('bulk-transfer-form').submit();
                            }
                        });
                    } else {
                        if (confirm(message)) {
                            document.getElementById('bulk-transfer-form').submit();
                        }
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
