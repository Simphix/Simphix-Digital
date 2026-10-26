<x-app-layout>
    <div class="space-y-8 max-w-3xl mx-auto">
        <!-- Back Link & Title -->
        <div class="space-y-4">
            <a href="{{ route('admin.manage.users.show', $outgoing) }}"
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors duration-150">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to {{ $outgoing->name }}
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Replace Manager</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Hand {{ $outgoing->name }}'s entire {{ str_replace('_', ' ', $outgoing->role) }} seat &mdash;
                    territory, downline, and SIM holdings &mdash; to a vacant account of the same role.
                </p>
            </div>
        </div>

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        <!-- Outgoing manager & impact summary -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="user-minus" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Outgoing: {{ $outgoing->name }}</h2>
                    <p class="text-xs text-slate-400">{{ $outgoing->email }} &middot; {{ str_replace('_', ' ', $outgoing->role) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                    <span class="block text-lg font-extrabold font-display text-slate-800">{{ $impact['downline_count'] }}</span>
                    <span class="block text-[11px] text-slate-400 font-semibold uppercase">Downline moving</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                    <span class="block text-lg font-extrabold font-display text-slate-800">{{ $impact['sim_count'] }}</span>
                    <span class="block text-[11px] text-slate-400 font-semibold uppercase">SIMs moving</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center col-span-2 sm:col-span-1">
                    <span class="block text-sm font-extrabold font-display text-slate-800 truncate" title="{{ $impact['jurisdiction_label'] ?? 'None' }}">
                        {{ $impact['jurisdiction_label'] ?? 'None' }}
                    </span>
                    <span class="block text-[11px] text-slate-400 font-semibold uppercase">Territory</span>
                </div>
                @if ($outgoing->role === 'business')
                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-center">
                        <span class="block text-lg font-extrabold font-display text-slate-800">{{ $impact['pricing_override_count'] }}</span>
                        <span class="block text-[11px] text-slate-400 font-semibold uppercase">Pricing overrides</span>
                    </div>
                @endif
            </div>

            <p class="text-xs text-slate-400">
                {{ $outgoing->first_name }}'s wallet balance and past commission history stay with their own account &mdash;
                only the seat itself (jurisdiction, downline, and SIM/pricing holdings) moves.
            </p>
        </div>

        <!-- Incoming candidate picker -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                </div>
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Incoming replacement</h2>
            </div>

            @if ($candidates->isEmpty())
                <div class="text-center py-10 text-slate-400 text-sm flex flex-col items-center justify-center border border-dashed rounded-3xl">
                    <i data-lucide="user-x" class="w-10 h-10 text-slate-300 mb-2.5"></i>
                    No vacant {{ str_replace('_', ' ', $outgoing->role) }} accounts found.
                    <span class="text-xs mt-1">Create or onboard a new account with this role first &mdash; it must have no downline, jurisdiction, or SIMs yet.</span>
                </div>
            @else
                <form method="POST" action="{{ route('admin.manage.users.replace.store', $outgoing) }}" id="replace-manager-form" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label value="Vacant account to receive the seat" />
                        <x-select-input name="incoming_user_id" required>
                            <option value="">Select an account&hellip;</option>
                            @foreach ($candidates as $candidate)
                                <option value="{{ $candidate->id }}">
                                    {{ $candidate->name }} &mdash; {{ $candidate->email }}
                                </option>
                            @endforeach
                        </x-select-input>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-50">
                        <x-danger-button type="button" onclick="confirmReplacement()">
                            Replace Manager
                        </x-danger-button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmReplacement() {
            const form = document.getElementById('replace-manager-form');
            const select = form.querySelector('[name="incoming_user_id"]');
            if (!select.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select an account',
                    text: 'Choose which vacant account will receive this seat.',
                    confirmButtonColor: '#0056D2',
                });
                return;
            }

            const incomingName = select.options[select.selectedIndex].text;

            Swal.fire({
                icon: 'warning',
                title: 'Replace this manager?',
                html: `This moves <b>{{ $impact['downline_count'] }} downline user(s)</b>, <b>{{ $impact['sim_count'] }} SIM(s)</b>` +
                      `{{ $impact['jurisdiction_label'] ? ', and the ' . $impact['jurisdiction_label'] . ' jurisdiction' : '' }} from ` +
                      `<b>{{ $outgoing->name }}</b> onto <b>${incomingName}</b>. This cannot be undone automatically.`,
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, replace',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
</x-app-layout>
