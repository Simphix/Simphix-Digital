<x-app-layout>
    <div class="space-y-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Manage Users</h1>
                <p class="text-xs text-slate-400 mt-1">View, search, and update accounts registered on SimPhix.</p>
            </div>
        </div>

        <!-- Success Alert -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Users Card -->
            <div class="p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Users</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalUsers) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Registered Accounts</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Active Users Card -->
            <div class="p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Users</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($activeUsers) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Currently Active</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100/50 flex items-center justify-center text-emerald-600">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Inactive Users Card -->
            <div class="p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Inactive Users</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($inactiveUsers) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">Requires Attention</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-100/50 flex items-center justify-center text-amber-600">
                        <i data-lucide="user-x" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Transacting Users Card -->
            <div class="p-6 rounded-xl bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Transacting Users</p>
                        <h3 class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($transactingUsers) }}</h3>
                        <p class="text-[11px] text-slate-400 mt-1.5 font-medium">With Activity</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 border border-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Section -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-6 border-b border-slate-100 bg-slate-50/30">
                <form method="GET" action="{{ route('admin.manage.users') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                    <!-- Search Input -->
                    <div class="relative flex-grow">
                        <x-text-input type="text" name="search" :value="request('search')"
                               class="pl-11 pr-4 rounded-xl font-semibold shadow-sm"
                               placeholder="Search by name, email or phone..." />
                        <div class="absolute left-4 top-3.5 text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full md:w-44">
                        <x-select-input name="status" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
                        </x-select-input>
                    </div>

                    <!-- Role Filter -->
                    <div class="w-full md:w-52">
                        <x-select-input name="role" class="rounded-xl !text-xs font-bold text-slate-600 shadow-sm">
                            <option value="">All Roles</option>
                            <option value="personal" {{ request('role') === 'personal' ? 'selected' : '' }}>Personal</option>
                            <option value="agent" {{ request('role') === 'agent' ? 'selected' : '' }}>Agent</option>
                            <option value="partner" {{ request('role') === 'partner' ? 'selected' : '' }}>Partner</option>
                            <option value="coordinator" {{ request('role') === 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                            <option value="regional_manager" {{ request('role') === 'regional_manager' ? 'selected' : '' }}>Regional Manager</option>
                            <option value="business" {{ request('role') === 'business' ? 'selected' : '' }}>Enterprise Business</option>
                            <option value="business_agent" {{ request('role') === 'business_agent' ? 'selected' : '' }}>Business Agent</option>
                            <option value="business_sales_manager" {{ request('role') === 'business_sales_manager' ? 'selected' : '' }}>Business Sales Manager</option>
                            <option value="support" {{ request('role') === 'support' ? 'selected' : '' }}>Support</option>
                            <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        </x-select-input>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <x-primary-button type="submit" class="flex-1 md:flex-none !text-xs">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            Filter
                        </x-primary-button>
                        @if(request('search') || request('status') || request('role'))
                            <a href="{{ route('admin.manage.users') }}" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/50 rounded-xl transition-all duration-150">
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
                            <th class="py-4 px-6">Joined Date</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm font-medium text-slate-700">
                        @forelse ($users as $u)
                            <tr class="hover:bg-slate-50/30 transition-all duration-150">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-[#0056D2]/5 text-[#0056D2] rounded-xl flex items-center justify-center font-bold text-xs uppercase">
                                            {{ substr($u->first_name, 0, 1) }}{{ substr($u->last_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <span class="block text-slate-800 font-bold font-display">{{ $u->first_name }} {{ $u->middle_name }} {{ $u->last_name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="block text-xs font-semibold text-slate-500">{{ $u->email }}</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">{{ $u->phone ?? 'No Phone' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 text-xs font-extrabold rounded-full bg-slate-100 text-slate-600 border border-slate-200/50 uppercase tracking-wider">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if ($u->status === 'active')
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
                                        <!-- View -->
                                        <a href="{{ route('admin.manage.users.show', $u) }}" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-[#0056D2]/5 hover:bg-[#0056D2]/10 text-[#0056D2] rounded-xl transition-all duration-150">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            View
                                        </a>

                                        <!-- Edit -->
                                        <a href="{{ route('admin.manage.users.edit', $u) }}" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition-all duration-150">
                                            <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                            Edit
                                        </a>

                                        <!-- Delete -->
                                        @if (auth()->id() !== $u->id)
                                            <form method="POST" action="{{ route('admin.manage.users.destroy', $u) }}" 
                                                  onsubmit="return confirm('Are you sure you want to permanently delete user {{ $u->first_name }} {{ $u->last_name }} and all their wallet and virtual account records?');" 
                                                  class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-100/50 rounded-xl transition-all duration-150">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400 text-sm">
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
