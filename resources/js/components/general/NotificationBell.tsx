import React, { useState, useEffect } from 'react';
import { Bell, X } from 'lucide-react';
import { usePushNotifications } from '@/hooks/usePushNotifications';
import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

// Key for local storage to track when the prompt was last dismissed
const LAST_DISMISSED_KEY = 'notification_prompt_dismissed_at';
const DISMISS_DURATION_MS = 24 * 60 * 60 * 1000; // 24 hours

export const NotificationBell = () => {
    const { isSupported, permissionStatus, isSubscribed, subscribe, loading, error } = usePushNotifications();
    const [showPrompt, setShowPrompt] = useState(false);

    useEffect(() => {
        // Only show prompt if supported, not yet subscribed, and permission is 'default' (not granted/denied)
        if (isSupported && !isSubscribed && permissionStatus === 'default' && !loading) {
            const lastDismissed = localStorage.getItem(LAST_DISMISSED_KEY);
            if (!lastDismissed || (Date.now() - parseInt(lastDismissed, 10) > DISMISS_DURATION_MS)) {
                setShowPrompt(true);
            }
        } else {
            setShowPrompt(false);
        }
    }, [isSupported, isSubscribed, permissionStatus, loading]);

    const handleSubscribe = async () => {
        await subscribe();
        // Prompt will be hidden by useEffect if subscription is successful or permission is denied
    };

    const handleDismiss = () => {
        localStorage.setItem(LAST_DISMISSED_KEY, Date.now().toString());
        setShowPrompt(false);
    };

    return (
        <>
            <Button
                variant="ghost"
                size="icon"
                onClick={() => {
                    if (!isSupported) {
                        console.log('Push notifications not supported by this browser.');
                        return;
                    }

                    if (isSubscribed) {
                        // TODO: Open notification center (for now, just log)
                        console.log('Already subscribed.');
                    } else if (permissionStatus === 'denied') {
                        // User has explicitly denied. Cannot subscribe unless they change browser settings.
                        console.log('Notifications denied by user. Please change browser settings.');
                    } else if (permissionStatus === 'default') {
                        // Permission is 'default', meaning we can ask the user.
                        setShowPrompt(true);
                    }
                }}
                className="relative"
                disabled={!isSupported} // Disable if push is not supported
            >
                <Bell className="h-5 w-5" />
                {/* {!isSubscribed && permissionStatus === 'default' && isSupported && (
                    <span className="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-500" />
                )} */}
            </Button>

            {/* AlertDialog for the subscription prompt */}
            <AlertDialog open={showPrompt} onOpenChange={setShowPrompt}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        {/*<AlertDialogTitle>¿Quieres recibir notificaciones?</AlertDialogTitle>*/}
                        <AlertDialogTitle>Do you want to receive notifications?</AlertDialogTitle>
                        <AlertDialogDescription>
                            We'll send you notifications when you receive new messages, even when the app isn't open.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={handleDismiss}>Not for now</AlertDialogCancel>
                        <AlertDialogAction onClick={handleSubscribe} disabled={loading}>
                            {loading ? 'Enabling...' : 'Yes, please!'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Optional: Error Toast/Alert */}
            {error && (
                <div className="fixed bottom-4 right-4 bg-red-500 text-white p-3 rounded-md shadow-lg flex items-center gap-2">
                    <span>Error: {error}</span>
                    <Button variant="ghost" size="icon" onClick={() => setShowPrompt(false)} className="h-auto w-auto p-1">
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            )}
        </>
    );
};
