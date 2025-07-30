<?php

namespace App\Http\Middleware;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureCurrentOrganization
{
    public function __construct( private OrganizationServiceInterface $organizationService ){}

    public function handle(Request $request, Closure $next)
    {
        Log::info('EnsureCurrentOrganization');
        if (auth()->check() && !session('currentOrganizationId')) {
            Log::info('Initializing current organization');
            $this->organizationService->initializeCurrentOrganization( $request, auth()->user() );
        }

        return $next($request);
    }
}
