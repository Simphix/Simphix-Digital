<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-4xl mx-auto space-y-6"
         x-data="{
             selectedType: '{{ old('pos_type', $selectedType ?? 'smart_pos') }}',
             photoPreview: null,
             photoError: '',
             handlePhoto(event) {
                 const file = event.target.files[0];
                 this.photoError = '';
                 if (!file) {
                     this.photoPreview = null;
                     return;
                 }
                 if (file.size > 1024 * 1024) {
                     this.photoError = 'Picture file size exceeds 1 MB limit. Please upload a smaller passport photo.';
                     event.target.value = '';
                     this.photoPreview = null;
                     return;
                 }
                 const reader = new FileReader();
                 reader.onload = (e) => {
                     this.photoPreview = e.target.result;
                 };
                 reader.readAsDataURL(file);
             }
         }">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <p class="text-sm font-bold">Please correct the following errors:</p>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Back Navigation & Tracking Link -->
        <div class="flex items-center justify-between">
            <a href="{{ route('moniepoint.pos.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-[#00266B] transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to POS Models</span>
            </a>

            <a href="{{ route('moniepoint.pos.tracking') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#00266B] hover:underline">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                <span>Track Existing Request</span>
            </a>
        </div>

        <!-- Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex items-center gap-3.5 sm:gap-4">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#001F5C] flex items-center justify-center shrink-0 shadow-sm border border-blue-900/40 text-[#ffcc00]">
                    <i data-lucide="smartphone" class="w-7 h-7"></i>
                </div>
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-[#00266B] text-[11px] sm:text-xs font-bold mb-1">
                        <span>Official Terminal Deployment Desk</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display tracking-tight">
                        POS Terminal Request Application
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Please provide your personal information, next of kin details, and referral information to receive your POS terminal.
                    </p>
                </div>
            </div>
        </div>

        <!-- Dedicated Application Form -->
        <form method="POST" action="{{ route('moniepoint.pos.store') }}" enctype="multipart/form-data" autocomplete="off" class="space-y-6">
            @csrf

            <!-- STEP 1: Select POS Hardware Model -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#00266B] text-white flex items-center justify-center text-xs font-bold shrink-0">1</span>
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 font-display">Select Preferred POS Terminal Model</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <!-- Smart POS (Android) -->
                    <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all"
                           :class="selectedType === 'smart_pos' ? 'border-[#00266B] bg-blue-50/50 ring-2 ring-[#00266B]/10' : 'border-slate-200 hover:border-slate-300 bg-white'">
                        <input type="radio" name="pos_type" value="smart_pos" x-model="selectedType" class="sr-only" required>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-extrabold text-slate-900">Smart POS (Android)</span>
                            <span class="w-4 h-4 rounded-full border flex items-center justify-center"
                                  :class="selectedType === 'smart_pos' ? 'border-[#00266B] bg-[#00266B]' : 'border-slate-300'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedType === 'smart_pos'"></span>
                            </span>
                        </div>
                        <div class="h-24 flex items-center justify-center p-2 mb-2 bg-white rounded-lg border border-slate-100">
                            <img src="{{ asset('assets/images/moniepoint-pos.png') }}" alt="Smart POS" class="h-20 w-auto object-contain">
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug">Touchscreen 4G Android terminal with fast thermal receipt printer.</p>
                    </label>

                    <!-- Traditional POS -->
                    <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all"
                           :class="selectedType === 'traditional_pos' ? 'border-[#00266B] bg-blue-50/50 ring-2 ring-[#00266B]/10' : 'border-slate-200 hover:border-slate-300 bg-white'">
                        <input type="radio" name="pos_type" value="traditional_pos" x-model="selectedType" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-extrabold text-slate-900">Traditional POS</span>
                            <span class="w-4 h-4 rounded-full border flex items-center justify-center"
                                  :class="selectedType === 'traditional_pos' ? 'border-[#00266B] bg-[#00266B]' : 'border-slate-300'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedType === 'traditional_pos'"></span>
                            </span>
                        </div>
                        <div class="h-24 flex items-center justify-center p-2 mb-2 bg-white rounded-lg border border-slate-100">
                            <img src="{{ asset('assets/images/moniepoint-traditional-pos.png') }}" alt="Traditional POS" class="h-20 w-auto object-contain">
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug">Rugged physical keypad terminal built for high-volume transactions.</p>
                    </label>

                    <!-- Mini POS (MPOS) -->
                    <label class="relative flex flex-col p-4 rounded-xl border-2 cursor-pointer transition-all"
                           :class="selectedType === 'mini_pos' ? 'border-[#00266B] bg-blue-50/50 ring-2 ring-[#00266B]/10' : 'border-slate-200 hover:border-slate-300 bg-white'">
                        <input type="radio" name="pos_type" value="mini_pos" x-model="selectedType" class="sr-only">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-extrabold text-slate-900">Mini POS (MPOS)</span>
                            <span class="w-4 h-4 rounded-full border flex items-center justify-center"
                                  :class="selectedType === 'mini_pos' ? 'border-[#00266B] bg-[#00266B]' : 'border-slate-300'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedType === 'mini_pos'"></span>
                            </span>
                        </div>
                        <div class="h-24 flex items-center justify-center p-2 mb-2 bg-white rounded-lg border border-slate-100">
                            <img src="{{ asset('assets/images/moniepoint-mini-pos.png') }}" alt="Mini POS" class="h-20 w-auto object-contain">
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug">Compact pocket-sized mobile terminal paired via Bluetooth.</p>
                    </label>
                </div>
            </div>

            <!-- STEP 2: Passport Photograph Upload -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#00266B] text-white flex items-center justify-center text-xs font-bold shrink-0">2</span>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 font-display">Passport Photograph</h2>
                        <p class="text-[11px] text-slate-500">Upload picture passport size (file limited to 1 MB maximum)</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-5">
                    <!-- Photo Preview Box -->
                    <div class="w-32 h-36 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0 shadow-xs relative">
                        <template x-if="photoPreview">
                            <img :src="photoPreview" alt="Passport Preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!photoPreview">
                            <div class="text-center p-3 text-slate-400">
                                <i data-lucide="user" class="w-10 h-10 mx-auto mb-1 opacity-50"></i>
                                <span class="text-[10px] font-semibold block leading-tight">Passport Photo Preview</span>
                            </div>
                        </template>
                    </div>

                    <!-- File Input & Instructions -->
                    <div class="flex-1 space-y-2 w-full">
                        <label class="block text-xs font-bold text-slate-700">
                            Select Passport Picture <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" name="passport_photo" accept="image/jpeg,image/png,image/jpg,image/webp"
                               @change="handlePhoto"
                               required
                               class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#00266B] file:text-white hover:file:bg-[#001F5C] file:cursor-pointer cursor-pointer border border-slate-200 rounded-xl p-1.5 focus:outline-none">
                        
                        <p class="text-[11px] text-slate-500">
                            Accepted formats: <strong>JPG, JPEG, PNG, WEBP</strong>. Maximum file size: <strong>1 MB</strong>.
                        </p>

                        <div x-show="photoError" x-cloak class="text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                            <span x-text="photoError"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Personal Information -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm"
                 x-data="{
                     state: '{{ old('state', $user->state ?? '') }}',
                     lga: '{{ old('lga', $user->lga ?? '') }}',
                     lgas: [],
                     init() {
                         if (this.state && stateLgaData[this.state]) {
                             this.lgas = stateLgaData[this.state];
                         }
                     },
                     onStateChange() {
                         this.lgas = stateLgaData[this.state] || [];
                         this.lga = '';
                     }
                 }">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#00266B] text-white flex items-center justify-center text-xs font-bold shrink-0">3</span>
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 font-display">Personal Information</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Full Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               value="{{ old('name', $user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) }}"
                               placeholder="e.g. Adebayo Ibrahim Chukwuma"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Phone No -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel" name="phone" required
                               value="{{ old('phone', $user->phone ?? '') }}"
                               placeholder="e.g. 08012345678"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Valid Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Valid Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" required
                               value="{{ old('email', $user->email ?? '') }}"
                               placeholder="e.g. merchant@example.com"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- BVN -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            BVN (Bank Verification Number) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="bvn" required maxlength="11" pattern="\d{11}"
                               value="{{ old('bvn', $user->bvn ?? '') }}"
                               placeholder="11-digit BVN"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        <p class="text-[10px] text-slate-400 mt-0.5">Used strictly for regulatory identity verification.</p>
                    </div>

                    <!-- NIN -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            NIN (National Identity Number) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nin" required maxlength="11" pattern="\d{11}"
                               value="{{ old('nin', $user->nin ?? '') }}"
                               placeholder="11-digit NIN"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                        <p class="text-[10px] text-slate-400 mt-0.5">Used strictly for statutory merchant registration.</p>
                    </div>

                    <!-- State -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            State <span class="text-rose-500">*</span>
                        </label>
                        <select name="state" x-model="state" @change="onStateChange" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B] bg-white">
                            <option value="">Select State</option>
                            @foreach ([
                                'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
                                'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo',
                                'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa',
                                'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba',
                                'Yobe', 'Zamfara'
                            ] as $st)
                                <option value="{{ $st }}" {{ old('state', $user->state ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Local Govt (LGA) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Local Govt (LGA) <span class="text-rose-500">*</span>
                        </label>
                        <select name="lga" x-model="lga" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B] bg-white">
                            <option value="">Select LGA</option>
                            <template x-for="item in lgas" :key="item">
                                <option :value="item" x-text="item" :selected="lga === item"></option>
                            </template>
                            @if (!empty(old('lga', $user->lga ?? '')))
                                <option value="{{ old('lga', $user->lga ?? '') }}" selected>{{ old('lga', $user->lga ?? '') }}</option>
                            @endif
                        </select>
                    </div>

                    <!-- Shop Address -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Shop Address <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="shop_address" rows="2" required
                                  placeholder="e.g. Shop 4B, Plaza 2, Main Market Road, Commercial Area"
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('shop_address', $user->address ?? '') }}</textarea>
                        <p class="text-[10px] text-slate-400 mt-0.5">Physical location where the POS terminal will be actively deployed.</p>
                    </div>

                    <!-- Home Address -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Home Address <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="home_address" rows="2" required
                                  placeholder="e.g. No 15 Crescent Estate, Off Ring Road"
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('home_address', $user->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Next of Kin Details -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#00266B] text-white flex items-center justify-center text-xs font-bold shrink-0">4</span>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 font-display">Next of Kin Details</h2>
                        <p class="text-[11px] text-slate-500">Provide verified contact details of your designated next of kin</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Next of Kin Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Next of Kin Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="next_of_kin_name" required
                               value="{{ old('next_of_kin_name') }}"
                               placeholder="e.g. Halima Ibrahim Adebayo"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Next of Kin Phone -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Next of Kin Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="tel" name="next_of_kin_phone" required
                               value="{{ old('next_of_kin_phone') }}"
                               placeholder="e.g. 08087654321"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Next of Kin Home Address -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Next of Kin Home Address <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="next_of_kin_address" rows="2" required
                                  placeholder="e.g. Block C, Flat 12, Sunnyvale Estate"
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('next_of_kin_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- STEP 5: Referral Details -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#00266B] text-white flex items-center justify-center text-xs font-bold shrink-0">5</span>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900 font-display">Referral Details</h2>
                        <p class="text-[11px] text-slate-500">Provide details of the agent or partner who referred you (Optional)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Referral Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Referral Full Name <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" name="referral_name"
                               value="{{ old('referral_name') }}"
                               placeholder="e.g. Emmanuel Chukwu"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Referral Phone -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Referral Phone Number <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="tel" name="referral_phone"
                               value="{{ old('referral_phone') }}"
                               placeholder="e.g. 08123456789"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>
                </div>
            </div>

            <!-- STEP 6: Business Details & Quantity (Optional/Config) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#00266B] text-white flex items-center justify-center text-xs font-bold shrink-0">6</span>
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 font-display">Business &amp; Order Configuration</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Business Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Registered Business / Store Name <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="text" name="business_name"
                               value="{{ old('business_name', $user->business_name ?? '') }}"
                               placeholder="e.g. Adebayo Stores &amp; Ventures"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Quantity Requested
                        </label>
                        <input type="number" name="quantity" min="1" max="50"
                               value="{{ old('quantity', 1) }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">
                    </div>

                    <!-- Special Notes -->
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Special Delivery Instructions / Notes <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <textarea name="notes" rows="2"
                                  placeholder="e.g. Prefer delivery during morning hours, include extra thermal paper rolls..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#00266B]/20 focus:border-[#00266B]">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Action -->
            <div class="bg-white rounded-2xl border-2 border-blue-200/80 p-5 sm:p-6 shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-600 flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <p class="font-medium">By submitting this form, you confirm that all provided details and identity credentials are valid and true.</p>
                </div>
                <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-9 py-4 rounded-xl bg-gradient-to-r from-[#0056D2] to-[#003D99] hover:from-[#0047B3] hover:to-[#002E7A] active:scale-[0.98] text-white text-sm sm:text-base font-extrabold shadow-lg shadow-blue-600/30 ring-2 ring-blue-500/20 transition-all transform hover:-translate-y-0.5 shrink-0 cursor-pointer">
                    <i data-lucide="send" class="w-5 h-5 text-amber-300"></i>
                    <span>Submit POS Application</span>
                </button>
            </div>
        </form>

    </div>

    <!-- Nigeria State/LGA Script Data -->
    <script>
        const stateLgaData = {
            "Abia": ["Aba North", "Aba South", "Arochukwu", "Bende", "Ikwuano", "Isiala Ngwa North", "Isiala Ngwa South", "Isuikwuato", "Obi Ngwa", "Ohafia", "Osisioma", "Ugwunagbo", "Ukwa East", "Ukwa West", "Umuahia North", "Umuahia South", "Umu Nneochi"],
            "Adamawa": ["Demsa", "Fufure", "Ganye", "Gayuk", "Gombi", "Girei", "Hong", "Jada", "Lamurde", "Madagali", "Maiha", "Mayo Belwa", "Michika", "Mubi North", "Mubi South", "Numan", "Shelleng", "Song", "Toungo", "Yola North", "Yola South"],
            "Akwa Ibom": ["Abak", "Eastern Obolo", "Eket", "Esit Eket", "Essien Udim", "Etim Ekpo", "Etinan", "Ibeno", "Ibesikpo Asutan", "Ibiono-Ibom", "Ika", "Ikono", "Ikot Abasi", "Ikot Ekpene", "Ini", "Itu", "Mbo", "Mkpat-Enin", "Nsit-Atai", "Nsit-Ibom", "Nsit-Ubium", "Obot Akara", "Okobo", "Onna", "Oron", "Oruk Anam", "Udung-Uko", "Ukanafun", "Uruan", "Urue-Offong/Oruko", "Uyo"],
            "Anambra": ["Aguata", "Anambra East", "Anambra West", "Anaocha", "Awka North", "Awka South", "Ayamelum", "Dunukofia", "Ekwusigo", "Idemili North", "Idemili South", "Ihiala", "Njikoka", "Nnewi North", "Nnewi South", "Ogbaru", "Onitsha North", "Onitsha South", "Orumba North", "Orumba South", "Oyi"],
            "Bauchi": ["Alkaleri", "Bauchi", "Bogoro", "Damban", "Darazo", "Dass", "Gamawa", "Ganjuwa", "Giade", "Itas/Gadau", "Jama'are", "Katagum", "Kirfi", "Misau", "Ningi", "Shira", "Tafawa Balewa", "Toro", "Warji", "Zaki"],
            "Bayelsa": ["Brass", "Ekeremor", "Kolokuma/Opokuma", "Nembe", "Ogbia", "Sagbama", "Southern Ijaw", "Yenagoa"],
            "Benue": ["Agatu", "Apa", "Ado", "Buruku", "Gboko", "Guma", "Gwer East", "Gwer West", "Katsina-Ala", "Konshisha", "Kwande", "Logo", "Makurdi", "Obi", "Ogbadibo", "Ohimini", "Oju", "Okpokwu", "Oturkpo", "Tarka", "Ukum", "Ushongo", "Vandeikya"],
            "Borno": ["Abadam", "Askira/Uba", "Bama", "Bayo", "Biu", "Chibok", "Damboa", "Dikwa", "Gubio", "Guzamala", "Gwoza", "Hawul", "Jere", "Kaga", "Kala/Balge", "Konduga", "Kukawa", "Kwaya Kusar", "Mafa", "Magumeri", "Maiduguri", "Marte", "Mobbar", "Monguno", "Ngala", "Nganzai", "Shani"],
            "Cross River": ["Abi", "Akamkpa", "Akpabuyo", "Bakassi", "Bekwarra", "Biase", "Boki", "Calabar Municipal", "Calabar South", "Etung", "Ikom", "Obanliku", "Obubra", "Obudu", "Odukpani", "Ogoja", "Yakuur", "Yala"],
            "Delta": ["Aniocha North", "Aniocha South", "Bomadi", "Burutu", "Ethiope East", "Ethiope West", "Ika North East", "Ika South", "Isoko North", "Isoko South", "Ndokwa East", "Ndokwa West", "Okpe", "Oshimili North", "Oshimili South", "Patani", "Sapele", "Udu", "Ughelli North", "Ughelli South", "Ukwuani", "Uvwie", "Warri North", "Warri South", "Warri South West"],
            "Ebonyi": ["Abakaliki", "Afikpo North", "Afikpo South", "Ebonyi", "Ezza North", "Ezza South", "Ikwo", "Ishielu", "Ivo", "Izzi", "Ohaozara", "Ohaukwu", "Onicha"],
            "Edo": ["Akoko-Edo", "Egor", "Esan Central", "Esan North-East", "Esan Southeast", "Esan West", "Etsako Central", "Etsako East", "Etsako West", "Igueben", "Ikpoba Okha", "Orhionmwon", "Oredo", "Ovia Northeast", "Ovia Southwest", "Owan East", "Owan West", "Uhunmwonde"],
            "Ekiti": ["Ado Ekiti", "Efon", "Ekiti East", "Ekiti Southwest", "Ekiti West", "Emure", "Gbonyin", "Ido Osi", "Ijero", "Ikere", "Ikole", "Ilejemeje", "Irepodun/Ifelodun", "Ise/Orun", "Moba", "Oye"],
            "Enugu": ["Aninri", "Awgu", "Enugu East", "Enugu North", "Enugu South", "Ezeagu", "Igbo Etiti", "Igbo Eze North", "Igbo Eze South", "Isi Uzo", "Nkanu East", "Nkanu West", "Nsukka", "Oji River", "Udenu", "Udi", "Uzo-Uwani"],
            "FCT": ["Abaji", "Bwari", "Gwagwalada", "Kuje", "Kwali", "Municipal Area Council"],
            "Gombe": ["Akko", "Balanga", "Billiri", "Dukku", "Funakaye", "Gombe", "Kaltungo", "Kwami", "Nafada", "Shongom", "Yamaltu/Deba"],
            "Imo": ["Ahiazu Mbaise", "Ehime Mbano", "Ezinihitte", "Ideato North", "Ideato South", "Ihitte/Uboma", "Ikeduru", "Isiala Mbano", "Isu", "Mbaitoli", "Ngor Okpala", "Njaba", "Nkwerre", "Nwangele", "Obowo", "Oguta", "Ohaji/Egbema", "Okigwe", "Orlu", "Orsu", "Oru East", "Oru West", "Owerri Municipal", "Owerri North", "Owerri West", "Unuimo"],
            "Jigawa": ["Auyo", "Babura", "Biriniwa", "Birnin Kudu", "Buji", "Dutse", "Gagarawa", "Garki", "Gumel", "Guri", "Gwaram", "Gwiwa", "Hadejia", "Jahun", "Kafur", "Kaugama", "Kazaure", "Kiri Kasama", "Kiyawa", "Maigatari", "Malam Madori", "Miga", "Ringim", "Roni", "Sule Tankarkar", "Taura", "Yankwashi"],
            "Kaduna": ["Birnin Gwari", "Chikun", "Giwa", "Kajuru", "Igabi", "Ikara", "Jaba", "Jema'a", "Kachia", "Kaduna North", "Kaduna South", "Kagarko", "Kaura", "Kauru", "Kubau", "Kudan", "Lere", "Makarfi", "Sabon Gari", "Sanga", "Soba", "Zangon Kataf", "Zaria"],
            "Kano": ["Ajingi", "Albasu", "Bagwai", "Bebeji", "Bichi", "Bunkure", "Dala", "Dambatta", "Dawakin Kudu", "Dawakin Tofa", "Doguwa", "Fagge", "Gabasawa", "Garko", "Garun Mallam", "Gaya", "Gezawa", "Gwale", "Gwarzo", "Kabo", "Karaye", "Kibiya", "Kiru", "Kumbotso", "Kunchi", "Kura", "Madobi", "Makoda", "Minjibir", "Nasarawa", "Rano", "Rimin Gado", "Rogo", "Shanono", "Sumaila", "Takai", "Tarauni", "Tofa", "Tsanyawa", "Tudun Wada", "Ungogo", "Warawa", "Wudil"],
            "Katsina": ["Bakori", "Batagarawa", "Batsari", "Baure", "Bindawa", "Charanchi", "Dandume", "Danja", "Dan Musa", "Daura", "Dutsin Ma", "Faskari", "Funtua", "Ingawa", "Jibia", "Kafur", "Kaita", "Kankara", "Kankia", "Kani", "Katsina", "Kurfi", "Kusada", "Mai'Adua", "Malumfashi", "Mani", "Mashi", "Musawa", "Rimi", "Sabuwa", "Safana", "Sandamu", "Zango"],
            "Kebbi": ["Aleiro", "Arewa Dandi", "Argungu", "Augie", "Bagudo", "Birnin Kebbi", "Bunza", "Dandi", "Fakai", "Gwandu", "Jega", "Kalgo", "Koko/Besse", "Maiyama", "Ngaski", "Sakaba", "Shanga", "Suru", "Wasagu/Danko", "Yauri", "Zuru"],
            "Kogi": ["Adavi", "Ajaokuta", "Ankpa", "Bassa", "Dekina", "Ibaji", "Idah", "Igalamela Odolu", "Ijumu", "Kabba/Bunu", "Kogi", "Lokoja", "Mopa Muro", "Ofu", "Ogori/Magongo", "Okehi", "Okene", "Olamaboro", "Omala", "Yagba East", "Yagba West"],
            "Kwara": ["Asa", "Baruten", "Edu", "Ekiti", "Ilorin East", "Ilorin South", "Ilorin West", "Irepodun", "Isin", "Kaiama", "Moro", "Offa", "Oke Ero", "Oyun", "Pategi"],
            "Lagos": ["Agege", "Ajeromi-Ifelodun", "Alimosho", "Amuwo-Odofin", "Apapa", "Badagry", "Epe", "Eti Osa", "Ibeju-Lekki", "Ifako-Ijaiye", "Ikeja", "Ikorodu", "Kosofe", "Lagos Island", "Lagos Mainland", "Mushin", "Ojo", "Oshodi-Isolo", "Shomolu", "Surulere"],
            "Nasarawa": ["Akwanga", "Awe", "Doma", "Karu", "Keana", "Keffi", "Kokona", "Lafia", "Nasarawa", "Nasarawa Egon", "Obi", "Toto", "Wamba"],
            "Niger": ["Agaie", "Agwara", "Bida", "Borgu", "Bosso", "Chanchaga", "Edati", "Gbako", "Gurara", "Katcha", "Kontagora", "Lapai", "Lavun", "Magama", "Mariga", "Mashegu", "Mokwa", "Moya", "Paikoro", "Rafi", "Rijau", "Shiroro", "Suleja", "Tafa", "Wushishi"],
            "Ogun": ["Abeokuta North", "Abeokuta South", "Ado-Odo/Ota", "Egbado North", "Egbado South", "Ewekoro", "Ifo", "Ijebu East", "Ijebu North", "Ijebu North East", "Ijebu Ode", "Ikenne", "Imeko Afon", "Ipokia", "Obafemi Owode", "Odeda", "Odogbolu", "Ogun Waterside", "Remo North", "Shagamu"],
            "Ondo": ["Akoko North-East", "Akoko North-West", "Akoko South-East", "Akoko South-West", "Ese Odo", "Idanre", "Ifedore", "Ilaje", "Ile Oluji/Okeigbo", "Irele", "Odigbo", "Okitipupa", "Ondo East", "Ondo West", "Ose", "Owo"],
            "Osun": ["Atakunmosa East", "Atakunmosa West", "Aiyedaade", "Aiyedire", "Boluwaduro", "Boripe", "Ede North", "Ede South", "Ife Central", "Ife East", "Ife North", "Ife South", "Egbedore", "Ejigbo", "Ila", "Ilesa East", "Ilesa West", "Irepodun", "Irewole", "Isokan", "Iwo", "Obokun", "Odo Otin", "Ola Oluwa", "Olorunda", "Oriade", "Orolu", "Osogbo"],
            "Oyo": ["Afijio", "Akinyele", "Atiba", "Atisbo", "Egbeda", "Ibadan North", "Ibadan North-East", "Ibadan North-West", "Ibadan South-East", "Ibadan South-West", "Ibarapa Central", "Ibarapa East", "Ibarapa North", "Ido", "Irepo", "Iseyin", "Itesiwaju", "Iwajowa", "Kajola", "Lagelu", "Ogbomosho North", "Ogbomosho South", "Ogo Oluwa", "Olorunsogo", "Oluyole", "Ona Ara", "Orelope", "Ori Ire", "Oyo East", "Oyo West", "Saki East", "Saki West", "Surulere"],
            "Plateau": ["Bokkos", "Barkin Ladi", "Bassa", "Jos East", "Jos North", "Jos South", "Kanam", "Kanke", "Langtang North", "Langtang South", "Mangu", "Mikang", "Pankshin", "Qua'an Pan", "Riyom", "Shendam", "Wase"],
            "Rivers": ["Abua/Odual", "Ahoada East", "Ahoada West", "Akuku Toru", "Andoni", "Asari-Toru", "Bonny", "Degema", "Eleme", "Emuoha", "Etche", "Gokana", "Ikwerre", "Khana", "Obio/Akpor", "Ogba/Egbema/Ndoni", "Ogu/Bolo", "Okrika", "Omuma", "Opobo/Nkoro", "Oyigbo", "Port Harcourt", "Tai"],
            "Sokoto": ["Binji", "Bodinga", "Dange Shuni", "Gada", "Goronyo", "Gudu", "Gwadabawa", "Illela", "Isa", "Kebbe", "Kware", "Rabah", "Sabon Birni", "Shagari", "Silame", "Sokoto North", "Sokoto South", "Tambuwal", "Tangaza", "Tureta", "Wamako", "Wurno", "Yabo"],
            "Taraba": ["Ardo Kola", "Bali", "Donga", "Gashaka", "Gassol", "Ibi", "Jalingo", "Karim Lamido", "Kumi", "Lau", "Sardauna", "Takum", "Ussa", "Wukari", "Yorro", "Zing"],
            "Yobe": ["Bade", "Bursari", "Damaturu", "Fika", "Fune", "Geidam", "Gujba", "Gulani", "Jakusko", "Karasuwa", "Machina", "Nangere", "Nguru", "Potiskum", "Tarmuwa", "Yunusari", "Yusufari"],
            "Zamfara": ["Anka", "Bakura", "Birnin Magaji/Kiyaw", "Bukkuyum", "Bungudu", "Gummi", "Gusau", "Kaura Namoda", "Maradun", "Maru", "Shinkafi", "Talata Mafara", "Zurmi"]
        };
    </script>
</x-app-layout>
