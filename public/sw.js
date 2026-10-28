/**
 * eHadir Progressive Web App (PWA) Service Worker
 * Version: v1.0.0
 *
 * Implements a 3-tier resilient caching strategy tailored for Laravel + Livewire 3:
 *  - Tier 1: Cache-First for static hashed Vite bundles, fonts, and brand icons.
 *  - Tier 2: Network-First (with timeout) for HTML navigations with offline shell fallback.
 *  - Tier 3: Network-Only bypass for dynamic mutations, CSRF tokens, and Livewire state.
 */

const VERSION = '1.0.0';
const STATIC_CACHE = `ehadir-static-v${VERSION}`;
const PAGES_CACHE = `ehadir-pages-v${VERSION}`;
const OFFLINE_CACHE = `ehadir-offline-v${VERSION}`;

const EXPECTED_CACHES = [STATIC_CACHE, PAGES_CACHE, OFFLINE_CACHE];

const PRECACHE_ASSETS = [
    '/offline',
    '/manifest.webmanifest',
    '/manifest.json',
    '/favicon.ico',
    '/icons/icon.svg',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/icon-maskable-192x192.png',
    '/icons/icon-maskable-512x512.png',
    '/icons/apple-touch-icon.png'
];

// Service Worker Installation
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(OFFLINE_CACHE).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[eHadir SW] Precache warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Service Worker Activation & Cache Cleanup
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (!EXPECTED_CACHES.includes(key)) {
                        console.log('[eHadir SW] Purging obsolete cache:', key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Interceptor
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only process HTTP(S) requests from our origin
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Tier 3: Direct Network Bypass for non-GET requests (POST, PUT, DELETE),
    // Livewire internal endpoints, tokenized calendar feeds, or TV kiosk streams
    if (
        request.method !== 'GET' ||
        url.pathname.includes('/livewire/') ||
        url.pathname.includes('/calendar/feed/') ||
        url.pathname.includes('/tv/kiosk/')
    ) {
        return;
    }

    // Tier 1: Static Vite Assets, Fonts, & Icons (Cache-First)
    const isStaticAsset = (
        url.pathname.startsWith('/build/assets/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.png')
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cachedResponse = await cache.match(request);
                if (cachedResponse) {
                    return cachedResponse;
                }

                try {
                    const networkResponse = await fetch(request);
                    if (networkResponse && networkResponse.status === 200) {
                        cache.put(request, networkResponse.clone());
                    }
                    return networkResponse;
                } catch (error) {
                    return cachedResponse || new Response('Asset unavailable offline', { status: 503 });
                }
            })
        );
        return;
    }

    // Tier 2: HTML Page Navigations (Network-First with Timeout & Offline Fallback)
    if (request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    // 3-second network timeout to prevent indefinite spinner on unstable mobile 4G/3G
                    const timeoutPromise = new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('Network timeout')), 3000)
                    );

                    const networkResponse = await Promise.race([
                        fetch(request),
                        timeoutPromise
                    ]);

                    if (networkResponse && networkResponse.status === 200) {
                        return networkResponse;
                    }

                    // If server returns error 5xx, try offline cache
                    const offlineCache = await caches.open(OFFLINE_CACHE);
                    const offlineShell = await offlineCache.match('/offline');
                    return offlineShell || networkResponse;
                } catch (error) {
                    // Offline condition reached: Return branded /offline page
                    const offlineCache = await caches.open(OFFLINE_CACHE);
                    const offlineShell = await offlineCache.match('/offline');
                    if (offlineShell) {
                        return offlineShell;
                    }
                    return new Response('eHadir sedang di luar talian.', {
                        status: 503,
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
                    });
                }
            })()
        );
        return;
    }
});

// Listener for manual update notifications
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

