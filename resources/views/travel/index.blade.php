<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-7xl mx-auto space-y-6" x-data="{
        showBookingModal: {{ $selectedPackage ? 'true' : 'false' }},
        selectedPackageId: '{{ $selectedPackage ? $selectedPackage->id : '' }}',
        selectedPackageName: '{{ $selectedPackage ? addslashes($selectedPackage->name) : '' }}',
        selectedCategory: '{{ $selectedPackage ? $selectedPackage->category : 'umrah' }}',
        openBooking(pkgId, pkgName, pkgCat) {
            this.selectedPackageId = pkgId;
            this.selectedPackageName = pkgName;
            this.selectedCategory = pkgCat;
            this.showBookingModal = true;
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

        <!-- Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold mb-2.5">
                        <i data-lucide="compass" class="w-3.5 h-3.5"></i>
                        <span>SimPhix Travel &amp; Tours Desk</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-display tracking-tight">
                        Umrah, Hajj &amp; International Travel Packages
                    </h1>
                    <p class="text-slate-500 text-sm mt-1 max-w-2xl">
                        Fulfill sacred pilgrimages or embark on international holidays. Select an existing package or request a tailored itinerary with verified flight and visa processing.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('travel.tracking') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-sm transition">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        <span>Track My Requests</span>
                    </a>
                    <button type="button" @click="openBooking('', 'Custom Travel / Other', 'custom')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Custom Travel Request</span>
                    </button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                <a href="{{ route('travel.index', ['category' => 'all']) }}"
                   class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition {{ $activeCategory === 'all' ? 'bg-[#0056D2] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    All Packages
                </a>
                <a href="{{ route('travel.index', ['category' => 'umrah']) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition {{ $activeCategory === 'umrah' ? 'bg-[#0056D2] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>🕋</span>
                    <span>Umrah Packages</span>
                </a>
                <a href="{{ route('travel.index', ['category' => 'hajj']) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition {{ $activeCategory === 'hajj' ? 'bg-[#0056D2] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>🕌</span>
                    <span>Hajj Pilgrimage</span>
                </a>
                <a href="{{ route('travel.index', ['category' => 'international']) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition {{ $activeCategory === 'international' ? 'bg-[#0056D2] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <span>✈️</span>
                    <span>International Travels</span>
                </a>
            </div>
        </div>

        @if ($userRequests->isNotEmpty())
            <!-- Recent Requests Quick Bar for Current User -->
            <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-700"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-amber-950 font-display">Your Recent Travel Inquiries</h3>
                    </div>
                    <a href="{{ route('travel.tracking') }}" class="text-xs font-semibold text-amber-800 hover:underline">
                        View All Applications &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($userRequests as $req)
                        <div class="bg-white rounded-xl border border-amber-200/60 p-3 shadow-xs flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold font-mono text-slate-900 truncate">{{ $req->reference }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $req->package_name }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $req->status_badge_class }}">
                                    {{ $req->status_label }}
                                </span>
                                <a href="{{ route('travel.tracking', ['ref' => $req->reference]) }}" class="block text-[10px] font-semibold text-primary hover:underline mt-1">
                                    Track &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Packages Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($packages as $pkg)
                @php
                    $isUmrah = $pkg->category === 'umrah';
                    $isHajj = $pkg->category === 'hajj';
                    $badgeColor = $isUmrah ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : ($isHajj ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-sky-100 text-sky-800 border-sky-200');
                    $categoryIcon = $isUmrah ? '🕋' : ($isHajj ? '🕌' : '✈️');
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between overflow-hidden">
                    <div class="p-6">
                        <!-- Top Header Info -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badgeColor }}">
                                <span>{{ $categoryIcon }}</span>
                                <span>{{ ucfirst($pkg->category) }}</span>
                            </span>
                            @if ($pkg->duration)
                                <span class="inline-flex items-center gap-1 text-xs text-slate-500 font-medium">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>{{ $pkg->duration }}</span>
                                </span>
                            @endif
                        </div>

                        <!-- Title & Tagline -->
                        <h3 class="text-lg font-bold text-slate-900 font-display mb-1.5">{{ $pkg->name }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2 mb-4">{{ $pkg->tagline ?? $pkg->description }}</p>

                        <!-- Price Estimation -->
                        @if ($pkg->price_range)
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Estimated Package Value</span>
                                <span class="text-sm font-extrabold text-[#0056D2] font-display">{{ $pkg->price_range }}</span>
                            </div>
                        @endif

                        <!-- Highlights List -->
                        @if (!empty($pkg->highlights))
                            <div class="space-y-2 mt-4 pt-4 border-t border-slate-100">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Package Inclusions</p>
                                <ul class="space-y-1.5 text-xs text-slate-600">
                                    @foreach (array_slice($pkg->highlights, 0, 4) as $item)
                                        <li class="flex items-start gap-2">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5"></i>
                                            <span class="leading-tight">{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- Bottom Action Button -->
                    <div class="p-6 pt-0 mt-2">
                        <button type="button"
                                @click="openBooking('{{ $pkg->id }}', '{{ addslashes($pkg->name) }}', '{{ $pkg->category }}')"
                                class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-[#0056D2] text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center gap-2">
                            <i data-lucide="bookmark-plus" class="w-4 h-4"></i>
                            <span>Request This Package</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-slate-200">
                    <i data-lucide="compass" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                    <p class="text-slate-500 text-sm">No packages found for this category.</p>
                </div>
            @endforelse
        </div>

        <!-- Booking Modal -->
        <div x-show="showBookingModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             @click.self="showBookingModal = false">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto p-6 sm:p-8 space-y-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#0056D2] flex items-center justify-center shrink-0">
                            <i data-lucide="plane-takeoff" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 font-display">Travel &amp; Tours Booking Request</h3>
                            <p class="text-xs text-slate-400">Fill your details below. Our officer will review and reach out immediately.</p>
                        </div>
                    </div>
                    <button type="button" @click="showBookingModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('travel.store') }}" class="space-y-4">
                    @csrf

                    <!-- Full Name (Required) -->
                    <div>
                        <label for="req_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="req_name" name="name" required
                               value="{{ old('name', Auth::user()?->name ?? '') }}"
                               placeholder="e.g. Ibrahim Abubakar"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Phone Number & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Phone Number (Required) -->
                        <div>
                            <label for="req_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Phone Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="req_phone" name="phone" required
                                   value="{{ old('phone', Auth::user()?->phone ?? '') }}"
                                   placeholder="e.g. 08012345678"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <!-- Email Address (Optional) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="req_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Email Address
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">Optional</span>
                            </div>
                            <input type="email" id="req_email" name="email"
                                   value="{{ old('email', Auth::user()?->email ?? '') }}"
                                   placeholder="e.g. ibrahim@example.com"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    <!-- Package Selection (Required) -->
                    <div>
                        <label for="req_package" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Selected Travel Package <span class="text-rose-500">*</span>
                        </label>
                        <select id="req_package" name="travel_package_id" x-model="selectedPackageId"
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                            <option value="">-- Choose a package or custom itinerary --</option>
                            <optgroup label="🕋 Umrah Packages">
                                @foreach ($packages->where('category', 'umrah') as $p)
                                    <option value="{{ $p->id }}" {{ old('travel_package_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->duration ?? 'Umrah' }})
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="🕌 Hajj Pilgrimage">
                                @foreach ($packages->where('category', 'hajj') as $p)
                                    <option value="{{ $p->id }}" {{ old('travel_package_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="✈️ International Travels & Tours">
                                @foreach ($packages->where('category', 'international') as $p)
                                    <option value="{{ $p->id }}" {{ old('travel_package_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Custom Itinerary">
                                <option value="" {{ old('travel_package_id') === '' ? 'selected' : '' }}>Custom Tailored Package (Flights &amp; Visa)</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Number of Travelers & Departure Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="req_travelers" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Number of Travelers
                            </label>
                            <input type="number" id="req_travelers" name="number_of_travelers" min="1" max="50"
                                   value="{{ old('number_of_travelers', 1) }}"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label for="req_departure" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Preferred Month / Date
                            </label>
                            <input type="text" id="req_departure" name="departure_date"
                                   value="{{ old('departure_date') }}"
                                   placeholder="e.g. Ramadan 2027 or Nov 15"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div>
                        <label for="req_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Special Inquiries or Preferences
                        </label>
                        <textarea id="req_notes" name="notes" rows="3"
                                  placeholder="Mention specific hotel preferences, visa history, companion details, or custom requests..."
                                  class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('notes') }}</textarea>
                    </div>

                    <!-- Notice & Submit Button -->
                    <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <p class="text-[11px] text-slate-400">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 inline text-emerald-500"></i>
                            Your request will generate a unique tracking reference code.
                        </p>
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                            Submit Travel Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
