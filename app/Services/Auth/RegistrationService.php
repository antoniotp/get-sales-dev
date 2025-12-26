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
     * Handle the registration of a new user and optionally their organization.
     *
     * @param  array<string, string>  $data
     */
    public function register(array $data, bool $createOrganization = true): User
    {
        return DB::transaction(function () use ($data, $createOrganization) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            if ($createOrganization) {
                $organization = Organization::create([
                    'name' => $data['name'].'\'s Organization',
                    'slug' => Str::slug($data['name'].'-organization-'.$user->id),
                    'owner_id' => $user->id,
                ]);

                $ownerRole = Role::where('slug', 'owner')->first();

                $user->organizations()->attach($organization->id, [
                    'role_id' => $ownerRole->id,
                    'status' => 1, // Active
                    'joined_at' => now(),
                ]);
            }

            return $user;
        });
    }
}
