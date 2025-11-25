<?php

namespace App\Services\Appointment;

use App\Contracts\Services\Appointment\AppointmentServiceInterface;
use App\Models\Appointment;
use App\Models\Chatbot;
use App\Models\Contact;
use Illuminate\Support\Arr;

class AppointmentService implements AppointmentServiceInterface
{
    /**
     * Schedules a new appointment for a given chatbot.
     * Finds or creates a contact if necessary, then creates the appointment.
     *
     * @param  Chatbot  $chatbot  The chatbot for which the appointment is being scheduled.
     * @param  array  $data  Data for scheduling the appointment, including contact info and appointment details.
     *                       Expected keys: 'appointment_at', 'chatbot_channel_id',
     *                       'contact_id' (optional), 'phone_number' (optional, if contact_id is null),
     *                       'first_name' (optional), 'last_name' (optional).
     * @return Appointment The newly created appointment.
     */
    public function schedule(Chatbot $chatbot, array $data): Appointment
    {
        $contactId = Arr::get($data, 'contact_id');
        $phoneNumber = Arr::get($data, 'phone_number');
        $firstName = Arr::get($data, 'first_name');
        $lastName = Arr::get($data, 'last_name');

        // Find or create contact
        if (! $contactId) {
            $contact = Contact::firstOrCreate(
                [
                    'organization_id' => $chatbot->organization_id,
                    'phone_number' => $phoneNumber,
                ],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]
            );
            $contactId = $contact->id;
        }

        // Create the appointment
        return Appointment::create([
            'contact_id' => $contactId,
            'chatbot_channel_id' => Arr::get($data, 'chatbot_channel_id'),
            'appointment_at' => Arr::get($data, 'appointment_at'),
            'status' => 'scheduled', // Default status
        ]);
    }
}
