
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installed');
    // Force immediate activation of the Service Worker.
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activated');
    // Claim all the clients so that the new Service Worker takes control immediately.
    event.waitUntil(self.clients.claim());
});
