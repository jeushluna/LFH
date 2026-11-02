const CACHE_NAME = "lorie-food-hub-pos-v3";

const FILES_TO_CACHE = [
    "/lorie-food-hub-pos/manifest.json",
    "/lorie-food-hub-pos/assets/css/style.css"
];

self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(FILES_TO_CACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener("activate", event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            );
        })
        .then(() => self.clients.claim())
    );
});

self.addEventListener("fetch", event => {

    // Do not intercept PHP page/navigation requests.
    if (event.request.mode === "navigate") {
        return;
    }

    // Only handle GET requests.
    if (event.request.method !== "GET") {
        return;
    }

    // Only cache/serve the specific static files we want.
    const url = new URL(event.request.url);

    const isStaticFile =
        url.pathname === "/lorie-food-hub-pos/manifest.json" ||
        url.pathname === "/lorie-food-hub-pos/assets/css/style.css";

    if (!isStaticFile) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                return cachedResponse || fetch(event.request);
            })
    );
});