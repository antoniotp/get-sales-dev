<?php

namespace App\Contracts\Services\Auth;

use App\Models\User;

interface RegistrationServiceInterface
{
    /**
     * Handle the registration of a new user and their organization.
     *
     * @param array<string, string> $data
     * @return User
     */
    public function register(array $data): User;
}
