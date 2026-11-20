/**
 * Precious Pakistan 2.0 Safe Service Worker
 *
 * Security & Correctness Invariants:
 * - Supports offline reading ONLY for explicitly downloaded itinerary packs (`/bookings/.../itinerary-offline`)
 *   and public static assets.
 * - STRICTLY BYPASSES all admin routes (`/admin`), authentication (`/login`, `/register`, `/logout`),
 *   private dashboards (`/dashboard`), invoices/payments (`/invoices`, `/verify`), and all non-GET mutations!
 */
const ITINERARY_CACHE = 'pp-offline-itineraries-v1';

const NEVER_CACHE_PREFIXES = [
    '/admin',
    '/dashboard',
    '/login',
    '/register',
    '/logout',
    '/forgot-password',
    '/reset-password',
    '/invoices',
    '/livewire',
    '/cms/preview',
];

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    for (const prefix of NEVER_CACHE_PREFIXES) {
        if (url.pathname.startsWith(prefix)) {
            return;
        }
    }

    // Only serve cached fallback for explicitly downloaded offline itinerary packs.
    if (/^\/bookings\/[^/]+\/itinerary-offline$/.test(url.pathname)) {
        event.respondWith(
            fetch(request).catch(() => caches.open(ITINERARY_CACHE).then((cache) => cache.match(request)))
        );
    }
});
