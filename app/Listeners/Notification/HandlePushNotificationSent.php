<?php

namespace App\Listeners\Notification;

use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Events\NotificationSent;

class HandlePushNotificationSent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        Log::debug('WebPush SENT', [
            'endpoint' => $event->report?->getEndpoint(),
        ]);
    }
}
