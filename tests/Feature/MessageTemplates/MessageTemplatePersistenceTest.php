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
                ['param_name' => 'campaign', 'example' => 'Summer Sale']
            ],
            'body_text_named_params' => [
                ['param_name' => 'store', 'example' => 'the end of August'],
                ['param_name' => 'promo_code', 'example' => '25OFF'],
                ['param_name' => 'discount', 'example' => '25%']
            ]
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
            'display_name' => 'Seasonal Promotion Positional', //Adapted from name
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
}
