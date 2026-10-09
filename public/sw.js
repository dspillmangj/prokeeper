// ProKeeper PWA Service Worker
const CACHE_NAME = 'prokeeper-v1.2';
const STATIC_ASSETS = [
    '/',
    '/install',
    '/scoreboard',
    '/watch',
    '/scorebook',
    '/favicon.svg',
    '/favicon.png',
    '/images/icon-white.svg',
    '/images/icon-blue.svg',
    '/icons/appicon_blue_pwa_192.png',
    '/icons/appicon_blue_pwa_512.png',
    '/icons/appicon_black_pwa_192.png',
    '/icons/appicon_black_pwa_512.png',
    '/icons/appicon_white_pwa_192.png',
    '/icons/appicon_white_pwa_512.png'
];

// Install Event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('PWA Precache failed for some assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event - Network First with Cache Fallback for dynamic data, Cache First for static
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests or Livewire internal requests
    if (request.method !== 'GET' || url.pathname.startsWith('/livewire/')) {
        return;
    }

    // Static assets - Cache first
    if (url.pathname.match(/\.(svg|png|jpg|jpeg|webp|woff2|woff|ttf|css|js)$/)) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
                    }
                    return networkResponse;
                }).catch(() => caches.match('/favicon.svg'));
            })
        );
        return;
    }

    // HTML Pages - Network first, fallback to cache
    event.respondWith(
        fetch(request)
            .then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
                }
                return networkResponse;
            })
            .catch(() => caches.match(request).then((cached) => cached || caches.match('/install')))
    );
});
