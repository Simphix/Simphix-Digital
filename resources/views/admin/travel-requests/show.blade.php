<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-5xl mx-auto space-y-6" x-data="{ showEditPackageModal: false }">

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

        <!-- Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.travel-requests.index') }}"
                       class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 font-mono">Reference</span>
                            <span class="text-base font-bold font-mono text-slate-900">{{ $travelRequest->reference }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display mt-0.5">
                            {{ $travelRequest->package_name }}
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $travelRequest->status_badge_class }}">
                        <span class="w-2 h-2 rounded-full bg-current"></span>
                        <span>{{ $travelRequest->status_label }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Left 2 Cols: Details & Status Update Form -->
            <div class="md:col-span-2 space-y-6">

                <!-- Customer & Traveler Info -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900 font-display flex items-center gap-2">
                            <i data-lucide="user" class="w-4 h-4 text-primary"></i>
                            <span>Lead Traveler &amp; Contact Information</span>
                        </h2>
                        @if ($travelRequest->user)
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                                Registered User
                            </span>
                        @else
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                Direct Guest
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Full Name</span>
                            <span class="font-bold text-slate-900 text-sm mt-0.5 block">{{ $travelRequest->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Phone Number</span>
                            <a href="tel:{{ $travelRequest->phone }}" class="font-bold text-[#0056D2] hover:underline font-mono text-sm mt-0.5 block">
                                {{ $travelRequest->phone }}
                            </a>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Email Address</span>
                            <span class="text-slate-700 mt-0.5 block">
                                {{ $travelRequest->email ?: 'None provided' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Travel Party Size</span>
                            <span class="font-bold text-slate-900 text-sm mt-0.5 block">
                                {{ $travelRequest->number_of_travelers }} Traveler(s)
                            </span>
                        </div>
                    </div>

                    @if ($travelRequest->departure_date)
                        <div class="pt-2 border-t border-slate-100 text-xs">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Preferred Departure Period</span>
                            <span class="text-slate-800 font-semibold mt-0.5 block">{{ $travelRequest->departure_date }}</span>
                        </div>
                    @endif

                    @if ($travelRequest->notes)
                        <div class="pt-2 border-t border-slate-100 text-xs">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Customer Inquiry Notes</span>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-slate-700 leading-relaxed">
                                {{ $travelRequest->notes }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Package Specifications -->
                @if ($travelRequest->travelPackage)
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 font-display flex items-center gap-2">
                                    <i data-lucide="package" class="w-4 h-4 text-primary"></i>
                                    <span>Selected Package Configuration</span>
                                </h2>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $travelRequest->travelPackage->name }}</p>
                            </div>
                            <button type="button" @click="showEditPackageModal = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-[#0056D2] text-white text-xs font-bold transition shadow-xs">
                                <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                <span>Edit This Package</span>
                            </button>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Package Title</span>
                                <span class="font-bold text-slate-800">{{ $travelRequest->travelPackage->name }}</span>
                            </div>
                            @if ($travelRequest->travelPackage->tagline)
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-slate-500 shrink-0">Subtitle / Tagline</span>
                                    <span class="text-slate-700 text-right font-medium">{{ $travelRequest->travelPackage->tagline }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Category</span>
                                <span class="font-bold text-slate-800">{{ $travelRequest->package_type_label }}</span>
                            </div>
                            @if ($travelRequest->travelPackage->duration)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Duration</span>
                                    <span class="font-bold text-slate-800">{{ $travelRequest->travelPackage->duration }}</span>
                                </div>
                            @endif
                            @if ($travelRequest->travelPackage->price_range)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Estimated Package Value</span>
                                    <span class="font-bold text-[#0056D2]">{{ $travelRequest->travelPackage->price_range }}</span>
                                </div>
                            @endif

                            @if (!empty($travelRequest->travelPackage->highlights))
                                <div class="pt-2 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                            Package Inclusions ({{ count($travelRequest->travelPackage->highlights) }})
                                        </p>
                                    </div>
                                    <ul class="space-y-1 text-slate-600">
                                        @foreach ($travelRequest->travelPackage->highlights as $hl)
                                            <li class="flex items-start gap-1.5">
                                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5"></i>
                                                <span>{{ $hl }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-bold text-slate-900 font-display flex items-center gap-2">
                                <i data-lucide="compass" class="w-4 h-4 text-primary"></i>
                                <span>Custom Request Itinerary</span>
                            </h2>
                            <a href="{{ route('admin.travel-requests.packages.index') }}"
                               class="text-xs font-bold text-[#0056D2] hover:underline flex items-center gap-1">
                                <span>Browse Catalog Packages</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        <p class="text-xs text-slate-600">
                            The traveler requested a tailored itinerary: <strong class="text-slate-900">{{ $travelRequest->package_name }}</strong>.
                        </p>
                    </div>
                @endif

                <!-- Status Update Form -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900 font-display flex items-center gap-2">
                            <i data-lucide="edit" class="w-4 h-4 text-primary"></i>
                            <span>Update Application Status &amp; Notes</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Change the workflow status and provide customer updates.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.travel-requests.update-status', $travelRequest) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Status Dropdown -->
                        <div>
                            <label for="admin_status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Status Pipeline <span class="text-rose-500">*</span>
                            </label>
                            <select id="admin_status" name="status" required
                                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                                @foreach (\App\Models\TravelRequest::STATUSES as $stKey => $stLabel)
                                    <option value="{{ $stKey }}" {{ old('status', $travelRequest->status) === $stKey ? 'selected' : '' }}>
                                        {{ $stLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Customer Note (Visible to user) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="customer_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Customer Response / Update
                                </label>
                                <span class="text-[10px] text-primary font-bold">Visible to Customer on Tracking</span>
                            </div>
                            <textarea id="customer_notes" name="customer_notes" rows="3"
                                      placeholder="Provide visa instructions, confirmed appointment dates, flight itineraries, or contact officer details..."
                                      class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('customer_notes', $travelRequest->customer_notes) }}</textarea>
                        </div>

                        <!-- Internal Admin Notes (Staff only) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="admin_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Internal Staff Notes
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">Internal Only</span>
                            </div>
                            <textarea id="admin_notes" name="admin_notes" rows="2"
                                      placeholder="Notes for internal dispatch, assigned travel agent, passport copy received, etc..."
                                      class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('admin_notes', $travelRequest->admin_notes) }}</textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2 flex justify-end">
                            <button type="submit"
                                    class="px-6 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                                Update Status &amp; Save Notes
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- Right Col: Audit & Timeline -->
            <div class="space-y-6">

                <!-- Timeline Audit Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider font-display border-b border-slate-100 pb-2">
                        Audit &amp; Activity Log
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Submitted</span>
                            <span class="text-slate-800 font-medium">{{ $travelRequest->created_at->format('M d, Y \a\t H:i') }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Customer Will Be Contacted</span>
                            <span class="text-slate-800 font-medium">
                                {{ $travelRequest->contacted_at ? $travelRequest->contacted_at->format('M d, Y \a\t H:i') : 'Not yet recorded' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Resolved / Settled</span>
                            <span class="text-slate-800 font-medium">
                                {{ $travelRequest->resolved_at ? $travelRequest->resolved_at->format('M d, Y \a\t H:i') : 'Pending' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Last Updated</span>
                            <span class="text-slate-800 font-medium">{{ $travelRequest->updated_at->format('M d, Y \a\t H:i') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions / Contact -->
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6 space-y-3">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider font-display">
                        Direct Outreach
                    </h3>
                    <p class="text-xs text-slate-500">Reach the lead traveler directly via phone call or WhatsApp.</p>
                    <div class="space-y-2 pt-2">
                        <a href="tel:{{ $travelRequest->phone }}"
                           class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-800 text-xs font-semibold shadow-xs transition">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Call Traveler</span>
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/^0/', '234', preg_replace('/[^0-9]/', '', $travelRequest->phone)) }}" target="_blank"
                           class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                            <span>Chat on WhatsApp</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>

        @if ($travelRequest->travelPackage)
            <!-- Edit Package Configuration Modal -->
            <div x-show="showEditPackageModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                 @click.self="showEditPackageModal = false">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0056D2] flex items-center justify-center shrink-0">
                                <i data-lucide="package" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 font-display">Modify Package Configuration</h3>
                                <p class="text-xs text-slate-400">Updates title, subtitle, estimated value, and inclusions across the platform.</p>
                            </div>
                        </div>
                        <button type="button" @click="showEditPackageModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.travel-requests.packages.update', $travelRequest->travelPackage) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                        <!-- Title & Category -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Package Title <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="name" required value="{{ $travelRequest->travelPackage->name }}"
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-semibold">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Category <span class="text-rose-500">*</span>
                                </label>
                                <select name="category" required
                                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                                    @foreach (\App\Models\TravelPackage::CATEGORIES as $ck => $cl)
                                        <option value="{{ $ck }}" {{ $travelRequest->travelPackage->category === $ck ? 'selected' : '' }}>
                                            {{ $cl }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Subtitle / Tagline -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Subtitle / Tagline
                            </label>
                            <input type="text" name="tagline" value="{{ $travelRequest->travelPackage->tagline }}"
                                   placeholder="Short subtitle summary"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <!-- Estimated Package Value & Duration -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Estimated Package Value
                                </label>
                                <input type="text" name="price_range" value="{{ $travelRequest->travelPackage->price_range }}"
                                       placeholder="e.g. From ₦2,650,000 / person"
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-[#0056D2]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Duration
                                </label>
                                <input type="text" name="duration" value="{{ $travelRequest->travelPackage->duration }}"
                                       placeholder="e.g. 14 Days / 13 Nights"
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>

                        <!-- Package Inclusions -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Package Inclusions
                                </label>
                                <span class="text-[10px] text-slate-400">One bullet point per line</span>
                            </div>
                            <textarea name="highlights" rows="5"
                                      class="w-full font-mono text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary leading-relaxed">{{ $travelRequest->travelPackage->highlights_text }}</textarea>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Full Description &amp; Itinerary
                            </label>
                            <textarea name="description" rows="3"
                                      class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ $travelRequest->travelPackage->description }}</textarea>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50">
                            <input type="checkbox" id="modal_is_active" name="is_active" value="1"
                                   {{ $travelRequest->travelPackage->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#0056D2] focus:ring-primary/20 border-slate-300">
                            <label for="modal_is_active" class="text-xs font-bold text-slate-800 cursor-pointer">
                                Active &amp; Visible in Customer Catalog
                            </label>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" @click="showEditPackageModal = false"
                                    class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                                Save Package Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
