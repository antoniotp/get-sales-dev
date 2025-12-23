
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

// Listener for push events from the server
self.addEventListener('push', (event) => {
    console.log('Service Worker: Push Received.');

    let pushData = {};
    try {
        pushData = event.data.json();
    } catch (e) {
        console.error('Service Worker: Failed to parse push data as JSON.', e);
        pushData = {
            title: 'New Message',
            body: 'You have a new message.',
            data: { url: '/dashboard' }
        };
    }

    const { title, body, data } = pushData;

    const options = {
        body: body,
        icon: '/logo.svg', // Optional: path to an icon
        badge: '/favicon.ico', // Optional: a smaller icon for the notification bar
        data: data, // This can hold any data, like the URL to open on click
    };

    // showNotification returns a promise that resolves when the notification has been shown.
    // We pass it to event.waitUntil to ensure the Service Worker doesn't terminate before the notification is shown.
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});
