import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { AlertCircle, BellOff, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatDistanceToNow, parseISO } from 'date-fns';

interface Notification {
    id: string;
    title: string;
    body: string;
    url: string;
    read_at: string | null;
    created_at: string;
}

interface NotificationDropdownProps {
    onClose: () => void;
}

export const NotificationDropdown = ({ onClose }: NotificationDropdownProps) => {
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fetchNotifications = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await axios.get(route('notifications.index'));
            setNotifications(response.data.data);
        } catch (err) {
            console.error('Failed to fetch notifications:', err);
            setError('Failed to load notifications.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchNotifications();
    }, []);

    const markAsRead = async (notificationId: string) => {
        try {
            await axios.post(route('notifications.mark-as-read', notificationId));
            setNotifications((prev) =>
                prev.map((notif) => (notif.id === notificationId ? { ...notif, read_at: new Date().toISOString() } : notif))
            );
        } catch (err) {
            console.error('Failed to mark notification as read:', err);
            setError('Failed to mark notification as read.');
        }
    };

    const markAllAsRead = async () => {
        try {
            await axios.post(route('notifications.mark-all-as-read'));
            setNotifications((prev) =>
                prev.map((notif) => (notif.read_at === null ? { ...notif, read_at: new Date().toISOString() } : notif))
            );
        } catch (err) {
            console.error('Failed to mark all notifications as read:', err);
            setError('Failed to mark all notifications as read.');
        }
    };

    return (
        <div>
            <div className="flex items-center justify-between p-4">
                <h4 className="font-medium text-sm">Notifications</h4>
                {notifications.some((n) => !n.read_at) && (
                    <Button variant="link" size="sm" onClick={markAllAsRead}>
                        Mark all as read
                    </Button>
                )}
            </div>
            <Separator />
            <ScrollArea className="h-72">
                {loading ? (
                    <div className="p-4 flex justify-center items-center text-muted-foreground">
                        <Loader2 className="h-5 w-5 animate-spin mr-2" /> Loading...
                    </div>
                ) : error ? (
                    <div className="p-4 text-destructive flex items-center">
                        <AlertCircle className="h-5 w-5 mr-2" /> {error}
                    </div>
                ) : notifications.length === 0 ? (
                    <div className="p-4 text-muted-foreground text-center">
                        <BellOff className="h-8 w-8 mx-auto mb-2" />
                        No new notifications.
                    </div>
                ) : (
                    <div className="flex flex-col">
                        {notifications.map((notification) => (
                            <Link
                                key={notification.id}
                                href={notification.url}
                                onClick={() => {
                                    markAsRead(notification.id);
                                    onClose(); // Close dropdown after clicking notification
                                }}
                                className={cn(
                                    "flex flex-col gap-1 p-3 border-b hover:bg-muted/50",
                                    { 'bg-accent/20 font-semibold': !notification.read_at }
                                )}
                            >
                                <p className="text-sm">{notification.title}</p>
                                <p className="text-xs text-muted-foreground line-clamp-2">{notification.body}</p>
                                <p className="text-xs text-muted-foreground">
                                    {formatDistanceToNow(parseISO(notification.created_at), { addSuffix: true })}
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </ScrollArea>
        </div>
    );
};
