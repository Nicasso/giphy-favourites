'use strict';

// Giphy service worker — makes the site installable and fast/offline-tolerant.
//
// Strategies:
//   • App shell (CSS/JS/vendor): stale-while-revalidate, in a versioned cache.
//   • HTML navigations: network-first (so a deploy's fresh markup + the mtime
//     ?v= asset URLs it points at are delivered), cached '/' as offline fallback.
//   • /fetch-giphys.php: network-only — it's a dynamic action, never cached.
//   • Cross-origin (api.giphy.com / media.giphy.com / any CDN): passed through.
// Bump VERSION whenever this file changes so the new worker installs.

const VERSION = 'giphy-v1';
const SHELL = `shell-${VERSION}`;

const SHELL_ASSETS = [
  '/css/style.css',
  '/js/script.js',
  '/vendor/jquery.min.js',
  '/vendor/bootstrap.min.css',
  '/vendor/bootstrap.bundle.min.js',
  '/vendor/masonry.pkgd.min.js',
  '/vendor/imagesloaded.pkgd.min.js',
  '/site.webmanifest',
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(SHELL).then((c) => c.addAll(SHELL_ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.endsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const { request } = e;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== location.origin) return; // giphy api/media + CDNs: pass through

  // Dynamic fetch action: never cache, always hit the network.
  if (url.pathname === '/fetch-giphys.php') return;

  // HTML navigations: network-first, cache the latest '/' for offline fallback.
  if (request.mode === 'navigate') {
    e.respondWith(
      fetch(request)
        .then((res) => { const copy = res.clone(); caches.open(SHELL).then((c) => c.put('/', copy)); return res; })
        .catch(() => caches.match(request).then((r) => r || caches.match('/')))
    );
    return;
  }

  // App shell: stale-while-revalidate.
  e.respondWith(
    caches.open(SHELL).then(async (cache) => {
      const hit = await cache.match(request);
      const fetched = fetch(request).then((res) => { if (res.ok) cache.put(request, res.clone()); return res; }).catch(() => hit);
      return hit || fetched;
    })
  );
});
