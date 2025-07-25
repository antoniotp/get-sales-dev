<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Organization;

class OrganizationSwitchController extends Controller
{
    public function switch(Request $request)
    {
        $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $organization = Organization::findOrFail($request->input('organization_id'));

        // Verify that user belongs to organization
        if (!auth()->user()->organizations()->where('organization_id', $organization->id)->exists()) {
            abort(403);
        }

        // Save the newly selected organization to the session
        $request->session()->put('currentOrganizationId', $organization->id);

        // Redirect user to the previous page or dashboard
        return Redirect::back();
    }
}
