const CACHE = 'simphix-static-v1';
const STATIC_ASSETS = ['/manifest.json'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);
    // Only ever cache same-origin, immutable-by-filename build assets and icons.
    // Everything else (pages, API calls, forms) goes straight to the network,
    // untouched — never intercept anything that could serve stale financial data.
    if (e.request.method !== 'GET' || url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/assets/images/icons/')) {
        e.respondWith(
            caches.match(e.request).then((cached) => cached || fetch(e.request).then((res) => {
                const clone = res.clone();
                caches.open(CACHE).then((c) => c.put(e.request, clone));
                return res;
            }))
        );
    }
});

self.addEventListener('push', (e) => {
    const data = e.data ? e.data.json() : {};
    e.waitUntil(self.registration.showNotification(data.title || 'SimPhix', {
        body: data.body || '',
        icon: data.icon || '/assets/images/icons/icon-192.png',
        badge: '/assets/images/icons/icon-192.png',
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const url = e.notification.data?.url || '/';
    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            const existing = list.find((c) => c.url === url);
            return existing ? existing.focus() : clients.openWindow(url);
        })
    );
});
