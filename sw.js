const CACHE_NAME = 'tarot-pwa-v1';
const ASSETS_TO_CACHE = [
    './',
    './index.php',
    './assets/css/style.css',
    './assets/js/app.js',
    './manifest.json',
    'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Lato:wght@300;400&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(ASSETS_TO_CACHE))
    );
});

self.addEventListener('fetch', (event) => {
    // Basic cache-first strategy for statics, network-first for API
    if (event.request.url.includes('/api/')) {
        event.respondWith(fetch(event.request));
    } else {
        event.respondWith(
            caches.match(event.request)
                .then((response) => response || fetch(event.request))
        );
    }
});
