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

        <!-- Navigation Bar -->
        <div class="flex items-center justify-between">
            <a href="{{ route('passport.index') }}"
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-emerald-700 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Back to Passport Portal</span>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('passport.tracking') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Track Another</span>
                </a>
                <a href="{{ route('passport.pdf', $passportRequest) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Download A4 PDF Slip</span>
                </a>
            </div>
        </div>

        <!-- Header Dossier Banner -->
        <div class="rounded-3xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden"
             style="background: linear-gradient(135deg, #022c22 0%, #064e3b 50%, #047857 100%) !important; color: #ffffff !important;">
            <div class="pointer-events-none absolute -top-12 -right-12 w-48 h-48 rounded-full bg-emerald-400/20 blur-2xl"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                              style="background-color: rgba(6, 78, 59, 0.9); color: #a7f3d0; border: 1px solid rgba(52, 211, 153, 0.45);">
                            {{ $passportRequest->application_type === 'new' ? 'New Passport Application' : 'Passport Renewal' }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $passportRequest->status_badge_class }}">
                            {{ $passportRequest->status_label }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black font-mono tracking-tight mt-2"
                        style="color: #ffffff !important; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);">
                        {{ $passportRequest->reference }}
                    </h1>

                    <p class="text-xs sm:text-sm mb-0"
                       style="color: #ecfdf5 !important;">
                        Applicant: <strong class="text-white" style="color: #ffffff !important;">{{ $passportRequest->full_name }}</strong> &bull; Submitted on {{ $passportRequest->created_at->format('M d, Y - h:i A') }}
                    </p>
                </div>

                <div class="flex sm:flex-col items-end gap-2 shrink-0">
                    <a href="{{ route('passport.pdf', $passportRequest) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black shadow-md transition">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span>Export A4 Dossier</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Capturing Schedule Notice (if scheduled) -->
        @if ($passportRequest->capturing_date || $passportRequest->capturing_center)
            <div class="p-5 rounded-2xl bg-emerald-50 border-2 border-emerald-300 text-emerald-900 shadow-xs flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 font-bold">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </div>
                <div class="flex-1 space-y-1">
                    <h3 class="text-sm font-bold text-emerald-950">Biometric Capturing Appointment Scheduled</h3>
                    <p class="text-xs text-emerald-800 mb-0">
                        @if ($passportRequest->capturing_date)
                            <strong>Scheduled Date:</strong> {{ $passportRequest->capturing_date->format('l, F j, Y') }}<br>
                        @endif
                        @if ($passportRequest->capturing_center)
                            <strong>Immigration Office / Center:</strong> {{ $passportRequest->capturing_center }}
                        @endif
                    </p>
                    <span class="text-[11px] text-emerald-700 block pt-1">
                        Please arrive at the immigration center with your printed A4 PDF Slip and original credentials.
                    </span>
                </div>
            </div>
        @endif

        @if ($passportRequest->customer_notes)
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-start gap-3">
                <i data-lucide="info" class="w-4 h-4 text-blue-600 mt-0.5 shrink-0"></i>
                <div>
                    <strong class="block font-bold mb-0.5">Desk Officer Note:</strong>
                    {{ $passportRequest->customer_notes }}
                </div>
            </div>
        @endif

        <!-- Applicant Demographics Card -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <i data-lucide="user-check" class="w-5 h-5 text-emerald-600"></i>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                    Applicant Information
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs text-slate-700">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">National Identification Number (NIN)</span>
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
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Next of Kin Name</span>
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

        <!-- Additional Documents (E-Copy) Gallery -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="paperclip" class="w-5 h-5 text-emerald-600"></i>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display mb-0">
                        Additional Documents (E-Copies)
                    </h2>
                </div>
                <span class="text-xs text-slate-400 font-medium">Digital scanned credentials</span>
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
                                    {{ $doc['path'] ? 'Attached & Verified' : 'Not attached' }}
                                </span>
                            </div>
                        </div>

                        @if ($doc['path'])
                            <div class="pt-2 border-t border-slate-200/60 flex items-center gap-2">
                                <a href="{{ route('passport.document', ['passportRequest' => $passportRequest, 'type' => $key]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shadow-2xs transition">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>Download E-Copy</span>
                                </a>
                            </div>
                        @else
                            <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-400 italic">
                                Document not uploaded
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-app-layout>
