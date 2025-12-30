<?php

namespace App\Listeners\Notification;

use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Events\NotificationFailed;

class HandleFailedPushNotification
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
    public function handle(NotificationFailed $event): void
    {
        $report = $event->report;

        Log::error('WebPush FAILED', [
            'endpoint' => $report?->getEndpoint(),
            'reason' => $report?->getReason(),
            'is_expired' => $report?->isSubscriptionExpired(),
            'status_code' => $report?->getResponse()?->getStatusCode(),
            'response_body' => $report?->getResponse()?->getBody()->getContents(),
        ]);
    }
}
