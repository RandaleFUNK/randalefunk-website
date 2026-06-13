const CACHE_NAME = "randale-wuerfel-v1";

const CACHE_URLS = [
  "./",
  "index.html",
  "styles.css",
  "app.js",
  "manifest.json",
  "data/quizfragen.json",
  "data/quizfragen-data.js",
  "assets/audio/rf-intro-short.wav",
  "assets/fonts/zp-sidestep.otf",
  "assets/images/animationsgrundlage.webp",
  "assets/images/app-icon.png",
  "assets/images/favicon-16x16.png",
  "assets/images/favicon-32x32.png",
  "assets/images/game-over.webp",
  "assets/images/icon-192.png",
  "assets/images/icon-512.png",
  "assets/images/randalefunk-logo.webp",
  "assets/video/mittelfinger.mp4",
  "assets/video/trinkanimation.mp4",
  "assets/video/wuerfelanimation.mp4"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => cache.addAll(CACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((cacheNames) => Promise.all(
        cacheNames
          .filter((cacheName) => cacheName !== CACHE_NAME)
          .map((cacheName) => caches.delete(cacheName))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(event.request)
        .then((networkResponse) => {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
          return networkResponse;
        })
        .catch(() => {
          if (event.request.mode === "navigate") {
            return caches.match("index.html");
          }
          return undefined;
        });
    })
  );
});
