self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open('v1').then((cache) => {
            return cache.addAll([
//                '/',
//                'index.html',
//                'static/css/style.css',
                'manifest.json',
                'static/icons/icon-192x192.png',
                'static/icons/icon-144x144.png',
                'static/icons/icon-512x512.png',
                // Add any other assets you want to cache
            ]);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
