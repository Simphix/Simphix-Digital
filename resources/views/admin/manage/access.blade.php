<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Manage Access Control</h1>
                <p class="text-xs text-slate-400 mt-1">Configure user access levels, system permissions, and promote users between roles.</p>
            </div>
        </div>

        <!-- Success Alert -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Card Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Search -->
            <div class="p-6 border-b border-slate-50 bg-slate-50/50">
                <form method="GET" action="{{ route('admin.manage.access') }}" class="w-full sm:max-w-md relative">
                    <div class="relative">
                        <x-text-input type="text" name="search" :value="request('search')"
                               class="pl-11 pr-4 rounded-xl font-semibold"
                               placeholder="Search user by name or email..." />
                        <div class="absolute left-4 top-3.5 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6">User Details</th>
                            <th class="py-4 px-6">Current Access level</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6 text-right">Modify Access Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($users as $u)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    <span class="block text-slate-800 font-bold font-display">{{ $u->first_name }} {{ $u->last_name }}</span>
                                    <span class="block text-xs font-semibold text-slate-400">{{ $u->email }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-[#0056D2]/10 text-[#0056D2] border border-[#0056D2]/20 uppercase tracking-wider">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if ($u->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-extrabold bg-slate-100 text-slate-500 border border-slate-200 rounded-full uppercase tracking-wider">
                                            {{ $u->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    @if ($u->role === 'super_admin')
                                        <div class="inline-flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold rounded-xl bg-purple-50 text-purple-700 border border-purple-200 uppercase tracking-wider">
                                                Super Admin
                                            </span>
                                            <span class="text-[11px] text-slate-400 italic font-medium">(CLI only)</span>
                                        </div>
                                    @elseif (auth()->user()->role === 'business_sales_manager' && $u->role === 'business_sales_manager')
                                        <div class="inline-flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-extrabold rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">
                                                BSM
                                            </span>
                                            <span class="text-[11px] text-slate-400 italic font-medium">(Protected)</span>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('admin.manage.access.update', $u) }}" class="inline-flex items-center gap-3">
                                            @csrf
                                            @method('PUT')
                                            
                                            <x-select-input name="role"
                                                    class="!w-auto !px-3 !py-1.5 rounded-xl !text-xs bg-slate-50 font-semibold">
                                                <option value="personal" {{ $u->role === 'personal' ? 'selected' : '' }}>Personal</option>
                                                <option value="agent" {{ $u->role === 'agent' ? 'selected' : '' }}>Agent</option>
                                                <option value="partner" {{ $u->role === 'partner' ? 'selected' : '' }}>Partner</option>
                                                <option value="coordinator" {{ $u->role === 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                                <option value="regional_manager" {{ $u->role === 'regional_manager' ? 'selected' : '' }}>Regional Manager</option>
                                                <option value="business" {{ $u->role === 'business' ? 'selected' : '' }}>Enterprise Business</option>
                                                <option value="business_agent" {{ $u->role === 'business_agent' ? 'selected' : '' }}>Business Agent</option>
                                                @if (auth()->user()->role !== 'business_sales_manager')
                                                    <option value="business_sales_manager" {{ $u->role === 'business_sales_manager' ? 'selected' : '' }}>Business Sales Manager</option>
                                                @endif
                                                <option value="support" {{ $u->role === 'support' ? 'selected' : '' }}>Support</option>
                                            </x-select-input>

                                            <x-primary-button type="submit" class="!px-4 !py-1.5 !text-xs">
                                                Save
                                            </x-primary-button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400 text-sm">
                                    No registered users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            {{ $users->withQueryString()->links('vendor.pagination.custom') }}
        </div>
    </div>
</x-app-layout>
