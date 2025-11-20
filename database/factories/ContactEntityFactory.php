<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactEntity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactEntityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactEntity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'type' => $this->faker->randomElement(['pet', 'child', 'vehicle']),
            'name' => $this->faker->firstName(),
        ];
    }
}
