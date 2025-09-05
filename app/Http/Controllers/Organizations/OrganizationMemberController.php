<?php

namespace App\Http\Controllers\Organizations;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, OrganizationServiceInterface $organizationService): Response
    {
        $user = Auth::user();
        $organization = $organizationService->getCurrentOrganization($request, $user);

        if (!$organization) {
            abort(404, 'Organization could not be determined.');
        }

        // Eager load the users with their pivot data
        $organization->load('users');

        // Load all roles to map role_id to role name in the frontend
        $roles = Role::all()->keyBy('id');

        $currentUserRoleSlug = $organization->getUserRole($user)?->slug;

        $invitations = Invitation::where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->with('role')
            ->get()
            ->map(function ($invitation) {
                $invitation->status = $invitation->expires_at->isPast() ? 'expired' : 'pending';
                return $invitation;
            });

        return Inertia::render('organization/members', [
            'organizationDetails' => $organization,
            'members' => $organization->users,
            'roles' => $roles,
            'currentUserRoleSlug' => $currentUserRoleSlug,
            'invitations' => $invitations,
        ]);
    }
}
