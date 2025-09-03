<?php

namespace App\Contracts\Services\Invitation;

use App\Models\Organization;
use App\Models\User;

interface InvitationServiceInterface
{
    /**
     * Create and send an invitation to a user to join an organization.
     *
     * @param User $inviter
     * @param Organization $organization
     * @param string $email
     * @param int $roleId
     * @return void
     */
    public function createAndSendInvitation(User $inviter, Organization $organization, string $email, int $roleId): void;
}
