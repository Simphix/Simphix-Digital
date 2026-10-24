<x-app-layout>
    <title>SimPhix - Leaderboard Settings</title>

    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-[#0056D2] border border-indigo-100/50 shadow-sm">
                    <i data-lucide="trophy" class="w-5 h-5"></i>
                </div>
                Leaderboard Settings
            </h1>
            <p class="text-sm text-slate-500 mt-1">Configure activation tiers, the counting period, and turn the partner leaderboard on or off.</p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <ul class="text-sm font-semibold list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Settings Card -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6"
                     x-data="{ periodType: '{{ old('period_type', $settings->period_type) }}' }">
                    <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">
                        Counting Period &amp; Status
                    </h3>
                    <form method="POST" action="{{ route('admin.leaderboard.settings.update') }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $settings->is_active ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-primary focus:ring-primary/20 w-4 h-4">
                            <span class="text-sm font-semibold text-slate-700">Leaderboard is active</span>
                        </label>

                        <div class="space-y-2">
                            <x-input-label value="Counting Period" />
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm text-slate-600">
                                    <input type="radio" name="period_type" value="weekly" x-model="periodType" class="text-primary focus:ring-primary/20">
                                    Weekly (Mon&ndash;Sun)
                                </label>
                                <label class="flex items-center gap-2 text-sm text-slate-600">
                                    <input type="radio" name="period_type" value="custom" x-model="periodType" class="text-primary focus:ring-primary/20">
                                    Custom range
                                </label>
                            </div>
                        </div>

                        <div x-show="periodType === 'custom'" x-cloak class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label value="Start Date" />
                                <x-text-input type="date" name="period_start" :value="old('period_start', optional($settings->period_start)->toDateString())" class="rounded-xl" />
                            </div>
                            <div>
                                <x-input-label value="End Date" />
                                <x-text-input type="date" name="period_end" :value="old('period_end', optional($settings->period_end)->toDateString())" class="rounded-xl" />
                            </div>
                        </div>

                        <x-primary-button type="submit" class="w-full !text-xs">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            Save Settings
                        </x-primary-button>
                    </form>
                </div>
            </div>

            <!-- Tiers -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">
                        Add a Tier
                    </h3>
                    <form method="POST" action="{{ route('admin.leaderboard.tiers.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @csrf
                        <div>
                            <x-input-label value="Activation Target" />
                            <x-text-input type="number" name="activation_target" min="1" required class="rounded-xl" placeholder="e.g. 15" />
                        </div>
                        <div>
                            <x-input-label value="Bonus Amount (₦)" />
                            <x-text-input type="number" name="bonus_amount" min="0" step="0.01" required class="rounded-xl" placeholder="e.g. 10000" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button type="submit" class="w-full !text-xs">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                Add Tier
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100 mb-4">
                        Configured Tiers
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase">
                                    <th class="py-2.5">Activation Target</th>
                                    <th class="py-2.5">Bonus Amount</th>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tiers as $tier)
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                        <td class="py-3">
                                            <input type="number" id="tier_target_{{ $tier->id }}" min="1" value="{{ $tier->activation_target }}"
                                                   class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-xs font-bold">
                                        </td>
                                        <td class="py-3">
                                            ₦<input type="number" id="tier_bonus_{{ $tier->id }}" min="0" step="0.01" value="{{ $tier->bonus_amount }}"
                                                   class="w-28 rounded-lg border border-slate-200 px-2 py-1 text-xs font-bold">
                                        </td>
                                        <td class="py-3">
                                            <select id="tier_status_{{ $tier->id }}" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-bold">
                                                <option value="active" {{ $tier->status === 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ $tier->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </td>
                                        <td class="py-3 text-right whitespace-nowrap space-x-1">
                                            <button type="button" onclick="submitTierUpdate({{ $tier->id }})"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-[#0056D2] hover:bg-[#354062] text-white rounded-xl transition-all duration-150">
                                                Save
                                            </button>
                                            <form method="POST" action="{{ route('admin.leaderboard.tiers.destroy', $tier) }}" class="inline-block"
                                                  onsubmit="return confirm('Remove this tier?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition-all duration-150">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-slate-400 font-semibold">No tiers configured yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function submitTierUpdate(tierId) {
            const target = document.getElementById('tier_target_' + tierId).value;
            const bonus = document.getElementById('tier_bonus_' + tierId).value;
            const status = document.getElementById('tier_status_' + tierId).value;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url('admin/leaderboard/tiers') }}/' + tierId;
            form.innerHTML = `
                @csrf
                @method('PUT')
                <input type="hidden" name="activation_target" value="${target}">
                <input type="hidden" name="bonus_amount" value="${bonus}">
                <input type="hidden" name="status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-app-layout>
