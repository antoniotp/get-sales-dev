<?php

namespace Tests\Feature\Public;

use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactEntity;
use App\Models\PublicFormLink;
use App\Models\PublicFormTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private PublicFormLink $formLink;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // 1. Use an existing chatbot from the seeder
        $chatbot = Chatbot::find(1);

        $channel = Channel::where('slug', 'whatsapp-web')->firstOrFail();

        $chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Web Channel 1',
            'credentials' => ['phone_number' => '+3333333333'],
            'status' => 1,
        ]);

        // 2. Create the form template with the veterinary schema and entity config
        $template = PublicFormTemplate::factory()->create([
            'name' => 'veterinary_registration',
            'custom_fields_schema' => $this->getVeterinarySchema(),
            'entity_config' => [
                'creates_entity' => true,
                'entity_type' => 'pet',
                'entity_name_field' => 'patient_name',
            ],
        ]);

        // 3. Create the public form link associated with the template and chatbot
        $this->formLink = PublicFormLink::factory()->create([
            'public_form_template_id' => $template->id,
            'chatbot_id' => $chatbot->id,
            'channel_id' => $channel->id,
        ]);
    }

    #[Test]
    public function it_throttles_requests_after_too_many_attempts(): void
    {
        // We defined a 'forms' throttle limit of 10 attempts per minute.
        $limit = 5;

        $validData = [
            'first_name' => 'Throttled',
            'last_name' => 'User',
            'email' => 'throttle@example.com',
            'phone_number' => '+1111111111',
            'country_code' => 'ES',
        ];

        // The first 5 should be okay
        for ($i = 0; $i < $limit; $i++) {
            $response = $this->post(route('public-forms.store', $this->formLink->uuid), $validData);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // The 6th attempt should be throttled
        $response = $this->post(route('public-forms.store', $this->formLink->uuid), $validData);
        $response->assertStatus(429); // Assert "Too Many Requests"
        RateLimiter::clear('public-form');
    }

    #[Test]
    public function it_fails_validation_when_required_fields_are_missing(): void
    {
        // Act: Post to the store endpoint with empty data
        $response = $this->post(route('public-forms.store', $this->formLink->uuid), []);

        // Assert: Check for validation errors for base fields
        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone_number', 'country_code']);

        // Assert: Check for validation errors for dynamic required fields
        $response->assertSessionHasErrors([
            'custom_fields.tutor_birthdate',
            'custom_fields.tutor_dni',
            'custom_fields.tutor_address',
            'custom_fields.patient_species',
            'custom_fields.patient_breed',
            'custom_fields.patient_age',
            'custom_fields.patient_sex',
            'custom_fields.patient_weight',
        ]);
    }

    #[Test]
    public function it_passes_validation_with_valid_data(): void
    {
        // Arrange: Prepare valid data for the form
        $validData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '+1234567890',
            'country_code' => 'ES',
            'language_code' => 'es',
            'timezone' => 'Europe/Madrid',
            'custom_fields' => [
                'tutor_birthdate' => '1990-01-15',
                'tutor_dni' => '12345678Z',
                'tutor_address' => '123 Fake St',
                'vet_full_name' => 'Dr. Smith',
                'patient_name' => 'Fido', // Added for entity name test
                'patient_species' => 'Dog',
                'patient_breed' => 'Golden Retriever',
                'patient_age' => 5,
                'patient_sex' => 'male',
                'patient_castrated' => true,
                'patient_castration_age' => 6,
                'patient_weight' => 30.5,
            ],
        ];

        // Act: Post to the store endpoint
        $response = $this->post(route('public-forms.store', $this->formLink->uuid), $validData);

        // Assert: Check that there are no validation errors
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_creates_the_contact_and_attributes_on_successful_submission(): void
    {
        // Arrange: Prepare valid data
        $validData = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone_number' => '+1987654321',
            'country_code' => 'ES',
            'language_code' => 'es',
            'timezone' => 'Europe/Madrid',
            'custom_fields' => [
                'tutor_birthdate' => '1992-05-20',
                'tutor_dni' => '87654321A',
                'tutor_address' => '456 Main St',
                'patient_name' => 'Whiskers',
                'patient_species' => 'Cat',
                'patient_breed' => 'Siamese',
                'patient_age' => 2,
                'patient_sex' => 'female',
                'patient_weight' => 4.5,
            ],
        ];

        // Act: Post valid data to the store endpoint
        $this->post(route('public-forms.store', $this->formLink->uuid), $validData);

        // Assert: A new contact was created with the base data
        $this->assertDatabaseHas('contacts', [
            'organization_id' => $this->formLink->chatbot->organization_id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone_number' => '+1987654321',
            'country_code' => 'ES',
            'language_code' => 'es',
            'timezone' => 'Europe/Madrid',
        ]);

        // Assert: A contact channel link was created
        $contact = Contact::where('email', 'jane.doe@example.com')->first();
        $this->assertDatabaseHas('contact_channels', [
            'contact_id' => $contact->id,
            'chatbot_id' => $this->formLink->chatbot_id,
            'channel_id' => $this->formLink->channel_id,
        ]);

        // Assert: A ContactEntity was created for the contact with the correct data
        $this->assertDatabaseHas('contact_entities', [
            'contact_id' => $contact->id,
            'type' => 'pet',
            'name' => 'Whiskers',
        ]);

        $entity = ContactEntity::where('contact_id', $contact->id)->first();
        $this->assertNotNull($entity);

        // Assert: Custom attributes were created for the entity
        $this->assertDatabaseHas('contact_attributes', [
            'contact_entity_id' => $entity->id,
            'attribute_name' => 'tutor_dni',
            'attribute_value' => '87654321A',
        ]);
        $this->assertDatabaseHas('contact_attributes', [
            'contact_entity_id' => $entity->id,
            'attribute_name' => 'patient_species',
            'attribute_value' => 'Cat',
        ]);
        $this->assertDatabaseHas('contact_attributes', [
            'contact_entity_id' => $entity->id,
            'attribute_name' => 'patient_weight',
            'attribute_value' => '4.5',
        ]);
    }

    #[Test]
    public function it_fails_validation_if_honeypot_field_is_filled(): void
    {
        // Arrange: Prepare data, but fill in the honeypot field
        $botData = [
            'first_name' => 'Bot',
            'last_name' => 'McBotface',
            'email' => 'bot@example.com',
            'phone_number' => '+1234567890',
            'country_code' => 'ES',
            'honeypot_field' => 'I am a bot', // <-- Bot fills this
        ];

        // Act: Post the data
        $response = $this->post(route('public-forms.store', $this->formLink->uuid), $botData);

        // Assert: Check for a validation error on the honeypot field
        $response->assertSessionHasErrors('honeypot_field');
    }

    /**
     * Helper to get the schema definition.
     */
    private function getVeterinarySchema(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/veterinary_form_schema.json')), true);
    }

    #[Test]
    public function it_creates_an_appointment_when_appointment_datetime_is_provided(): void
    {
        // Arrange: Add the appointment field to the form's schema for this test
        $schema = $this->getVeterinarySchema();
        $schema[] = [
            'name' => 'appointment_datetime',
            'label' => 'Fecha y Hora de la Cita',
            'type' => 'datetime-local',
            'validation' => ['required', 'date', 'after:now'],
        ];
        $this->formLink->publicFormTemplate->update(['custom_fields_schema' => $schema]);

        $appointmentTime = now()->addDay()->toDateTimeString();

        $validData = [
            'first_name' => 'Appointment',
            'last_name' => 'User',
            'email' => 'appointment.user@example.com',
            'phone_number' => '+1231231234',
            'country_code' => 'ES',
            'custom_fields' => [
                'tutor_birthdate' => '1995-02-10',
                'tutor_dni' => '11223344B',
                'tutor_address' => '789 Appointment Ave',
                'patient_name' => 'Whiskers',
                'patient_species' => 'Dog',
                'patient_breed' => 'Beagle',
                'patient_age' => 3,
                'patient_sex' => 'male',
                'patient_weight' => 12.0,
                'appointment_datetime' => $appointmentTime,
            ],
        ];

        // Act: Post valid data to the store endpoint
        $this->post(route('public-forms.store', $this->formLink->uuid), $validData);

        // Assert: A new contact was created
        $this->assertDatabaseHas('contacts', [
            'email' => 'appointment.user@example.com',
        ]);
        $contact = Contact::where('email', 'appointment.user@example.com')->first();

        // Find the correct chatbot_channel_id to assert against
        $chatbotChannel = ChatbotChannel::where('chatbot_id', $this->formLink->chatbot_id)
            ->where('channel_id', $this->formLink->channel_id)
            ->first();

        // Assert: An appointment was created with the correct data
        $this->assertDatabaseHas('appointments', [
            'contact_id' => $contact->id,
            'chatbot_channel_id' => $chatbotChannel->id,
            'appointment_at' => $appointmentTime,
            'status' => 'scheduled',
        ]);

        // Assert: An appointment attribute was NOT created in the generic attributes table
        $entity = $contact->contactEntities->first();
        $this->assertNotNull($entity);
        $this->assertDatabaseMissing('contact_attributes', [
            'contact_entity_id' => $entity->id,
            'attribute_name' => 'appointment_datetime',
        ]);
    }
}
