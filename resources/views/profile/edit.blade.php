<x-app-layout>
    @php
        // Internal/back-office accounts (staff, checker, support,
        // super_admin) get none of the customer self-service tabs —
        // withdrawal account, upgrade, and delete are all no-ops for them,
        // and personal info is managed by a super_admin via Manage Access,
        // not self-edited. See App\Support\StaffAccess.
        $isInternalStaff = \App\Support\StaffAccess::isInternal($user);
    @endphp
    <!-- Page Header Hero Card -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden">
            <!-- Decorative Accent Gradients -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-[#0056D2]/5 rounded-full blur-3xl"></div>
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/5 rounded-full blur-3xl"></div>

            <div class="flex items-center gap-5 relative z-10">
                <div class="relative">
                    @if ($user->profile_photo)
                        <img src="{{ asset($user->profile_photo) }}" 
                             class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-4 border-slate-50 shadow-md" 
                             alt="Avatar">
                    @else
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-[#0056D2]/10 text-[#0056D2] flex items-center justify-center font-bold text-3xl font-display border-4 border-slate-50 shadow-md">
                            {{ strtoupper(substr($user->first_name ?? $user->email, 0, 1)) }}
                        </div>
                    @endif
                    <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-500 rounded-full border-4 border-white flex items-center justify-center shadow">
                        <span class="block w-2 h-2 bg-emerald-100 rounded-full animate-ping"></span>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 font-display">
                        {{ $user->first_name }} {{ $user->last_name }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 mt-1.5">
                        <span class="px-3 py-0.5 text-xs font-bold bg-[#0056D2]/10 text-[#0056D2] rounded-full uppercase tracking-wider">
                            {{ $user->role }}
                        </span>
                        @if ($user->hasJurisdictionRole())
                            <span class="text-slate-300 text-xs">•</span>
                            @if ($user->jurisdictionLabel())
                                <span class="px-3 py-0.5 text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                    {{ $user->jurisdictionLabel() }}
                                </span>
                            @else
                                <span class="px-3 py-0.5 text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <i data-lucide="map-pin-off" class="w-3.5 h-3.5"></i>
                                    Unassigned
                                </span>
                            @endif
                        @endif
                        <span class="text-slate-300 text-xs">•</span>
                        <span class="px-3 py-0.5 text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            Tier {{ $user->account_tier }} Verified
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4 relative z-10">
                <div class="bg-slate-50 border border-slate-100 px-6 py-4 rounded-2xl text-center md:text-right min-w-[150px]">
                    <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Wallet Balance</span>
                    <span class="block text-xl font-extrabold text-[#0056D2] font-display">
                        ₦{{ number_format($user->wallet->balance ?? 0.00, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation and View Panel -->
    <div x-data="{
        activeTab: '{{ $errors->updatePassword->any() || $errors->updatePin->any() ? 'security' : ($errors->updateWithdrawal->any() ? 'withdrawal' : ($errors->any() ? 'profile' : 'profile')) }}'
    }" class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        
        <!-- Sidebar Navigation Options -->
        <div class="lg:col-span-1 space-y-2 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="px-3 mb-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Settings Menu</h3>
            
            <button @click="activeTab = 'profile'" 
                    :class="activeTab === 'profile' ? 'bg-[#0056D2] text-white shadow-lg shadow-[#0056D2]/10' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span>Personal Profile</span>
            </button>
            
            <button @click="activeTab = 'security'" 
                    :class="activeTab === 'security' ? 'bg-[#0056D2] text-white shadow-lg shadow-[#0056D2]/10' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'" 
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                <i data-lucide="shield" class="w-5 h-5"></i>
                <span>Security & PIN</span>
            </button>
            
            @unless ($isInternalStaff)
                <button @click="activeTab = 'withdrawal'"
                        :class="activeTab === 'withdrawal' ? 'bg-[#0056D2] text-white shadow-lg shadow-[#0056D2]/10' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                    <i data-lucide="banknote" class="w-5 h-5"></i>
                    <span>Withdrawal Account</span>
                </button>

                <button @click="activeTab = 'upgrade'"
                        :class="activeTab === 'upgrade' ? 'bg-[#0056D2] text-white shadow-lg shadow-[#0056D2]/10' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                    <i data-lucide="award" class="w-5 h-5"></i>
                    <span>Account Upgrade</span>
                </button>

                <button @click="activeTab = 'danger'"
                        :class="activeTab === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-transparent text-slate-600 hover:bg-rose-50 hover:text-rose-700'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-200">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>Delete Account</span>
                </button>
            @endunless
        </div>

        <!-- Form Panels -->
        <div class="lg:col-span-3">
            <!-- Global Feedback Alerts -->
            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3 animate-in fade-in slide-in-from-top-4 duration-200">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any() && !$errors->updatePassword->any() && !$errors->updatePin->any() && !$errors->updateWithdrawal->any())
                <div class="mb-6 p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl animate-in fade-in slide-in-from-top-4 duration-200">
                    <div class="flex items-center gap-3 mb-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500 flex-shrink-0"></i>
                        <span class="text-sm font-bold">Please correct the following errors:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li class="text-xs font-semibold">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- TAB 1: PERSONAL PROFILE -->
            <div x-show="activeTab === 'profile'" 
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6">
                
                <div>
                    <h2 class="text-lg font-bold text-slate-800 font-display">Personal Details</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        @if ($isInternalStaff)
                            Your profile information is managed by a super admin and cannot be self-edited.
                        @else
                            Manage your basic profile information, gender identity, and profile picture.
                        @endif
                    </p>
                </div>

                @if ($isInternalStaff)
                    <!-- Read-only summary — internal/back-office accounts don't self-edit their profile -->
                    <div class="flex flex-col sm:flex-row items-center gap-6 pb-6 border-b border-slate-100">
                        @if ($user->profile_photo)
                            <img src="{{ asset($user->profile_photo) }}"
                                 class="w-24 h-24 rounded-3xl object-cover border-4 border-slate-50 shadow-md"
                                 alt="Avatar">
                        @else
                            <div class="w-24 h-24 rounded-3xl bg-[#0056D2]/10 text-[#0056D2] flex items-center justify-center font-bold text-3xl font-display border-4 border-slate-50 shadow-md">
                                {{ strtoupper(substr($user->first_name ?? $user->email, 0, 1)) }}
                            </div>
                        @endif
                        <div class="text-center sm:text-left space-y-1">
                            <h4 class="text-sm font-bold text-slate-700">Avatar Photo</h4>
                            <p class="text-xs text-slate-400">Contact a super admin to change your photo.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <x-input-label value="First Name" />
                            <x-text-input type="text" :value="$user->first_name" disabled />
                        </div>
                        <div>
                            <x-input-label value="Middle Name" />
                            <x-text-input type="text" :value="$user->middle_name" disabled />
                        </div>
                        <div>
                            <x-input-label value="Last Name" />
                            <x-text-input type="text" :value="$user->last_name" disabled />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <x-input-label value="Email Address" />
                            <x-text-input type="email" :value="$user->email" disabled />
                        </div>
                        <div>
                            <x-input-label value="Phone Number" />
                            <x-text-input type="text" :value="$user->phone" disabled />
                        </div>
                        <div>
                            <x-input-label value="Gender" />
                            <x-text-input type="text" :value="$user->gender ? ucfirst($user->gender) : 'Not set'" disabled />
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('patch')

                        <!-- Photo Upload Area -->
                        <div x-data="{ photoPreview: null }" class="flex flex-col sm:flex-row items-center gap-6 pb-6 border-b border-slate-100">
                            <div class="relative group">
                                @if ($user->profile_photo)
                                    <img x-show="!photoPreview"
                                         src="{{ asset($user->profile_photo) }}"
                                         class="w-24 h-24 rounded-3xl object-cover border-4 border-slate-50 shadow-md group-hover:opacity-80 transition-all duration-200"
                                         alt="Avatar">
                                @else
                                    <div x-show="!photoPreview" class="w-24 h-24 rounded-3xl bg-[#0056D2]/10 text-[#0056D2] flex items-center justify-center font-bold text-3xl font-display border-4 border-slate-50 shadow-md group-hover:opacity-80 transition-all duration-200">
                                        {{ strtoupper(substr($user->first_name ?? $user->email, 0, 1)) }}
                                    </div>
                                @endif

                                <img x-show="photoPreview"
                                     :src="photoPreview"
                                     class="w-24 h-24 rounded-3xl object-cover border-4 border-slate-50 shadow-md"
                                     style="display: none;"
                                     alt="Preview">

                                <!-- Overlay camera button -->
                                <label for="profile_photo" class="absolute inset-0 flex items-center justify-center bg-slate-900/40 rounded-3xl opacity-0 group-hover:opacity-100 cursor-pointer transition-all duration-200">
                                    <i data-lucide="camera" class="w-6 h-6 text-white"></i>
                                </label>

                                <input type="file"
                                       id="profile_photo"
                                       name="profile_photo"
                                       class="hidden"
                                       accept="image/*"
                                       @change="
                                           const reader = new FileReader();
                                           reader.onload = (e) => {
                                               photoPreview = e.target.result;
                                           };
                                           reader.readAsDataURL($event.target.files[0]);
                                       ">
                            </div>

                            <div class="text-center sm:text-left space-y-1.5">
                                <h4 class="text-sm font-bold text-slate-700">Avatar Photo</h4>
                                <p class="text-xs text-slate-400">Accepts PNG, JPG, or JPEG. Max file limit is 2MB.</p>
                                <x-secondary-button type="button"
                                        @click="document.getElementById('profile_photo').click()"
                                        class="!py-1.5 !text-xs">
                                    Choose New File
                                </x-secondary-button>
                            </div>
                        </div>

                        <!-- Input Grid (Read-only / Disabled) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <x-input-label value="First Name" />
                                <x-text-input type="text" :value="old('first_name', $user->first_name)" disabled />
                            </div>
                            <div>
                                <x-input-label value="Middle Name (Optional)" />
                                <x-text-input type="text" :value="old('middle_name', $user->middle_name)" disabled />
                            </div>
                            <div>
                                <x-input-label value="Last Name" />
                                <x-text-input type="text" :value="old('last_name', $user->last_name)" disabled />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Email Address" />
                                <x-text-input type="email" :value="old('email', $user->email)" disabled />
                            </div>
                            <div>
                                <x-input-label value="Phone Number" />
                                <x-text-input type="text" :value="old('phone', $user->phone)" disabled />
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Gender Identification" />
                            <x-select-input name="gender">
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                            </x-select-input>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-primary-button type="submit">
                                Save Profile Changes
                            </x-primary-button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- TAB 2: SECURITY & PIN -->
            <div x-show="activeTab === 'security'" 
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-8" 
                 style="display: none;">
                
                <!-- Change password block -->
                <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 font-display">Account Password</h2>
                        <p class="text-xs text-slate-400 mt-1">Make sure you use a secure, complex password for your login security.</p>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                        @csrf
                        @method('put')

                        @if ($errors->updatePassword->any())
                            <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach ($errors->updatePassword->all() as $error)
                                        <li class="text-xs font-semibold">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div>
                            <x-input-label value="Current Password" />
                            <x-text-input type="password" name="current_password" required
                                   :class="$errors->updatePassword->has('current_password') ? 'border-rose-400' : ''" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="New Password" />
                                <x-text-input type="password" name="password" required
                                       :class="$errors->updatePassword->has('password') ? 'border-rose-400' : ''" />
                            </div>
                            <div>
                                <x-input-label value="Confirm New Password" />
                                <x-text-input type="password" name="password_confirmation" required />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <x-primary-button type="submit">
                                Update Login Password
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Transaction PIN block -->
                <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 font-display">Secure Transaction PIN</h2>
                            <p class="text-xs text-slate-400 mt-1">A 4-digit security PIN is required to authorize all wallet transactions and service purchases.</p>
                        </div>
                        <div class="shrink-0">
                            @if ($user->transaction_pin)
                                <span class="px-3 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                    Active
                                </span>
                                @if ($user->pin_set_at)
                                    <p class="text-xs text-slate-400 text-right mt-1">Last set {{ $user->pin_set_at->diffForHumans() }}</p>
                                @endif
                            @else
                                <span class="px-3 py-1 text-xs font-extrabold bg-amber-50 text-amber-600 border border-amber-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                    Not Set
                                </span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('profile.pin.update') }}" class="space-y-6">
                        @csrf

                        @if ($errors->updatePin->any())
                            <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach ($errors->updatePin->all() as $error)
                                        <li class="text-xs font-semibold">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div>
                            <x-input-label value="Confirm Account Password" />
                            <x-text-input type="password" name="password" required
                                   placeholder="Enter password to authorise PIN update"
                                   :class="$errors->updatePin->has('password') ? 'border-rose-400' : ''" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="New Transaction PIN" class="text-center block" />
                                <x-pin-input name="transaction_pin" :error="$errors->updatePin->has('transaction_pin')" />
                            </div>
                            <div>
                                <x-input-label value="Confirm Transaction PIN" class="text-center block" />
                                <x-pin-input name="transaction_pin_confirmation" :error="$errors->updatePin->has('transaction_pin_confirmation')" />
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
                            PIN must be exactly <strong>4 numeric digits</strong>. It is stored securely using bcrypt encryption and is never visible to anyone.
                        </p>

                        <div class="flex justify-end pt-2">
                            <x-primary-button type="submit">
                                Save Transaction PIN
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Device Registration block -->
                <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6"
                     x-data="{ showConfirm: false, pendingEnabled: {{ $user->device_registration_enabled ? 'true' : 'false' }} }">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 font-display">New Device Sign-In Verification</h2>
                            <p class="text-xs text-slate-400 mt-1">Require a one-time code when signing in from a device we haven't seen before, sent to your phone, email, or an already-trusted device.</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-3">
                            @if ($user->device_registration_enabled)
                                <span class="px-3 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                    Enabled
                                </span>
                            @else
                                <span class="px-3 py-1 text-xs font-extrabold bg-slate-50 text-slate-500 border border-slate-200 rounded-full uppercase tracking-wider flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                    Disabled
                                </span>
                            @endif
                            <x-toggle-switch :checked="$user->device_registration_enabled"
                                              x-on:click.prevent="pendingEnabled = !pendingEnabled; showConfirm = true" />
                        </div>
                    </div>

                    @if ($errors->deviceRegistration->any())
                        <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl">
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach ($errors->deviceRegistration->all() as $error)
                                    <li class="text-xs font-semibold">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div x-show="showConfirm" x-cloak class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <form method="POST" action="{{ route('profile.device-registration.update') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="enabled" x-bind:value="pendingEnabled ? 1 : 0">
                            <p class="text-xs text-slate-500"
                               x-text="pendingEnabled ? 'Confirm your password to require a code on new devices from now on.' : 'Confirm your password to stop requiring a code on new devices.'"></p>
                            <x-text-input type="password" name="password" required placeholder="Confirm your password" class="w-full" />
                            <div class="flex gap-2">
                                <x-primary-button type="submit">
                                    <span x-text="pendingEnabled ? 'Enable' : 'Disable'"></span>
                                </x-primary-button>
                                <button type="button" @click="showConfirm = false" class="text-xs font-semibold text-slate-500 px-3">Cancel</button>
                            </div>
                        </form>
                    </div>

                    @if ($user->phone && !$user->phone_verified_at)
                        <div x-data="{ codeSent: false }" class="p-4 bg-amber-50/60 border border-amber-100 rounded-xl space-y-3">
                            <p class="text-xs text-amber-700">Verify your phone number to unlock SMS as a sign-in verification channel.</p>
                            <form method="POST" action="{{ route('profile.phone.send-otp') }}" x-show="!codeSent" @submit="codeSent = true">
                                @csrf
                                <button type="submit" class="text-xs font-bold text-amber-700 hover:text-amber-800 px-3 py-1.5 rounded-lg border border-amber-200 hover:bg-amber-100 transition">
                                    Send Verification Code
                                </button>
                            </form>
                            <form method="POST" action="{{ route('profile.phone.verify-otp') }}" x-show="codeSent" x-cloak class="space-y-2">
                                @csrf
                                @if ($errors->phoneVerification->any())
                                    <p class="text-xs font-semibold text-rose-600">{{ $errors->phoneVerification->first('code') }}</p>
                                @endif
                                <x-text-input type="text" name="code" maxlength="6" placeholder="Enter the 6-digit code" class="w-full" />
                                <x-primary-button type="submit">Verify Phone</x-primary-button>
                            </form>
                        </div>
                    @endif

                    <div class="space-y-2">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Trusted Devices</h3>
                        @forelse ($trustedDevices as $device)
                            <div class="flex items-center justify-between gap-3 p-3 bg-slate-50/60 border border-slate-100 rounded-xl">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-700 truncate">{{ $device->label }}</p>
                                    <p class="text-[11px] text-slate-400">Last used {{ $device->last_used_at?->diffForHumans() ?? 'never' }} @if ($device->last_used_ip) &middot; {{ $device->last_used_ip }} @endif</p>
                                </div>
                                <form method="POST" action="{{ route('profile.trusted-devices.revoke', $device) }}" class="shrink-0">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-[11px] font-bold text-rose-600 hover:text-rose-700 px-2.5 py-1 rounded-lg border border-rose-100 hover:bg-rose-50 transition">
                                        Revoke
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No trusted devices yet — the next device you verify from will appear here.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @unless ($isInternalStaff)
            <!-- TAB: WITHDRAWAL ACCOUNT -->
            <div x-show="activeTab === 'withdrawal'"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6"
                 style="display: none;">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 font-display">Withdrawal Account</h2>
                        <p class="text-xs text-slate-400 mt-1">The single bank account your cash-out requests are paid into. Only one account can be saved at a time — saving a new one replaces it. Our team's approval is required before any funds are sent.</p>
                    </div>
                    <div class="shrink-0">
                        @if ($withdrawalAccount)
                            <span class="px-3 py-1 text-xs font-extrabold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                Set
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-extrabold bg-amber-50 text-amber-600 border border-amber-100 rounded-full uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                Not Set
                            </span>
                        @endif
                    </div>
                </div>

                @if ($withdrawalAccount)
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-center gap-3.5 shadow-inner">
                        <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm text-[#0056D2]">
                            <i data-lucide="landmark" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-slate-800 truncate">{{ $withdrawalAccount->account_name }}</p>
                            <p class="text-xs text-slate-400 font-semibold">{{ $withdrawalAccount->bank_name }} &bull; {{ substr($withdrawalAccount->account_no, 0, 3) }}****{{ substr($withdrawalAccount->account_no, -3) }}</p>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.withdrawal-account.update') }}" id="withdrawalAccountForm" class="space-y-4">
                    @csrf

                    @if ($errors->updateWithdrawal->any())
                        <div class="p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl">
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach ($errors->updateWithdrawal->all() as $error)
                                    <li class="text-xs font-semibold">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Hidden native select — the actual form-submitted field --}}
                    <select name="bankCode" id="wa_bank_code" class="hidden" required>
                        <option value="">Choose a bank...</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->bank_code }}" data-name="{{ $bank->bank_name }}"
                                {{ old('bankCode', $withdrawalAccount->bank_code ?? '') === $bank->bank_code ? 'selected' : '' }}>
                                {{ $bank->bank_name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="space-y-1.5" id="wa_bankPickerWrapper">
                        <x-input-label value="Bank" />
                        <div class="relative">
                            <div class="w-full flex items-center border border-slate-200 rounded-xl bg-white shadow-sm cursor-pointer select-none" id="wa_bankPickerTrigger" role="button" tabindex="0">
                                <div class="flex-grow pl-3.5 pr-4 py-3.5 flex items-center justify-between text-xs font-semibold text-slate-700">
                                    <span id="wa_bankPickerLabel" class="{{ ($withdrawalAccount->bank_name ?? null) ? 'text-slate-800 font-bold' : 'text-slate-400' }}">{{ $withdrawalAccount->bank_name ?? 'Choose a bank...' }}</span>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="wa_bankChevron"></i>
                                </div>
                            </div>
                            <div id="wa_bankPickerDropdown" class="hidden absolute left-0 right-0 z-50 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                                <div class="p-2 border-b border-slate-100 bg-slate-50">
                                    <x-text-input type="text" id="wa_bankSearchInput" placeholder="Search bank name..." autocomplete="off" />
                                </div>
                                <ul class="list-none mb-0 max-h-56 overflow-y-auto divide-y divide-slate-50" id="wa_bankPickerList">
                                    @foreach ($banks as $bank)
                                        <li class="wa-bank-item flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors cursor-pointer" data-code="{{ $bank->bank_code }}" data-name="{{ $bank->bank_name }}">
                                            <span class="text-xs font-semibold text-slate-700">{{ $bank->bank_name }}</span>
                                        </li>
                                    @endforeach
                                    <li class="px-4 py-6 text-center text-xs text-slate-400 hidden" id="wa_bankNoResults">No banks found</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label for="wa_account_no" value="Account Number" />
                        <x-text-input type="text" id="wa_account_no" name="account_no"
                               placeholder="Enter 10-digit account number" maxlength="10" inputmode="numeric" required
                               :value="old('account_no', $withdrawalAccount->account_no ?? '')"
                               :class="$errors->updateWithdrawal->has('account_no') ? 'border-rose-400' : ''" />
                        <div class="mt-1.5 min-h-[22px]">
                            <div id="wa_accountNameDisplay" class="text-xs font-bold"></div>
                            <div id="wa_accountErrorDisplay" class="text-xs font-bold text-rose-600"></div>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-slate-300 shrink-0"></i>
                        The account name shown is resolved directly with the bank and cannot be edited manually.
                    </p>

                    <div class="flex justify-end pt-2">
                        <x-primary-button type="submit">
                            Save Withdrawal Account
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- TAB 3: ACCOUNT UPGRADE -->
            <div x-show="activeTab === 'upgrade'" 
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6" 
                 style="display: none;">
                
                <div>
                    <h2 class="text-lg font-bold text-slate-800 font-display">Upgrade Account Status</h2>
                    <p class="text-xs text-slate-400 mt-1">Upgrade your tier status and access bulk discounts by updating to an Agent, Partner, or Business tier.</p>
                </div>

                <!-- Request Status Info Banners -->
                @if ($user->upgrade_status === 'pending')
                    <div class="p-4 bg-amber-50 border border-amber-100 text-amber-700 rounded-2xl flex items-start gap-3">
                        <i data-lucide="clock" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5 animate-pulse"></i>
                        <div>
                            <span class="block text-sm font-bold">Upgrade Request Pending</span>
                            <span class="block text-xs mt-0.5 font-medium leading-relaxed">
                                Your application to upgrade to <strong class="uppercase">{{ $user->pending_role }}</strong> was submitted on {{ $user->upgrade_requested_at->format('M d, Y h:i A') }} and is currently under review by our team.
                            </span>
                        </div>
                    </div>
                @elseif ($user->upgrade_status === 'rejected')
                    <div class="p-4 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-start gap-3">
                        <i data-lucide="x-circle" class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <span class="block text-sm font-bold">Upgrade Application Declined</span>
                            <span class="block text-xs mt-0.5 font-medium leading-relaxed">
                                Your previous upgrade application was declined. You may review your business information below and re-submit a new application.
                            </span>
                        </div>
                    </div>
                @elseif ($user->upgrade_status === 'approved')
                    <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <span class="block text-sm font-bold">Upgrade Request Approved</span>
                            <span class="block text-xs mt-0.5 font-medium leading-relaxed">
                                Congratulations! Your upgrade to <strong class="uppercase">{{ $user->role }}</strong> has been approved. You are now enjoying premium rates and API permissions.
                            </span>
                        </div>
                    </div>
                @endif

                @if ($user->upgrade_status === 'pending' || $user->upgrade_status === 'approved' || in_array($user->role, ['agent', 'partner', 'business']))
                    <!-- Read-only Summary Cards -->
                    <div class="bg-slate-50 border border-slate-200/60 rounded-3xl p-6 sm:p-8 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Target Account Tier</label>
                                <div class="px-4 py-3 bg-slate-100/50 border border-slate-200 rounded-xl text-sm text-slate-700 font-semibold capitalize">
                                    {{ $user->pending_role ?? $user->role }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Confirm Gender</label>
                                <div class="px-4 py-3 bg-slate-100/50 border border-slate-200 rounded-xl text-sm text-slate-700 font-semibold capitalize">
                                    {{ $user->gender }}
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-200/60 pt-6 space-y-6">
                            <h4 class="text-sm font-bold text-slate-700">Business Registration & Credentials</h4>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Registered Business Name</label>
                                <div class="px-4 py-3 bg-slate-100/50 border border-slate-200 rounded-xl text-sm text-slate-700 font-semibold">
                                    {{ $user->business_name }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Business Category Type</label>
                                    <div class="px-4 py-3 bg-slate-100/50 border border-slate-200 rounded-xl text-sm text-slate-700 font-semibold capitalize">
                                        {{ str_replace('_', ' ', $user->business_type) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">CAC Registration Number</label>
                                    <div class="px-4 py-3 bg-slate-100/50 border border-slate-200 rounded-xl text-sm text-slate-700 font-semibold">
                                        {{ $user->cac_number }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('profile.upgrade.request') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label value="Target Account Tier" />
                                <x-select-input name="role">
                                    <option value="" disabled selected>Select Tier </option>
                                    <option value="partner" {{ old('role', $user->pending_role ?? $user->role) == 'partner' ? 'selected' : '' }}>Partner (Integrator APIs & custom values)</option>
                                    <option value="business" {{ old('role', $user->pending_role ?? $user->role) == 'business' ? 'selected' : '' }}>Business (Wholesale pricing tiers)</option>
                                </x-select-input>
                            </div>

                            <div>
                                <x-input-label value="Confirm Gender" />
                                <x-select-input name="gender">
                                    <option value="" disabled selected>Select Gender</option>
                                    <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </x-select-input>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-6 space-y-6">
                            <h4 class="text-sm font-bold text-slate-700">Business Registration & Credentials</h4>

                            <div>
                                <x-input-label value="Registered Business Name" />
                                <x-text-input type="text" name="business_name" required
                                       :value="old('business_name', $user->business_name)"
                                       placeholder="e.g. Smart Telecoms Ltd" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label value="Business Category Type" />
                                    <x-select-input name="business_type">
                                        <option value="" disabled selected>Select Category</option>
                                        <option value="sole_proprietor" {{ old('business_type', $user->business_type) == 'sole_proprietor' ? 'selected' : '' }}>Sole Proprietorship</option>
                                        <option value="llc" {{ old('business_type', $user->business_type) == 'llc' ? 'selected' : '' }}>Limited Liability Company (LLC)</option>
                                        <option value="partnership" {{ old('business_type', $user->business_type) == 'partnership' ? 'selected' : '' }}>Partnership</option>
                                    </x-select-input>
                                </div>

                                <div>
                                    <x-input-label value="CAC Registration Number" />
                                    <x-text-input type="text" name="cac_number" required
                                           :value="old('cac_number', $user->cac_number)"
                                           placeholder="e.g. RC-1234567" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <x-primary-button type="submit">
                                Submit Upgrade Request
                            </x-primary-button>
                        </div>
                    </form>
                @endif
            </div>
            </div>

            <!-- TAB 4: DANGER ZONE -->
            <div x-show="activeTab === 'danger'" 
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="bg-white p-6 sm:p-8 rounded-xl border border-rose-200 shadow-sm space-y-6" 
                 style="display: none;">
                
                <div>
                    <h2 class="text-lg font-bold text-rose-700 font-display">Deactivate Account</h2>
                    <p class="text-xs text-slate-400 mt-1">Once your account is deactivated, you will be logged out and your access will be suspended.</p>
                </div>

                <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl">
                    <p class="text-xs text-rose-700 font-medium leading-relaxed">
                        Warning: Deactivating your account will result in immediate loss of access to your dashboard, transaction logs, and API credentials. Your profile data and history will be safely preserved but locked.
                    </p>
                </div>

                <div class="flex justify-start">
                    <x-danger-button type="button"
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
                        Deactivate My SmartSIM Account
                    </x-danger-button>
                </div>

                <!-- Breeze Modal Integration -->
                <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                    <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8 space-y-6">
                        @csrf
                        @method('delete')

                        <div>
                            <h2 class="text-lg font-bold text-slate-800 font-display">
                                Are you sure you want to deactivate your account?
                            </h2>
                            <p class="text-xs text-slate-400 mt-1">
                                Please type your password to confirm that you want to deactivate this account.
                            </p>
                        </div>

                        <div>
                            <x-input-label value="Account Password" />
                            <x-text-input type="password" name="password" required
                                   placeholder="Type account password" />
                            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <x-secondary-button type="button"
                                    x-on:click="$dispatch('close')">
                                Cancel
                            </x-secondary-button>
                            <x-danger-button type="submit">
                                Deactivate Account
                            </x-danger-button>
                        </div>
                    </form>
                </x-modal>
            </div>
            @endunless

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const bankCodeSelect = document.getElementById('wa_bank_code');
        if (!bankCodeSelect) return; // withdrawal tab markup not present

        const accountNoInput   = document.getElementById('wa_account_no');
        const accountNameDisp  = document.getElementById('wa_accountNameDisplay');
        const accountErrorDisp = document.getElementById('wa_accountErrorDisplay');

        /* ── Custom Bank Picker ───────────────────────── */
        const trigger    = document.getElementById('wa_bankPickerTrigger');
        const dropdown   = document.getElementById('wa_bankPickerDropdown');
        const searchInput = document.getElementById('wa_bankSearchInput');
        const label       = document.getElementById('wa_bankPickerLabel');
        const chevron     = document.getElementById('wa_bankChevron');
        const items       = document.querySelectorAll('.wa-bank-item');
        const noResults   = document.getElementById('wa_bankNoResults');

        function openPicker() {
            dropdown.classList.remove('hidden');
            chevron.style.transform = 'rotate(180deg)';
            searchInput.value = '';
            items.forEach(el => el.classList.remove('hidden'));
            noResults?.classList.add('hidden');
            setTimeout(() => searchInput.focus(), 60);
        }
        function closePicker() {
            dropdown.classList.add('hidden');
            chevron.style.transform = '';
        }
        function applySelection(code, name) {
            bankCodeSelect.value = code;
            bankCodeSelect.dispatchEvent(new Event('change'));
            label.textContent = name;
            label.classList.remove('text-slate-400');
            label.classList.add('text-slate-800', 'font-bold');
            closePicker();
        }

        trigger.addEventListener('click', e => {
            e.stopPropagation();
            dropdown.classList.contains('hidden') ? openPicker() : closePicker();
        });

        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            items.forEach(item => {
                const match = item.getAttribute('data-name').toLowerCase().includes(q);
                item.classList.toggle('hidden', !match);
                if (match) visible++;
            });
            noResults?.classList.toggle('hidden', visible > 0);
        });

        items.forEach(item => {
            item.addEventListener('click', () => applySelection(
                item.getAttribute('data-code'),
                item.getAttribute('data-name')
            ));
        });

        document.addEventListener('click', e => {
            if (!document.getElementById('wa_bankPickerWrapper')?.contains(e.target)) {
                closePicker();
            }
        });

        /* ── Live account-name preview (server re-verifies on submit regardless) ── */
        let verificationTimeout;
        function performVerification() {
            const bankCode = bankCodeSelect.value;
            const acctNo = accountNoInput.value;
            if (!(bankCode && acctNo.length === 10)) return;

            accountNameDisp.innerHTML = '<span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full"><span class="w-2.5 h-2.5 border border-slate-400 border-t-transparent rounded-full animate-spin"></span> Verifying...</span>';
            accountErrorDisp.innerHTML = '';

            fetch("{{ route('withdraw.verifyAccount') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ bankCode: bankCode, account_no: acctNo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    accountNameDisp.innerHTML = `<span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full shadow-sm"><i data-lucide="check-circle" class="w-3.5 h-3.5 inline-block"></i> ${data.account_name}</span>`;
                    accountErrorDisp.innerHTML = '';
                } else {
                    accountNameDisp.innerHTML = '';
                    accountErrorDisp.innerHTML = `<span class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-3 py-1 rounded-full shadow-sm"><i data-lucide="alert-circle" class="w-3.5 h-3.5 inline-block"></i> ${data.message}</span>`;
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(() => {
                accountNameDisp.innerHTML = '';
                accountErrorDisp.innerHTML = '<span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 bg-amber-50 border border-amber-100 px-3 py-1 rounded-full shadow-sm"><i data-lucide="wifi-off" class="w-3.5 h-3.5 inline-block"></i> Connection failed</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }

        accountNoInput.addEventListener('input', () => {
            clearTimeout(verificationTimeout);
            if (accountNoInput.value.length === 10) {
                verificationTimeout = setTimeout(performVerification, 500);
            } else {
                accountNameDisp.innerHTML = '';
                accountErrorDisp.innerHTML = '';
            }
        });

        bankCodeSelect.addEventListener('change', performVerification);
    });
    </script>
</x-app-layout>
