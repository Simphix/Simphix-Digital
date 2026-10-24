<x-app-layout>
    <title>SimPhix - Onboard a {{ str_replace('_', ' ', $nextRole) }}</title>

    <div class="max-w-xl mx-auto space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                </div>
                Onboard a {{ str_replace('_', ' ', $nextRole) }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                They'll receive an email with a link to set their password and activate their account.
            </p>
        </div>

        @if (session('error'))
            <div class="bg-red-50 border border-red-100 rounded-lg p-4 text-red-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <x-card padding="p-6">
            <form method="POST" action="{{ route('onboarding.invite.send') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="first_name" value="First Name" />
                        <x-text-input id="first_name" type="text" name="first_name" :value="old('first_name')" required class="mt-1" />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Last Name" />
                        <x-text-input id="last_name" type="text" name="last_name" :value="old('last_name')" required class="mt-1" />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <x-input-label for="email" value="Email Address" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required class="mt-1" placeholder="name@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone Number (optional)" />
                    <x-text-input id="phone" type="text" name="phone" :value="old('phone')" class="mt-1" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
                </div>

                <div class="pt-2">
                    <x-primary-button type="submit" class="w-full font-display justify-center">
                        <span>Send Invite</span>
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
