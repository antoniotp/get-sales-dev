<?php

namespace Tests\Feature\Public;

use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\PublicFormLink;
use App\Models\PublicFormTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // 2. Create the form template with the veterinary schema
        $template = PublicFormTemplate::factory()->create([
            'name' => 'veterinary_registration',
            'custom_fields_schema' => $this->getVeterinarySchema(),
        ]);

        // 3. Create the public form link associated with the template and chatbot
        $this->formLink = PublicFormLink::factory()->create([
            'public_form_template_id' => $template->id,
            'chatbot_id' => $chatbot->id,
            'channel_id' => $channel->id,
        ]);
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

    /**
     * Helper to get the schema definition.
     */
    private function getVeterinarySchema(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/veterinary_form_schema.json')), true);
    }
}
