<?php

namespace App\Contracts\Services\Auth;

use App\Models\User;

interface RegistrationServiceInterface
{
    /**
     * Handle the registration of a new user and their organization.
     *
     * @param array<string, string> $data
     * @param bool $createOrganization
     * @return User
     */
    public function register(array $data, bool $createOrganization = true): User;
}
