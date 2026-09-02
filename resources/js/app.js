

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global "Copied!" snackbar — one shared store + helper so every copy
// button in the app gets identical feedback with a one-line @click, instead
// of each page reinventing its own toast/text-swap/SweetAlert.
document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        show: false,
        message: '',
        _timer: null,
        display(message) {
            this.message = message;
            this.show = true;
            clearTimeout(this._timer);
            this._timer = setTimeout(() => { this.show = false; }, 2500);
        },
    });
});

window.copyToClipboard = function (text, label) {
    navigator.clipboard.writeText(text).then(() => {
        Alpine.store('toast').display((label ? label + ' ' : '') + 'copied to clipboard!');
    });
};

Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
    window.dispatchEvent(new CustomEvent('pwa-installable'));
});
window.promptPwaInstall = async () => {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-install-resolved'));
};

// Push notification opt-in — no header button. Auto-prompts on every
// authenticated page load, then keeps retrying on a short interval while
// the tab stays open, so a user who dismissed or missed the browser's
// permission bubble keeps getting another chance rather than only ever
// being asked once at login.
const PUSH_RETRY_INTERVAL_MS = 2 * 60 * 1000; // 2 minutes

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

// Idempotent and silent on failure — safe to call on every login without
// ever un-subscribing an already-subscribed device or nagging a user who
// has already denied permission (requestPermission() just no-ops for them).
async function subscribeToPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    if (Notification.permission === 'denied') return;

    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidKey) {
        console.warn('Push notifications are not configured on this server (no VAPID public key) — skipping the enable prompt.');
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        if (await registration.pushManager.getSubscription()) return; // already subscribed

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });

        const res = await fetch('/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify(subscription.toJSON()),
        });

        if (!res.ok) {
            // Saved nowhere — undo the browser-level subscription rather
            // than leaving a phantom one the server doesn't know about.
            await subscription.unsubscribe();
            console.error(`Push subscribe failed to save on the server (HTTP ${res.status}).`);
        }
    } catch (e) {
        console.error('Push auto-subscribe failed:', e);
    }
}

// The vapid-public-key meta tag is only rendered in the authenticated app
// layout (never on guest/landing pages), so its presence doubles as "this
// is a logged-in page where prompting makes sense."
if (document.querySelector('meta[name="vapid-public-key"]')) {
    subscribeToPush();
    setInterval(subscribeToPush, PUSH_RETRY_INTERVAL_MS);
}
