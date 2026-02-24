import React, { useEffect, useState } from 'react';
import { usePushNotifications } from '@/hooks/usePushNotifications';
import HeadingSmall from '@/components/heading-small';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Info, AlertCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export const UpdateNotificationsSettings = () => {
    const { t } = useTranslation('settings');

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
        statusMessage = t('notifications.not_supported');
        statusVariant = 'destructive';
        statusIcon = <AlertCircle className="h-4 w-4" />;
    } else if (permissionStatus === 'denied') {
        statusMessage = t('notifications.blocked');
        statusVariant = 'destructive';
        statusIcon = <AlertCircle className="h-4 w-4" />;
    } else if (permissionStatus === 'default' && !isSubscribed) {
        statusMessage = t('notifications.enable_prompt');
        statusVariant = 'default';
        statusIcon = <Info className="h-4 w-4" />;
    } else if (isSubscribed) {
        statusMessage = t('notifications.enabled');
        statusVariant = 'default';
    }

    return (
        <div className="space-y-6">
            <HeadingSmall
                title={t('notifications.heading_title')}
                description={t('notifications.heading_description')}
            />

            {!isSupported || permissionStatus === 'denied' || (permissionStatus === 'default' && !isSubscribed) ? (
                <Alert variant={statusVariant}>
                    {statusIcon}
                    <AlertTitle>{t('notifications.status_title')}</AlertTitle>
                    <AlertDescription>
                        {statusMessage}
                        {(permissionStatus === 'default' && isSupported) && (
                            <Button onClick={() => subscribe()} disabled={loading} className="ml-4">
                                {loading ? t('notifications.enabling') : t('notifications.enable_now')}
                            </Button>
                        )}
                        {error && <p className="text-red-500 mt-2">{t('notifications.error_prefix')} {error}</p>}
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
                        {t('notifications.receive_label')}
                    </Label>
                    {error && <p className="text-red-500 mt-2">{t('notifications.error_prefix')} {error}</p>}
                </div>
            )}
        </div>
    );
};