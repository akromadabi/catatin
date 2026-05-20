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

// Listen for Push Events
self.addEventListener('push', function (e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (e.data) {
        var msg = e.data.json();
        e.waitUntil(self.registration.showNotification(msg.title, {
            body: msg.body,
            icon: msg.icon || '/icons/icon-192x192.png',
            badge: '/icons/icon-192x192.png',
            data: msg.data || {},
            actions: msg.actions || []
        }));
    }
});

// Handle Notification Clicks
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    var clickResponsePromise = Promise.resolve();
    if (event.notification.data && event.notification.data.url) {
        clickResponsePromise = clients.openWindow(event.notification.data.url);
    } else {
        clickResponsePromise = clients.openWindow('/');
    }

    event.waitUntil(clickResponsePromise);
});
