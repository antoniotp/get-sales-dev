<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCurrentOrganization
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && !session('currentOrganizationId')) {
            $defaultOrganization = auth()->user()->organizations()->first();

            if ($defaultOrganization) {
                session(['currentOrganizationId' => $defaultOrganization->id]);
            }
        }

        return $next($request);
    }
}
