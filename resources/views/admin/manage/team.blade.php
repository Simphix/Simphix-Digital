<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Back Link & Title -->
        <div class="space-y-4">
            <a href="{{ route('admin.manage.users.show', $user) }}"
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-[#0056D2] transition-colors duration-150">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to {{ $user->first_name }}'s Profile
            </a>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">{{ $user->first_name }}'s Team</h1>
                    <p class="text-xs text-slate-400 mt-1">
                        @if (request()->filled('role'))
                            Showing downline members with role <strong class="text-slate-700">{{ request('role') === 'business' ? 'Enterprise Business' : (request('role') === 'business_agent' ? 'Business Agent' : str_replace('_', ' ', request('role'))) }}</strong>.
                        @else
                            Everyone {{ $user->first_name }} has onboarded into their downline, invited or active.
                        @endif
                    </p>
                </div>
                @if (request()->filled('role'))
                    <div>
                        <a href="{{ route('admin.manage.users.team', $user) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 shadow-xs">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            Clear Filter
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Card Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">Name</th>
                            <th class="py-4 px-6">Email / Phone</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6">Joined Date</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($team as $u)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-[#0056D2]/5 text-[#0056D2] rounded-xl flex items-center justify-center font-bold text-xs uppercase">
                                            {{ substr($u->first_name, 0, 1) }}{{ substr($u->last_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <span class="block text-slate-800 font-bold font-display">{{ trim("{$u->first_name} {$u->middle_name} {$u->last_name}") }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="block text-xs font-semibold text-slate-500">{{ $u->email }}</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">{{ $u->phone ?? 'No Phone' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-slate-100 text-slate-600 border border-slate-200/50 uppercase tracking-wider">
                                        {{ $u->role === 'business' ? 'Enterprise Business' : ($u->role === 'business_agent' ? 'Business Agent' : str_replace('_', ' ', $u->role)) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if ($u->status === 'invited')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                                            Invited
                                        </span>
                                    @elseif ($u->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            Active
                                        </span>
                                    @elseif ($u->status === 'suspended')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-amber-50 text-amber-600 border border-amber-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                            Suspended
                                        </span>
                                    @elseif ($u->status === 'inactive')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-slate-100 text-slate-500 border border-slate-200 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                            Inactive
                                        </span>
                                    @elseif ($u->status === 'banned')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-extrabold bg-rose-50 text-rose-600 border border-rose-100 rounded-full uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                            Banned
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-400 text-xs">
                                    {{ $u->created_at->format('M d, Y') }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.manage.users.show', $u) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-[#0056D2]/5 hover:bg-[#0056D2]/10 text-[#0056D2] rounded-xl transition-all duration-150">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-sm">
                                    {{ $user->first_name }} hasn't onboarded anyone yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            {{ $team->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
