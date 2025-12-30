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

    // Check current subscription status on mount
    useEffect(() => {
        if (!state.isSupported) {
            setState((prev) => ({ ...prev, loading: false }));
            return;
        }

        const checkSubscription = async () => {
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();
                setState((prev) => ({
                    ...prev,
                    isSubscribed: !!subscription,
                    loading: false,
                }));
            } catch (err) {
                console.error('Error checking subscription:', err);
                setState((prev) => ({ ...prev, error: 'Failed to check subscription status.', loading: false }));
            }
        };

        checkSubscription();
    }, [state.isSupported]);

    // Function to subscribe to push notifications
    const subscribe = useCallback(async () => {
        if (!state.isSupported || state.permissionStatus === 'denied' || state.loading) {
            return;
        }

        setState((prev) => ({ ...prev, loading: true, error: null }));

        try {
            const permission = await Notification.requestPermission();
            setState((prev) => ({ ...prev, permissionStatus: permission }));

            if (permission === 'granted') {
                const registration = await navigator.serviceWorker.ready;
                const existingSubscription = await registration.pushManager.getSubscription();

                if (existingSubscription) {
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

                // Send subscription to backend
                await axios.post(route('notifications.subscriptions.store'), subscription.toJSON());

                setState((prev) => ({ ...prev, isSubscribed: true, loading: false }));
            } else {
                setState((prev) => ({ ...prev, error: 'Notification permission denied.', loading: false }));
            }
        } catch (err) {
            console.error('Error subscribing to push notifications:', err);
            setState((prev) => ({ ...prev, error: 'Failed to subscribe to notifications.', loading: false }));
        }
    }, [state.isSupported, state.permissionStatus, state.loading, vapidPublicKey]);

    // Function to unsubscribe from push notifications
    const unsubscribe = useCallback(async () => {
        if (!state.isSupported || state.loading) {
            return;
        }

        setState((prev) => ({ ...prev, loading: true, error: null }));

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();
                await axios.delete(route('notifications.subscriptions.destroy'), { data: subscription.toJSON() });

                setState((prev) => ({ ...prev, isSubscribed: false, loading: false }));
            } else {
                setState((prev) => ({ ...prev, loading: false })); // Already unsubscribed
            }
        } catch (err) {
            console.error('Error unsubscribing from push notifications:', err);
            setState((prev) => ({ ...prev, error: 'Failed to unsubscribe from notifications.', loading: false }));
        }
    }, [state.isSupported, state.loading]);

    return {
        ...state,
        subscribe,
        unsubscribe,
    };
};
