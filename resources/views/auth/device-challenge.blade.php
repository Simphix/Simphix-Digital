<x-guest-layout>
    <div>
        <header class="mb-8 text-center">
            <div class="w-12 h-12 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="mail-check" class="w-6 h-6"></i>
            </div>
            <h1 class="text-[26px] font-bold leading-tight tracking-[-0.025em] text-[#15253a]">Verify It's You</h1>
            <p class="mt-2 text-[15px] text-[#526071]">
                We've sent a 6-digit verification code to <span class="font-semibold text-slate-800">{{ $challenge->user->email }}</span>. Enter the code below to finish signing in.
            </p>
        </header>

        @if (session('success'))
            <div class="mb-5 p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold text-center">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-5 p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl text-xs font-semibold text-center">
                {{ session('error') }}
            </div>
        @endif

        <div class="space-y-6">
            <form method="POST" action="{{ route('device-challenge.verify', $challenge->token) }}" class="space-y-6">
                @csrf
                <x-input-error :messages="$errors->get('code')" class="text-center" />
                <x-pin-input name="code" :length="6" :error="$errors->has('code')" />

                <x-primary-button type="submit" class="w-full justify-center">
                    Verify &amp; Continue
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('device-challenge.resend', $challenge->token) }}" class="text-center">
                @csrf
                <button type="submit" class="text-xs font-semibold text-primary hover:text-[#0049b8] transition">
                    Didn't get a code? Resend
                </button>
            </form>
        </div>

        <div class="text-center text-sm text-slate-500 pt-6 border-t border-slate-100 mt-6 font-display">
            <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-[#0049b8] transition">
                Back to login
            </a>
        </div>
    </div>
</x-guest-layout>
