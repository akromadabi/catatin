const CACHE_NAME = 'catatin-pwa-v1';

self.addEventListener('install', event => {
    // Skip waiting to immediately activate the new service worker
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    // Claim clients to immediately control all open tabs
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    // Just a basic pass-through fetch handler
    // This is required for the PWA install prompt to trigger
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});
