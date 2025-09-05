<?php

namespace App\Services\Invitation;

use App\Contracts\Services\Invitation\InvitationServiceInterface;
use App\Exceptions\InvitationException;
use App\Mail\Invitation\OrganizationInvitation;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        $inviterRole = $inviter->getRoleInOrganization($organization);
        if (!$inviterRole || !in_array($inviterRole->slug, ['owner', 'admin'])) {
            throw new InvitationException('You do not have permission to invite users to this organization.', 403);
        }

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

        $token = Str::random(32);
        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role_id' => $roleId,
            'created_by' => $inviter->id,
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new OrganizationInvitation($invitation));
    }

    /**
     * Accept an invitation for the given user.
     *
     * @param string $token
     * @param User $user
     * @return void
     * @throws InvitationException
     */
    public function accept(string $token, User $user): void
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->expires_at->isPast() || $invitation->accepted_at) {
            throw new InvitationException('This invitation link is invalid or has expired.', 400);
        }

        if ($invitation->email !== $user->email) {
            throw new InvitationException('This invitation is for a different email address.', 403);
        }

        DB::transaction(function () use ($invitation, $user) {
            $organization = $invitation->organization;

            // Add user to the organization
            $organization->users()->attach($user->id, [
                'role_id' => $invitation->role_id,
                'status' => 1,
                'joined_at' => now(),
            ]);

            // Mark invitation as accepted
            $invitation->accepted_at = now();
            $invitation->save();

            // Update user's last organization
            $user->last_organization_id = $organization->id;
            $user->save();
        });
    }

    /**
     * Cancel an invitation.
     *
     * @param Invitation $invitation
     * @return void
     * @throws InvitationException
     */
    public function cancel(Invitation $invitation): void
    {
        if ($invitation->accepted_at) {
            throw new InvitationException('This invitation has already been accepted.');
        }

        $invitation->delete();
    }

    /**
     * Resend an invitation.
     *
     * @param Invitation $invitation
     * @return void
     * @throws InvitationException
     */
    public function resend(Invitation $invitation): void
    {
        if ($invitation->accepted_at) {
            throw new InvitationException('This invitation has already been accepted.');
        }

        // Update the expiration date
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        Mail::to($invitation->email)->send(new OrganizationInvitation($invitation));
    }
}
