<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.manage.replace-manager.index') }}"
                       class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 inline mr-1"></i> Replace Manager
                    </a>
                </div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display mt-1">Downline Batches</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Manage detached downline groups placed under the Platform Master Manager during manager replacements and execute bulk transfers.
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
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                    <i data-lucide="layers" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Batches</span>
                    <span class="text-2xl font-black text-slate-800">{{ number_format($stats['total_batches'] ?? 0) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Held Batches</span>
                    <span class="text-2xl font-black text-amber-600">{{ number_format($stats['held_batches'] ?? 0) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Currently Held Downlines</span>
                    <span class="text-2xl font-black text-rose-600">{{ number_format($stats['total_held_users'] ?? 0) }}</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Transferred Users</span>
                    <span class="text-2xl font-black text-emerald-600">{{ number_format($stats['transferred_users'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Filter & Batches Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/40">
                <form method="GET" action="{{ route('admin.manage.downline-batches.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                    <div class="relative flex-grow">
                        <x-text-input type="text" name="search" :value="request('search')"
                               class="pl-11 pr-4 rounded-xl font-semibold shadow-sm w-full"
                               placeholder="Search by batch reference, title, source user name, email, or phone..." />
                        <div class="absolute left-4 top-3 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>

                    <div class="w-full md:w-56">
                        <x-select-input name="status" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm w-full">
                            <option value="">All Statuses</option>
                            <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>Held (Pending Transfer)</option>
                            <option value="partially_transferred" {{ request('status') === 'partially_transferred' ? 'selected' : '' }}>Partially Transferred</option>
                            <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>Fully Transferred</option>
                        </x-select-input>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-primary-button type="submit" class="flex-1 md:flex-none !text-xs !py-2.5">
                            <i data-lucide="filter" class="w-3.5 h-3.5 mr-1"></i>
                            Filter
                        </x-primary-button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('admin.manage.downline-batches.index') }}"
                               class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/60">
                            <th class="py-4 px-6">Batch Reference / Title</th>
                            <th class="py-4 px-6">Original Upline</th>
                            <th class="py-4 px-6">Batch Source</th>
                            <th class="py-4 px-6 text-center">Downlines (Held / Initial)</th>
                            <th class="py-4 px-6 text-center">Status</th>
                            <th class="py-4 px-6">Created At</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($batches as $batch)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <!-- Reference & Title -->
                                <td class="py-4 px-6">
                                    <div class="font-mono font-bold text-slate-800 text-xs">
                                        {{ $batch->batch_reference }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium mt-0.5 max-w-xs truncate" title="{{ $batch->title }}">
                                        {{ $batch->title }}
                                    </div>
                                    @if ($batch->masterManager)
                                        <div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400">
                                            <i data-lucide="shield" class="w-3 h-3 text-indigo-400"></i>
                                            Anchor: <span class="text-slate-600 font-semibold">{{ $batch->masterManager->name }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Original Upline -->
                                <td class="py-4 px-6">
                                    @if ($batch->sourceUser)
                                        <div class="font-bold text-slate-800">{{ $batch->sourceUser->name }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $batch->sourceUser->email }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $batch->sourceUser->phone }}</div>
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[10px] font-extrabold uppercase tracking-wide">
                                            {{ str_replace('_', ' ', $batch->source_role ?? $batch->sourceUser->role) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic">User deleted (#{{ $batch->source_user_id }})</span>
                                    @endif
                                </td>

                                <!-- Source Type -->
                                <td class="py-4 px-6">
                                    @if ($batch->source_type === 'outgoing_manager')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <i data-lucide="user-minus" class="w-3 h-3"></i> Outgoing Manager
                                        </span>
                                    @elseif ($batch->source_type === 'incoming_manager' || $batch->source_type === 'incoming_replacement')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            <i data-lucide="user-plus" class="w-3 h-3"></i> Incoming Replacement
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            {{ ucfirst(str_replace('_', ' ', $batch->source_type ?? 'detached')) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Users count -->
                                <td class="py-4 px-6 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <div class="flex items-center gap-1 font-mono font-bold text-sm">
                                            <span class="{{ $batch->current_count > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                                {{ $batch->current_count }}
                                            </span>
                                            <span class="text-slate-300">/</span>
                                            <span class="text-slate-600">
                                                {{ $batch->initial_count }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $batch->transferred_count }} transferred
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-6 text-center">
                                    @if ($batch->status === \App\Models\DownlineBatch::STATUS_HELD)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i data-lucide="clock" class="w-3 h-3"></i> Held
                                        </span>
                                    @elseif ($batch->status === \App\Models\DownlineBatch::STATUS_PARTIALLY_TRANSFERRED)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Partial
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i data-lucide="check" class="w-3 h-3"></i> Transferred
                                        </span>
                                    @endif
                                </td>

                                <!-- Created At -->
                                <td class="py-4 px-6 text-slate-500 whitespace-nowrap">
                                    <div class="font-semibold text-slate-700">{{ $batch->created_at->format('M d, Y') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $batch->created_at->format('h:i A') }}</div>
                                    @if ($batch->creator)
                                        <div class="text-[10px] text-slate-400">by {{ $batch->creator->name }}</div>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.manage.downline-batches.show', $batch) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 shadow-sm transition">
                                        <i data-lucide="share-2" class="w-3.5 h-3.5 text-indigo-500"></i>
                                        <span>Manage & Transfer</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                            <i data-lucide="inbox" class="w-6 h-6"></i>
                                        </div>
                                        <div class="text-sm font-bold text-slate-600">No downline batches found</div>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            When managers are replaced, their direct downlines are automatically preserved here in batches under the Platform Master Manager.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="p-5 border-t border-slate-100 bg-slate-50/30">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
