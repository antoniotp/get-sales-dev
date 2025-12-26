<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'chatbot_channel_id' => 1,
            'appointment_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'status' => 'scheduled',
            'reminder_sent_at' => null,
        ];
    }
}
