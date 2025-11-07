<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicContactRequest;
use App\Models\PublicFormLink;

class PublicFormController extends Controller
{
    /**
     * Display the specified public form.
     */
    public function show(PublicFormLink $formLink)
    {
        // Load the related publicFormTemplate
        $formLink->load('publicFormTemplate');

        // Check if formLink is active, otherwise abort
        if (! $formLink->is_active) {
            abort(404);
        }

        // Pass the formLink to a Blade view
        return view('public.contact-registration.show', compact('formLink'));
    }

    /**
     * Store a new contact from the public form submission.
     */
    public function store(StorePublicContactRequest $request, PublicFormLink $formLink)
    {
        return response()->json(['message' => 'Validation passed!']);
    }
}
