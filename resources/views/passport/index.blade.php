<x-app-layout>
    <div class="container-fluid py-4 px-0 space-y-6 max-w-6xl mx-auto">

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

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <p class="text-sm font-medium">{{ session('error') }}</p>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        @endif

        <!-- Hero Section -->
        <div class="rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-lg"
             style="background: linear-gradient(135deg, #064e3b 0%, #065f46 45%, #047857 100%);">
            <div class="pointer-events-none absolute -top-16 -right-16 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl"></div>
            
            <div class="relative z-10 max-w-2xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-900/60 border border-emerald-400/40 text-emerald-200">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    Immigration &amp; Passport Services
                </div>

                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight font-display leading-tight text-white">
                    International Passport Registration (New &amp; Renewal)
                </h1>

                <p class="text-sm sm:text-base text-emerald-100/90 leading-relaxed">
                    Submit applications for fresh Nigerian standard e-passports or seamless renewals. Fill in applicant demographics, attach required digital e-copies, track processing milestones, and generate downloadable A4 official dossiers.
                </p>

                <div class="pt-2 flex flex-wrap items-center gap-3">
                    <a href="{{ route('passport.create', ['type' => 'new']) }}"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white text-emerald-900 font-bold text-sm shadow hover:bg-emerald-50 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-700"></i>
                        <span>Apply New Passport</span>
                    </a>
                    <a href="{{ route('passport.create', ['type' => 'renewal']) }}"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-sm border border-emerald-400/50 shadow transition">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-emerald-200"></i>
                        <span>Apply for Renewal</span>
                    </a>
                    <a href="{{ route('passport.tracking') }}"
                       class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-950/40 hover:bg-emerald-950/60 text-emerald-100 font-semibold text-sm border border-emerald-400/30 transition">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Track Application</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Official Compliance Disclaimer -->
        <div class="rounded-2xl bg-amber-500/10 border-2 border-amber-500/40 p-4 sm:p-5 flex items-start gap-3.5 sm:gap-4 shadow-xs">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center shrink-0 font-bold shadow-xs">
                <i data-lucide="shield-alert" class="w-5 h-5"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500 text-slate-950">
                        Notice
                    </span>
                    <span class="text-xs font-bold text-slate-700">Authentic Documentation Policy</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-900 mt-1 font-medium leading-relaxed mb-0">
                    Ensure all submitted applicant data precisely matches your National Identification Number (NIN) record. Uploaded documents must be clear, high-resolution digital e-copies (JPEG/PNG/PDF). SimPhix Nig Ltd staff will not solicit private payments outside official channels.
                </p>
            </div>
        </div>

        <!-- Requirements Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Information Format Card -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Required Personal Details</h3>
                        <p class="text-xs text-slate-500">Demographic biodata for immigration profiling</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-700">
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>NIN:</strong> 11-digit national identity</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Surname &amp; First Name:</strong> As on NIN</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Middle Name:</strong> Optional/if applicable</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Date of Birth:</strong> Day / Month / Year</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>State of Origin &amp; LGA:</strong> Official origin</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Hometown &amp; Place of Birth:</strong> Location</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Mobile Number &amp; Email:</strong> Active contacts</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Sex &amp; Occupation:</strong> Employment status</span>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center gap-2 col-span-full">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span><strong>Residential Address:</strong> Current full residential address</span>
                    </div>
                    <div class="p-2 rounded-lg bg-emerald-50/60 border border-emerald-100 flex items-center gap-2 col-span-full">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        <span><strong>Next of Kin:</strong> Full Name, Relationship, Phone Number, and Address</span>
                    </div>
                </div>
            </div>

            <!-- Additional Documents Checklist Card -->
            <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold">
                        <i data-lucide="paperclip" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Additional Documents (E-Copy Only)</h3>
                        <p class="text-xs text-slate-500">Required scanned files (Max 5MB each)</p>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs text-slate-700">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <i data-lucide="camera" class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-bold text-slate-900 block">1. Passport Photograph</span>
                            <span class="text-slate-500 text-[11px]">Clear white background portrait, neutral expression, no eyeglasses or headwear (except religious headcover).</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <i data-lucide="award" class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-bold text-slate-900 block">2. State of Origin Certificate</span>
                            <span class="text-slate-500 text-[11px]">Official certificate of identification issued by your Local Government Council or State Liaison office.</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <i data-lucide="file-badge-2" class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-bold text-slate-900 block">3. Birth Certificate / Declaration of Age</span>
                            <span class="text-slate-500 text-[11px]">National Population Commission (NPC) birth certificate or statutory age declaration sworn in a competent court.</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <i data-lucide="id-card" class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-bold text-slate-900 block">4. National Identification Number (NIN) Slip</span>
                            <span class="text-slate-500 text-[11px]">Digital standard NIN slip or e-ID card printout displaying verified 11-digit number.</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <i data-lucide="pen-tool" class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-bold text-slate-900 block">5. Signature on White Paper</span>
                            <span class="text-slate-500 text-[11px]">Clean handwritten applicant signature signed in blue or black ink on plain white paper.</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200/70 flex items-start gap-3">
                        <i data-lucide="book" class="w-4 h-4 text-amber-700 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-bold text-amber-900 block">6. Previous Passport Data Page (For Renewal Only)</span>
                            <span class="text-amber-800 text-[11px]">Scanned biodata page of your expired or expiring international passport.</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Recent Applications Table -->
        <div class="dash-card p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 font-display">My Passport Applications</h3>
                    <p class="text-xs text-slate-500">Track and download dossiers of your submitted requests</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('passport.create', ['type' => 'new']) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>New Request</span>
                    </a>
                    <a href="{{ route('passport.tracking') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        <span>Track By Ref</span>
                    </a>
                </div>
            </div>

            @if ($userRequests->isEmpty())
                <div class="text-center py-10 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="book-open" class="w-6 h-6"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800 mb-1">No Passport Requests Yet</h4>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                        You have not submitted any international passport requests. Click below to begin your application.
                    </p>
                    <a href="{{ route('passport.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow hover:bg-emerald-700 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Start Passport Application</span>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Reference</th>
                                <th class="py-3 px-4">Applicant</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4">NIN</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Submitted</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($userRequests as $req)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        <a href="{{ route('passport.show', $req) }}" class="text-emerald-700 hover:underline">
                                            {{ $req->reference }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800">{{ $req->full_name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $req->mobile_number }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $req->application_type === 'new' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                            {{ $req->application_type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600">{{ $req->nin }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider {{ $req->status_badge_class }}">
                                            {{ $req->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $req->created_at->format('M d, Y') }}</td>
                                    <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                        <a href="{{ route('passport.show', $req) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>View</span>
                                        </a>
                                        <a href="{{ route('passport.pdf', $req) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-[11px] border border-emerald-200 transition"
                                           title="Download A4 PDF Slip">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>A4 PDF</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
