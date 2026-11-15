<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-8">
        <div class="space-y-3">
            <a href="{{ route('admin.manage.users') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to Users
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Create Admin Account</h1>
                <p class="mt-1 text-xs text-slate-500">Create an active internal account with dashboard access.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.manage.admins.store') }}" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label for="first_name" value="First Name" />
                    <x-text-input id="first_name" name="first_name" type="text" :value="old('first_name')" required />
                    @error('first_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="middle_name" value="Middle Name" />
                    <x-text-input id="middle_name" name="middle_name" type="text" :value="old('middle_name')" />
                    @error('middle_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="last_name" value="Last Name" />
                    <x-text-input id="last_name" name="last_name" type="text" :value="old('last_name')" required />
                    @error('last_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="email" value="Email Address" />
                    <x-text-input id="email" name="email" type="email" :value="old('email')" required />
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="phone" value="Phone Number" />
                    <x-text-input id="phone" name="phone" type="text" :value="old('phone')" required />
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <x-input-label for="role" value="Admin Role" />
                <x-select-input id="role" name="role" required>
                    <option value="support" {{ old('role') === 'support' ? 'selected' : '' }}>Support</option>
                    <option value="business_sales_manager" {{ old('role') === 'business_sales_manager' ? 'selected' : '' }}>Business Sales Manager</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </x-select-input>
                @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="password" value="Temporary Password (12+ characters)" />
                    <x-text-input id="password" name="password" type="password" required autocomplete="new-password" />
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('admin.manage.users') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <x-primary-button>Create Admin</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>