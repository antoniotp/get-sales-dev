<?php

namespace App\Http\Controllers\Organizations;

use App\Contracts\Services\Invitation\InvitationServiceInterface;
use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    private InvitationServiceInterface $invitationService;
    private OrganizationServiceInterface $organizationService;

    public function __construct(
        InvitationServiceInterface $invitationService,
        OrganizationServiceInterface $organizationService
    ) {
        $this->invitationService = $invitationService;
        $this->organizationService = $organizationService;
    }

    /**
     * Store a newly created invitation in storage.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $organization = $this->organizationService->getCurrentOrganization($request, $request->user());

        if (!$organization) {
            return back()->with('error', 'Could not determine the current organization.');
        }

        try {
            $this->invitationService->createAndSendInvitation(
                $request->user(),
                $organization,
                $request->input('email'),
                $request->input('role_id')
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invitation sent successfully.');
    }

    /**
     * Show the invitation acceptance page.
     */
    public function show(Request $request): Response
    {
        $token = $request->query('token');
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || $invitation->expires_at->isPast() || $invitation->accepted_at) {
            return Inertia::render('invitations/invalid');
        }

        return Inertia::render('invitations/accept', [
            'invitationDetails' => [
                'organization_name' => $invitation->organization->name,
                'inviter_name' => $invitation->inviter->name,
                'email' => $invitation->email,
            ],
            'token' => $token,
            'userExists' => User::where('email', $invitation->email)->exists(),
        ]);
    }

    /**
     * Process the acceptance of an invitation.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        try {
            $this->invitationService->accept($token, $request->user());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'You have successfully joined the organization.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invitation $invitation): RedirectResponse
    {
        try {
            $this->invitationService->cancel($invitation);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invitation cancelled successfully.');
    }

    /**
     * Resend the specified invitation.
     */
    public function resend(Invitation $invitation): RedirectResponse
    {
        try {
            $this->invitationService->resend($invitation);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invitation resent successfully.');
    }
}