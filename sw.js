// IMPORTANTE: Cambiar esta versión cada vez que actualices CSS/JS
const CACHE_VERSION = 'v2';
const CACHE_NAME = 'tarfia-' + CACHE_VERSION;

// Solo cachear recursos externos (CDNs) que no cambian
const STATIC_ASSETS = [
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
  'https://code.jquery.com/jquery-3.7.1.min.js'
];

// Instalar: cachear solo CDNs
self.addEventListener('install', event => {
  console.log('SW: Instalando versión', CACHE_VERSION);
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .catch(err => console.log('SW install error:', err))
  );
  self.skipWaiting();
});

// Mensaje para forzar activación
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Activar: BORRAR todos los cachés antiguos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name.startsWith('tarfia-') && name !== CACHE_NAME)
          .map(name => {
            console.log('SW: Borrando caché antiguo:', name);
            return caches.delete(name);
          })
      );
    })
  );
  // Tomar control de todas las páginas inmediatamente
  self.clients.claim();
});

// Fetch: NETWORK FIRST siempre para archivos locales
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  
  const url = new URL(event.request.url);
  
  // Ignorar APIs completamente
  if (url.pathname.includes('/api/')) return;
  
  // Para archivos CSS/JS locales: SIEMPRE ir a la red primero
  const isLocalAsset = url.pathname.endsWith('.css') || 
                       url.pathname.endsWith('.js') || 
                       url.pathname.endsWith('.php');
  
  if (isLocalAsset && url.origin === self.location.origin) {
    event.respondWith(
      fetch(event.request)
        .then(response => response)
        .catch(() => caches.match(event.request))
    );
    return;
  }
  
  // Para CDNs: Cache first (no cambian)
  if (url.origin !== self.location.origin) {
    event.respondWith(
      caches.match(event.request)
        .then(cached => cached || fetch(event.request))
    );
    return;
  }
  
  // Resto: Network first con fallback
  event.respondWith(
    fetch(event.request)
      .catch(() => caches.match(event.request))
      .then(response => {
        if (response) return response;
        return new Response(
          '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sin conexión</title><style>body{font-family:system-ui;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#1a2744;color:#fff;text-align:center;}h1{color:#f7b32b;}</style></head><body><div><h1>Sin conexión</h1><p>Comprueba tu conexión a internet.</p></div></body></html>',
          { headers: { 'Content-Type': 'text/html' } }
        );
      })
  );
});
