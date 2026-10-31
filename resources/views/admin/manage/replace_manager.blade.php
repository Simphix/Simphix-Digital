<x-app-layout>
    <div class="space-y-8 max-w-4xl mx-auto">
        <!-- Back Link & Title -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-2">
                <a href="{{ route('admin.manage.users.show', $outgoing) }}"
                   class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors duration-150">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Back to {{ $outgoing->name }}
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Replace Manager</h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Transfer the <strong class="text-slate-700 uppercase">{{ str_replace('_', ' ', $outgoing->role) }}</strong> seat to any platform user.
                        The selected replacement is upgraded to this role, and all downlines are safely placed under the Platform Master Manager in batches.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.manage.downline-batches.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition">
                    <i data-lucide="layers" class="w-3.5 h-3.5 text-slate-400"></i>
                    Downline Batches & Transfers
                </a>
            </div>
        </div>

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold shadow-sm animate-in fade-in duration-300">
                {{ session('success') }}
            </div>
        @endif

        <!-- Outgoing manager & impact summary -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-7 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <i data-lucide="user-minus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-rose-600 uppercase tracking-widest block">Outgoing Manager</span>
                        <h2 class="text-base font-bold text-slate-800">{{ $outgoing->name }}</h2>
                        <p class="text-xs text-slate-400">{{ $outgoing->email }} &bull; {{ $outgoing->phone ?? 'No phone' }} &bull; <span class="capitalize">{{ str_replace('_', ' ', $outgoing->role) }}</span></p>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-medium">Holding Master Manager:</span>
                    <strong class="text-xs font-bold text-[#0056D2]">{{ $masterManager->name }}</strong>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                    <span class="block text-xl font-extrabold font-display text-slate-900">{{ $impact['downline_count'] }}</span>
                    <span class="block text-[11px] text-slate-500 font-semibold uppercase mt-0.5">Downlines Batched</span>
                </div>
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                    <span class="block text-xl font-extrabold font-display text-slate-900">{{ $impact['sim_count'] }}</span>
                    <span class="block text-[11px] text-slate-500 font-semibold uppercase mt-0.5">SIMs Moving</span>
                </div>
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-center col-span-2 sm:col-span-1">
                    <span class="block text-sm font-extrabold font-display text-slate-900 truncate" title="{{ $impact['jurisdiction_label'] ?? 'None' }}">
                        {{ $impact['jurisdiction_label'] ?? 'None' }}
                    </span>
                    <span class="block text-[11px] text-slate-500 font-semibold uppercase mt-0.5">Territory Handover</span>
                </div>
                @if ($outgoing->role === 'business')
                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                        <span class="block text-xl font-extrabold font-display text-slate-900">{{ $impact['pricing_override_count'] }}</span>
                        <span class="block text-[11px] text-slate-500 font-semibold uppercase mt-0.5">Pricing Overrides</span>
                    </div>
                @else
                    <div class="p-4 bg-blue-50/50 border border-blue-100/50 rounded-2xl text-center">
                        <span class="block text-xl font-extrabold font-display text-[#0056D2]">Auto</span>
                        <span class="block text-[11px] text-slate-500 font-semibold uppercase mt-0.5">Role Upgrade</span>
                    </div>
                @endif
            </div>

            <div class="bg-indigo-50/60 border border-indigo-100/80 rounded-2xl p-4 text-xs text-indigo-900 flex items-start gap-3">
                <i data-lucide="info" class="w-4 h-4 text-indigo-500 shrink-0 mt-0.5"></i>
                <div class="leading-relaxed">
                    <strong>Batching & Reassignment Protocol:</strong>
                    When replaced, all <strong>{{ $impact['downline_count'] }}</strong> downline users of {{ $outgoing->name }} will be detached and placed under the <strong>Platform Master Manager</strong> in a tracked batch. Any existing downlines of the incoming candidate will also be placed in a separate batch, allowing you to bulk transfer them to other managers at any time.
                </div>
            </div>
        </div>

        <!-- Incoming candidate selector -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-7 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest block">Replacement Candidate</span>
                        <h2 class="text-base font-bold text-slate-800">Select Any User on Platform</h2>
                        <p class="text-xs text-slate-400">Search by name, email, or phone. The chosen user will be automatically upgraded to <strong>{{ str_replace('_', ' ', $outgoing->role) }}</strong>.</p>
                    </div>
                </div>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.manage.users.replace', $outgoing) }}" class="flex gap-2">
                <div class="relative flex-grow">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search by name, email address, or phone number..."
                           class="w-full pl-10 pr-4 py-2.5 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:border-[#0056D2] focus:ring-1 focus:ring-[#0056D2] transition shadow-xs">
                </div>
                <button type="submit" class="px-4 py-2.5 bg-slate-800 text-white text-xs font-bold rounded-xl hover:bg-slate-900 transition flex items-center gap-1.5 shadow-sm">
                    Search
                </button>
                @if(!empty($search))
                    <a href="{{ route('admin.manage.users.replace', $outgoing) }}" class="px-3 py-2.5 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-200 transition flex items-center">
                        Clear
                    </a>
                @endif
            </form>

            <form method="POST" action="{{ route('admin.manage.users.replace.store', $outgoing) }}" id="replace-manager-form" class="space-y-6">
                @csrf

                @if ($candidates->isEmpty())
                    <div class="text-center py-12 text-slate-400 text-sm flex flex-col items-center justify-center border border-dashed border-slate-200 rounded-3xl">
                        <i data-lucide="users" class="w-10 h-10 text-slate-300 mb-2.5"></i>
                        <span class="font-bold text-slate-700">No matching users found</span>
                        <span class="text-xs mt-1 text-slate-400">Try searching with a different name, email, or phone number.</span>
                    </div>
                @else
                    <div class="border border-slate-200/80 rounded-2xl overflow-hidden divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        @foreach ($candidates as $candidate)
                            @php
                                $needsUpgrade = ($candidate->role !== $outgoing->role);
                                $downlinesCount = $candidate->referrals()->count();
                            @endphp
                            <label class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-50/80 transition duration-150 has-[:checked]:bg-blue-50/50 has-[:checked]:border-l-4 has-[:checked]:border-l-[#0056D2]">
                                <input type="radio"
                                       name="incoming_user_id"
                                       value="{{ $candidate->id }}"
                                       data-name="{{ $candidate->name }}"
                                       data-role="{{ $candidate->role }}"
                                       data-upgrade="{{ $needsUpgrade ? 'yes' : 'no' }}"
                                       data-downlines="{{ $downlinesCount }}"
                                       class="w-4 h-4 text-[#0056D2] border-slate-300 focus:ring-[#0056D2]"
                                       required>

                                <div class="min-w-0 flex-grow">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold text-slate-800">{{ $candidate->name }}</span>
                                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                                            Current: {{ str_replace('_', ' ', $candidate->role) }}
                                        </span>
                                        @if ($needsUpgrade)
                                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1">
                                                <i data-lucide="arrow-up-circle" class="w-3 h-3"></i>
                                                Will upgrade to {{ str_replace('_', ' ', $outgoing->role) }}
                                            </span>
                                        @else
                                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Matches Role
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">
                                        {{ $candidate->email }} &bull; {{ $candidate->phone ?? 'No phone' }}
                                        @if ($downlinesCount > 0)
                                            &bull; <strong class="text-indigo-600">{{ $downlinesCount }} current downline(s)</strong> will be moved to Master Manager batch
                                        @endif
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @if(method_exists($candidates, 'hasPages') && $candidates->hasPages())
                        <div class="pt-2">
                            {{ $candidates->links() }}
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-400">
                            Confirming will immediately upgrade the candidate and isolate existing downlines into master manager batches.
                        </p>
                        <x-danger-button type="button" onclick="confirmReplacement()" class="shrink-0">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mr-1.5"></i>
                            Confirm & Replace Manager
                        </x-danger-button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmReplacement() {
            const form = document.getElementById('replace-manager-form');
            const selected = form.querySelector('[name="incoming_user_id"]:checked');
            if (!selected) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a user',
                    text: 'Please select which platform user will replace {{ $outgoing->name }}.',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            const incomingName = selected.dataset.name;
            const needsUpgrade = selected.dataset.upgrade === 'yes';
            const downlines = parseInt(selected.dataset.downlines || '0', 10);
            const targetRoleLabel = @json(ucfirst(str_replace('_', ' ', $outgoing->role)));

            let upgradeNotice = '';
            if (needsUpgrade) {
                upgradeNotice = `<br><br><span class="text-amber-600 font-bold">&bull; Role Upgrade:</span> <b>${incomingName}</b> will be automatically upgraded to <b>${targetRoleLabel}</b>.`;
            }

            let downlinesNotice = `<br><span class="text-indigo-600 font-bold">&bull; Outgoing Downline:</span> <b>{{ $impact['downline_count'] }} user(s)</b> from {{ $outgoing->name }} will be placed under the Platform Master Manager in a batch.`;
            if (downlines > 0) {
                downlinesNotice += `<br><span class="text-indigo-600 font-bold">&bull; Replacement Downline:</span> <b>${downlines} user(s)</b> from ${incomingName} will also be placed under the Platform Master Manager in a separate batch.`;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Replace this manager?',
                html: `You are about to transfer the <b>${targetRoleLabel}</b> seat to <b>${incomingName}</b>.` +
                      `${upgradeNotice}` +
                      `${downlinesNotice}` +
                      `<br><br>Territory and SIM cascade will move to <b>${incomingName}</b>. This action is recorded in the platform ledger.`,
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, execute replacement',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
</x-app-layout>
