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

        <!-- Header Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ request('redirect_to', route('admin.travel-requests.packages.index')) }}"
                       class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 font-mono">Travel Packages</span>
                            <span class="text-xs text-slate-300">•</span>
                            <span class="text-xs text-[#0056D2] font-semibold">{{ $isEdit ? 'Edit Package' : 'New Package' }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display mt-0.5">
                            {{ $isEdit ? "Edit \"{$package->name}\"" : 'Create New Travel Package' }}
                        </h1>
                    </div>
                </div>

                @if ($isEdit)
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('travel.index', ['category' => $package->category]) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold shadow-xs transition">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>View in Catalog</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <form method="POST"
                  action="{{ $isEdit ? route('admin.travel-requests.packages.update', $package) : route('admin.travel-requests.packages.store') }}"
                  class="space-y-6">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                @if (request('redirect_to'))
                    <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
                @endif

                <!-- Section: Basic Information -->
                <div class="space-y-4">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider font-display border-b border-slate-100 pb-2">
                        1. Package Title, Subtitle &amp; Classification
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Package Title -->
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Package Title <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" required
                                   value="{{ old('name', $package->name) }}"
                                   placeholder="e.g. Economy Umrah Package, Dubai Family Vacation"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-semibold">
                            <p class="text-[11px] text-slate-400 mt-1">The primary name displayed on the customer catalog and tracking receipts.</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <select id="category" name="category" required
                                    class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                                @foreach (\App\Models\TravelPackage::CATEGORIES as $catKey => $catLabel)
                                    <option value="{{ $catKey }}" {{ old('category', $package->category) === $catKey ? 'selected' : '' }}>
                                        {{ $catLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Subtitle / Tagline -->
                    <div>
                        <label for="tagline" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Subtitle / Tagline
                        </label>
                        <input type="text" id="tagline" name="tagline"
                               value="{{ old('tagline', $package->tagline) }}"
                               placeholder="e.g. Affordable and spiritually fulfilling pilgrimage with verified hotel accommodation."
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-[11px] text-slate-400 mt-1">Short subtitle summarizing what makes this package unique.</p>
                    </div>
                </div>

                <!-- Section: Pricing & Duration -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider font-display border-b border-slate-100 pb-2">
                        2. Estimated Pricing &amp; Duration
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Estimated Package Value -->
                        <div>
                            <label for="price_range" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Estimated Package Value
                            </label>
                            <input type="text" id="price_range" name="price_range"
                                   value="{{ old('price_range', $package->price_range) }}"
                                   placeholder="e.g. From ₦2,650,000 / person or Official NAHCON Quota"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold text-[#0056D2]">
                            <p class="text-[11px] text-slate-400 mt-1">Displayed as the price estimation badge on catalog cards.</p>
                        </div>

                        <!-- Duration -->
                        <div>
                            <label for="duration" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Duration / Timeline
                            </label>
                            <input type="text" id="duration" name="duration"
                                   value="{{ old('duration', $package->duration) }}"
                                   placeholder="e.g. 14 Days / 13 Nights, 7 Days, Customizable"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <p class="text-[11px] text-slate-400 mt-1">Length of stay or travel window.</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Package Inclusions -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider font-display">
                            3. Package Inclusions (Highlights)
                        </h2>
                        <span class="text-[11px] text-slate-400 font-medium">One bullet point per line</span>
                    </div>

                    <div>
                        <label for="highlights" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Inclusion Bullet Points
                        </label>
                        <textarea id="highlights" name="highlights" rows="6"
                                  placeholder="Umrah e-Visa processing & health insurance&#10;Round-trip scheduled airline flights&#10;3-star standard hotel accommodation (Makkah & Madinah)&#10;Air-conditioned airport & intercity transfers&#10;Guided Ziyarat to historical holy sites&#10;Dedicated SimPhix on-ground support guide"
                                  class="w-full font-mono text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary leading-relaxed">{{ old('highlights', $package->highlights_text) }}</textarea>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Type or paste each inclusion on a new line. These will render as checkmark bullet items on the package card.
                        </p>
                    </div>
                </div>

                <!-- Section: Description & Visibility -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider font-display border-b border-slate-100 pb-2">
                        4. Overview &amp; Catalog Visibility
                    </h2>

                    <!-- Full Description -->
                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Full Package Overview &amp; Itinerary Details
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Complete logistical overview, hotel tiers, airline details, visa rules..."
                                  class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('description', $package->description) }}</textarea>
                    </div>

                    <!-- Toggles Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#0056D2] focus:ring-primary/20 border-slate-300">
                            <div>
                                <label for="is_active" class="block text-xs font-bold text-slate-800 cursor-pointer">
                                    Active / Visible
                                </label>
                                <span class="text-[10px] text-slate-400 block">Show in public catalog</span>
                            </div>
                        </div>

                        <!-- Featured Toggle -->
                        <div class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                   {{ old('is_featured', $package->is_featured ?? false) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded text-[#0056D2] focus:ring-primary/20 border-slate-300">
                            <div>
                                <label for="is_featured" class="block text-xs font-bold text-slate-800 cursor-pointer">
                                    Featured Package
                                </label>
                                <span class="text-[10px] text-slate-400 block">Pin to prominent shelf</span>
                            </div>
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <label for="sort_order" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Sort Order
                            </label>
                            <input type="number" id="sort_order" name="sort_order" min="0" max="999"
                                   value="{{ old('sort_order', $package->sort_order ?? 0) }}"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <span class="text-[10px] text-slate-400 block mt-0.5">Lower numbers appear first</span>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ request('redirect_to', route('admin.travel-requests.packages.index')) }}"
                       class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs sm:text-sm font-semibold transition">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>{{ $isEdit ? 'Update Package Configuration' : 'Create Package' }}</span>
                    </button>
                </div>

            </form>
        </div>

    </div>
</x-app-layout>
