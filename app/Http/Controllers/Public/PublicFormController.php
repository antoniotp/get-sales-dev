<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PublicFormLink;
use Illuminate\Http\Request;

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
    public function store(Request $request, PublicFormLink $formLink)
    {
        // TODO: Use a FormRequest for validation
        // TODO: Call the ContactRegistrationService
        // TODO: Return a JSON response
    }
}
