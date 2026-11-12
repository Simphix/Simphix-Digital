<x-app-layout>
    <div class="space-y-8 max-w-3xl mx-auto">
        <!-- Back Link & Title -->
        <div class="space-y-4">
            <a href="{{ route('admin.manage.users') }}" 
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors duration-150">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Users List
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Edit User details</h1>
                <p class="text-xs text-slate-400 mt-1">Modify account settings, tier permissions, and state status for {{ $user->first_name }}.</p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <form method="POST" action="{{ route('admin.manage.users.update', $user) }}"
                  x-data="adminUplinePicker({ role: @js(old('role', $user->role)), current: @js($currentUpline ? ['id' => $currentUpline->id, 'name' => $currentUpline->name, 'email' => $currentUpline->email, 'phone' => $currentUpline->phone, 'role' => $currentUpline->role, 'role_label' => ucwords(str_replace('_', ' ', $currentUpline->role))] : null), url: @js(route('admin.manage.users.eligible-uplines', $user)) })"
                  class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Name Details -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <x-input-label value="First Name" />
                        <x-text-input type="text" name="first_name" :value="old('first_name', $user->first_name)" required />
                    </div>
                    <div>
                        <x-input-label value="Middle Name" />
                        <x-text-input type="text" name="middle_name" :value="old('middle_name', $user->middle_name)" />
                    </div>
                    <div>
                        <x-input-label value="Last Name" />
                        <x-text-input type="text" name="last_name" :value="old('last_name', $user->last_name)" required />
                    </div>
                </div>

                <!-- Contact details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Email Address" />
                        <x-text-input type="email" name="email" :value="old('email', $user->email)" required />
                    </div>
                    <div>
                        <x-input-label value="Phone Number" />
                        <x-text-input type="text" name="phone" :value="old('phone', $user->phone)" required />
                    </div>
                </div>

                <!-- Settings & Access -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="User Status" />
                        <x-select-input name="status">
                            <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status', $user->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Inactive / Deactivated</option>
                            <option value="banned" {{ old('status', $user->status) == 'banned' ? 'selected' : '' }}>Banned</option>
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label value="System Role" />
                        @if ($user->role === 'super_admin')
                            <div class="mt-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 flex items-center justify-between">
                                <span class="uppercase tracking-wider">Super Admin</span>
                                <span class="text-[11px] font-normal text-slate-400 italic">Managed via CLI only</span>
                            </div>
                            <input type="hidden" name="role" value="super_admin">
                        @elseif (auth()->user()->role === 'business_sales_manager' && $user->role === 'business_sales_manager')
                            <div class="mt-1 px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 flex items-center justify-between">
                                <span class="uppercase tracking-wider">Business Sales Manager</span>
                                <span class="text-[11px] font-normal text-slate-400 italic">Protected Role</span>
                            </div>
                            <input type="hidden" name="role" value="business_sales_manager">
                        @else
                            <x-select-input name="role" x-model="role" @change="roleChanged">
                                <option value="personal" {{ old('role', $user->role) == 'personal' ? 'selected' : '' }}>Personal (User)</option>
                                <option value="agent" {{ old('role', $user->role) == 'agent' ? 'selected' : '' }}>Agent</option>
                                <option value="partner" {{ old('role', $user->role) == 'partner' ? 'selected' : '' }}>Partner</option>
                                <option value="coordinator" {{ old('role', $user->role) == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                <option value="regional_manager" {{ old('role', $user->role) == 'regional_manager' ? 'selected' : '' }}>Regional Manager</option>
                                <option value="business" {{ old('role', $user->role) == 'business' ? 'selected' : '' }}>Enterprise Business</option>
                                <option value="business_agent" {{ old('role', $user->role) == 'business_agent' ? 'selected' : '' }}>Business Agent</option>
                                @if (auth()->user()->role !== 'business_sales_manager')
                                    <option value="business_sales_manager" {{ old('role', $user->role) == 'business_sales_manager' ? 'selected' : '' }}>Business Sales Manager</option>
                                @endif
                                <option value="support" {{ old('role', $user->role) == 'support' ? 'selected' : '' }}>Support</option>
                            </x-select-input>
                        @endif
                    </div>
                </div>

                <div x-cloak x-show="isCatalogRole" class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 space-y-3">
                    <div>
                        <label for="upline-search" class="block text-xs font-bold uppercase tracking-wide text-slate-700">Upline Manager</label>
                        <p class="text-[11px] text-slate-500 mt-1">Search active managers eligible to be this account’s upline. Leave blank to clear the upline.</p>
                    </div>
                    <input type="hidden" name="referred_by" :value="selected?.id || ''" :disabled="!isCatalogRole">
                    <div class="relative">
                        <input id="upline-search" type="search" x-model="search" @input.debounce.250ms="fetchManagers"
                               placeholder="Search by name, email, or phone..."
                               class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <div x-show="loading" class="absolute right-3 top-3 text-xs text-indigo-600">Searching…</div>
                    </div>
                    <div x-show="selected" class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs">
                        <span>Selected: <strong x-text="selected?.name"></strong> <span class="text-slate-500" x-text="selected?.role_label"></span></span>
                        <button type="button" @click="selected = null" class="font-bold text-rose-600 hover:text-rose-800">Clear</button>
                    </div>
                    <div x-show="search.length > 0" class="max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-white divide-y divide-slate-100">
                        <template x-for="manager in managers" :key="manager.id">
                            <button type="button" @click="select(manager)" class="w-full px-3 py-2.5 text-left hover:bg-indigo-50 flex items-center justify-between gap-3">
                                <span class="min-w-0"><strong class="block text-xs text-slate-800" x-text="manager.name"></strong><span class="block truncate text-[11px] text-slate-500" x-text="manager.email || manager.phone"></span></span>
                                <span class="shrink-0 text-[10px] font-bold uppercase text-indigo-700" x-text="manager.role_label"></span>
                            </button>
                        </template>
                        <p x-show="!loading && managers.length === 0" class="p-3 text-xs text-slate-500">No eligible managers found.</p>
                    </div>
                    @error('referred_by') <p class="text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4 border-t border-slate-50">
                    <x-primary-button type="submit">
                        Save Changes
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function adminUplinePicker(config) {
            return {
                role: config.role,
                initialRole: config.role,
                current: config.current,
                url: config.url,
                search: '',
                managers: [],
                selected: config.current,
                loading: false,
                get isCatalogRole() {
                    return ['regional_manager', 'coordinator', 'partner', 'business', 'business_agent'].includes(this.role);
                },
                roleChanged() {
                    if (this.role !== this.initialRole) this.selected = null;
                    else this.selected = this.current;
                },
                async fetchManagers() {
                    if (!this.isCatalogRole || this.search.trim().length < 1) {
                        this.managers = [];
                        return;
                    }
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({ role: this.role, q: this.search.trim() });
                        const response = await fetch(`${this.url}?${params}`, {
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        this.managers = response.ok ? await response.json() : [];
                    } catch (error) {
                        console.error('Could not search eligible uplines', error);
                        this.managers = [];
                    } finally {
                        this.loading = false;
                    }
                },
                select(manager) {
                    this.selected = manager;
                    this.search = '';
                    this.managers = [];
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
