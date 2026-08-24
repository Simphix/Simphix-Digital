

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

// Push notification opt-in/out, driven by the header's bell-plus/bell-ring
// toggle. Resolves to the resulting subscribed state (true/false).
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content;
}

// Throws on any failure — callers must catch and surface it, rather than
// silently leaving the toggle in a stale state with no saved subscription.
window.togglePushSubscription = async () => {
    const registration = await navigator.serviceWorker.ready;
    const existing = await registration.pushManager.getSubscription();

    if (existing) {
        const res = await fetch('/push/unsubscribe', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ endpoint: existing.endpoint }),
        });
        if (!res.ok) throw new Error(`Failed to unsubscribe on the server (HTTP ${res.status}).`);
        await existing.unsubscribe();
        return false;
    }

    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidKey) {
        throw new Error('Push notifications are not configured on this server yet (no VAPID public key). Ask an admin to run "php artisan notifications:generate-vapid-keys" and set the keys in .env.');
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return false;

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
        // The browser-level subscription succeeded but saving it server-side
        // didn't — undo the browser subscription rather than leaving the UI
        // showing "subscribed" for a device the server never recorded.
        await subscription.unsubscribe();
        throw new Error(`Subscribed in the browser, but saving it on the server failed (HTTP ${res.status}). Not enabled — try again.`);
    }

    return true;
};
