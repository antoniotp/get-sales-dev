<?php

namespace App\Policies;

use App\Models\Chatbot;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChatbotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Chatbot $chatbot): bool
    {
        return $user->belongsToOrganization($chatbot->organization);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Organization $organization): bool
    {
        $role = $user->getRoleInOrganization($organization);

        return $role && $role->level > 40;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Chatbot $chatbot): Response
    {
        $organization = $chatbot->organization;
        $role = $user->getRoleInOrganization($organization);

        return ($role && $role->level > 40)
            ? Response::allow()
            : Response::deny('You do not have permission to update this chatbot.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Chatbot $chatbot): Response
    {
        $organization = $chatbot->organization;
        $role = $user->getRoleInOrganization($organization);

        return ($role && $role->level > 40)
            ? Response::allow()
            : Response::deny('You do not have permission to delete this chatbot.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Chatbot $chatbot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Chatbot $chatbot): bool
    {
        return false;
    }
}
