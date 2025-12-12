<?php

namespace App\Policies;

use App\Enums\Chatbot\AgentVisibility;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    /**
     * Determine whether the user can assign the model.
     */
    public function assign(User $user, Conversation $conversation): Response
    {
        $organization = $conversation->chatbotChannel->chatbot->organization;
        $role = $user->getRoleInOrganization($organization);

        // User must have a role with level > 40
        if (! $role || $role->level <= 40) {
            return Response::deny('You do not have permission to assign this conversation.');
        }

        // The user must belong to the organization where the conversation is.
        if (! $user->belongsToOrganization($organization)) {
            return Response::deny('You do not have permission to assign this conversation.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Conversation $conversation): Response
    {
        $chatbot = $conversation->chatbotChannel->chatbot;
        $organization = $chatbot->organization;

        // The user must belong to the organization where the conversation is.
        if (! $user->belongsToOrganization($organization)) {
            return Response::deny('You do not have permission to update this conversation.');
        }

        if ($chatbot->agent_visibility === AgentVisibility::ASSIGNED_ONLY) {
            $role = $user->getRoleInOrganization($organization);
            // If the user has no role, or is an agent and the conversation is not assigned to them, deny.
            if (! $role || ($role->slug === 'agent' && $conversation->assigned_user_id !== $user->id)) {
                return Response::deny('You do not have permission to access this conversation.');
            }
        }

        return Response::allow();
    }
}
