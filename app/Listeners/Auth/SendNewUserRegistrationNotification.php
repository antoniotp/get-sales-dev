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
        // Only send the notification if the user registration also created a new organization.
        // users registered via invitation are not yet attached to an organization.
        $isNewOrgRegistration = $event->user->organizations()->exists();

        if ($isNewOrgRegistration) {
            $recipients = collect(config('notifications.new_user_registration.recipients'))
                ->filter()
                ->all();

            if (! empty($recipients)) {
                Log::debug('Sending new user registration notification to: '.implode(', ', $recipients));
                Mail::to($recipients)->send(new NewUserRegistered($event->user));
            }
        }
    }
}
