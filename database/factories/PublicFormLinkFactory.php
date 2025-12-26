<?php

namespace Database\Factories;

use App\Models\Chatbot;
use App\Models\PublicFormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PublicFormLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_form_template_id' => PublicFormTemplate::factory(),
            'chatbot_id' => Chatbot::first(),
            'channel_id' => null,
            'is_active' => true,
            'success_message' => 'Success!',
        ];
    }
}
