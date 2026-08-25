<header class="bg-white border-b border-slate-100/80 sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between px-4 sm:px-6 lg:px-8 shadow-sm">
    
    <!-- Left Section: Toggle & Search -->
    <div class="flex items-center gap-4 flex-grow max-w-sm lg:max-w-md">
        <!-- Sidebar Toggle (Mobile only) -->
        <button @click="sidebarOpen = true" 
                type="button" 
                class="p-2 -ml-2 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-slate-800 lg:hidden focus:outline-none transition-colors">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <!-- Inline Search Bar (Desktop only) -->
        <div x-data="{
            query: '',
            results: [],
            loading: false,
            open: false,
            activeIndex: -1,
            performSearch() {
                if (this.query.trim().length === 0) {
                    this.results = [];
                    this.activeIndex = -1;
                    return;
                }
                this.loading = true;
                this.open = true;
                this.activeIndex = -1;
                fetch('/search?q=' + encodeURIComponent(this.query))
                    .then(res => res.json())
                    .then(data => {
                        this.results = data;
                        this.loading = false;
                        this.$nextTick(() => {
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        });
                    })
                    .catch(() => {
                        this.loading = false;
                    });
            },
            clearSearch() {
                this.query = '';
                this.results = [];
                this.activeIndex = -1;
                this.$refs.headerSearchInput.focus();
            },
            moveActive(delta) {
                if (!this.results.length) return;
                this.open = true;
                this.activeIndex = (this.activeIndex + delta + this.results.length) % this.results.length;
                this.$nextTick(() => {
                    this.$refs.resultsList?.querySelector('[data-active=\'true\']')?.scrollIntoView({ block: 'nearest' });
                });
            },
            selectActive() {
                if (this.activeIndex > -1 && this.results[this.activeIndex]) {
                    window.location.href = this.results[this.activeIndex].url;
                }
            }
        }"
        @click.away="open = false"
        @keydown.window.cmd.k.prevent="$refs.headerSearchInput.focus()"
        @keydown.window.ctrl.k.prevent="$refs.headerSearchInput.focus()"
        @keydown.window.slash.prevent="if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') { $refs.headerSearchInput.focus() }"
        class="relative w-full hidden md:block">

            <!-- Search Input -->
            <div class="relative">
                <i data-lucide="search" class="absolute left-3.5 top-2.5 w-4 h-4 text-slate-400 z-10"></i>
                <x-text-input x-model.debounce.250ms="query"
                       @input="performSearch()"
                       @focus="open = true"
                       @keydown.down.prevent="moveActive(1)"
                       @keydown.up.prevent="moveActive(-1)"
                       @keydown.enter.prevent="selectActive()"
                       @keydown.escape="open = false; $refs.headerSearchInput.blur()"
                       x-ref="headerSearchInput"
                       type="text"
                       autocomplete="off"
                       placeholder="Search settings, services, users..."
                       class="pl-9 pr-14 !py-2 font-medium text-slate-700 bg-slate-50 focus:bg-white focus:!ring-1" />
                <button type="button" x-show="query.length > 0" @click="clearSearch()" style="display:none"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
                <span x-show="query.length === 0" class="absolute right-2.5 top-1.5 px-1.5 py-0.5 text-xs font-bold text-slate-400 bg-white border border-slate-200 rounded-md shadow-sm pointer-events-none">Ctrl K</span>
            </div>

            <!-- Floating Search Dropdown Card -->
            <div x-show="open && (query.length > 0 || loading)"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute left-0 mt-1.5 w-full bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/30 py-2.5 z-50 outline-none max-h-96 overflow-y-auto"
                 x-ref="resultsList"
                 style="display: none;">

                <!-- Loading State -->
                <div x-show="loading" class="flex items-center justify-center py-6 gap-2 text-slate-400">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-primary"></i>
                    <span class="text-xs font-semibold font-display">Searching SmartSIM...</span>
                </div>

                <!-- No Results State -->
                <div x-show="!loading && results.length === 0" class="px-4 py-4 text-center text-slate-400">
                    <i data-lucide="search-x" class="w-5 h-5 mx-auto text-slate-300 mb-1"></i>
                    <span class="block text-xs font-semibold font-display">No matches found for "<span class="text-slate-600" x-text="query"></span>"</span>
                </div>

                <!-- Results List -->
                <div x-show="!loading && results.length > 0" class="space-y-3 px-1">
                    <template x-for="category in [...new Set(results.map(r => r.category))]" :key="category">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider px-3 mb-1 font-display" x-text="category"></span>
                            <div class="space-y-0.5">
                                <template x-for="item in results.filter(r => r.category === category)" :key="item.title + item.url">
                                    <a :href="item.url"
                                       :data-active="results[activeIndex] === item"
                                       @mouseenter="activeIndex = results.indexOf(item)"
                                       :class="results[activeIndex] === item ? 'bg-slate-50 text-primary ring-1 ring-primary/15' : 'hover:bg-slate-50 hover:text-primary'"
                                       class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl group transition-colors">
                                        <div :class="results[activeIndex] === item ? 'bg-white text-primary border-primary/20' : 'bg-slate-50 group-hover:bg-white text-slate-500 group-hover:text-primary border-slate-100'"
                                             class="w-7 h-7 rounded-lg border flex items-center justify-center transition-colors shadow-sm shrink-0">
                                            <i :data-lucide="item.icon" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <span class="block text-xs font-semibold text-slate-700 group-hover:text-slate-900 truncate font-display" x-text="item.title"></span>
                                            <span class="block text-xs text-slate-400 truncate mt-0.5 font-display" x-text="item.description"></span>
                                        </div>
                                        <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300 group-hover:text-slate-500 group-hover:translate-x-0.5 transition-all shrink-0"></i>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Keyboard Hint Footer -->
                <div x-show="!loading && results.length > 0" class="flex items-center gap-3 px-4 pt-2.5 mt-1 border-t border-slate-100 text-xs text-slate-400 font-semibold">
                    <span class="flex items-center gap-1"><span class="px-1.5 py-0.5 bg-slate-50 border border-slate-200 rounded shadow-sm">&uarr;&darr;</span> navigate</span>
                    <span class="flex items-center gap-1"><span class="px-1.5 py-0.5 bg-slate-50 border border-slate-200 rounded shadow-sm">&crarr;</span> select</span>
                    <span class="flex items-center gap-1"><span class="px-1.5 py-0.5 bg-slate-50 border border-slate-200 rounded shadow-sm">Esc</span> close</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section: Actions & User Profile -->
    <div class="flex items-center gap-3">
        <!-- Search Trigger (Mobile only) -->
        <button @click="$dispatch('open-search')" type="button" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition-colors focus:outline-none">
            <i data-lucide="search" class="w-5 h-5"></i>
        </button>

        <!-- Invite Enterprise Partner -->
        @if (auth()->user() && in_array(auth()->user()->role, ['partner', 'coordinator', 'regional_manager'], true))
            <a href="{{ route('enterprise-partner.invite') }}" title="Invite Enterprise Partner"
               class="p-2 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition-colors outline-none">
                <i data-lucide="handshake" class="w-5 h-5"></i>
            </a>
        @endif

        <!-- Notifications Dropdown -->
        <div x-data="{
            open: false,
            badge: {{ count($headerNotifications ?? []) }},
            notifications: {{ json_encode($headerNotifications ?? []) }}
        }" @click.away="open = false" class="relative">
            <!-- Trigger Button -->
            <button @click="open = !open" type="button" class="p-2 rounded-xl text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition-colors relative outline-none">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span x-show="badge > 0" class="absolute top-1.5 right-1.5 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                </span>
            </button>

            <!-- Dropdown Card -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/50 py-2 z-50 outline-none"
                 style="display: none;">
                
                <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-slate-800 font-display">
                            {{ auth()->user() && auth()->user()->isStaff() ? 'System Transactions' : 'Recent Transactions' }}
                        </span>
                        <span x-show="badge > 0" class="px-1.5 py-0.5 text-xs font-bold bg-[#0056D2]/10 text-[#0056D2] rounded-md font-display" x-text="badge"></span>
                    </div>
                    <button x-show="badge > 0" @click="badge = 0; notifications = notifications.map(n => ({ ...n, read: true }))" class="text-xs font-bold text-[#0056D2] hover:underline font-display">
                        Mark all as read
                    </button>
                </div>

                <div class="max-h-72 overflow-y-auto divide-y divide-slate-50">
                    <!-- Empty State -->
                    <div x-show="notifications.length === 0" class="p-6 text-center text-slate-400 text-xs font-medium font-display">
                        <i data-lucide="bell-off" class="w-5 h-5 mx-auto text-slate-300 mb-1.5"></i>
                        No recent transactions.
                    </div>

                    <template x-for="notif in notifications" :key="notif.id">
                        <div class="p-3 hover:bg-slate-50/50 transition-colors flex gap-2.5 items-start">
                            <div class="mt-1 flex-shrink-0 w-2 h-2 rounded-full" :class="notif.read ? 'bg-slate-200' : 'bg-[#0056D2]'"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="block text-xs font-semibold text-slate-700 truncate font-display" x-text="notif.title"></span>
                                    <span class="text-xs text-slate-400 font-medium whitespace-nowrap font-display" x-text="notif.time"></span>
                                </div>
                                <span class="block text-xs text-slate-500 mt-0.5 leading-normal" x-text="notif.desc"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div x-data="{ open: false }" @click.away="open = false" class="relative">
            <!-- Dropdown Trigger -->
            <button @click="open = !open" 
                    type="button" 
                    class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-50 transition-all duration-200 outline-none">
                <!-- User Avatar (Photo or Initials) -->
                @if(Auth::user()->profile_photo)
                    <img src="{{ asset(Auth::user()->profile_photo) }}" 
                         alt="{{ Auth::user()->first_name ?? Auth::user()->name }}" 
                         class="w-8 h-8 rounded-xl object-cover shadow-inner">
                @else
                    <div class="w-8 h-8 rounded-xl bg-[#0056D2]/10 text-[#0056D2] flex items-center justify-center font-bold text-xs font-display shadow-inner">
                        {{ strtoupper(substr(Auth::user()->first_name ?? Auth::user()->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name ?? '', 0, 1)) }}
                    </div>
                @endif
                <!-- User Details (Hidden on tiny screens) -->
                <div class="hidden md:block text-left pr-1">
                    <span class="block text-xs font-semibold text-slate-700 font-display leading-none">{{ Auth::user()->first_name ?? Auth::user()->name }}  {{ Auth::user()->last_name ?? Auth::user()->name }}</span>
                    <span class="block text-xs text-slate-400 font-medium mt-0.5 leading-none">{{ Auth::user()->email }}</span>
                </div>
                <i data-lucide="chevron-down" :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl shadow-slate-200/50 py-1.5 z-50 outline-none"
                 style="display: none;">
                
                <!-- Header details for mobile -->
                <div class="md:hidden px-4 py-2 border-b border-slate-50">
                    <span class="block text-xs font-semibold text-slate-700">{{ Auth::user()->first_name ?? Auth::user()->name }}</span>
                    <span class="block text-xs text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</span>
                </div>

                <!-- Profile Link -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all font-display">
                    <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                    <span>{{ __('My Profile') }}</span>
                </a>

                <div class="border-t border-slate-50 my-1.5"></div>

                <!-- Log Out -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();"
                       class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-500 hover:bg-rose-500/5 transition-all font-display">
                        <i data-lucide="log-out" class="w-4 h-4 text-rose-400"></i>
                        <span>{{ __('Log Out') }}</span>
                    </a>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Mobile/Shortcut Search Command Palette Modal -->
<div x-data="{
    searchOpen: false,
    query: '',
    results: [],
    loading: false,
    activeIndex: -1,
    performSearch() {
        if (this.query.trim().length === 0) {
            this.results = [];
            this.activeIndex = -1;
            return;
        }
        this.loading = true;
        this.activeIndex = -1;
        fetch('/search?q=' + encodeURIComponent(this.query))
            .then(res => res.json())
            .then(data => {
                this.results = data;
                this.loading = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            })
            .catch(() => {
                this.loading = false;
            });
    },
    clearSearch() {
        this.query = '';
        this.results = [];
        this.activeIndex = -1;
        this.$refs.searchInput.focus();
    },
    moveActive(delta) {
        if (!this.results.length) return;
        this.activeIndex = (this.activeIndex + delta + this.results.length) % this.results.length;
        this.$nextTick(() => {
            this.$refs.mobileResultsList?.querySelector('[data-active=\'true\']')?.scrollIntoView({ block: 'nearest' });
        });
    },
    selectActive() {
        if (this.activeIndex > -1 && this.results[this.activeIndex]) {
            window.location.href = this.results[this.activeIndex].url;
        }
    }
}"
@open-search.window="searchOpen = true; $nextTick(() => { setTimeout(() => { $refs.searchInput.focus() }, 50) })"
class="relative">
    <template x-teleport="body">
        <div x-show="searchOpen" 
             class="fixed inset-0 z-[99999] flex items-start justify-center pt-20 px-4 sm:px-6"
             style="display: none;">
            <!-- Backdrop -->
            <div x-show="searchOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="searchOpen = false; query = ''; results = []"
                 class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm"></div>

            <!-- Command Palette Dialog -->
            <div x-show="searchOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="relative w-full max-w-lg bg-white rounded-2xl border border-slate-100 shadow-2xl shadow-slate-900/10 overflow-hidden flex flex-col max-h-[80vh]">
                
                <!-- Header / Search input -->
                <div class="relative border-b border-slate-100 p-4">
                    <i data-lucide="search" class="absolute left-7 top-[23px] w-5 h-5 text-slate-400 z-10"></i>
                    <x-text-input x-model.debounce.250ms="query"
                           @input="performSearch()"
                           @keydown.down.prevent="moveActive(1)"
                           @keydown.up.prevent="moveActive(-1)"
                           @keydown.enter.prevent="selectActive()"
                           @keydown.escape="searchOpen = false; query = ''; results = []"
                           type="text"
                           autocomplete="off"
                           placeholder="Type to search users, services, accounts..."
                           class="pl-10 pr-10 rounded-xl bg-slate-50 focus:bg-white focus:!ring-1"
                           x-ref="searchInput" />
                    <button type="button" x-show="query.length > 0" @click="clearSearch()" style="display:none"
                            class="absolute right-7 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>

                <!-- Content / Results -->
                <div class="flex-1 overflow-y-auto p-3 min-h-[120px]" x-ref="mobileResultsList">
                    <!-- Loading state -->
                    <div x-show="loading" class="flex flex-col items-center justify-center py-10 gap-2 text-slate-400">
                        <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-primary"></i>
                        <span class="text-xs font-semibold font-display">Searching SmartSIM...</span>
                    </div>

                    <!-- Empty / Initial state -->
                    <div x-show="!loading && query.length === 0" class="flex flex-col items-center justify-center py-10 text-slate-400 gap-1.5 text-center">
                        <i data-lucide="command" class="w-8 h-8 text-slate-300"></i>
                        <div>
                            <p class="text-xs font-bold text-slate-500 font-display">Universal Search Palette</p>
                            <p class="text-xs text-slate-400 mt-0.5 leading-relaxed font-display">Search for features, virtual accounts, settings, or users instantly.</p>
                        </div>
                    </div>

                    <!-- No Results found state -->
                    <div x-show="!loading && query.length > 0 && results.length === 0" class="flex flex-col items-center justify-center py-10 text-slate-400 gap-1.5 text-center">
                        <i data-lucide="search-x" class="w-8 h-8 text-slate-300"></i>
                        <div>
                            <p class="text-xs font-bold text-slate-500 font-display">No results found</p>
                            <p class="text-xs text-slate-400 mt-0.5 font-display">We couldn't find anything matching "<span class="font-semibold text-slate-600" x-text="query"></span>".</p>
                        </div>
                    </div>

                    <!-- Results List -->
                    <div x-show="!loading && results.length > 0" class="space-y-4">
                        <template x-for="category in [...new Set(results.map(r => r.category))]" :key="category">
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider px-2.5 mb-1.5 font-display" x-text="category"></span>
                                <div class="space-y-1">
                                    <template x-for="item in results.filter(r => r.category === category)" :key="item.title + item.url">
                                        <a :href="item.url"
                                           :data-active="results[activeIndex] === item"
                                           @mouseenter="activeIndex = results.indexOf(item)"
                                           :class="results[activeIndex] === item ? 'bg-slate-50 text-primary ring-1 ring-primary/15' : 'hover:bg-slate-50 hover:text-slate-900'"
                                           class="flex items-center gap-3 px-3 py-2 rounded-xl group transition-all">
                                            <div :class="results[activeIndex] === item ? 'bg-white text-primary border-primary/20' : 'bg-slate-50 group-hover:bg-white text-slate-500 group-hover:text-primary border-slate-100'"
                                                 class="w-8 h-8 rounded-lg border flex items-center justify-center transition-colors shadow-sm shrink-0">
                                                <i :data-lucide="item.icon" class="w-4 h-4"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <span class="block text-xs font-semibold text-slate-700 group-hover:text-slate-900 truncate font-display" x-text="item.title"></span>
                                                <span class="block text-xs text-slate-400 truncate mt-0.5 leading-none font-display" x-text="item.description"></span>
                                            </div>
                                            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 group-hover:text-slate-500 group-hover:translate-x-0.5 transition-all shrink-0"></i>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer with instructions -->
                <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-2.5 flex items-center justify-between text-xs text-slate-400 font-semibold font-display">
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded-md shadow-sm">Esc</span>
                        <span>to close</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded-md shadow-sm">↵ Enter</span>
                        <span>to select</span>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

