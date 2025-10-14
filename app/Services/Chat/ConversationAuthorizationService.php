<?php

namespace App\Services\Chat;

use App\Contracts\Services\Chat\ConversationAuthorizationServiceInterface;
use App\Models\Organization;
use App\Models\User;

class ConversationAuthorizationService implements ConversationAuthorizationServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function isAgentSubjectToVisibilityRules(User $user, Organization $organization): bool
    {
        $role = $user->getRoleInOrganization($organization);

        // Roles with level <= 40 (i.e., Agents) are subject to visibility rules.
        // Other roles (Manager, Admin, Owner) are not.
        return $role && $role->level <= 40;
    }
}
