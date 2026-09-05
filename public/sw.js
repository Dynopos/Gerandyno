/**
 * Deliberately narrow: this worker caches versioned build assets and the
 * app's own images, and nothing else.
 *
 * It must never serve a cached HTML page. Every page here is behind a login
 * and carries a per-session CSRF token, so a stale page means either
 * somebody else's figures on screen or a 419 the moment they submit
 * anything. Navigations therefore go straight to the network — the app is a
 * live sales report, and stale numbers are worse than an offline message.
 */
const CACHE = 'dynopos-static-v1';

// /build is content-hashed by Vite, so a cached entry is never the wrong
// version of itself — a new deploy simply requests new filenames.
const CACHEABLE = [/^\/build\//, /^\/images\//, /^\/favicon\.png$/];

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET' || request.mode === 'navigate') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || !CACHEABLE.some((pattern) => pattern.test(url.pathname))) {
        return;
    }

    event.respondWith(
        caches.match(request).then(
            (cached) =>
                cached ??
                fetch(request).then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE).then((cache) => cache.put(request, copy));
                    }

                    return response;
                })
        )
    );
});
