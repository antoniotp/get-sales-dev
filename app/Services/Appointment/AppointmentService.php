<?php

namespace App\Services\Appointment;

use App\Contracts\Services\Appointment\AppointmentServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Models\Appointment;
use App\Models\Chatbot;
use App\Models\Contact;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AppointmentService implements AppointmentServiceInterface
{
    public function __construct(private readonly PhoneNumberNormalizerInterface $normalizer) {}

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

        // --- Timezone handling ---
        $organizationTimezone = $chatbot->organization->timezone;
        $localAppointmentTime = Carbon::parse(Arr::get($data, 'appointment_at'), $organizationTimezone);
        $utcAppointmentTime = $localAppointmentTime->utc();

        // Initialize to null, then assign if data exists
        $utcEndAt = null;
        if (Arr::has($data, 'end_at')) {
            $endAt = Arr::get($data, 'end_at');
            $utcEndAt = $endAt ? Carbon::parse($endAt, $organizationTimezone)->utc() : null;
        }

        $utcRemindAt = null;
        if (Arr::has($data, 'remind_at')) {
            $remindAt = Arr::get($data, 'remind_at');
            $utcRemindAt = $remindAt ? Carbon::parse($remindAt, $organizationTimezone)->utc() : null;
        }
        // --- End Timezone handling ---

        // Find or create contact
        if (! $contactId) {
            $normalizedPhoneNumber = $this->normalizer->normalize($phoneNumber);
            $contact = Contact::firstOrCreate(
                [
                    'organization_id' => $chatbot->organization_id,
                    'phone_number' => $normalizedPhoneNumber,
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
            'appointment_at' => $utcAppointmentTime, // Use the UTC Carbon instance
            'end_at' => $utcEndAt,
            'status' => 'scheduled', // Default status
            'remind_at' => $utcRemindAt,
        ]);
    }

    /**
     * Updates an existing appointment.
     *
     * @param  Appointment  $appointment  The appointment to update.
     * @param  array  $data  The data to update the appointment with.
     * @return Appointment The updated appointment.
     */
    public function update(Appointment $appointment, array $data): Appointment
    {
        $organizationTimezone = $appointment->chatbotChannel->chatbot->organization->timezone;

        if (Arr::has($data, 'appointment_at')) {
            $localAppointmentTime = Carbon::parse(Arr::get($data, 'appointment_at'), $organizationTimezone);
            $data['appointment_at'] = $localAppointmentTime->utc();
        }

        if (Arr::has($data, 'end_at')) {
            $endAt = Arr::get($data, 'end_at');
            $data['end_at'] = $endAt ? Carbon::parse($endAt, $organizationTimezone)->utc() : null;
        }

        if (Arr::has($data, 'remind_at')) {
            $remindAt = Arr::get($data, 'remind_at');
            $data['remind_at'] = $remindAt ? Carbon::parse($remindAt, $organizationTimezone)->utc() : null;
        }

        $appointment->update($data);

        return $appointment;
    }

    /**
     * Cancels/deletes an existing appointment.
     *
     * @param  Appointment  $appointment  The appointment to cancel.
     */
    public function cancel(Appointment $appointment): void
    {
        $appointment->delete();
    }
}
