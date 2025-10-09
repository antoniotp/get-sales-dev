<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $phoneNumber = str_replace('+', '', $this->faker->e164PhoneNumber());

        return [
            'external_conversation_id' => $phoneNumber,
            'contact_name' => $this->faker->name(),
            'contact_phone' => $phoneNumber,
            'contact_email' => '',
            'contact_avatar' => $this->faker->word(),
            'status' => 1,
            'mode' => 'human',
            'last_message_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'contact_channel_id' => 1,

            'chatbot_channel_id' => 1,
            'assigned_user_id' => User::factory(),
        ];
    }
}
