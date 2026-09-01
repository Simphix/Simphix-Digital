<x-guest-layout>
    <div x-data="{ step: '{{ $challenge->channel ? 'code' : 'picker' }}' }">
        <header class="mb-8 text-center">
            <h1 class="text-[26px] font-bold leading-tight tracking-[-0.025em] text-[#15253a]">Verify It's You</h1>
            <p class="mt-2 text-[15px] text-[#526071]">We don't recognize this device. Confirm it's you to finish signing in.</p>
        </header>

        @if (session('success'))
            <div class="mb-5 p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-5 p-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-xl text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

        {{-- Step 1: pick a channel --}}
        <div x-show="step === 'picker'" class="space-y-3">
            @if (empty($availableChannels))
                <p class="text-sm text-slate-500">No verification channel is available for your account. Please contact support.</p>
            @else
                <form method="POST" action="{{ route('device-challenge.send', $challenge->token) }}" x-data="{ channel: null }">
                    @csrf
                    <input type="hidden" name="channel" x-model="channel">

                    <div class="space-y-2.5">
                        @if (in_array('email', $availableChannels))
                            <button type="button" @click="channel = 'email'"
                                    :class="channel === 'email' ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200'"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-xl border transition-all text-left">
                                <i data-lucide="mail" class="w-4.5 h-4.5 text-primary shrink-0"></i>
                                <span class="text-sm font-semibold text-slate-700">Email</span>
                            </button>
                        @endif
                        @if (in_array('sms', $availableChannels))
                            <button type="button" @click="channel = 'sms'"
                                    :class="channel === 'sms' ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200'"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-xl border transition-all text-left">
                                <i data-lucide="phone" class="w-4.5 h-4.5 text-primary shrink-0"></i>
                                <span class="text-sm font-semibold text-slate-700">Text Message (SMS)</span>
                            </button>
                        @endif
                        @if (in_array('push', $availableChannels))
                            <button type="button" @click="channel = 'push'"
                                    :class="channel === 'push' ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200'"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-xl border transition-all text-left">
                                <i data-lucide="bell" class="w-4.5 h-4.5 text-primary shrink-0"></i>
                                <span class="text-sm font-semibold text-slate-700">Notify a trusted device</span>
                            </button>
                        @endif
                    </div>

                    <div class="pt-5">
                        <x-primary-button type="submit" class="w-full justify-center" x-bind:disabled="!channel">
                            Send Code
                        </x-primary-button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Step 2: enter the code --}}
        <div x-show="step === 'code'" x-cloak class="space-y-5">
            <p class="text-sm text-slate-500 text-center">Enter the 6-digit code we sent you.</p>

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

            <button type="button" @click="step = 'picker'" class="w-full text-center text-xs text-slate-400 hover:text-slate-600 transition">
                Choose a different channel
            </button>
        </div>

        <div class="text-center text-sm text-slate-500 pt-6 border-t border-slate-100 mt-6 font-display">
            <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-[#0049b8] transition">
                Back to login
            </a>
        </div>
    </div>
</x-guest-layout>
