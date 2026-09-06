

import Alpine from 'alpinejs';

import './charts';

window.Alpine = Alpine;

Alpine.start();

// Registers the service worker that backs the installable app. It caches
// static assets only — never pages — so an installed DynoPOS can never show
// yesterday's figures or a stale CSRF token (see public/sw.js).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // An unregistered worker costs nothing but the install prompt;
            // the app itself works exactly the same without it.
        });
    });
}
