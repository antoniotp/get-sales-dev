<?php

namespace App\Services\Contact;

use App\Contracts\Services\Contact\ContactServiceInterface;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContactService implements ContactServiceInterface
{
    public function updateFirstName(int $contactId, string $firstName): bool
    {
        $contact = Contact::find($contactId);

        if (! $contact) {
            Log::warning('Attempted to update a non-existent contact.', ['contact_id' => $contactId]);

            return false;
        }

        return $contact->update(['first_name' => $firstName]);
    }
}
