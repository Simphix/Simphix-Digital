<header
    x-data="{
        open: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleDark() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('simphix-theme', this.dark ? 'dark' : 'light');
        },
    }"
    class="sticky top-0 z-50 border-b bg-[var(--lp-nav)] backdrop-blur"
    style="border-color: var(--lp-border);"
>
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 lg:px-8" aria-label="Primary">
        <a href="{{ url('/') }}" class="inline-flex items-center">
            <img src="{{ asset('assets/images/logo/logo1.png') }}" alt="SimPhix" class="h-12 w-auto">
        </a>

        <!-- Desktop nav -->
        <div class="hidden items-center gap-8 text-sm font-medium text-[var(--lp-text-soft)] md:flex">
            <a href="#features" class="hover:text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary">Features</a>
            <a href="#pricing" class="hover:text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary">Pricing</a>
            <a href="#faq" class="hover:text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary">FAQ</a>
            <a href="#support" class="hover:text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary">Support</a>
        </div>

        <div class="flex items-center gap-2">
            <!-- Theme toggle -->
            <button
                type="button"
                @click="toggleDark()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--lp-text-soft)] transition hover:bg-[var(--lp-surface-alt)] hover:text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                :aria-label="dark ? 'Switch to light theme' : 'Switch to dark theme'"
            >
                <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
                <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
            </button>

            <!-- Desktop auth actions -->
            <div class="hidden items-center gap-2 md:flex">
                @auth
                    <a href="{{ url('/dashboard') }}" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0049b8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md px-4 py-2 text-sm font-medium text-[var(--lp-text-soft)] transition hover:text-[var(--lp-text)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Log In
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0049b8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Get Started
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Mobile menu toggle -->
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open"
                aria-controls="mobile-menu"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-[var(--lp-text-soft)] hover:bg-[var(--lp-surface-alt)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary md:hidden"
            >
                <span class="sr-only">Toggle navigation menu</span>
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </nav>

    <!-- Mobile menu panel -->
    <div
        id="mobile-menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        @click.outside="open = false"
        class="border-t bg-[var(--lp-nav)] px-6 pb-6 pt-2 backdrop-blur md:hidden"
        style="border-color: var(--lp-border);"
    >
        <div class="flex flex-col gap-1 pt-4 text-base font-medium text-[var(--lp-text-soft)]">
            <a href="#features" @click="open = false" class="rounded-md px-3 py-2.5 transition hover:bg-[var(--lp-surface-alt)] hover:text-[var(--lp-text)]">Features</a>
            <a href="#pricing" @click="open = false" class="rounded-md px-3 py-2.5 transition hover:bg-[var(--lp-surface-alt)] hover:text-[var(--lp-text)]">Pricing</a>
            <a href="#faq" @click="open = false" class="rounded-md px-3 py-2.5 transition hover:bg-[var(--lp-surface-alt)] hover:text-[var(--lp-text)]">FAQ</a>
            <a href="#support" @click="open = false" class="rounded-md px-3 py-2.5 transition hover:bg-[var(--lp-surface-alt)] hover:text-[var(--lp-text)]">Support</a>
        </div>
        <div class="mt-4 flex flex-col gap-3 border-t pt-4" style="border-color: var(--lp-border);">
            @auth
                <a href="{{ url('/dashboard') }}" class="rounded-md bg-primary px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#0049b8]">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-md border px-5 py-3 text-center text-sm font-medium text-[var(--lp-text)] transition hover:bg-[var(--lp-surface-alt)]" style="border-color: var(--lp-border-strong);">
                    Log In
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="rounded-md bg-primary px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#0049b8]">
                        Get Started
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>
