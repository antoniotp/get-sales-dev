<?php

namespace Database\Factories;

use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MessageTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chatbot_channel_id' => 1,
            'name' => $this->faker->words(3, true),
            'category_id' => 1,
            'language' => 'es',
            'status' => 'approved',
            'platform_status' => 1,
            'header_type' => 'none',
            'body_content' => $this->faker->sentence().' {{1}}',
            'variables_count' => 1,
        ];
    }
}
