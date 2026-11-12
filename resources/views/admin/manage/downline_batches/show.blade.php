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
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-bold shadow-sm transition">
                    <i data-lucide="repeat" class="w-3.5 h-3.5 text-slate-400"></i>
                    Replace Another Manager
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300 flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Batch Summary Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Source Manager -->
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Source Manager</span>
                    @if ($batch->sourceUser)
                        <div class="font-bold text-slate-800 text-sm">{{ $batch->sourceUser->name }}</div>
                        <div class="text-xs text-slate-500">{{ $batch->sourceUser->email }}</div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700">
                            {{ str_replace('_', ' ', $batch->source_role ?? $batch->sourceUser->role) }}
                        </span>
                    @else
                        <span class="text-xs text-slate-400 italic">No source manager recorded</span>
                    @endif
                </div>

                <!-- Current Holding Upline -->
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Current Holding Upline</span>
                    <div class="font-bold text-indigo-700 text-sm flex items-center gap-1.5">
                        <i data-lucide="shield" class="w-4 h-4 text-indigo-500"></i>
                        {{ $batch->masterManager?->name ?? 'Platform Master Manager' }}
                    </div>
                    <div class="text-xs text-slate-500">Temporary holding seat pending reassignment</div>
                </div>

                <!-- Counts Breakdown -->
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Downline Status</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-800">{{ $batch->initial_count }}</span>
                        <span class="text-xs text-slate-400 font-semibold">total initial</span>
                    </div>
                    <div class="text-xs text-slate-500">
                        <strong class="text-amber-600 font-bold">{{ $batch->current_count }} held</strong> &bull;
                        <strong class="text-emerald-600 font-bold">{{ $batch->transferred_count }} transferred</strong>
                    </div>
                </div>

                <!-- Source Type & Progress -->
                <div class="space-y-2">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Transfer Progress</span>
                    @php
                        $pct = $batch->initial_count > 0 ? round(($batch->transferred_count / $batch->initial_count) * 100) : 0;
                    @endphp
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-slate-700">{{ $pct }}% Complete</span>
                        <span class="text-slate-400">{{ $batch->transferred_count }}/{{ $batch->initial_count }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </div>

            @if ($batch->notes)
                <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500">
                    <span class="font-bold text-slate-700">Audit Notes:</span> {{ $batch->notes }}
                </div>
            @endif
        </div>

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
                                Search and select an eligible destination upline manager below to receive these downlines. Their upline (<code class="font-mono text-indigo-700">referred_by</code>) will be reassigned immediately.
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

                <!-- Hierarchy & Role Eligibility Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0 text-xs font-bold shadow-xs">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                        <div class="text-xs">
                            <span class="font-bold text-slate-700">Eligible Destination Role:</span>
                            <span class="inline-block font-extrabold uppercase px-2 py-0.5 ml-1 rounded bg-white text-indigo-700 border border-indigo-300 shadow-xs">
                                {{ $eligibleRolesLabel }}
                            </span>
                            <span class="text-slate-500 ml-1.5 hidden md:inline">
                                Only managers with this role can receive these downlines to preserve hierarchy integrity.
                            </span>
                        </div>
                    </div>
                    <div class="text-xs font-semibold text-indigo-800 shrink-0">
                        <span x-text="managersList.length"></span> eligible manager(s) available
                    </div>
                </div>

                <form id="bulk-transfer-form" method="POST" action="{{ route('admin.manage.downline-batches.transfer', $batch) }}" @submit.prevent="confirmTransfer" class="space-y-4">
                    @csrf
                    
                    <input type="hidden" name="destination_user_id" id="destination_user_id" :value="selectedManagerId" required>

                    <!-- Hidden inputs for selected users to ensure reliable submission -->
                    <template x-for="id in selectedUserIds" :key="id">
                        <input type="hidden" name="user_ids[]" :value="id">
                    </template>

                    <!-- Destination Manager Search & Selector -->
                    <div class="space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">
                                Search & Select Destination Manager <span class="text-rose-500">*</span>
                            </label>
                            <span x-show="selectedManagerId" class="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                Selected: <strong x-text="selectedManagerName"></strong> (<span x-text="selectedManagerRole"></span>)
                            </span>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <input type="text"
                                   x-model="searchQuery"
                                   @input="onSearchInput()"
                                   placeholder="Search eligible {{ $eligibleRolesLabel }} by name, email, or phone number..."
                                   class="w-full pl-10 pr-24 py-2.5 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition shadow-xs">
                            <div class="absolute left-3.5 top-3 text-slate-400 pointer-events-none">
                                <i data-lucide="search" class="w-4 h-4"></i>
                            </div>
                            <div class="absolute right-3 top-2.5 flex items-center gap-1.5">
                                <div x-show="isLoadingManagers" class="text-indigo-600 text-xs animate-spin">
                                    <i data-lucide="loader-2" class="w-4 h-4"></i>
                                </div>
                                <button type="button"
                                        x-show="searchQuery.length > 0"
                                        @click="clearSearch()"
                                        class="px-2 py-0.5 rounded text-[11px] font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Managers List -->
                        <div class="border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100 max-h-64 overflow-y-auto bg-white shadow-xs">
                            <template x-if="managersList.length === 0">
                                <div class="text-center py-8 text-slate-400 text-xs flex flex-col items-center justify-center p-4">
                                    <i data-lucide="user-x" class="w-8 h-8 text-slate-300 mb-2"></i>
                                    <span class="font-bold text-slate-700">No eligible destination managers found</span>
                                    <span class="text-[11px] mt-0.5 text-slate-400">
                                        No active {{ $eligibleRolesLabel }} matches your search criteria. Try a different query.
                                    </span>
                                </div>
                            </template>

                            <template x-for="m in managersList" :key="m.id">
                                <label @click="selectManager(m)"
                                       :class="selectedManagerId == m.id ? 'bg-indigo-50/70 border-l-4 border-l-indigo-600 ring-1 ring-inset ring-indigo-500/20' : 'hover:bg-slate-50/80'"
                                       class="p-3.5 flex items-center gap-3.5 cursor-pointer transition duration-150">
                                    <input type="radio"
                                           :name="'dest_mgr_picker'"
                                           :value="m.id"
                                           :checked="selectedManagerId == m.id"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">

                                    <div class="min-w-0 flex-grow">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-slate-800" x-text="m.name"></span>
                                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="m.role_label"></span>
                                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                Eligible Upline
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 text-[11px] text-slate-500 mt-1 flex-wrap">
                                            <span x-text="m.email"></span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span x-text="m.phone || 'No phone'"></span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span class="text-indigo-600 font-semibold" x-text="m.downline_count + ' current downline(s)'"></span>
                                        </div>
                                    </div>

                                    <div x-show="selectedManagerId == m.id" class="text-indigo-600 shrink-0">
                                        <i data-lucide="check" class="w-5 h-5"></i>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Selected Manager Confirmation Bar & Submit Button -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-indigo-100/80">
                        <div>
                            <template x-if="selectedManagerId">
                                <div class="text-xs text-slate-600">
                                    Destination: <strong class="text-slate-800" x-text="selectedManagerName"></strong> &bull; Role: <span class="font-bold text-indigo-700" x-text="selectedManagerRole"></span>
                                </div>
                            </template>
                            <template x-if="!selectedManagerId">
                                <span class="text-xs text-slate-400 italic">Please search or select an eligible destination manager from the list above.</span>
                            </template>
                        </div>

                        <div class="shrink-0">
                            <x-primary-button type="submit"
                                              :disabled="!selectedManagerId"
                                              class="w-full sm:w-auto justify-center !py-2.5 !px-6 !text-xs !bg-indigo-600 hover:!bg-indigo-700 shadow-sm font-bold disabled:opacity-50 disabled:cursor-not-allowed">
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
                                            <input type="checkbox" value="{{ $item->user_id }}"
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
                
                // Destination manager search & picker state
                selectedManagerId: '{{ old("destination_user_id", "") }}',
                selectedManagerName: '',
                selectedManagerRole: '',
                searchQuery: '{{ addslashes($managerSearch ?? "") }}',
                isLoadingManagers: false,
                managersList: @json($destinationManagersData),
                searchTimeout: null,

                init() {
                    if (this.selectedManagerId) {
                        const found = this.managersList.find(m => String(m.id) === String(this.selectedManagerId));
                        if (found) {
                            this.selectedManagerName = found.name;
                            this.selectedManagerRole = found.role_label;
                        }
                    }
                    if (typeof lucide !== 'undefined') {
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                selectManager(manager) {
                    this.selectedManagerId = String(manager.id);
                    this.selectedManagerName = manager.name;
                    this.selectedManagerRole = manager.role_label;
                },

                onSearchInput() {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.fetchManagers();
                    }, 300);
                },

                async fetchManagers() {
                    const query = this.searchQuery.trim();
                    this.isLoadingManagers = true;
                    try {
                        const url = '{{ route("admin.manage.downline-batches.search-destinations", $batch) }}?q=' + encodeURIComponent(query);
                        const res = await fetch(url, {
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.managersList = data;
                            if (typeof lucide !== 'undefined') {
                                this.$nextTick(() => lucide.createIcons());
                            }
                        }
                    } catch (e) {
                        console.error('Failed to search destination managers', e);
                    } finally {
                        this.isLoadingManagers = false;
                    }
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.fetchManagers();
                },

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
                    if (!this.selectedManagerId) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Destination Manager Required',
                                text: 'Please search or select an eligible destination manager to receive the transferred downlines.',
                                confirmButtonColor: '#4f46e5',
                            });
                        } else {
                            alert('Please search or select an eligible destination manager to receive the transferred downlines.');
                        }
                        return;
                    }

                    const count = this.selectedUserIds.length > 0 ? this.selectedUserIds.length : {{ $batch->current_count }};
                    const managerText = `${this.selectedManagerName} (${this.selectedManagerRole})`;
                    const message = `Are you sure you want to bulk transfer ${count} downline user(s) to:\n\n${managerText}\n\nTheir upline manager will be updated immediately.`;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Confirm Bulk Downline Transfer',
                            html: `<p class="text-sm text-slate-600 mb-2">You are about to transfer <strong>${count}</strong> downline user(s) to eligible upline:</p><p class="text-sm font-bold text-slate-800 bg-slate-100 p-2.5 rounded-xl border border-slate-200">${managerText}</p><p class="text-xs text-amber-600 mt-2">Their upline manager will be updated immediately.</p>`,
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
