

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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

// Push notification opt-in, triggered automatically right after login (see
// the "prompt-push-permission" meta tag below) — no header button; this is
// the only entry point.
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

if (document.querySelector('meta[name="prompt-push-permission"]')) {
    subscribeToPush();
}
