<?php

namespace App\Http\Controllers\Organizations;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationSwitchController extends Controller
{
    public function switch(Request $request, OrganizationServiceInterface $organizationService)
    {
        $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $success = $organizationService->switchOrganization(
            $request,
            auth()->user(),
            $request->integer('organization_id')
        );

        if (!$success) {
            return response()->json([
                'message' => 'You do not have access to this organization.'
            ], 403);
        }

        // Redirect user to the previous page or dashboard
        return Inertia::location(route('chatbots.index'));
    }
}
