// ProKeeper Ultra-Fast PWA Service Worker (Client-Side UI Shell & Offline Resilience)
const CACHE_NAME = 'prokeeper-v2.0';
const RUNTIME_CACHE = 'prokeeper-runtime-v2.0';

const STATIC_ASSETS = [
    '/',
    '/install',
    '/scoreboard',
    '/watch',
    '/scorebook',
    '/dashboard',
    '/favicon.svg',
    '/favicon.png',
    '/favicon.ico',
    '/images/icon-white.svg',
    '/images/icon-blue.svg',
    '/icons/appicon_blue_pwa_192.png',
    '/icons/appicon_blue_pwa_512.png',
    '/icons/appicon_black_pwa_192.png',
    '/icons/appicon_black_pwa_512.png',
    '/icons/appicon_white_pwa_192.png',
    '/icons/appicon_white_pwa_512.png',
    '/manifest.json',
    '/manifest-blue.json',
    '/manifest-black.json',
    '/manifest-white.json'
];

// Install Event: Pre-cache core UI shell and assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('PWA precache notice for some assets:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event: Clear stale caches and claim clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME && cache !== RUNTIME_CACHE) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests or Livewire mutation endpoints
    if (request.method !== 'GET' || url.pathname.startsWith('/livewire/update')) {
        return;
    }

    // 1. Static UI Assets, Scripts, Styles, Images, Fonts: Cache-First with Background Revalidation
    if (
        url.pathname.match(/\.(svg|png|jpg|jpeg|webp|woff2|woff|ttf|css|js|ico|json)$/) ||
        url.hostname.includes('fonts.bunny.net') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/images/')
    ) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, responseToCache));
                    }
                    return networkResponse;
                }).catch(() => null);

                // Return from cache immediately if available (0ms latency), revalidate in background
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetchPromise.then((res) => res || caches.match('/favicon.svg'));
            })
        );
        return;
    }

    // 2. HTML Navigation & Page UI Shells: Stale-While-Revalidate with Instant Cache Fallback
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            caches.open(RUNTIME_CACHE).then((cache) => {
                return cache.match(request).then((cachedResponse) => {
                    const networkFetch = fetch(request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            cache.put(request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(() => {
                        // Offline or network error: return cached page or fallback UI shell
                        return cachedResponse || caches.match('/install') || caches.match('/');
                    });

                    // If cached page is available, serve it instantly while updating in background
                    return cachedResponse || networkFetch;
                });
            })
        );
        return;
    }

    // Default: Network with Cache Fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
