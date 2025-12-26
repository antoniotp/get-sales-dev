<?php

namespace App\Contracts\Services\PublicForm;

use App\Models\Contact;
use App\Models\PublicFormLink;

interface PublicContactFormServiceInterface
{
    /**
     * Register a new contact from a public form submission.
     *
     * @param  PublicFormLink  $formLink  The form link instance.
     * @param  array  $validatedData  The validated data from the request.
     * @return Contact The newly created or updated contact.
     */
    public function register(PublicFormLink $formLink, array $validatedData): Contact;
}
