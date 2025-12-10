<?php

namespace App\Contracts\Services\Contact;

interface ContactServiceInterface
{
    public function updateFirstName(int $contactId, string $firstName): bool;
}
