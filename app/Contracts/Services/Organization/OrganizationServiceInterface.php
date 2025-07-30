<?php

namespace App\Contracts\Services\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

interface OrganizationServiceInterface
{
    public function setCurrentOrganization(Request $request, User $user, int $organizationId): bool;
    public function initializeCurrentOrganization(Request $request, User $user): ?Organization;
    public function getCurrentOrganization(Request $request, User $user): ?Organization;
    public function resetUserOrganizationPreference(User $user): void;
    public function switchOrganization(Request $request, User $user, int $organizationId): bool;
}
