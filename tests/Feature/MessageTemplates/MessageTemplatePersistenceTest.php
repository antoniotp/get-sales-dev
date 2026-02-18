<?php

namespace Tests\Feature\MessageTemplates;

use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageTemplatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Chatbot $chatbot;

    private MessageTemplateCategory $category;

    private ChatbotChannel $channel;

    private MessageTemplateCategory $marketingCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::firstOrFail();
        $organization = $this->user->organizations()->firstOrFail();
        $this->chatbot = $organization->chatbots()->firstOrFail();
        $this->channel = $this->chatbot->chatbotChannels()->firstOrFail();
        $this->category = MessageTemplateCategory::firstOrFail();
        $this->marketingCategory = MessageTemplateCategory::where('slug', 'marketing')->firstOrFail();

        $this->actingAs($this->user);
        $this->withSession([
            'organization_id' => $organization->id,
            'chatbot_id' => $this->chatbot->id,
        ]);
    }

    #[Test]
    public function it_correctly_persists_template_with_named_body_variables_from_form_submission()
    {
        $templateData = [
            'name' => 'seasonal_promotion',
            'display_name' => 'Seasonal Promotion',
            'chatbot_channel_id' => $this->channel->id,
            'category_id' => $this->marketingCategory->id,
            'language' => 'en_US',
            'header_type' => 'text',
            'header_content' => 'Our {{campaign}} is on!',
            'header_variable' => ['placeholder' => '{{campaign}}', 'example' => 'Summer Sale'],
            'header_variable_type' => 'named',
            'body_content' => 'Shop now through {{store}} and use code {{promo_code}} to get {{discount}} off of all merchandise.',
            'footer_content' => 'Use the buttons below to manage your marketing subscriptions',
            'button_config' => [
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from Promos'],
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from All'],
            ],
            'variables_schema' => [
                ['placeholder' => '{{store}}', 'example' => 'the end of August'],
                ['placeholder' => '{{promo_code}}', 'example' => '25OFF'],
                ['placeholder' => '{{discount}}', 'example' => '25%'],
            ],
            'variable_type' => 'named',
        ];

        $response = $this->post(route('message-templates.store', $this->chatbot->id), $templateData);
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $createdTemplate = MessageTemplate::latest('id')->first();
        $this->assertNotNull($createdTemplate);

        // Expected example_data structure
        $expectedExampleData = [
            'header_text_named_params' => [
                ['param_name' => 'campaign', 'example' => 'Summer Sale'],
            ],
            'body_text_named_params' => [
                ['param_name' => 'store', 'example' => 'the end of August'],
                ['param_name' => 'promo_code', 'example' => '25OFF'],
                ['param_name' => 'discount', 'example' => '25%'],
            ],
        ];

        // Assert all relevant fields
        $this->assertEquals($templateData['name'], $createdTemplate->name);
        $this->assertEquals($templateData['display_name'], $createdTemplate->display_name);
        $this->assertEquals($templateData['chatbot_channel_id'], $createdTemplate->chatbot_channel_id);
        $this->assertEquals($templateData['category_id'], $createdTemplate->category_id);
        $this->assertEquals($templateData['language'], $createdTemplate->language);
        $this->assertEquals($templateData['header_type'], $createdTemplate->header_type);
        $this->assertEquals($templateData['header_content'], $createdTemplate->header_content);
        $this->assertEquals($templateData['body_content'], $createdTemplate->body_content);
        $this->assertEquals($templateData['footer_content'], $createdTemplate->footer_content);
        $this->assertEquals($templateData['button_config'], $createdTemplate->button_config);
        $this->assertEquals(4, $createdTemplate->variables_count); // 1 header + 3 body named vars
        $this->assertEquals('pending', $createdTemplate->status); // Default status
        $this->assertEquals(1, $createdTemplate->platform_status); // Default platform status
        $this->assertEquals($expectedExampleData, $createdTemplate->example_data);
    }

    #[Test]
    public function it_correctly_persists_template_with_positional_variables_from_form_submission()
    {
        $templateData = [
            'name' => 'seasonal_promotion_positional', // Different name to avoid conflict
            'display_name' => 'Seasonal Promotion Positional', // Adapted from name
            'chatbot_channel_id' => $this->channel->id,
            'category_id' => $this->marketingCategory->id,
            'language' => 'en_US',
            'header_type' => 'text',
            'header_content' => 'Our {{1}} is on!',
            'header_variable' => ['placeholder' => '{{1}}', 'example' => 'Summer Sale'],
            'header_variable_type' => 'positional',
            'body_content' => 'Shop now through {{1}} and use code {{2}} to get {{3}} off of all merchandise.',
            'footer_content' => 'Use the buttons below to manage your marketing subscriptions',
            'button_config' => [
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from Promos'],
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from All'],
            ],
            'variables_schema' => [
                ['placeholder' => '{{1}}', 'example' => 'the end of August'],
                ['placeholder' => '{{2}}', 'example' => '25OFF'],
                ['placeholder' => '{{3}}', 'example' => '25%'],
            ],
            'variable_type' => 'positional',
        ];

        $response = $this->post(route('message-templates.store', $this->chatbot->id), $templateData);
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $createdTemplate = MessageTemplate::latest('id')->first();
        $this->assertNotNull($createdTemplate);

        // Expected example_data structure for positional
        $expectedExampleData = [
            'header_text' => ['Summer Sale'],
            'body_text' => [['the end of August', '25OFF', '25%']],
        ];

        // Assert all relevant fields
        $this->assertEquals($templateData['name'], $createdTemplate->name);
        $this->assertEquals($templateData['display_name'], $createdTemplate->display_name);
        $this->assertEquals($templateData['chatbot_channel_id'], $createdTemplate->chatbot_channel_id);
        $this->assertEquals($templateData['category_id'], $createdTemplate->category_id);
        $this->assertEquals($templateData['language'], $createdTemplate->language);
        $this->assertEquals($templateData['header_type'], $createdTemplate->header_type);
        $this->assertEquals($templateData['header_content'], $createdTemplate->header_content);
        $this->assertEquals($templateData['body_content'], $createdTemplate->body_content);
        $this->assertEquals($templateData['footer_content'], $createdTemplate->footer_content);
        $this->assertEquals($templateData['button_config'], $createdTemplate->button_config);
        $this->assertEquals(4, $createdTemplate->variables_count); // 1 header + 3 body positional vars
        $this->assertEquals('pending', $createdTemplate->status);
        $this->assertEquals(1, $createdTemplate->platform_status);
        $this->assertEquals($expectedExampleData, $createdTemplate->example_data);
    }

    #[Test]
    public function it_correctly_persists_a_plain_text_template_with_no_variables()
    {
        $templateData = [
            'name' => 'plain_text_template',
            'display_name' => 'Plain Text Template',
            'chatbot_channel_id' => $this->channel->id,
            'category_id' => $this->category->id,
            'language' => 'en_US',
            'header_type' => 'text',
            'header_content' => 'This is a simple text header.',
            // No header_variable or header_variable_type
            'body_content' => 'This is the main message content without any variables.',
            'footer_content' => 'Simple footer.',
            'button_config' => [], // No buttons
            // No variables_schema or variable_type
            // No header_variable_mapping or variable_mappings
        ];

        $response = $this->post(route('message-templates.store', $this->chatbot->id), $templateData);
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $createdTemplate = MessageTemplate::latest('id')->first();
        $this->assertNotNull($createdTemplate);

        // Assert that variable-related fields are null/empty
        $this->assertEquals(0, $createdTemplate->variables_count);
        $this->assertNull($createdTemplate->example_data);
        $this->assertNull($createdTemplate->variable_mappings);

        // Assert content fields are correct
        $this->assertEquals($templateData['name'], $createdTemplate->name);
        $this->assertEquals($templateData['display_name'], $createdTemplate->display_name);
        $this->assertEquals($templateData['header_content'], $createdTemplate->header_content);
        $this->assertEquals($templateData['body_content'], $createdTemplate->body_content);
        $this->assertEquals($templateData['footer_content'], $createdTemplate->footer_content);
        $this->assertNull($createdTemplate->button_config);
        $this->assertEquals('pending', $createdTemplate->status);
        $this->assertEquals(1, $createdTemplate->platform_status);
    }

    #[Test]
    public function it_correctly_persists_variable_mappings_from_form_submission()
    {
        $templateData = [
            'name' => 'template_with_mappings',
            'display_name' => 'Template With Mappings',
            'chatbot_channel_id' => $this->channel->id,
            'category_id' => $this->category->id,
            'language' => 'es',
            'header_type' => 'text',
            'header_content' => '{{contact_first_name}}',
            'header_variable' => ['placeholder' => '{{contact_first_name}}', 'example' => 'Cris'],
            'header_variable_type' => 'named',
            'body_content' => '{{organization_name}}! {{contact_email}}',
            'footer_content' => '',
            'button_config' => [],
            'variables_schema' => [
                ['placeholder' => '{{organization_name}}', 'example' => 'Jobomas'],
                ['placeholder' => '{{contact_email}}', 'example' => 'cgonzalez@example.com'],
            ],
            'variable_type' => 'named',
            // --- new mapping data ---
            'header_variable_mapping' => [
                'placeholder' => '{{contact_first_name}}',
                'source' => 'contact.first_name',
                'label' => 'Contacto: Nombre',
                'fallback_value' => '',
            ],
            'variable_mappings' => [
                [
                    'placeholder' => '{{organization_name}}',
                    'source' => 'organization.name',
                    'label' => 'Organización: Nombre',
                    'fallback_value' => '',
                ],
                [
                    'placeholder' => '{{contact_email}}',
                    'source' => 'contact.email',
                    'label' => 'Contacto: Email',
                    'fallback_value' => '',
                ],
            ],
        ];

        $response = $this->post(route('message-templates.store', $this->chatbot->id), $templateData);
        $response->assertSessionHasNoErrors();

        $createdTemplate = MessageTemplate::latest('id')->first();
        $this->assertNotNull($createdTemplate);

        // expected structure in `variable_mappings` column
        $expectedVariableMappings = [
            'header' => [
                'placeholder' => '{{contact_first_name}}',
                'source' => 'contact.first_name',
                'label' => 'Contacto: Nombre',
                'fallback_value' => '',
            ],
            'body' => [
                [
                    'placeholder' => '{{organization_name}}',
                    'source' => 'organization.name',
                    'label' => 'Organización: Nombre',
                    'fallback_value' => '',
                ],
                [
                    'placeholder' => '{{contact_email}}',
                    'source' => 'contact.email',
                    'label' => 'Contacto: Email',
                    'fallback_value' => '',
                ],
            ],
        ];

        $this->assertEquals($expectedVariableMappings, $createdTemplate->variable_mappings);
    }
}
