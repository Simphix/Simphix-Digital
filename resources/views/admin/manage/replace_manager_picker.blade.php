<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Replace Manager</h1>
                <p class="text-xs text-slate-400 mt-1">Pick the outgoing manager whose seat &mdash; jurisdiction, downline, and SIM holdings &mdash; you want to hand to a vacant same-role account.</p>
            </div>
        </div>

        <!-- Card Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-6 border-b border-slate-100 bg-slate-50/30">
                <form method="GET" action="{{ route('admin.manage.replace-manager.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                    <!-- Search Input -->
                    <div class="relative flex-grow">
                        <x-text-input type="text" name="search" :value="request('search')"
                               class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                               placeholder="Search by name, email or phone..." />
                        <div class="absolute left-4 top-3.5 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <div class="w-full md:w-52">
                        <x-select-input name="role" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                            <option value="">All Manager Roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $role)) }}
                                </option>
                            @endforeach
                        </x-select-input>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <x-primary-button type="submit" class="flex-1 md:flex-none !text-xs">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            Filter
                        </x-primary-button>
                        @if(request('search') || request('role'))
                            <a href="{{ route('admin.manage.replace-manager.index') }}" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150">
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
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Name</th>
                            <th class="py-4 px-6">Email / Phone</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($managers as $m)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-[#0056D2]/5 text-[#0056D2] rounded-xl flex items-center justify-center font-bold text-xs uppercase">
                                            {{ substr($m->first_name, 0, 1) }}{{ substr($m->last_name, 0, 1) }}
                                        </div>
                                        <span class="block text-slate-800 font-bold font-display">{{ $m->first_name }} {{ $m->middle_name }} {{ $m->last_name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="block text-xs font-semibold text-slate-500">{{ $m->email }}</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">{{ $m->phone ?? 'No Phone' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-slate-100 text-slate-600 border border-slate-200/50 uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $m->role) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if ($m->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-slate-100 text-slate-500 border border-slate-200 rounded-full uppercase tracking-wider">
                                            {{ ucfirst($m->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('admin.manage.users.replace', $m) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                        <i data-lucide="repeat" class="w-3.5 h-3.5"></i>
                                        Replace
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400 text-sm">
                                    No manager accounts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            {{ $managers->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
