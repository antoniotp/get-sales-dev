<?php

namespace App\Listeners\Auth;

use App\Mail\Auth\NewUserRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewUserRegistrationNotification implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(Registered $event): void
    {
        $recipients = collect(config('notifications.new_user_registration.recipients'))
            ->filter()
            ->all();

        if (! empty($recipients)) {
            Log::debug('Sending new user registration notification to: '.implode(', ', $recipients));
            Mail::to($recipients)->send(new NewUserRegistered($event->user));
        }
    }
}
