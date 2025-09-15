<?php

namespace App\Http\Middleware;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCurrentOrganization
{
    public function __construct( private OrganizationServiceInterface $organizationService ){}

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            if (!session('currentOrganizationId')) {
                $this->organizationService->initializeCurrentOrganization($request, auth()->user());
            }

            if ($organizationId = session('currentOrganizationId')) {
                $organization = Organization::query()->find($organizationId);

                if ($organization && Auth::user()->belongsToOrganization($organization)) {
                    app()->singleton(Organization::class, fn() => $organization);
                }
            }
        }

        return $next($request);
    }
}
