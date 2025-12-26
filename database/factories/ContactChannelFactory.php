<?php

namespace Database\Factories;

use App\Models\ContactChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactChannelFactory extends Factory
{
    protected $model = ContactChannel::class;

    public function definition(): array
    {
        return [
            'contact_id' => 1,
            'chatbot_id' => 1,
            'channel_id' => 1,
            'channel_identifier' => '1234567890',
            'channel_data' => [],
            'is_primary' => 1,
        ];
    }
}
