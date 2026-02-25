<?php

namespace Tests\Unit\Services\MessageTemplate;

use App\Models\Contact;
use App\Models\ContactAttribute;
use App\Models\ContactEntity;
use App\Models\MessageTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\MessageTemplate\MessageTemplateResolverService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(MessageTemplateResolverService::class)]
class MessageTemplateResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageTemplateResolverService $service;

    private Organization $organization;

    private Contact $contact;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->service = new MessageTemplateResolverService;

        $this->organization = Organization::where('slug', 'test-organization-1')->firstOrFail();
        $this->agent = User::where('email', 'agent@example.com')->firstOrFail();

        // Create a contact linked to the organization
        $this->contact = Contact::factory()->create([
            'organization_id' => $this->organization->id,
            'first_name' => 'Cris',
            'last_name' => null,
            'email' => 'cris@example.com',
        ]);
    }

    #[Test]
    public function it_resolves_system_and_manual_variables(): void
    {
        // Arrange
        $template = MessageTemplate::factory()->create([
            'variable_mappings' => [
                'header' => [
                    'placeholder' => '{{contact_name}}',
                    'source' => 'contact.first_name',
                    'label' => 'Contact Name',
                ],
                'body' => [
                    [
                        'placeholder' => '{{org_name}}',
                        'source' => 'organization.name',
                        'label' => 'Org Name',
                    ],
                    [
                        'placeholder' => '{{manual_promo}}',
                        'source' => 'manual',
                        'label' => 'Promo Code',
                    ],
                ],
            ],
        ]);

        $manualValues = ['manual_promo' => 'OFF50'];

        // Act
        $resolved = $this->service->resolveValues($template, $this->contact, $manualValues);

        // Assert
        $this->assertEquals('Cris', $resolved['header']['value']);
        $this->assertEquals('{{contact_name}}', $resolved['header']['placeholder']);

        $this->assertCount(2, $resolved['body']);
        $this->assertEquals($this->organization->name, $resolved['body'][0]['value']);
        $this->assertEquals('OFF50', $resolved['body'][1]['value']);
    }

    #[Test]
    public function it_resolves_contact_entity_variables(): void
    {
        // Arrange
        $entity = ContactEntity::factory()->create([
            'contact_id' => $this->contact->id,
            'name' => 'pet',
            'type' => 'dog',
        ]);

        ContactAttribute::factory()->create([
            'contact_entity_id' => $entity->id,
            'attribute_name' => 'pet_name',
            'attribute_value' => 'Fido',
        ]);

        $template = MessageTemplate::factory()->create([
            'variable_mappings' => [
                'body' => [
                    [
                        'placeholder' => '{{contact_entity_pet_pet_name}}',
                        'source' => 'contact_entity.pet.pet_name',
                        'label' => 'Pet Name',
                    ],
                ],
            ],
        ]);

        // Act
        $resolved = $this->service->resolveValues($template, $this->contact);

        // Assert
        $this->assertEquals('Fido', $resolved['body'][0]['value']);
    }

    #[Test]
    public function it_uses_fallback_values_when_data_is_missing(): void
    {
        // Arrange
        $template = MessageTemplate::factory()->create([
            'variable_mappings' => [
                'body' => [
                    [
                        'placeholder' => '{{missing_var}}',
                        'source' => 'contact.last_name', // no last_name in setUp
                        'label' => 'Last Name',
                        'fallback_value' => 'Friend',
                    ],
                ],
            ],
        ]);

        // Act
        $resolved = $this->service->resolveValues($template, $this->contact);

        // Assert
        $this->assertEquals('Friend', $resolved['body'][0]['value']);
    }

    #[Test]
    public function it_renders_final_content_correctly(): void
    {
        // Arrange
        $template = MessageTemplate::factory()->create([
            'header_content' => 'Hi {{name}}',
            'body_content' => 'Welcome to {{org}}. Your code is {{code}}.',
            'footer_content' => 'Best, Team',
        ]);

        $resolvedValues = [
            'header' => ['placeholder' => '{{name}}', 'value' => 'Cris'],
            'body' => [
                ['placeholder' => '{{org}}', 'value' => 'GetSales'],
                ['placeholder' => '{{code}}', 'value' => '12345'],
            ],
        ];

        // Act
        $rendered = $this->service->render($template, $resolvedValues);

        // Assert
        $this->assertEquals('Hi Cris', $rendered['header']);
        $this->assertEquals('Welcome to GetSales. Your code is 12345.', $rendered['body']);
        $this->assertEquals('Best, Team', $rendered['footer']);
    }
}
