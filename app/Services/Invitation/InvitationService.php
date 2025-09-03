<?php

namespace App\Services\Invitation;

use App\Contracts\Services\Invitation\InvitationServiceInterface;
use App\Exceptions\InvitationException;
use App\Mail\Invitation\OrganizationInvitation;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService implements InvitationServiceInterface
{
    /**
     * Create and send an invitation to a user to join an organization.
     *
     * @param User $inviter
     * @param Organization $organization
     * @param string $email
     * @param int $roleId
     * @return void
     * @throws InvitationException
     */
    public function createAndSendInvitation(User $inviter, Organization $organization, string $email, int $roleId): void
    {
        // 1. Authorization Check
        $inviterRole = $inviter->getRoleInOrganization($organization);
        if (!$inviterRole || !in_array($inviterRole->slug, ['owner', 'admin'])) {
            throw new InvitationException('You do not have permission to invite users to this organization.', 403);
        }

        // 2. Validation
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $organization->hasMember($existingUser)) {
            throw new InvitationException('This user is already a member of the organization.');
        }

        $existingInvitation = Invitation::where('organization_id', $organization->id)
            ->where('email', $email)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->exists();

        if ($existingInvitation) {
            throw new InvitationException('An invitation has already been sent to this email address.');
        }

        // 3. Create Invitation
        $token = Str::random(32);
        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role_id' => $roleId,
            'created_by' => $inviter->id,
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        // 4. Send Email
        Mail::to($email)->send(new OrganizationInvitation($invitation));
    }
}