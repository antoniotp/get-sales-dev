<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    /**
     * Determine whether the user can assign the model.
     *
     * @param User $user
     * @param Conversation $conversation
     * @return bool
     */
    public function assign(User $user, Conversation $conversation): Response
    {
        $organization = $conversation->chatbotChannel->chatbot->organization;
        $role = $user->getRoleInOrganization($organization);

        // User must have a role with level > 40
        if (!$role || $role->level <= 40) {
            return Response::deny('You do not have permission to assign this conversation.');
        }

        // The user must belong to the organization where the conversation is.
        if (!$user->belongsToOrganization($organization)) {
            return Response::deny('You do not have permission to assign this conversation.');
        }

        return Response::allow();
    }
}
