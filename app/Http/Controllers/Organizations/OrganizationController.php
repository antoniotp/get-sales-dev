<?php

namespace App\Http\Controllers\Organizations;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Contracts\Services\System\TimezoneServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('organization/form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request, OrganizationServiceInterface $organizationService): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);
        $validated['owner_id'] = Auth::id();
        $validated['timezone'] = $validated['timezone'] ?? 'UTC';
        $validated['locale'] = $validated['locale'] ?? 'en';

        $organization = Organization::create($validated);

        $ownerRole = Role::where('slug', 'owner')->first();

        $organization->users()->attach(Auth::user(), [
            'role_id' => $ownerRole->id,
            'status' => 1,
            'joined_at' => now(),
        ]);

        $organizationService->setCurrentOrganization($request, Auth::user(), $organization->id);

        return redirect()->route('chatbots.index')->with('success', 'Organization created successfully.');
    }

    public function edit(
        TimezoneServiceInterface $timezoneService
    ): Response {
        $timezones = $timezoneService->getAllFormatted();

        // always edits the current organization defined in the middleware HandlerInertiaRequests
        return Inertia::render('organization/form', [
            // 'organization.current' is defined in HandlerInertiaRequests, and it is used to populate the form
            'timezones' => $timezones,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validated();
        // update slug if the name was changed
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $organization->update($validated);

        return redirect()
            ->route('organizations.edit')
            ->with('success', 'Organization updated successfully.');
    }
}
