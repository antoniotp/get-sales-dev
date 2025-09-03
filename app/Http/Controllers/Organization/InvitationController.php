<?php

namespace App\Http\Controllers\Organization;

use App\Contracts\Services\Invitation\InvitationServiceInterface;
use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
     *
     * @param StoreInvitationRequest $request
     * @return RedirectResponse
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
}