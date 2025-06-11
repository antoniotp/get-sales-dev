<?php

namespace Database\Seeders;

use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationUser;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear la organización
        $organization = Organization::create([
            'name' => 'Test Organization',
            'slug' => 'test-organization',
            'owner_id' => 1, // Usuario existente
            'status' => 1,
        ]);

        // 2. Crear la relación organization_users
        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => 1,
            'role_id' => 1, // Role 'owner'
            'status' => 1,
            'joined_at' => now(),
        ]);

        // 3. Crear la suscripción gratuita para la organización
        OrganizationSubscription::create([
            'organization_id' => $organization->id,
            'subscription_id' => 1, // Suscripción free existente
            'status' => 1,
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // 4. Crear un chatbot
        $chatbot = Chatbot::create([
            'organization_id' => $organization->id,
            'name' => 'Test WhatsApp Bot',
            'description' => 'Test chatbot for WhatsApp',
            'system_prompt' => 'You are a helpful assistant.',
            'status' => 1,
            'response_delay_min' => 1,
            'response_delay_max' => 5,
        ]);

        // 5. Crear la relación chatbot_channel
        ChatbotChannel::create([
            'chatbot_id' => $chatbot->id,
            'channel_id' => 1, // Canal WhatsApp existente
            'name' => 'WhatsApp Test Channel',
            'webhook_url' => 'https://example.com/webhook',
            'credentials' => [
                'api_key' => 'test_api_key',
                'phone_number' => '+1234567890'
            ],
            'webhook_config' => [
                'verify_token' => 'test_verify_token'
            ],
            'status' => 1,
        ]);
    }
}
