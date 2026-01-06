import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

// Helper function to convert VAPID public key to Uint8Array
const urlBase64ToUint8Array = (base64String: string) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
};

interface PushSubscriptionState {
    isSupported: boolean;
    permissionStatus: NotificationPermission;
    isSubscribed: boolean;
    error: string | null;
    loading: boolean;
}

export const usePushNotifications = () => {
    const isPushSupported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    const [state, setState] = useState<PushSubscriptionState>({
        isSupported: isPushSupported,
        permissionStatus: isPushSupported ? Notification.permission : 'denied',
        isSubscribed: false,
        error: null,
        loading: true,
    });

    const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;

    // Register service worker and check for existing subscription on mount
    useEffect(() => {
        if (!isPushSupported) {
            console.log('Push notifications are not supported by this browser.');
            setState((prev) => ({ ...prev, loading: false }));
            return;
        }

        const registerAndCheckSubscription = async () => {
            try {
                console.log('Registering service worker...');
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('ServiceWorker registration successful.');

                await navigator.serviceWorker.ready;
                console.log('Service worker is ready, checking subscription...');

                const subscription = await registration.pushManager.getSubscription();
                setState((prev) => ({
                    ...prev,
                    isSubscribed: !!subscription,
                    loading: false,
                }));
                console.log('Subscription check complete. Is subscribed:', !!subscription);
            } catch (err) {
                console.error('Error during service worker registration or subscription check:', err);
                let errorMessage = 'Failed to initialize push notifications.';
                if (err instanceof Error) errorMessage = err.message;
                setState((prev) => ({ ...prev, error: errorMessage, loading: false }));
            }
        };

        registerAndCheckSubscription();
    }, [isPushSupported]);

    // Function to subscribe to push notifications
    const subscribe = useCallback(async () => {
        if (!state.isSupported || state.permissionStatus === 'denied' || state.loading) {
            console.log('Subscribe conditions not met:', { isSupported: state.isSupported, permissionStatus: state.permissionStatus, loading: state.loading });
            return;
        }
        console.log('Attempting to subscribe...');
        setState((prev) => ({ ...prev, loading: true, error: null }));

        try {
            const permission = await Notification.requestPermission();
            setState((prev) => ({ ...prev, permissionStatus: permission }));
            console.log('Permission requested. Result:', permission);

            if (permission === 'granted') {
                const registration = await navigator.serviceWorker.ready;
                const existingSubscription = await registration.pushManager.getSubscription();

                if (existingSubscription) {
                    console.log('Already subscribed with existing subscription.');
                    setState((prev) => ({ ...prev, isSubscribed: true, loading: false }));
                    return;
                }

                if (!vapidPublicKey) {
                    throw new Error('VAPID Public Key not defined in environment variables.');
                }

                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey,
                });
                console.log('Service Worker subscribed. Sending to backend...');
                // Send subscription to backend
                await axios.post(route('notifications.subscriptions.store'), subscription.toJSON());

                setState((prev) => ({ ...prev, isSubscribed: true, loading: false }));
                console.log('Subscription successful!');
            } else {
                console.log('Permission not granted. Status:', permission);
                setState((prev) => ({ ...prev, error: 'Notification permission denied or dismissed.', loading: false }));
            }
        } catch (err) {
            console.error('Error subscribing to push notifications:', err);
            let errorMessage = 'Failed to subscribe to notifications.';
            if (err instanceof Error) errorMessage = err.message;
            else if (axios.isAxiosError(err) && err.response) errorMessage = err.response.data.message || err.message;
            else if (typeof err === 'string') errorMessage = err;

            setState((prev) => ({ ...prev, error: errorMessage, loading: false }));
        }
    }, [state.isSupported, state.permissionStatus, state.loading, vapidPublicKey]);

    // Function to unsubscribe from push notifications
    const unsubscribe = useCallback(async () => {
        if (!state.isSupported || state.loading) {
            console.log('Unsubscribe conditions not met:', { isSupported: state.isSupported, loading: state.loading });
            return;
        }
        console.log('Attempting to unsubscribe...');
        setState((prev) => ({ ...prev, loading: true, error: null }));

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();
                console.log('Service Worker unsubscribed. Notifying backend...');
                await axios.delete(route('notifications.subscriptions.destroy'), { data: subscription.toJSON() });

                setState((prev) => ({ ...prev, isSubscribed: false, loading: false }));
                console.log('Unsubscription successful!');
            } else {
                console.log('Already unsubscribed or no active subscription.');
                setState((prev) => ({ ...prev, loading: false })); // Already unsubscribed
            }
        } catch (err) {
            console.error('Error unsubscribing from push notifications:', err);
            let errorMessage = 'Failed to unsubscribe from notifications.';
            if (err instanceof Error) errorMessage = err.message;
            else if (axios.isAxiosError(err) && err.response) errorMessage = err.response.data.message || err.message;
            else if (typeof err === 'string') errorMessage = err;

            setState((prev) => ({ ...prev, error: errorMessage, loading: false }));
        }
    }, [state.isSupported, state.loading]);

    return {
        ...state,
        subscribe,
        unsubscribe,
    };
};
