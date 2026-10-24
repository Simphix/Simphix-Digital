<div x-data="{
        show: false,
        dismissedRecently() {
            const dismissedAt = localStorage.getItem('pwa-install-dismissed-at');
            return dismissedAt && (Date.now() - Number(dismissedAt)) < 7 * 24 * 60 * 60 * 1000;
        },
     }"
     x-init="
        window.addEventListener('pwa-installable', () => { if (!dismissedRecently()) show = true });
        window.addEventListener('pwa-install-resolved', () => show = false);
     "
     x-show="show" x-cloak
     class="fixed bottom-4 inset-x-4 sm:left-auto sm:right-4 sm:w-80 z-50 bg-white rounded-xl border border-slate-200 shadow-xl p-4 flex items-start gap-3">
    <img src="{{ asset('assets/images/icons/icon-192.png') }}" alt="" class="w-10 h-10 rounded-lg shrink-0">
    <div class="flex-1 min-w-0">
        <p class="text-sm font-bold text-slate-800 font-display">Install SimPhix</p>
        <p class="text-xs text-slate-500 mt-0.5">Add SimPhix to your home screen for quick, full-screen access.</p>
        <div class="flex gap-2 mt-3">
            <button type="button" @click="window.promptPwaInstall(); show = false"
                    class="text-xs font-bold px-3 py-1.5 rounded-lg bg-primary text-white hover:bg-[#0049b8] transition-colors">Install</button>
            <button type="button" @click="show = false; localStorage.setItem('pwa-install-dismissed-at', Date.now())"
                    class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50 transition-colors">Not now</button>
        </div>
    </div>
</div>
