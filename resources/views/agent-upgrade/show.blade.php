<x-app-layout>
    <title>SimPhix - Upgrade to Agent</title>

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                Upgrade to Agent
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                @if ($notApplicable ?? false)
                    @if ($alreadyAgent ?? false)
                        You're already an Agent — enjoy your perks below.
                    @else
                        Agent upgrade isn't available for your account type.
                    @endif
                @else
                    Keep at least ₦{{ number_format($threshold ?? 0, 2) }} in your wallet to unlock an instant, free upgrade.
                @endif
            </p>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-4 text-emerald-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-rose-800 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500 shrink-0 mt-0.5"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if (!($notApplicable ?? false))
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800 font-display pb-3 border-b border-slate-100">Eligibility</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Your Wallet Balance</p>
                        <p class="font-semibold text-slate-800">₦{{ number_format($balance, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 mb-0.5">Minimum Required</p>
                        <p class="font-semibold text-slate-800">₦{{ number_format($threshold, 2) }}</p>
                    </div>
                </div>

                @if ($eligible)
                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-3 text-xs text-emerald-800 flex items-start gap-2.5">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                        You're eligible — upgrade any time, no debit to your wallet.
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs text-amber-800 flex items-start gap-2.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                        You need ₦{{ number_format(max($threshold - $balance, 0), 2) }} more in your wallet to qualify.
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                <h3 class="text-sm font-semibold text-slate-800 font-display">Agent benefits</h3>
                <ul class="text-xs text-slate-500 space-y-2 list-disc list-inside">
                    <li>Discounted service pricing — pay agent rates on every purchase going forward.</li>
                    <li>Agent-only tools and dashboards built specifically for growing your business.</li>
                    <li>Priority access to new services and promotions as they roll out to agents first.</li>
                </ul>
            </div>

            @if ($eligible)
                <form method="POST" action="{{ route('agent-upgrade.process') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg bg-primary text-white text-sm font-bold shadow-sm transition-all hover:bg-primary/90">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        Upgrade to Agent
                    </button>
                </form>
            @endif
        @endif
    </div>
</x-app-layout>
