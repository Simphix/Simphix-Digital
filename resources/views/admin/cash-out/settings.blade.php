<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight font-display">Cash Out Control</h1>
            <p class="text-xs text-slate-400 mt-1">Master switch for cash outs across the whole platform.</p>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800">Cash out is
                        <span class="{{ $settings->is_enabled ? 'text-emerald-600' : 'text-rose-600' }}">{{ $settings->is_enabled ? 'ON' : 'OFF' }}</span>
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        @if ($settings->is_enabled)
                            Users can request cash outs and admins can approve them.
                        @else
                            New cash outs (users and platform wallets) and approval payouts are blocked. Pending requests can still be rejected and refunded.
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.cash-out.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_enabled" value="{{ $settings->is_enabled ? 0 : 1 }}">
                    <button type="submit" role="switch" aria-checked="{{ $settings->is_enabled ? 'true' : 'false' }}"
                            aria-label="Toggle cash out"
                            onclick="return confirm('{{ $settings->is_enabled ? 'Switch cash out OFF for the whole platform?' : 'Switch cash out back ON?' }}')"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition {{ $settings->is_enabled ? 'bg-emerald-500' : 'bg-slate-300' }}">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $settings->is_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
