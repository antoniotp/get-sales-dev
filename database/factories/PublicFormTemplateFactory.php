<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PublicFormTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'custom_fields_schema' => [],
            'entity_config' => null,
        ];
    }
}
