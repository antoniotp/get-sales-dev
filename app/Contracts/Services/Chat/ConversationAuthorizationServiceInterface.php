<?php

namespace App\Contracts\Services\Chat;

use App\Models\Organization;
use App\Models\User;

interface ConversationAuthorizationServiceInterface
{
    /**
     * Check if a user is an agent subject to conversation visibility rules.
     */
    public function isAgentSubjectToVisibilityRules(User $user, Organization $organization): bool;
}
