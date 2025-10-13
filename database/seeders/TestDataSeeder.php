<?php

namespace Database\Seeders;

use App\Enums\Chatbot\AgentVisibility;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // === USERS ===
        // User 1 is created by the main DatabaseSeeder, we'll create others here.
        $user2 = User::factory()->create([
            'name' => 'Second Owner',
            'email' => 'owner2@example.com',
        ]);

        $agentUser = User::factory()->create([
            'name' => 'Agent User',
            'email' => 'agent@example.com',
        ]);


        // === ORGANIZATION 1 SETUP ===
        $org1 = Organization::create([
            'name' => 'Test Organization 1',
            'slug' => 'test-organization-1',
            'owner_id' => 1, // User 1 (from main seeder)
            'status' => 1,
        ]);

        // Link Owner (User 1) to Org 1
        OrganizationUser::create([
            'organization_id' => $org1->id,
            'user_id' => 1,
            'role_id' => 1, // 'owner'
            'status' => 1,
            'joined_at' => now(),
        ]);

        // Link Agent to Org 1
        OrganizationUser::create([
            'organization_id' => $org1->id,
            'user_id' => $agentUser->id,
            'role_id' => 4, // 'agent' role
            'status' => 1,
            'joined_at' => now(),
        ]);

        OrganizationSubscription::create([
            'organization_id' => $org1->id,
            'subscription_id' => 1, // Suscripción free existente
            'status' => 1,
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Chatbot 1 for Org 1 (Standard)
        $chatbot1 = Chatbot::create([
            'organization_id' => $org1->id,
            'name' => 'Org1 Standard Bot',
            'description' => 'Standard chatbot for Org 1',
            'system_prompt' => 'You are a helpful assistant.',
            'status' => 1,
        ]);

        ChatbotChannel::create([
            'chatbot_id' => $chatbot1->id,
            'channel_id' => 1, // Canal WhatsApp existente
            'name' => 'WhatsApp Channel 1',
            'credentials' => ['phone_number' => '+1111111111'],
            'status' => 1,
        ]);

        // Chatbot 2 for Org 1 (For Agent Visibility Test)
        $chatbot2 = Chatbot::create([
            'organization_id' => $org1->id,
            'name' => 'Org1 Agent-Visible Bot',
            'description' => 'Chatbot with agent visibility set to assigned only',
            'system_prompt' => 'You are a private assistant.',
            'status' => 1,
            'agent_visibility' => AgentVisibility::ASSIGNED_ONLY,
        ]);

        ChatbotChannel::create([
            'chatbot_id' => $chatbot2->id,
            'channel_id' => 1, // Canal WhatsApp existente
            'name' => 'WhatsApp Channel 2',
            'credentials' => ['phone_number' => '+2222222222'],
            'status' => 1,
        ]);


        // === ORGANIZATION 2 SETUP (for auth tests) ===
        $org2 = Organization::create([
            'name' => 'Test Organization 2',
            'slug' => 'test-organization-2',
            'owner_id' => $user2->id,
            'status' => 1,
        ]);

        OrganizationUser::create([
            'organization_id' => $org2->id,
            'user_id' => $user2->id,
            'role_id' => 1, // 'owner'
            'status' => 1,
            'joined_at' => now(),
        ]);

        // Chatbot 3 for Org 2
        $chatbot3 = Chatbot::create([
            'organization_id' => $org2->id,
            'name' => 'Org2 Secret Bot',
            'description' => 'A chatbot that users from Org1 should not see.',
            'system_prompt' => 'You are a secret assistant.',
            'status' => 1,
        ]);

        ChatbotChannel::create([
            'chatbot_id' => $chatbot3->id,
            'channel_id' => 1, // Canal WhatsApp existente
            'name' => 'WhatsApp Channel 3',
            'credentials' => ['phone_number' => '+3333333333'],
            'status' => 1,
        ]);
    }
}
