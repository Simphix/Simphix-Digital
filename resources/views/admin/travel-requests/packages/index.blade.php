<x-app-layout>
    <div class="container-fluid py-4 px-0 max-w-7xl mx-auto space-y-6">

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

        <!-- Header Card & Tabs -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="package" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 font-display tracking-tight">
                            Travel &amp; Tours Packages Configuration
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Modify package titles, subtitles, estimated pricing, and inclusions displayed to customers across the platform.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a href="{{ route('travel.index') }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
                        <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Customer Catalog</span>
                    </a>
                    <a href="{{ route('admin.travel-requests.packages.create') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0056D2] hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Add New Package</span>
                    </a>
                </div>
            </div>

            <!-- Main Desk Navigation Tabs -->
            <div class="flex items-center gap-2 border-b border-slate-100 pt-6 mt-4">
                <a href="{{ route('admin.travel-requests.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                    <span>Travel Requests</span>
                </a>
                <a href="{{ route('admin.travel-requests.packages.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 border-[#0056D2] text-[#0056D2] transition">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                    <span>Configurable Packages</span>
                    <span class="ml-1 px-2 py-0.5 text-[10px] rounded-full bg-blue-50 text-[#0056D2] font-bold">
                        {{ $stats['total'] }}
                    </span>
                </a>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-6 pt-2">
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Packages</span>
                    <span class="text-xl font-bold font-display text-slate-900 mt-1 block">{{ number_format($stats['total']) }}</span>
                </div>
                <div class="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200/60">
                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Active &amp; Visible</span>
                    <span class="text-xl font-bold font-display text-emerald-900 mt-1 block">{{ number_format($stats['active']) }}</span>
                </div>
                <div class="bg-amber-50/60 rounded-xl p-3.5 border border-amber-200/60">
                    <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Umrah Packages</span>
                    <span class="text-xl font-bold font-display text-amber-900 mt-1 block">{{ number_format($stats['umrah']) }}</span>
                </div>
                <div class="bg-indigo-50/60 rounded-xl p-3.5 border border-indigo-200/60">
                    <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Hajj Pilgrimage</span>
                    <span class="text-xl font-bold font-display text-indigo-900 mt-1 block">{{ number_format($stats['hajj']) }}</span>
                </div>
                <div class="bg-sky-50/60 rounded-xl p-3.5 border border-sky-200/60">
                    <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">International Travels</span>
                    <span class="text-xl font-bold font-display text-sky-900 mt-1 block">{{ number_format($stats['international']) }}</span>
                </div>
            </div>

            <!-- Filters Form -->
            <form method="GET" action="{{ route('admin.travel-requests.packages.index') }}" class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search package title, subtitle, duration, or value..."
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <!-- Category Filter -->
                <div>
                    <select name="category" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="all">All Categories</option>
                        @foreach (\App\Models\TravelPackage::CATEGORIES as $catKey => $catLabel)
                            <option value="{{ $catKey }}" {{ request('category') === $catKey ? 'selected' : '' }}>
                                {{ $catLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter & Submit -->
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive / Hidden</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-[#0056D2] text-white text-xs font-bold transition shrink-0">
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'category', 'status']))
                        <a href="{{ route('admin.travel-requests.packages.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition shrink-0" title="Clear Filters">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Packages Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($packages as $pkg)
                @php
                    $badgeColors = match ($pkg->category) {
                        'umrah'         => 'bg-amber-50 text-amber-700 border-amber-200',
                        'hajj'          => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'international' => 'bg-blue-50 text-[#0056D2] border-blue-200',
                        default         => 'bg-purple-50 text-purple-700 border-purple-200',
                    };
                    $categoryIcons = [
                        'umrah'         => '🕋',
                        'hajj'          => '🕌',
                        'international' => '✈️',
                        'custom'        => '🧭',
                    ];
                    $categoryIcon = $categoryIcons[$pkg->category] ?? '✈️';
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between overflow-hidden transition hover:shadow-md">
                    <div class="p-6">
                        <!-- Top Badges -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badgeColors }}">
                                <span>{{ $categoryIcon }}</span>
                                <span>{{ $pkg->category_label }}</span>
                            </span>

                            <div class="flex items-center gap-1.5">
                                @if ($pkg->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        Hidden
                                    </span>
                                @endif

                                @if ($pkg->is_featured)
                                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800" title="Featured Package">
                                        ★
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Package Title & Subtitle -->
                        <h3 class="text-base font-bold text-slate-900 font-display mb-1">
                            {{ $pkg->name }}
                        </h3>
                        <p class="text-xs text-slate-500 line-clamp-2 mb-4">
                            {{ $pkg->tagline ?: 'No subtitle provided.' }}
                        </p>

                        <!-- Estimated Package Value & Duration -->
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 mb-4 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Estimated Value</span>
                                @if ($pkg->duration)
                                    <span class="text-[11px] font-medium text-slate-500 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>
                                        {{ $pkg->duration }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-sm font-extrabold text-[#0056D2] font-display block">
                                {{ $pkg->price_range ?: 'Price on Inquiry' }}
                            </span>
                        </div>

                        <!-- Package Inclusions List Preview -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    Package Inclusions ({{ count($pkg->highlights ?? []) }})
                                </span>
                            </div>
                            @if (!empty($pkg->highlights))
                                <ul class="space-y-1 text-xs text-slate-600">
                                    @foreach (array_slice($pkg->highlights, 0, 3) as $hl)
                                        <li class="flex items-start gap-1.5">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5"></i>
                                            <span class="truncate">{{ $hl }}</span>
                                        </li>
                                    @endforeach
                                    @if (count($pkg->highlights) > 3)
                                        <li class="text-[11px] text-slate-400 italic pl-5">
                                            + {{ count($pkg->highlights) - 3 }} more inclusion(s)
                                        </li>
                                    @endif
                                </ul>
                            @else
                                <p class="text-xs text-slate-400 italic">No inclusions listed.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions Footer -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <!-- Toggle Active/Inactive -->
                            <form method="POST" action="{{ route('admin.travel-requests.packages.toggle-status', $pkg) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition {{ $pkg->is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                        title="{{ $pkg->is_active ? 'Hide from catalog' : 'Make visible in catalog' }}">
                                    {{ $pkg->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <!-- Delete (only if 0 requests or confirm) -->
                            <form method="POST" action="{{ route('admin.travel-requests.packages.destroy', $pkg) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this travel package?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Delete Package">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Edit Button -->
                        <a href="{{ route('admin.travel-requests.packages.edit', $pkg) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-[#0056D2] text-white text-xs font-bold transition shadow-xs">
                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                            <span>Edit Package</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <i data-lucide="package-search" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                    <h3 class="text-sm font-bold text-slate-800 font-display">No Travel Packages Found</h3>
                    <p class="text-xs text-slate-400 mt-1">Adjust your filters or create a new package for your catalog.</p>
                    <div class="mt-4">
                        <a href="{{ route('admin.travel-requests.packages.create') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0056D2] text-white text-xs font-bold shadow-sm hover:bg-blue-700 transition">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Create New Package</span>
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</x-app-layout>
