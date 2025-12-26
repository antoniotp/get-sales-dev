<?php

namespace App\Contracts\Services\Invitation;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;

interface InvitationServiceInterface
{
    public function createAndSendInvitation(User $inviter, Organization $organization, string $email, int $roleId): void;

    public function accept(string $token, User $user): void;

    public function cancel(Invitation $invitation): void;

    public function resend(Invitation $invitation): void;
}
