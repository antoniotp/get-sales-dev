<?php

namespace App\Listeners\Notification;

use App\Enums\Chatbot\AgentVisibility;
use App\Events\Message\NewMessageReceived;
use App\Models\User;
use App\Notifications\Message\NewMessagePushNotification;
use Illuminate\Support\Facades\Gate;

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
        $message = $event->message;
        $conversation = $message->conversation;
        $chatbot = $conversation->chatbotChannel->chatbot;

        if (! $chatbot) {
            return;
        }

        $organization = $chatbot->organization;
        $usersToNotify = collect();

        // 1. Add assigned user if they exist
        if ($conversation->assignedUser) {
            $usersToNotify->push($conversation->assignedUser);
        }

        // 2. Apply agent_visibility logic
        if ($chatbot->agent_visibility === AgentVisibility::ASSIGNED_ONLY) {
            $managers = User::whereHas('organizations', function ($query) use ($organization) {
                $query->where('organization_users.organization_id', $organization->id);
            })
                ->whereHas('organizationUsers.role', function ($query) {
                    $query->where('level', '>', 40);
                })
                ->get();
            $usersToNotify = $usersToNotify->merge($managers);

        } elseif ($chatbot->agent_visibility === AgentVisibility::ALL) {
            $usersToNotify = $usersToNotify->merge($organization->users);
        }

        // Deduplicate, filter by subscriptions and authorization, then notify
        $usersToNotify->unique('id')->filter(function (User $user) use ($chatbot) {
            return $user->pushSubscriptions->isNotEmpty() && Gate::forUser($user)->allows('view', $chatbot);
        })->each(function (User $user) use ($message) {
            $user->notify(new NewMessagePushNotification($message));
        });
    }
}
