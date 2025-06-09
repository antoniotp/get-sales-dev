<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'Organization owner with full administrative access',
                'can_manage_chats' => true,
                'level' => 100,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with full management capabilities',
                'can_manage_chats' => true,
                'level' => 80,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manager with team oversight and chat management',
                'can_manage_chats' => true,
                'level' => 60,
            ],
            [
                'name' => 'Agent',
                'slug' => 'agent',
                'description' => 'Chat agent with conversation handling capabilities',
                'can_manage_chats' => true,
                'level' => 40,
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to organization data',
                'can_manage_chats' => false,
                'level' => 20,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
