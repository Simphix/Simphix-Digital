<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-7xl mx-auto space-y-6" x-data="{
        showRequestModal: false,
        selectedType: '{{ $selectedType ?? 'smart_pos' }}',
        openModal(type) {
            this.selectedType = type;
            this.showRequestModal = true;
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
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5 sm:gap-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-[#001F5C] flex items-center justify-center p-2.5 shrink-0 shadow-sm border border-blue-900/40">
                        <img src="{{ asset('assets/images/moniepoint-logo.png') }}" alt="Moniepoint" class="h-5 sm:h-6 w-auto object-contain">
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-[#00266B] text-[11px] sm:text-xs font-bold mb-1.5">
                            <span>Official Moniepoint POS Hardware Partner</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 font-display tracking-tight">
                            Moniepoint POS Terminals Request
                        </h1>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 shrink-0">
                    <a href="{{ route('moniepoint.pos.tracking') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold shadow-sm transition">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        <span>Track My POS Request</span>
                    </a>
                    <button type="button" @click="openModal('smart_pos')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001b4c] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-[#ffcc00]"></i>
                        <span>Request POS Terminal</span>
                    </button>
                </div>
            </div>
        </div>

        @if ($userRequests->isNotEmpty())
            <!-- Recent Requests Quick Bar -->
            <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-[#00266B]"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 font-display">Your Recent POS Terminal Requests</h3>
                    </div>
                    <a href="{{ route('moniepoint.pos.tracking') }}" class="text-xs font-semibold text-[#00266B] hover:underline">
                        View All Applications &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($userRequests as $req)
                        <div class="bg-white rounded-xl border border-blue-200/60 p-3 shadow-xs flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold font-mono text-slate-900 truncate">{{ $req->reference }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $req->pos_type_label }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $req->status_badge_class }}">
                                    {{ $req->status_label }}
                                </span>
                                <a href="{{ route('moniepoint.pos.tracking', ['ref' => $req->reference]) }}" class="block text-[10px] font-semibold text-primary hover:underline mt-1">
                                    Track &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- POS Terminal Models Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Card 1: Moniepoint Smart POS (Android) -->
            <div class="bg-white rounded-2xl border-2 border-[#00266B]/30 hover:border-[#00266B] shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden relative">
                <div class="p-6">
                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300 mb-3">
                        Most Popular &bull; High Volume
                    </span>

                    <div class="h-44 flex items-center justify-center p-3 mb-4 bg-slate-50 rounded-xl overflow-hidden">
                        <img src="{{ asset('assets/images/moniepoint-pos.png') }}" alt="Moniepoint Smart POS" class="h-36 w-36 object-contain drop-shadow-sm transition-transform duration-300 hover:scale-105">
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 font-display">Moniepoint Smart POS (Android)</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Full-touchscreen 4G Android terminal with built-in high-speed thermal receipt printer, barcode reader, and long-lasting battery.
                    </p>

                    <div class="space-y-2 mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Instant card and transfer settlement</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Dual SIM 4G LTE + High-gain Wi-Fi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>High-speed thermal printer (paper roll included)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>5200mAh heavy-duty rechargeable battery</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 pt-0 mt-3">
                    <button type="button" @click="openModal('smart_pos')"
                            class="w-full py-3 px-4 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center gap-2">
                        <i data-lucide="shopping-cart" class="w-4 h-4 text-[#ffcc00]"></i>
                        <span>Request Smart Android POS</span>
                    </button>
                </div>
            </div>

            <!-- Card 2: Moniepoint Traditional POS -->
            <div class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden">
                <div class="p-6">
                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200 mb-3">
                        Rugged Keypad &bull; Field Favorite
                    </span>

                    <div class="h-44 flex items-center justify-center p-3 mb-4 bg-slate-50 rounded-xl overflow-hidden">
                        <img src="{{ asset('assets/images/moniepoint-traditional-pos.png') }}" alt="Moniepoint Traditional POS" class="h-36 w-36 object-contain drop-shadow-sm transition-transform duration-300 hover:scale-105">
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 font-display">Moniepoint Traditional POS</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Physical mechanical keypad terminal designed for intense, heavy-duty retail and kiosk transaction processing.
                    </p>

                    <div class="space-y-2 mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Tactile keypad for fast PIN entry</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>All-weather shock-resistant build</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Fast paper receipt issuing</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Dual 4G connectivity support</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 pt-0 mt-3">
                    <button type="button" @click="openModal('traditional_pos')"
                            class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-[#00266B] text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center gap-2">
                        <i data-lucide="shopping-cart" class="w-4 h-4 text-white"></i>
                        <span>Request Traditional POS</span>
                    </button>
                </div>
            </div>

            <!-- Card 3: Moniepoint Mini POS / MPOS -->
            <div class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden">
                <div class="p-6">
                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 mb-3">
                        Pocket Size &bull; Mobile Sellers
                    </span>

                    <div class="h-44 flex items-center justify-center p-3 mb-4 bg-slate-50 rounded-xl overflow-hidden">
                        <img src="{{ asset('assets/images/moniepoint-mini-pos.png') }}" alt="Moniepoint Mini POS" class="h-36 w-36 object-contain drop-shadow-sm transition-transform duration-300 hover:scale-105">
                    </div>

                    <h3 class="text-lg font-bold text-slate-900 font-display">Moniepoint Mini POS (MPOS)</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Lightweight Bluetooth-paired pocket terminal for delivery riders, mobile sales agents, and pop-up store vendors.
                    </p>

                    <div class="space-y-2 mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Bluetooth sync with mobile phone</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Digital SMS/Email receipts</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Pocket-friendly, zero bulkiness</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                            <span>Accepts Chip &amp; PIN + Contactless NFC</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 pt-0 mt-3">
                    <button type="button" @click="openModal('mini_pos')"
                            class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-[#00266B] text-white text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center gap-2">
                        <i data-lucide="shopping-cart" class="w-4 h-4 text-white"></i>
                        <span>Request Mini POS</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Request Modal -->
        <div x-show="showRequestModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             @click.self="showRequestModal = false">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 sm:p-8 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#00266B] flex items-center justify-center shrink-0">
                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Moniepoint POS Request</h3>
                            <p class="text-xs text-slate-400">Fill your contact details and desired terminal model.</p>
                        </div>
                    </div>
                    <button type="button" @click="showRequestModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('moniepoint.pos.store') }}" autocomplete="off" class="space-y-4">
                    @csrf

                    <!-- Full Name (Required) -->
                    <div>
                        <label for="pos_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="pos_name" name="name" required autocomplete="off"
                               value="{{ old('name') }}"
                               placeholder="e.g. Chukwuemeka Eze"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Phone & Email (Both Required as per request) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="pos_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Phone Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="pos_phone" name="phone" required autocomplete="off"
                                   value="{{ old('phone') }}"
                                   placeholder="e.g. 08012345678"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label for="pos_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" id="pos_email" name="email" required autocomplete="off"
                                   value="{{ old('email') }}"
                                   placeholder="e.g. agent@example.com"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    <!-- POS Type Selection (Required) -->
                    <div>
                        <label for="pos_type_select" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            POS Terminal Type <span class="text-rose-500">*</span>
                        </label>
                        <select id="pos_type_select" name="pos_type" x-model="selectedType" required
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                            @foreach (\App\Models\MoniepointPosRequest::POS_TYPES as $k => $label)
                                <option value="{{ $k }}" {{ old('pos_type') === $k ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Business Name & Quantity -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label for="pos_biz" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Business / Store Name
                            </label>
                            <input type="text" id="pos_biz" name="business_name" autocomplete="off"
                                   value="{{ old('business_name') }}"
                                   placeholder="e.g. Eze Supermarket &amp; POS Services"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label for="pos_qty" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Quantity
                            </label>
                            <input type="number" id="pos_qty" name="quantity" min="1" max="50" autocomplete="off"
                                   value="{{ old('quantity', 1) }}"
                                   class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div>
                        <label for="pos_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Delivery Address (City / State / Landmark)
                        </label>
                        <textarea id="pos_address" name="delivery_address" rows="2" autocomplete="off"
                                  placeholder="Enter complete store or home address for hardware delivery..."
                                  class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('delivery_address') }}</textarea>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3">
                        <p class="text-[11px] text-slate-400">Generates unique tracking reference.</p>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-xl bg-[#00266B] hover:bg-[#001F5C] text-white text-xs sm:text-sm font-bold shadow-sm transition">
                            Submit POS Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
