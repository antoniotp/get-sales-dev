<?php

namespace Tests\Feature\Appointment;

use App\Models\Appointment;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Services\Util\PhoneNumberNormalizer;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_returns_appointments_within_date_range_for_a_specific_chatbot(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        // Use pre-seeded data as per TestDataSeeder
        $targetChatbot = Chatbot::find(1); // Belongs to Org 1
        $targetChannel = ChatbotChannel::find(1); // Belongs to Chatbot 1

        $otherChatbotInOrg = Chatbot::find(2); // Also belongs to Org 1
        $otherChannelInOrg = ChatbotChannel::find(2);

        $contact = Contact::factory()->create(['organization_id' => $targetChatbot->organization_id]);

        $startDate = '2025-12-01';
        $endDate = '2025-12-31';

        // Appointment IN range for the correct chatbot
        $appointmentInRange = Appointment::factory()->create([
            'chatbot_channel_id' => $targetChannel->id,
            'contact_id' => $contact->id,
            'appointment_at' => '2025-12-15 10:00:00',
        ]);

        // Appointment OUT of range
        Appointment::factory()->create([
            'chatbot_channel_id' => $targetChannel->id,
            'contact_id' => $contact->id,
            'appointment_at' => '2026-01-10 10:00:00',
        ]);

        // Appointment for a DIFFERENT chatbot in the same org
        Appointment::factory()->create([
            'chatbot_channel_id' => $otherChannelInOrg->id,
            'contact_id' => $contact->id,
            'appointment_at' => '2025-12-20 10:00:00',
        ]);

        // --- Act ---
        $response = $this->getJson(route('appointments.list', [
            'chatbot' => $targetChatbot->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        // --- Assert ---
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $appointmentInRange->id,
            'contact_id' => $contact->id,
        ]);
    }

    #[Test]
    public function it_returns_an_empty_list_when_no_appointments_are_in_range(): void
    {
        // --- Arrange ---
        $this->actingAs(User::find(1));
        $chatbot = Chatbot::find(1);
        $channel = $chatbot->chatbotChannels->first();
        $contact = Contact::factory()->create(['organization_id' => $chatbot->organization_id]);
        $startDate = '2025-11-01';
        $endDate = '2025-11-30';

        // Appointment OUT of range
        Appointment::factory()->create([
            'chatbot_channel_id' => $channel->id,
            'contact_id' => $contact->id,
            'appointment_at' => '2025-12-15 10:00:00',
        ]);

        // --- Act ---
        $response = $this->getJson(route('appointments.list', [
            'chatbot' => $chatbot->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        // --- Assert ---
        $response->assertOk();
        $response->assertJsonCount(0);
    }

    #[Test]
    public function it_returns_validation_error_if_dates_are_missing(): void
    {
        // --- Arrange ---
        $this->actingAs(User::find(1));
        $chatbot = Chatbot::find(1);

        // --- Act ---
        $response = $this->getJson(route('appointments.list', [
            'chatbot' => $chatbot->id,
        ]));

        // --- Assert ---
        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function another_organization_user_cannot_access_appointments(): void
    {
        // --- Arrange ---
        // User 2 belongs to Org 2, as per TestDataSeeder
        $otherUser = User::find(2);
        $this->actingAs($otherUser);

        // Try to access appointments for a chatbot from Org 1
        $chatbotFromOrg1 = Chatbot::find(1);

        // --- Act ---
        $response = $this->getJson(route('appointments.list', [
            'chatbot' => $chatbotFromOrg1->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]));

        // --- Assert ---
        // Should fail because of Route Model Binding authorization which is handled by a custom implementation.
        $response->assertForbidden();
    }

    #[Test]
    public function it_creates_an_appointment_for_an_existing_contact(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $chatbot = Chatbot::find(1);
        $channel = $chatbot->chatbotChannels->first();
        $existingContact = Contact::factory()->create(['organization_id' => $chatbot->organization_id]);

        $appointmentCarbon = now()->addDays(5)->startOfHour()->utc();
        $appointmentForDb = $appointmentCarbon->toDateTimeString();
        $appointmentForJson = $appointmentCarbon->format('Y-m-d\TH:i:s.u\Z');

        $endAtCarbon = $appointmentCarbon->copy()->addHour();
        $endAtForDb = $endAtCarbon->toDateTimeString();
        $endAtForJson = $endAtCarbon->format('Y-m-d\TH:i:s.u\Z');

        $remindAtCarbon = $appointmentCarbon->copy()->subDay();
        $remindAtForDb = $remindAtCarbon->toDateTimeString();
        $remindAtForJson = $remindAtCarbon->format('Y-m-d\TH:i:s.u\Z');

        $postData = [
            'contact_id' => $existingContact->id,
            'chatbot_channel_id' => $channel->id,
            'appointment_at' => $appointmentForDb, // Send standard format
            'end_at' => $endAtForDb,
            'remind_at' => $remindAtForDb,
        ];

        // --- Act ---
        $response = $this->postJson(route('appointments.store', ['chatbot' => $chatbot->id]), $postData);

        // --- Assert ---
        $response->assertCreated();
        $response->assertJsonFragment([
            'contact_id' => $existingContact->id,
            'appointment_at' => $appointmentForJson, // Assert against ISO format
            'end_at' => $endAtForJson,
            'status' => 'scheduled',
            'remind_at' => $remindAtForJson,
        ]);

        $this->assertDatabaseHas('appointments', [
            'contact_id' => $existingContact->id,
            'chatbot_channel_id' => $channel->id,
            'appointment_at' => $appointmentForDb, // Assert against DB format
            'end_at' => $endAtForDb,
            'remind_at' => $remindAtForDb,
        ]);
    }

    #[Test]
    public function it_creates_a_new_contact_and_an_appointment(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $chatbot = Chatbot::find(1);
        $channel = $chatbot->chatbotChannels->first();
        $appointmentCarbon = now()->addDays(10)->startOfHour()->utc();
        $appointmentForDb = $appointmentCarbon->toDateTimeString();
        // Format to match Laravel's default model serialization
        $appointmentForJson = $appointmentCarbon->format('Y-m-d\TH:i:s.u\Z');
        $newPhoneNumber = '+15005550006';

        $endAtCarbon = $appointmentCarbon->copy()->addHour();
        $endAtForDb = $endAtCarbon->toDateTimeString();
        $endAtForJson = $endAtCarbon->format('Y-m-d\TH:i:s.u\Z');

        $remindAtCarbon = $appointmentCarbon->copy()->subDay();
        $remindAtForDb = $remindAtCarbon->toDateTimeString();
        $remindAtForJson = $remindAtCarbon->format('Y-m-d\TH:i:s.u\Z');

        $postData = [
            'phone_number' => $newPhoneNumber,
            'first_name' => 'John',
            'last_name' => 'New',
            'chatbot_channel_id' => $channel->id,
            'appointment_at' => $appointmentForDb, // Send standard format
            'end_at' => $endAtForDb,
            'remind_at' => $remindAtForDb,
        ];

        // --- Act ---
        $response = $this->postJson(route('appointments.store', ['chatbot' => $chatbot->id]), $postData);

        // --- Assert ---
        $response->assertCreated();

        // Assert a new contact was created
        $normalizer = new PhoneNumberNormalizer();
        $normalizedPhoneNumber = $normalizer->normalize($newPhoneNumber);

        $this->assertDatabaseHas('contacts', [
            'organization_id' => $chatbot->organization_id,
            'phone_number' => $normalizedPhoneNumber,
            'first_name' => 'John',
            'last_name' => 'New',
        ]);

        $newContact = Contact::where('phone_number', $normalizedPhoneNumber)->first();
        $this->assertNotNull($newContact);

        // Assert the appointment was created for the new contact
        $this->assertDatabaseHas('appointments', [
            'contact_id' => $newContact->id,
            'chatbot_channel_id' => $channel->id,
            'appointment_at' => $appointmentForDb, // Assert against DB format
            'end_at' => $endAtForDb,
            'remind_at' => $remindAtForDb,
        ]);

        $response->assertJsonFragment([
            'contact_id' => $newContact->id,
            'appointment_at' => $appointmentForJson, // Assert against ISO format
            'end_at' => $endAtForJson,
            'remind_at' => $remindAtForJson,
        ]);
    }

    #[Test]
    public function it_updates_an_appointment_datetime(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $chatbot = Chatbot::find(1);
        $appointment = Appointment::factory()->create([
            'chatbot_channel_id' => $chatbot->chatbotChannels->first()->id,
        ]);

        $newAppointmentCarbon = now()->addDays(20)->startOfHour()->utc();
        $newAppointmentForDb = $newAppointmentCarbon->toDateTimeString();
        $newAppointmentForJson = $newAppointmentCarbon->format('Y-m-d\TH:i:s.u\Z');

        $newEndAtCarbon = $newAppointmentCarbon->copy()->addHours(2);
        $newEndAtForDb = $newEndAtCarbon->toDateTimeString();
        $newEndAtForJson = $newEndAtCarbon->format('Y-m-d\TH:i:s.u\Z');

        $newRemindAtCarbon = $newAppointmentCarbon->copy()->subHours(6);
        $newRemindAtForDb = $newRemindAtCarbon->toDateTimeString();
        $newRemindAtForJson = $newRemindAtCarbon->format('Y-m-d\TH:i:s.u\Z');

        // --- Act ---
        $response = $this->putJson(route('appointments.update', ['appointment' => $appointment->id]), [
            'appointment_at' => $newAppointmentForDb,
            'end_at' => $newEndAtForDb,
            'remind_at' => $newRemindAtForDb,
        ]);

        // --- Assert ---
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $appointment->id,
            'appointment_at' => $newAppointmentForJson,
            'end_at' => $newEndAtForJson,
            'remind_at' => $newRemindAtForJson,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'appointment_at' => $newAppointmentForDb,
            'end_at' => $newEndAtForDb,
            'remind_at' => $newRemindAtForDb,
        ]);
    }

    #[Test]
    public function it_deletes_an_appointment(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $chatbot = Chatbot::find(1);
        $appointment = Appointment::factory()->create([
            'chatbot_channel_id' => $chatbot->chatbotChannels->first()->id,
        ]);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);

        // --- Act ---
        $response = $this->deleteJson(route('appointments.destroy', ['appointment' => $appointment->id]));

        // --- Assert ---
        $response->assertNoContent();
        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    }

    #[Test]
    public function another_organization_user_cannot_update_an_appointment(): void
    {
        // --- Arrange ---
        $chatbotFromOrg1 = Chatbot::find(1);
        $appointmentFromOrg1 = Appointment::factory()->create([
            'chatbot_channel_id' => $chatbotFromOrg1->chatbotChannels->first()->id,
        ]);

        $userFromOrg2 = User::find(2); // Belongs to Org 2
        $this->actingAs($userFromOrg2);

        // --- Act ---
        $response = $this->putJson(route('appointments.update', ['appointment' => $appointmentFromOrg1->id]), [
            'appointment_at' => now()->addDay()->toDateTimeString(),
        ]);

        // --- Assert ---
        $response->assertForbidden();
    }

    #[Test]
    public function another_organization_user_cannot_delete_an_appointment(): void
    {
        // --- Arrange ---
        $chatbotFromOrg1 = Chatbot::find(1);
        $appointmentFromOrg1 = Appointment::factory()->create([
            'chatbot_channel_id' => $chatbotFromOrg1->chatbotChannels->first()->id,
        ]);

        $userFromOrg2 = User::find(2); // Belongs to Org 2
        $this->actingAs($userFromOrg2);

        // --- Act ---
        $response = $this->deleteJson(route('appointments.destroy', ['appointment' => $appointmentFromOrg1->id]));

        // --- Assert ---
        $response->assertForbidden();
    }
}
