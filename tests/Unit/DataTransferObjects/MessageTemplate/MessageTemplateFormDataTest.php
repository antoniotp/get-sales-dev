<?php

namespace Tests\Unit\DataTransferObjects\MessageTemplate;

use App\DataTransferObjects\MessageTemplate\MessageTemplateFormData;
use App\Models\MessageTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(MessageTemplateFormData::class)]
class MessageTemplateFormDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_correctly_destructures_variable_mappings_for_frontend()
    {
        $template = MessageTemplate::factory()->create([
            'header_content' => 'Hello {{contact_name}}!',
            'body_content' => 'Your order {{order_id}} is ready.',
        ]);

        $testHeaderMapping = [
            'placeholder' => '{{contact_name}}',
            'source' => 'contact.first_name',
            'label' => 'Contacto: Nombre',
            'fallback_value' => 'Invitado',
        ];

        $testBodyMappings = [
            [
                'placeholder' => '{{order_id}}',
                'source' => 'order.id',
                'label' => 'Pedido: ID',
                'fallback_value' => '',
            ],
            [
                'placeholder' => '{{order_date}}',
                'source' => 'order.date',
                'label' => 'Pedido: Fecha',
                'fallback_value' => '',
            ],
        ];

        $template->variable_mappings = [
            'header' => $testHeaderMapping,
            'body' => $testBodyMappings,
        ];
        $template->save();
        $template->refresh();

        // 2. Act
        $formData = MessageTemplateFormData::fromMessageTemplate($template);

        // 3. Assert
        $this->assertEquals($testHeaderMapping, $formData->header_variable_mapping);
        $this->assertEquals($testBodyMappings, $formData->variable_mappings);

        $this->assertEquals($template->id, $formData->id);
        $this->assertEquals($template->body_content, $formData->body_content);
        $this->assertEquals($template->header_content, $formData->header_content);
    }

    #[Test]
    public function it_handles_null_variable_mappings_correctly()
    {
        // Arrange - Create a MessageTemplate with null variable_mappings
        $template = MessageTemplate::factory()->create([
            'variable_mappings' => null,
        ]);

        // Action - Call the fromMessageTemplate method
        $formData = MessageTemplateFormData::fromMessageTemplate($template);

        // Assert
        $this->assertNull($formData->header_variable_mapping);
        $this->assertNull($formData->variable_mappings);
    }

    #[Test]
    public function it_handles_partial_variable_mappings_correctly()
    {
        // Arrange - Create a MessageTemplate with only the header mapping
        $template = MessageTemplate::factory()->create([
            'header_content' => 'Hello {{contact_name}}!',
            'variable_mappings' => [
                'header' => [
                    'placeholder' => '{{contact_name}}',
                    'source' => 'contact.first_name',
                    'label' => 'Contacto: Nombre',
                ],
            ],
        ]);
        $template->save();
        $template->refresh();

        // Act
        $formData = MessageTemplateFormData::fromMessageTemplate($template);

        // Assert - The header mapping must be set, the body mapping must be null
        $this->assertNotNull($formData->header_variable_mapping);
        $this->assertNull($formData->variable_mappings);

        // Arrange - Create a MessageTemplate with only the body mapping
        $template = MessageTemplate::factory()->create([
            'body_content' => 'Your order {{order_id}} is ready.',
            'variable_mappings' => [
                'body' => [
                    [
                        'placeholder' => '{{order_id}}',
                        'source' => 'order.id',
                        'label' => 'Pedido: ID',
                    ],
                ],
            ],
        ]);
        $template->save();
        $template->refresh();

        // Act
        $formData = MessageTemplateFormData::fromMessageTemplate($template);

        // Assert
        $this->assertNull($formData->header_variable_mapping);
        $this->assertNotNull($formData->variable_mappings);
        $this->assertCount(1, $formData->variable_mappings);
    }
}
