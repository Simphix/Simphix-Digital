<x-app-layout>
    <div x-data="{
        addModalOpen: false,
        editModalOpen: false,
        editState: { id: '', name: '', region_id: '' }
    }" class="space-y-6 max-w-6xl mx-auto">

        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Jurisdictions</h1>
            <p class="text-xs text-slate-400 mt-1">Assign geographic coverage to Regional Managers (one Region each) and Coordinators (one Senatorial District each). Moving a State to a different Region automatically remaps every Coordinator whose district sits in it.</p>
        </div>

        @include('admin.jurisdictions.partials.tabs')

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
        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl space-y-1.5">
                <span class="text-sm font-extrabold uppercase tracking-wider">Validation Errors</span>
                <ul class="list-disc list-inside text-xs font-semibold space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-md font-bold text-slate-800 font-display">States</h3>
                    <p class="text-xs text-slate-400 mt-1">Reassigning a state's Region here is the "remap" action — it instantly changes the effective jurisdiction of every Coordinator whose district is in this state.</p>
                </div>
                <x-primary-button @click="addModalOpen = true" class="!px-3 !py-2 !text-xs">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    Add State
                </x-primary-button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">S/N</th>
                            <th class="py-4 px-6">Name</th>
                            <th class="py-4 px-6">Region</th>
                            <th class="py-4 px-6">Senatorial Districts</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($states as $state)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6 text-slate-400 text-xs">{{ $states->firstItem() + $loop->index }}</td>
                                <td class="py-4 px-6 font-bold text-slate-800">{{ $state->name }}</td>
                                <td class="py-4 px-6">
                                    @if ($state->region)
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-primary/10 text-primary border border-primary/20">{{ $state->region->name }}</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-amber-50 text-amber-600 border border-amber-100 uppercase tracking-wider">Unassigned</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-500">{{ $state->senatorial_districts_count }}</td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="editState = { id: '{{ $state->id }}', name: '{{ addslashes($state->name) }}', region_id: '{{ $state->region_id }}' }; editModalOpen = true;"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            Edit
                                        </button>
                                        <button type="button" @click="confirmDeleteState('{{ $state->id }}', '{{ addslashes($state->name) }}')"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100/50 rounded-xl transition-all duration-150">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                        <form id="delete-state-form-{{ $state->id }}" action="{{ route('admin.jurisdictions.states.destroy', $state) }}" method="POST" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400 text-sm">No states created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $states->withQueryString()->links('vendor.pagination.custom') }}
        </div>

        <!-- Add State Modal -->
        <div x-show="addModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="addModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-900 font-display">Add State</h3>
                        <button @click="addModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.jurisdictions.states.store') }}" method="POST">
                        @csrf
                        <div class="space-y-5">
                            <div>
                                <x-input-label value="State Name" />
                                <x-text-input type="text" name="name" required placeholder="e.g., Lagos" class="rounded-xl bg-slate-50/50" />
                            </div>
                            <div>
                                <x-input-label value="Region" />
                                <x-select-input name="region_id" class="rounded-xl bg-slate-50/50">
                                    <option value="">Unassigned</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                                    @endforeach
                                </x-select-input>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" @click="addModalOpen = false" class="!text-xs">Cancel</x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">Create State</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit State Modal -->
        <div x-show="editModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="editModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 transform transition-all overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <h3 class="text-lg font-bold text-slate-900 font-display">Edit / Remap State</h3>
                        <button @click="editModalOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form :action="`/admin/jurisdictions/states/${editState.id}`" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-5">
                            <div>
                                <x-input-label value="State Name" />
                                <x-text-input type="text" name="name" x-bind:value="editState.name" required class="rounded-xl bg-slate-50/50" />
                            </div>
                            <div>
                                <x-input-label value="Region" />
                                <x-select-input name="region_id" x-model="editState.region_id" class="rounded-xl bg-slate-50/50">
                                    <option value="">Unassigned</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                                    @endforeach
                                </x-select-input>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 mt-8 border-t border-slate-100 pt-4">
                            <x-secondary-button type="button" @click="editModalOpen = false" class="!text-xs">Cancel</x-secondary-button>
                            <x-primary-button type="submit" class="!text-xs">Save</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDeleteState(id, name) {
            Swal.fire({
                title: 'Delete State?',
                text: `Are you sure you want to permanently delete the state "${name}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-state-form-' + id).submit();
                }
            });
        }
    </script>
</x-app-layout>
