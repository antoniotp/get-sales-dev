<?php

namespace App\Listeners\Notification;

use App\Events\Message\NewMessageReceived;
use App\Notifications\Message\NewMessagePushNotification;

class SendNewMessagePushNotification
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
    public function handle(NewMessageReceived $event): void
    {
        $conversation = $event->message->conversation;
        $user = $conversation->assignedUser;

        if ($user && $user->pushSubscriptions->isNotEmpty()) {
            $user->notify(new NewMessagePushNotification);
        }
    }
}
