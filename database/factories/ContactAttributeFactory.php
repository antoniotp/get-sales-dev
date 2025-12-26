<?php

namespace Database\Factories;

use App\Models\ContactAttribute;
use App\Models\ContactEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_entity_id' => ContactEntity::factory(),
            'attribute_name' => $this->faker->word(),
            'attribute_value' => $this->faker->sentence(),
            'source' => $this->faker->randomElement(['conversation', 'manual', 'api', 'import']),
        ];
    }
}
