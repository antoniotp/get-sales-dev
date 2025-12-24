import React, { useEffect, useState } from 'react';
import { usePushNotifications } from '@/hooks/usePushNotifications';
import HeadingSmall from '@/components/heading-small';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Info, AlertCircle } from 'lucide-react';

export const UpdateNotificationsSettings = () => {
    const { isSupported, permissionStatus, isSubscribed, subscribe, unsubscribe, loading, error } = usePushNotifications();
    const [localIsSubscribed, setLocalIsSubscribed] = useState(isSubscribed);

    // Sync local state with hook's isSubscribed status
    useEffect(() => {
        setLocalIsSubscribed(isSubscribed);
    }, [isSubscribed]);

    const handleToggle = async (checked: boolean) => {
        if (loading) return;

        if (checked) {
            await subscribe();
        } else {
            await unsubscribe();
        }
    };

    let statusMessage = '';
    let statusVariant: 'default' | 'destructive' | null | undefined;
    let statusIcon = null;

    if (!isSupported) {
        statusMessage = "Push notifications are not supported by your browser.";
        statusVariant = 'destructive';
        statusIcon = <AlertCircle className="h-4 w-4" />;
    } else if (permissionStatus === 'denied') {
        statusMessage = "You have blocked notifications. Please enable them in your browser settings to receive updates.";
        statusVariant = 'destructive';
        statusIcon = <AlertCircle className="h-4 w-4" />;
    } else if (permissionStatus === 'default' && !isSubscribed) {
        statusMessage = "Enable push notifications to get real-time updates for new messages.";
        statusVariant = 'default';
        statusIcon = <Info className="h-4 w-4" />;
    } else if (isSubscribed) {
        statusMessage = "Push notifications are enabled. You will receive updates.";
        statusVariant = 'default';
    }


    return (
        <div className="space-y-6">
            <HeadingSmall title="Push Notifications" description="Manage your real-time message notifications." />

            {!isSupported || permissionStatus === 'denied' || (permissionStatus === 'default' && !isSubscribed) ? (
                <Alert variant={statusVariant}>
                    {statusIcon}
                    <AlertTitle>Notification Status</AlertTitle>
                    <AlertDescription>
                        {statusMessage}
                        {(permissionStatus === 'default' && isSupported) && (
                            <Button onClick={() => subscribe()} disabled={loading} className="ml-4">
                                {loading ? 'Enabling...' : 'Enable Now'}
                            </Button>
                        )}
                        {error && <p className="text-red-500 mt-2">Error: {error}</p>}
                    </AlertDescription>
                </Alert>
            ) : (
                <div className="flex items-center space-x-2">
                    <Switch
                        id="push-notifications-toggle"
                        checked={localIsSubscribed}
                        onCheckedChange={handleToggle}
                        disabled={loading}
                    />
                    <Label htmlFor="push-notifications-toggle">
                        Receive new message notifications
                    </Label>
                    {error && <p className="text-red-500 mt-2">Error: {error}</p>}
                </div>
            )}
        </div>
    );
};
