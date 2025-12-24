
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
    const urlToOpen = data.url;

    // Prevent notification if the user is already on the page and has it focused.
    const promiseChain = self.clients.matchAll({
        type: 'window',
        includeUncontrolled: true
    }).then((windowClients) => {
        let clientIsFocused = false;

        for (const windowClient of windowClients) {
            // The URL might have query params, so check if the client URL starts with the base URL.
            if (windowClient.url.startsWith(urlToOpen) && windowClient.focused) {
                clientIsFocused = true;
                break;
            }
        }

        if (clientIsFocused) {
            console.log("Service Worker: Notification not shown because the target window is already focused.");
            return; // Don't show the notification
        }

        // If no client is focused on the target URL, show the notification.
        const options = {
            body: body,
            icon: '/logo.svg',
            badge: '/favicon.ico',
            data: data,
        };
        return self.registration.showNotification(title, options);
    });

    event.waitUntil(promiseChain);
});

// Listener for notification clicks
self.addEventListener('notificationclick', (event) => {
    console.log('Service Worker: Notification clicked.');
    event.notification.close(); // Close the notification

    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus(); // Focus existing tab if URL matches
                }
            }
            // Otherwise, open a new tab
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
            return null;
        })
    );
});
