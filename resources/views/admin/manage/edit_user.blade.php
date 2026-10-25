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
            <form method="POST" action="{{ route('admin.manage.users.update', $user) }}" class="space-y-6">
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
                        <x-select-input name="role">
                            <option value="personal" {{ old('role', $user->role) == 'personal' ? 'selected' : '' }}>Personal (User)</option>
                            <option value="agent" {{ old('role', $user->role) == 'agent' ? 'selected' : '' }}>Agent</option>
                            <option value="partner" {{ old('role', $user->role) == 'partner' ? 'selected' : '' }}>Partner</option>
                            <option value="coordinator" {{ old('role', $user->role) == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                            <option value="regional_manager" {{ old('role', $user->role) == 'regional_manager' ? 'selected' : '' }}>Regional Manager</option>
                            <option value="business" {{ old('role', $user->role) == 'business' ? 'selected' : '' }}>Enterprise Business</option>
                            <option value="business_agent" {{ old('role', $user->role) == 'business_agent' ? 'selected' : '' }}>Business Agent</option>
                            <option value="business_sales_manager" {{ old('role', $user->role) == 'business_sales_manager' ? 'selected' : '' }}>Business Sales Manager</option>
                            <option value="support" {{ old('role', $user->role) == 'support' ? 'selected' : '' }}>Support</option>
                            <option value="super_admin" {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        </x-select-input>
                    </div>
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
</x-app-layout>
