<?php

namespace App\Contracts\Services\Appointment;

use App\Models\Appointment;
use App\Models\Chatbot;

interface AppointmentServiceInterface
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
    public function schedule(Chatbot $chatbot, array $data): Appointment;

    /**
     * Updates an existing appointment.
     *
     * @param  Appointment  $appointment  The appointment to update.
     * @param  array  $data  The data to update the appointment with.
     * @return Appointment The updated appointment.
     */
    public function update(Appointment $appointment, array $data): Appointment;

    /**
     * Cancels/deletes an existing appointment.
     *
     * @param  Appointment  $appointment  The appointment to cancel.
     */
    public function cancel(Appointment $appointment): void;
}
