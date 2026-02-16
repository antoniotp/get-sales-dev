<?php

namespace Tests\Unit\Services\MessageTemplate;

use App\Models\Chatbot;
use App\Models\ChatbotContactSchema;
use App\Services\MessageTemplate\TemplateVariableService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(TemplateVariableService::class)]
class TemplateVariableServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateVariableService $service;

    private Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TemplateVariableService;
        $this->seed(DatabaseSeeder::class);
        $this->chatbot = Chatbot::firstOrFail();

        // Create a schema with a defined entity type for the chatbot
        ChatbotContactSchema::factory()->create([
            'chatbot_id' => $this->chatbot->id,
            'name' => 'Pet Info',
            'entity_type' => 'pet',
            'schema_definition' => [
                ['name' => 'pet_name', 'label' => 'Nombre de la Mascota'],
                ['name' => 'pet_breed', 'label' => 'Raza de la Mascota'],
            ],
        ]);

        // Create another schema for the same chatbot to test merging
        ChatbotContactSchema::factory()->create([
            'chatbot_id' => $this->chatbot->id,
            'name' => 'Tutor Info',
            'entity_type' => 'tutor',
            'schema_definition' => [
                ['name' => 'tutor_dni', 'label' => 'DNI del Tutor'],
            ],
        ]);

        // Create a schema with a null entity_type, which should be ignored
        ChatbotContactSchema::factory()->create([
            'chatbot_id' => $this->chatbot->id,
            'entity_type' => null,
            'schema_definition' => [
                ['name' => 'should_be_ignored', 'label' => 'Ignored Field'],
            ],
        ]);
    }

    #[Test]
    public function it_correctly_builds_the_available_variables_array(): void
    {
        // Act
        $variables = $this->service->getAvailableVariables($this->chatbot);

        // Assert
        $this->assertIsArray($variables);
        $this->assertNotEmpty($variables);

        // Convert the array to a collection for easier assertions
        $variablesCollection = collect($variables);

        // 1. Check for a system variable
        $contactNameVar = $variablesCollection->firstWhere('source_path', 'contact.first_name');
        $this->assertNotNull($contactNameVar);
        $this->assertEquals('Contacto: Nombre', $contactNameVar['label']);
        $this->assertEquals('contact_first_name', $contactNameVar['placeholder_name']);

        // 2. Check for a dynamic variable from the 'pet' schema
        $petBreedVar = $variablesCollection->firstWhere('source_path', 'contact_entity.pet.pet_breed');
        $this->assertNotNull($petBreedVar);
        $this->assertEquals('Raza de la Mascota', $petBreedVar['label']);
        $this->assertEquals('contact_entity_pet_pet_breed', $petBreedVar['placeholder_name']);

        // 3. Check for a dynamic variable from the 'tutor' schema
        $tutorDniVar = $variablesCollection->firstWhere('source_path', 'contact_entity.tutor.tutor_dni');
        $this->assertNotNull($tutorDniVar);
        $this->assertEquals('DNI del Tutor', $tutorDniVar['label']);
        $this->assertEquals('contact_entity_tutor_tutor_dni', $tutorDniVar['placeholder_name']);

        // 4. Check that the variable from the null entity_type schema was ignored
        $ignoredVar = $variablesCollection->firstWhere('source_path', 'contact_entity..should_be_ignored');
        $this->assertNull($ignoredVar);
        $this->assertNull($variablesCollection->firstWhere('placeholder_name', 'should_be_ignored'));

        // 5. Check the total count (5 system variables + 3 dynamic variables = 8)
        $this->assertCount(8, $variables);
    }
}
