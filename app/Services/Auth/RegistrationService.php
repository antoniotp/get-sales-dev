<?php

namespace App\Services\Auth;

use App\Contracts\Services\Auth\RegistrationServiceInterface;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrationService implements RegistrationServiceInterface
{
    /**
     * Handle the registration of a new user and their organization.
     *
     * @param array<string, string> $data
     * @return User
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $organization = Organization::create([
                'name' => $data['name'] . '\'s Organization',
                'slug' => Str::slug($data['name'] . '-organization'),
                'owner_id' => $user->id,
            ]);

            $ownerRole = Role::where('name', 'owner')->first();

            $user->organizations()->attach($organization->id, [
                'role_id' => $ownerRole->id,
                'status' => 1, // Active
                'joined_at' => now(),
            ]);

            return $user;
        });
    }
}
