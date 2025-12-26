<?php

namespace App\Services\Organization;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationService implements OrganizationServiceInterface
{
    /**
     * Set the current organization for a user.
     */
    public function setCurrentOrganization(Request $request, User $user, int $organizationId): bool
    {
        // Verify user has access to this organization
        if (!$user->organizations()->where('organizations.id', $organizationId)->exists()) {
            return false;
        }

        return DB::transaction(function () use ($request, $user, $organizationId) {
            // Set in session
            $request->session()->put('currentOrganizationId', $organizationId);

            // Update user's last organization in database
            $user->update(['last_organization_id' => $organizationId]);

            return true;
        });
    }

    /**
     * Initialize the current organization for a user session.
     */
    public function initializeCurrentOrganization(Request $request, User $user): ?Organization
    {
        // Check if already set in session
        $currentOrganizationId = $request->session()->get('currentOrganizationId');

        if ($currentOrganizationId && $user->organizations()->where('organizations.id', $currentOrganizationId)->exists()) {
            return Organization::find($currentOrganizationId);
        }

        // Get the appropriate organization (last selected or first available)
        $organization = $this->determineUserOrganization($user);

        if ($organization) {
            $request->session()->put('currentOrganizationId', $organization->id);

            // Update last_organization_id if it wasn't already set correctly
            if ($user->last_organization_id !== $organization->id) {
                $user->update(['last_organization_id' => $organization->id]);
            }

            return $organization;
        }

        return null;
    }

    /**
     * Get current organization from session.
     */
    public function getCurrentOrganization(Request $request, User $user): ?Organization
    {
        $currentOrganizationId = $request->session()->get('currentOrganizationId');

        if (!$currentOrganizationId) {
            return $this->initializeCurrentOrganization($request, $user);
        }

        return Organization::find($currentOrganizationId);
    }

    /**
     * Determine which organization should be used for the user.
     * This encapsulates the business logic for organization selection.
     */
    private function determineUserOrganization(User $user): ?Organization
    {
        // First try to get the last selected organization if it exists and user still has access
        if ($user->last_organization_id && $user->organizations()->where('organizations.id', $user->last_organization_id)->exists()) {
            return $user->lastOrganization;
        }

        // Fallback to first available organization
        return $user->organizations()->first();
    }

    /**
     * Reset user's organization preference (useful for testing or admin actions).
     */
    public function resetUserOrganizationPreference(User $user): void
    {
        $user->update(['last_organization_id' => null]);
    }

    /**
     * Switch organization and ensure user has access.
     */
    public function switchOrganization(Request $request, User $user, int $organizationId): bool
    {
        $organization = $user->organizations()->find($organizationId);

        if (!$organization || !$organization->isActive()) {
            return false;
        }

        return $this->setCurrentOrganization($request, $user, $organizationId);
    }
}
