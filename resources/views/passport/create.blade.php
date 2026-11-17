<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-4xl mx-auto space-y-6">

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

        <!-- Navigation Bar -->
        <div class="flex items-center justify-between">
            <a href="{{ route('passport.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-700 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Passport Portal</span>
            </a>

            <a href="{{ route('passport.tracking') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                <span>Track Existing Application</span>
            </a>
        </div>

        <!-- Header Card -->
        <div class="rounded-2xl p-6 bg-gradient-to-r from-emerald-900 to-emerald-700 text-white shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-800/80 text-emerald-200 border border-emerald-500/40 mb-2">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                    Standard &amp; Official E-Passport Application
                </span>
                <h1 class="text-xl sm:text-2xl font-extrabold text-white font-display mb-1">
                    Application for International Passport (New / Renewal)
                </h1>
                <p class="text-xs sm:text-sm text-emerald-100/90 mb-0">
                    Complete all demographic fields and attach mandatory digital e-copies in the specified format.
                </p>
            </div>
        </div>

        <!-- Application Form -->
        <form action="{{ route('passport.store') }}"
              method="POST"
              enctype="multipart/form-data"
              x-data="{ applicationType: '{{ old('application_type', $type ?? 'new') }}' }"
              class="space-y-6">
            @csrf

            <!-- Section 0: Application Type Selection -->
            <div class="dash-card p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Application Type <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 transition"
                           :class="applicationType === 'new' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="application_type" value="new" x-model="applicationType" class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">New Passport Application</span>
                            <span class="text-xs text-slate-500">First-time passport issuance for Nigerian citizens</span>
                        </div>
                    </label>

                    <label class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 transition"
                           :class="applicationType === 'renewal' ? 'border-emerald-600 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="application_type" value="renewal" x-model="applicationType" class="text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Passport Renewal / Re-issue</span>
                            <span class="text-xs text-slate-500">Renewal of expired/expiring passport or page exhaustion</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Section 1: Applicant Demographic Details -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        1
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                            Applicant Biodata &amp; Demographic Information
                        </h2>
                        <span class="text-[11px] text-slate-400">Must match your National Identification Number (NIN) slip</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- NIN -->
                    <div class="sm:col-span-2 md:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            NIN (11 Digits) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="nin"
                               value="{{ old('nin', auth()->user()->nin ?? '') }}"
                               maxlength="11"
                               pattern="\d{11}"
                               placeholder="e.g. 12345678901"
                               required
                               class="w-full text-xs font-mono rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <span class="text-[10px] text-slate-400">National Identification Number</span>
                    </div>

                    <!-- SURNAME -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            SURNAME <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="surname"
                               value="{{ old('surname', auth()->user()->last_name ?? '') }}"
                               placeholder="e.g. ADEYEMI"
                               required
                               class="w-full text-xs uppercase rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- FIRST NAME -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            FIRST NAME <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="first_name"
                               value="{{ old('first_name', auth()->user()->first_name ?? '') }}"
                               placeholder="e.g. BABATUNDE"
                               required
                               class="w-full text-xs uppercase rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- MIDDLE NAME -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            MIDDLE NAME
                        </label>
                        <input type="text"
                               name="middle_name"
                               value="{{ old('middle_name') }}"
                               placeholder="e.g. CHUKWUEMEKA (optional)"
                               class="w-full text-xs uppercase rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- DATE OF BIRTH -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            DATE OF BIRTH <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="date_of_birth"
                               value="{{ old('date_of_birth') }}"
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- SEX -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            SEX <span class="text-rose-500">*</span>
                        </label>
                        <select name="sex" required class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">-- Select Sex --</option>
                            <option value="Male" {{ old('sex') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex') === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <!-- STATE OF ORIGIN -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            STATE OF ORIGIN <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="state_of_origin"
                               value="{{ old('state_of_origin') }}"
                               placeholder="e.g. Kano, Oyo, Enugu..."
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- L. G. A -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            L. G. A <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="lga"
                               value="{{ old('lga') }}"
                               placeholder="e.g. Ibadan North, Ikeja..."
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- HOMETOWN -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            HOMETOWN <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="hometown"
                               value="{{ old('hometown') }}"
                               placeholder="e.g. Owo, Nnewi, Daura..."
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- PLACE OF BIRTH -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            PLACE OF BIRTH <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="place_of_birth"
                               value="{{ old('place_of_birth') }}"
                               placeholder="City/Town of Birth"
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- MOBILE NUMBER -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            MOBILE NUMBER <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="mobile_number"
                               value="{{ old('mobile_number', auth()->user()->phone ?? '') }}"
                               placeholder="e.g. 08012345678"
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- E-MAIL -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            E-MAIL <span class="text-rose-500">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', auth()->user()->email ?? '') }}"
                               placeholder="e.g. applicant@example.com"
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- OCCUPATION -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            OCCUPATION <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="occupation"
                               value="{{ old('occupation') }}"
                               placeholder="e.g. Civil Servant, Trader, Engineer..."
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- ADDRESS -->
                    <div class="sm:col-span-2 md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            RESIDENTIAL ADDRESS <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="address"
                                  rows="2"
                                  required
                                  placeholder="Full current residential street address, city, state"
                                  class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Next of Kin Information -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        2
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                            Next of Kin Information
                        </h2>
                        <span class="text-[11px] text-slate-400">Emergency emergency contact and designated next of kin</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- NEXT OF KIN NAME -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            NEXT OF KIN NAME <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="next_of_kin_name"
                               value="{{ old('next_of_kin_name') }}"
                               placeholder="Full Name of Next of Kin"
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- RELATIONSHIP -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            RELATIONSHIP <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="next_of_kin_relationship"
                               value="{{ old('next_of_kin_relationship') }}"
                               placeholder="e.g. Spouse, Brother, Mother..."
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- PHONE NUMBER -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            PHONE NUMBER <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="next_of_kin_phone"
                               value="{{ old('next_of_kin_phone') }}"
                               placeholder="e.g. 08098765432"
                               required
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- ADDRESS -->
                    <div class="sm:col-span-2 md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            NEXT OF KIN ADDRESS <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="next_of_kin_address"
                                  rows="2"
                                  required
                                  placeholder="Full residential street address of next of kin"
                                  class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('next_of_kin_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Additional Documents (E-COPY Only) -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        3
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                            Additional Documents (E-Copy Only)
                        </h2>
                        <span class="text-[11px] text-slate-400">Accepted formats: JPG, JPEG, PNG, WEBP, PDF (Max 5MB each)</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- PASSPORT PHOTOGRAPH -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <label class="block text-xs font-bold text-slate-800">
                            1. PASSPORT PHOTOGRAPH <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 leading-snug mb-1">
                            Recent white background color portrait with ears and eyes clearly visible.
                        </p>
                        <input type="file"
                               name="passport_photo"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                    </div>

                    <!-- STATE OF ORIGIN CERTIFICATE -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <label class="block text-xs font-bold text-slate-800">
                            2. STATE OF ORIGIN CERTIFICATE <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 leading-snug mb-1">
                            Certificate of state/LGA identification issued by local government council.
                        </p>
                        <input type="file"
                               name="state_of_origin_cert"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                    </div>

                    <!-- BIRTH CERTIFICATE -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <label class="block text-xs font-bold text-slate-800">
                            3. BIRTH CERTIFICATE <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 leading-snug mb-1">
                            NPC birth certificate or court-sworn statutory declaration of age.
                        </p>
                        <input type="file"
                               name="birth_certificate"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                    </div>

                    <!-- NIN -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <label class="block text-xs font-bold text-slate-800">
                            4. NIN SLIP / CARD <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 leading-snug mb-1">
                            Scanned copy of digital National Identification Number (NIN) slip.
                        </p>
                        <input type="file"
                               name="nin_document"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                    </div>

                    <!-- SIGNATURE ON WHITE PAPER -->
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                        <label class="block text-xs font-bold text-slate-800">
                            5. SIGNATURE ON WHITE PAPER <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-slate-500 leading-snug mb-1">
                            Clean specimen signature signed in black or blue ink on plain white paper.
                        </p>
                        <input type="file"
                               name="signature"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               required
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                    </div>

                    <!-- PREVIOUS PASSPORT (Renewal Only) -->
                    <div x-show="applicationType === 'renewal'"
                         x-transition
                         class="p-4 rounded-xl border border-amber-300 bg-amber-50/60 space-y-2">
                        <label class="block text-xs font-bold text-amber-900">
                            6. PREVIOUS PASSPORT DATA PAGE <span class="text-rose-500">*</span>
                        </label>
                        <p class="text-[11px] text-amber-800 leading-snug mb-1">
                            Scanned page of your previous expired/expiring passport showing photo &amp; number.
                        </p>
                        <input type="file"
                               name="previous_passport"
                               accept=".jpg,.jpeg,.png,.webp,.pdf"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-600 file:text-white hover:file:bg-amber-700">
                    </div>
                </div>
            </div>

            <!-- Section 4: Processing Notes / Preferred Capturing Office -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        4
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                            Capturing Preferences &amp; Applicant Notes
                        </h2>
                        <span class="text-[11px] text-slate-400">Optional notes or preferred Nigeria Immigration Service (NIS) command office</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Preferred NIS Capturing State / Center (Optional)
                        </label>
                        <input type="text"
                               name="capturing_center"
                               value="{{ old('capturing_center') }}"
                               placeholder="e.g. NIS Headquarters Abuja, Alausa Lagos, Ibadan..."
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Additional Instructions or Remarks (Optional)
                        </label>
                        <textarea name="notes"
                                  rows="2"
                                  placeholder="Mention any expedited travel timeline or special considerations..."
                                  class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-500">
                    <p class="mb-0 font-medium">By submitting, you certify that all applicant details and uploaded e-copies are authentic.</p>
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
