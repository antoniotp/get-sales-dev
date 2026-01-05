import React, { useState, useEffect, useRef, useCallback } from 'react';
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
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { NotificationDropdown } from './NotificationDropdown';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export const NotificationBell = () => {
    const { isSupported, permissionStatus, isSubscribed, subscribe, loading, error } = usePushNotifications();
    const [showPrompt, setShowPrompt] = useState(false);
    const [showNotificationDropdown, setShowNotificationDropdown] = useState(false);
    const [unreadCount, setUnreadCount] = useState(0);
    const { props } = usePage<PageProps>();
    const { auth } = props;

    const originalDocumentTitle = useRef(document.title); // Store original title

    const fetchUnreadCount = useCallback(async () => {
        if (!auth?.user) return;
        try {
            const response = await axios.get(route('notifications.status'));
            setUnreadCount(response.data.unread_count);
        } catch (err) {
            console.error('Failed to fetch unread notification count:', err);
        }
    }, [auth?.user]);

    useEffect(() => {
        fetchUnreadCount();

        if (!showNotificationDropdown) {
            fetchUnreadCount();
        }
    }, [showNotificationDropdown, auth?.user, fetchUnreadCount]);

    // Effect for updating document title
    useEffect(() => {
        if (unreadCount > 0) {
            document.title = `(${unreadCount}) ${originalDocumentTitle.current}`;
        } else {
            document.title = originalDocumentTitle.current;
        }
    }, [unreadCount]);

    const handleSubscribe = async () => {
        await subscribe();
        setShowPrompt(false);
        fetchUnreadCount();
    };

    return (
        <>
            <Popover open={showNotificationDropdown} onOpenChange={setShowNotificationDropdown}>
                <PopoverTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => {
                            if (!isSupported) {
                                console.log('Push notifications not supported by this browser.');
                                return;
                            }

                            if (isSubscribed) {
                                // Toggle dropdown visibility if already subscribed
                                setShowNotificationDropdown((prev) => !prev);
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
                        {unreadCount > 0 && (
                            <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                                {unreadCount > 99 ? '99+' : unreadCount}
                            </span>
                        )}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-80 p-0" align="end">
                    <NotificationDropdown onClose={() => setShowNotificationDropdown(false)} onUpdateUnreadCount={setUnreadCount} />
                </PopoverContent>
            </Popover>


            {/* AlertDialog for the subscription prompt */}
            <AlertDialog open={showPrompt} onOpenChange={setShowPrompt}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Do you want to receive notifications?</AlertDialogTitle>
                        <AlertDialogDescription>
                            We'll send you notifications when you receive new messages, even when the app isn't open.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Not for now</AlertDialogCancel>
                        <AlertDialogAction onClick={handleSubscribe} disabled={loading}>
                            {loading ? 'Enabling...' : 'Yes, please!'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

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
