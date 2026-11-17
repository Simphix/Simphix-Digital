<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-4xl mx-auto space-y-6">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <p class="text-sm font-semibold">{{ session('success') }}</p>
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
                <ul class="list-disc list-inside text-xs space-y-1 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Navigation Bar -->
        <div class="flex items-center justify-between">
            <a href="{{ route('passport.index') }}"
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-emerald-700 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Passport Portal</span>
            </a>

            <a href="{{ route('passport.tracking') }}"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:underline">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                <span>Track Existing Application</span>
            </a>
        </div>

        <!-- Header Card -->
        <div class="rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative overflow-hidden"
             style="background: linear-gradient(135deg, #022c22 0%, #064e3b 50%, #047857 100%) !important; color: #ffffff !important;">
            <div class="pointer-events-none absolute -top-12 -right-12 w-48 h-48 rounded-full bg-emerald-400/20 blur-2xl"></div>

            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-2"
                      style="background-color: rgba(6, 78, 59, 0.9); color: #a7f3d0; border: 1px solid rgba(52, 211, 153, 0.45);">
                    <i data-lucide="book-open" class="w-3.5 h-3.5" style="color: #6ee7b7;"></i>
                    Standard &amp; Official E-Passport Application
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold font-display mb-1"
                    style="color: #ffffff !important; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);">
                    Application for International Passport (New / Renewal)
                </h1>
                <p class="text-xs sm:text-sm font-medium mb-0"
                   style="color: #ecfdf5 !important;">
                    Complete all demographic fields and attach mandatory digital e-copies in the specified format.
                </p>
            </div>
        </div>

        <!-- Application Form -->
        <form action="{{ route('passport.store') }}"
              method="POST"
              enctype="multipart/form-data"
              autocomplete="off"
              x-data="{ applicationType: '{{ old('application_type', $type ?? 'new') }}' }"
              class="space-y-6">
            @csrf

            <!-- Section 0: Application Type Selection -->
            <div class="dash-card p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider">
                    Application Type <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 transition"
                           :class="applicationType === 'new' ? 'border-emerald-600 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-300 bg-slate-50/50 hover:border-slate-400 hover:bg-white'">
                        <input type="radio" name="application_type" value="new" x-model="applicationType" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="block text-sm font-extrabold text-slate-900">New Passport Application</span>
                            <span class="text-xs font-medium" :class="applicationType === 'new' ? 'text-emerald-900' : 'text-slate-700'">First-time passport issuance for Nigerian citizens</span>
                        </div>
                    </label>

                    <label class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 transition"
                           :class="applicationType === 'renewal' ? 'border-emerald-600 bg-emerald-50/80 ring-2 ring-emerald-500/20' : 'border-slate-300 bg-slate-50/50 hover:border-slate-400 hover:bg-white'">
                        <input type="radio" name="application_type" value="renewal" x-model="applicationType" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="block text-sm font-extrabold text-slate-900">Passport Renewal / Re-issue</span>
                            <span class="text-xs font-medium" :class="applicationType === 'renewal' ? 'text-emerald-900' : 'text-slate-700'">Renewal of expired/expiring passport or page exhaustion</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Section 1: Applicant Demographic Details -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-5">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-sm">
                        1
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider font-display mb-0">
                            Applicant Biodata &amp; Demographic Information
                        </h2>
                        <span class="text-xs font-semibold text-slate-600">Must match your National Identification Number (NIN) slip</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- NIN -->
                    <div class="sm:col-span-2 md:col-span-1">
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            NIN (11 Digits) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="nin"
                               value="{{ old('nin', auth()->user()->nin ?? '') }}"
                               maxlength="11"
                               pattern="\d{11}"
                               placeholder="e.g. 12345678901"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-mono font-bold tracking-wider bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                        <span class="text-[11px] font-semibold text-slate-600 block mt-1">National Identification Number (11 digits)</span>
                    </div>

                    <!-- SURNAME -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            SURNAME <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="surname"
                               value="{{ old('surname', auth()->user()->last_name ?? '') }}"
                               placeholder="e.g. ADEYEMI"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold uppercase bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- FIRST NAME -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            FIRST NAME <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="first_name"
                               value="{{ old('first_name', auth()->user()->first_name ?? '') }}"
                               placeholder="e.g. BABATUNDE"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold uppercase bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- MIDDLE NAME -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            MIDDLE NAME
                        </label>
                        <input type="text"
                               name="middle_name"
                               value="{{ old('middle_name') }}"
                               placeholder="e.g. CHUKWUEMEKA (optional)"
                               autocomplete="off"
                               class="w-full text-xs font-bold uppercase bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- DATE OF BIRTH -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            DATE OF BIRTH <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="date_of_birth"
                               value="{{ old('date_of_birth') }}"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- SEX -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            SEX <span class="text-rose-500">*</span>
                        </label>
                        <select name="sex" required autocomplete="off" class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                            <option value="" class="text-slate-500 font-normal">-- Select Sex --</option>
                            <option value="Male" {{ old('sex') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex') === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <!-- STATE OF ORIGIN -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            STATE OF ORIGIN <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="state_of_origin"
                               value="{{ old('state_of_origin') }}"
                               placeholder="e.g. Kano, Oyo, Enugu..."
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- L. G. A -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            L. G. A <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="lga"
                               value="{{ old('lga') }}"
                               placeholder="e.g. Ibadan North, Ikeja..."
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- HOMETOWN -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            HOMETOWN <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="hometown"
                               value="{{ old('hometown') }}"
                               placeholder="e.g. Owo, Nnewi, Daura..."
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- PLACE OF BIRTH -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            PLACE OF BIRTH <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="place_of_birth"
                               value="{{ old('place_of_birth') }}"
                               placeholder="City/Town of Birth"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- MOBILE NUMBER -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            MOBILE NUMBER <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="mobile_number"
                               value="{{ old('mobile_number', auth()->user()->phone ?? '') }}"
                               placeholder="e.g. 08012345678"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- E-MAIL -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            E-MAIL <span class="text-rose-500">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', auth()->user()->email ?? '') }}"
                               placeholder="e.g. applicant@example.com"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- OCCUPATION -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            OCCUPATION <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="occupation"
                               value="{{ old('occupation') }}"
                               placeholder="e.g. Civil Servant, Trader, Engineer..."
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- ADDRESS -->
                    <div class="sm:col-span-2 md:col-span-3">
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            RESIDENTIAL ADDRESS <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="address"
                                  rows="2"
                                  required
                                  autocomplete="off"
                                  placeholder="Full current residential street address, city, state"
                                  class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Next of Kin Information -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-5">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-sm">
                        2
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider font-display mb-0">
                            Next of Kin Information
                        </h2>
                        <span class="text-xs font-semibold text-slate-600">Emergency contact person and designated next of kin</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- NEXT OF KIN NAME -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            NEXT OF KIN NAME <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="next_of_kin_name"
                               value="{{ old('next_of_kin_name') }}"
                               placeholder="Full Name of Next of Kin"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- RELATIONSHIP -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            RELATIONSHIP <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="next_of_kin_relationship"
                               value="{{ old('next_of_kin_relationship') }}"
                               placeholder="e.g. Spouse, Brother, Mother..."
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- PHONE NUMBER -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            PHONE NUMBER <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="next_of_kin_phone"
                               value="{{ old('next_of_kin_phone') }}"
                               placeholder="e.g. 08098765432"
                               required
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <!-- ADDRESS -->
                    <div class="sm:col-span-2 md:col-span-3">
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            NEXT OF KIN ADDRESS <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="next_of_kin_address"
                                  rows="2"
                                  required
                                  autocomplete="off"
                                  placeholder="Full residential street address of next of kin"
                                  class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">{{ old('next_of_kin_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Additional Documents (E-COPY Only) -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-5">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-sm">
                        3
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider font-display mb-0">
                            Additional Documents (E-Copy Only)
                        </h2>
                        <span class="text-xs font-semibold text-slate-600">Accepted formats: JPG, JPEG, PNG, WEBP, PDF (Max 5MB each)</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- PASSPORT PHOTOGRAPH -->
                    <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 bg-white shadow-2xs space-y-2.5 transition">
                        <label class="block text-xs font-extrabold text-slate-900 tracking-wide">
                            1. PASSPORT PHOTOGRAPH <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-xs font-medium text-slate-700 leading-snug mb-1.5">
                            Recent white background color portrait with ears and eyes clearly visible.
                        </p>
                        <input type="file"
                               name="passport_photo"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs font-semibold text-slate-800 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                    </div>

                    <!-- STATE OF ORIGIN CERTIFICATE -->
                    <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 bg-white shadow-2xs space-y-2.5 transition">
                        <label class="block text-xs font-extrabold text-slate-900 tracking-wide">
                            2. STATE OF ORIGIN CERTIFICATE <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-xs font-medium text-slate-700 leading-snug mb-1.5">
                            Certificate of state/LGA identification issued by local government council.
                        </p>
                        <input type="file"
                               name="state_of_origin_cert"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs font-semibold text-slate-800 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                    </div>

                    <!-- BIRTH CERTIFICATE -->
                    <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 bg-white shadow-2xs space-y-2.5 transition">
                        <label class="block text-xs font-extrabold text-slate-900 tracking-wide">
                            3. BIRTH CERTIFICATE <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-xs font-medium text-slate-700 leading-snug mb-1.5">
                            NPC birth certificate or court-sworn statutory declaration of age.
                        </p>
                        <input type="file"
                               name="birth_certificate"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs font-semibold text-slate-800 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                    </div>

                    <!-- NIN -->
                    <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 bg-white shadow-2xs space-y-2.5 transition">
                        <label class="block text-xs font-extrabold text-slate-900 tracking-wide">
                            4. NIN SLIP / CARD <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-xs font-medium text-slate-700 leading-snug mb-1.5">
                            Scanned copy of digital National Identification Number (NIN) slip.
                        </p>
                        <input type="file"
                               name="nin_document"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs font-semibold text-slate-800 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                    </div>

                    <!-- SIGNATURE ON WHITE PAPER -->
                    <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-emerald-300 bg-white shadow-2xs space-y-2.5 transition">
                        <label class="block text-xs font-extrabold text-slate-900 tracking-wide">
                            5. SIGNATURE ON WHITE PAPER <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-xs font-medium text-slate-700 leading-snug mb-1.5">
                            Clean specimen signature signed in black or blue ink on plain white paper.
                        </p>
                        <input type="file"
                               name="signature"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs font-semibold text-slate-800 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                    </div>

                    <!-- PREVIOUS PASSPORT (Renewal Only) -->
                    <div x-show="applicationType === 'renewal'"
                         x-transition
                         class="p-4 rounded-xl border-2 border-amber-300 bg-amber-50/80 shadow-2xs space-y-2.5">
                        <label class="block text-xs font-extrabold text-amber-950 tracking-wide">
                            6. PREVIOUS PASSPORT DATA PAGE <span class="text-rose-600">*</span>
                        </label>
                        <p class="text-xs font-semibold text-amber-900 leading-snug mb-1.5">
                            Scanned page of your previous expired/expiring passport showing photo &amp; number.
                        </p>
                        <input type="file"
                               name="previous_passport"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               class="w-full text-xs font-semibold text-amber-950 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-600 file:text-white hover:file:bg-amber-700 cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Section 4: Processing Notes / Preferred Capturing Office -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-sm">
                        4
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider font-display mb-0">
                            Capturing Preferences &amp; Applicant Notes
                        </h2>
                        <span class="text-xs font-semibold text-slate-600">Optional notes or preferred Nigeria Immigration Service (NIS) command office</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            Preferred NIS Capturing State / Center (Optional)
                        </label>
                        <input type="text"
                               name="capturing_center"
                               value="{{ old('capturing_center') }}"
                               placeholder="e.g. NIS Headquarters Abuja, Alausa Lagos, Ibadan..."
                               autocomplete="off"
                               class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-extrabold text-slate-900 mb-1.5 uppercase tracking-wide">
                            Additional Instructions or Remarks (Optional)
                        </label>
                        <textarea name="notes"
                                  rows="2"
                                  autocomplete="off"
                                  placeholder="Mention any expedited travel timeline or special considerations..."
                                  class="w-full text-xs font-bold bg-white text-slate-900 rounded-xl border border-slate-300 placeholder:text-slate-400 placeholder:font-normal focus:border-emerald-600 focus:ring-emerald-600 shadow-2xs">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-700">
                    <p class="mb-0 font-semibold">By submitting, you certify that all applicant details and uploaded e-copies are authentic.</p>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <a href="{{ route('passport.index') }}"
                       class="w-1/2 sm:w-auto px-5 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs text-center hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit"
                            class="w-1/2 sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Submit Passport Application</span>
                    </button>
                </div>
            </div>

        </form>

    </div>
</x-app-layout>
