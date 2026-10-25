<x-app-layout>
    <title>SimPhix - Invite Enterprise Business</title>

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-slate-800 flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <i data-lucide="handshake" class="w-5 h-5"></i>
                </div>
                Invite an Enterprise Business
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Share this link with a business that wants to run its own SIM distribution network. They'll register,
                build an order mixing any SIM categories and networks in stock, buy a minimum of 100 in total to
                activate, and you'll earn a referral bonus once they do.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-slate-800 font-display">Your invite link</h3>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" readonly value="{{ $inviteUrl }}" id="ep-invite-url"
                       class="flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-700 font-mono">
                <button type="button" onclick="copyToClipboard(document.getElementById('ep-invite-url').value, 'Invite link')"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-primary text-white text-xs font-bold shadow-sm transition-all hover:bg-primary/90">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i> Copy
                </button>
            </div>
        </div>

        <div class="bg-amber-50/70 border border-amber-100 rounded-xl p-4 text-amber-800 flex items-start gap-3">
            <i data-lucide="info" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
            <p class="text-xs leading-relaxed">
                Eligibility to become an Enterprise Business requires purchasing a minimum of 100 SIM cards, mixing any
                categories/networks currently in stock. Their dashboard stays locked until that payment clears.
            </p>
        </div>
    </div>
</x-app-layout>
