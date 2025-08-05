<?php

namespace App\Http\Middleware;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use Closure;
use Illuminate\Http\Request;

class EnsureCurrentOrganization
{
    public function __construct( private OrganizationServiceInterface $organizationService ){}

    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && !session('currentOrganizationId')) {
            $this->organizationService->initializeCurrentOrganization( $request, auth()->user() );
        }

        return $next($request);
    }
}
