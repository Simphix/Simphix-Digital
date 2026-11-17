<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-5xl mx-auto space-y-6">

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
            <a href="{{ route('admin.passport-requests.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-700 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to All Passport Requests</span>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.passport-requests.pdf', $passportRequest) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Download Official A4 Dossier</span>
                </a>
            </div>
        </div>

        <!-- Header Card -->
        <div class="rounded-3xl p-6 sm:p-8 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 text-white shadow-md relative overflow-hidden">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/10 text-emerald-300 border border-emerald-400/30">
                            {{ $passportRequest->application_type === 'new' ? 'New Passport Application' : 'Passport Renewal' }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $passportRequest->status_badge_class }}">
                            {{ $passportRequest->status_label }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black font-mono tracking-tight text-white mt-2">
                        {{ $passportRequest->reference }}
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-300 mb-0">
                        Submitted by: <strong class="text-white">{{ $passportRequest->user ? $passportRequest->user->first_name . ' ' . $passportRequest->user->last_name . ' (' . $passportRequest->user->email . ')' : 'Guest' }}</strong>
                        &bull; {{ $passportRequest->created_at->format('M d, Y - h:i A') }}
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.passport-requests.pdf', $passportRequest) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black shadow-md transition">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>A4 PDF Dossier</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Management Form -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <i data-lucide="sliders" class="w-5 h-5 text-emerald-600"></i>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                    Administrative Action &amp; Status Pipeline
                </h2>
            </div>

            <form action="{{ route('admin.passport-requests.update-status', $passportRequest) }}"
                  method="POST"
                  class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Processing Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" required class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach (\App\Models\PassportRequest::STATUSES as $val => $lbl)
                                <option value="{{ $val }}" {{ old('status', $passportRequest->status) === $val ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Capturing Date -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Biometric Capturing Date (Optional)
                        </label>
                        <input type="date"
                               name="capturing_date"
                               value="{{ old('capturing_date', $passportRequest->capturing_date ? $passportRequest->capturing_date->format('Y-m-d') : '') }}"
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- Capturing Center -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Designated NIS Center (Optional)
                        </label>
                        <input type="text"
                               name="capturing_center"
                               value="{{ old('capturing_center', $passportRequest->capturing_center) }}"
                               placeholder="e.g. NIS Command Office, Alausa, Ikeja"
                               class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <!-- Internal Admin Notes -->
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Internal Staff Processing Notes (Not visible to customer)
                        </label>
                        <textarea name="admin_notes"
                                  rows="2"
                                  placeholder="Add internal verification notes, payment verification info, officer details..."
                                  class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('admin_notes', $passportRequest->admin_notes) }}</textarea>
                    </div>

                    <!-- Customer Notes -->
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Customer-Facing Status Notes / Instructions (Visible to applicant on tracking page)
                        </label>
                        <textarea name="customer_notes"
                                  rows="2"
                                  placeholder="e.g. Biometrics capturing scheduled for Wednesday. Please come with your original birth certificate and printed A4 slip."
                                  class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">{{ old('customer_notes', $passportRequest->customer_notes) }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Update Request Status</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Applicant Demographics Card -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <i data-lucide="user" class="w-5 h-5 text-emerald-600"></i>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                    Applicant Information
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs text-slate-700">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">NIN</span>
                    <span class="text-sm font-mono font-bold text-slate-900">{{ $passportRequest->nin }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Surname</span>
                    <span class="text-sm font-bold text-slate-900 uppercase">{{ $passportRequest->surname }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">First &amp; Middle Name</span>
                    <span class="text-sm font-bold text-slate-900 uppercase">
                        {{ $passportRequest->first_name }} {{ $passportRequest->middle_name ?? '' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Date of Birth / Sex</span>
                    <span class="font-bold text-slate-800">
                        {{ $passportRequest->date_of_birth ? $passportRequest->date_of_birth->format('d/m/Y') : 'N/A' }}
                        <span class="text-slate-400 font-normal">({{ $passportRequest->sex }})</span>
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">State of Origin / LGA</span>
                    <span class="font-bold text-slate-800">{{ $passportRequest->state_of_origin }} &bull; {{ $passportRequest->lga }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Hometown / Place of Birth</span>
                    <span class="font-bold text-slate-800">{{ $passportRequest->hometown }} &bull; {{ $passportRequest->place_of_birth }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Mobile Number</span>
                    <span class="font-bold text-slate-800">{{ $passportRequest->mobile_number }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Email Address</span>
                    <span class="font-bold text-slate-800">{{ $passportRequest->email }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Occupation</span>
                    <span class="font-bold text-slate-800">{{ $passportRequest->occupation }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 col-span-full">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Residential Address</span>
                    <span class="font-medium text-slate-800">{{ $passportRequest->address }}</span>
                </div>
            </div>
        </div>

        <!-- Next of Kin Information Card -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                    Next of Kin Details
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs text-slate-700">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Name</span>
                    <span class="font-bold text-slate-900">{{ $passportRequest->next_of_kin_name }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Relationship</span>
                    <span class="font-bold text-slate-900">{{ $passportRequest->next_of_kin_relationship }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Phone Number</span>
                    <span class="font-bold text-slate-900">{{ $passportRequest->next_of_kin_phone }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 col-span-full">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Address</span>
                    <span class="font-medium text-slate-800">{{ $passportRequest->next_of_kin_address }}</span>
                </div>
            </div>
        </div>

        <!-- Uploaded Documents Gallery (Staff Downloads) -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="paperclip" class="w-5 h-5 text-emerald-600"></i>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                        Uploaded Digital E-Copies
                    </h2>
                </div>
                <span class="text-xs text-slate-400 font-medium">Click to inspect and download applicant documents</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @php
                    $docs = [
                        'passport_photo'       => ['label' => 'Passport Photograph', 'path' => $passportRequest->passport_photo, 'icon' => 'camera'],
                        'state_of_origin_cert' => ['label' => 'State of Origin Certificate', 'path' => $passportRequest->state_of_origin_cert, 'icon' => 'award'],
                        'birth_certificate'    => ['label' => 'Birth Certificate / Age Dec.', 'path' => $passportRequest->birth_certificate, 'icon' => 'file-badge-2'],
                        'nin_document'         => ['label' => 'NIN Slip / e-ID', 'path' => $passportRequest->nin_document, 'icon' => 'id-card'],
                        'signature'            => ['label' => 'Signature on White Paper', 'path' => $passportRequest->signature, 'icon' => 'pen-tool'],
                    ];
                    if ($passportRequest->application_type === 'renewal') {
                        $docs['previous_passport'] = ['label' => 'Previous Passport Data Page', 'path' => $passportRequest->previous_passport, 'icon' => 'book'];
                    }
                @endphp

                @foreach ($docs as $key => $doc)
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between space-y-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $doc['icon'] }}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 truncate">{{ $doc['label'] }}</h4>
                                <span class="text-[10px] text-slate-400 block">
                                    {{ $doc['path'] ? 'File Attached' : 'Missing' }}
                                </span>
                            </div>
                        </div>

                        @if ($doc['path'])
                            <div class="pt-2 border-t border-slate-200/60 flex items-center gap-2">
                                <a href="{{ route('admin.passport-requests.documents.download', ['passportRequest' => $passportRequest, 'type' => $key]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>Download E-Copy</span>
                                </a>
                            </div>
                        @else
                            <div class="pt-2 border-t border-slate-200/60 text-[11px] text-rose-500 font-semibold italic">
                                Document not uploaded
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>
