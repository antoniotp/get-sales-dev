<?php

namespace Tests\Feature\MessageTemplates;

use App\Contracts\Services\MessageTemplate\MessageTemplateServiceInterface;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateMessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Chatbot $chatbot;

    private MessageTemplateCategory $category;

    private ChatbotChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed the database with initial data
        $this->seed(DatabaseSeeder::class);

        // 2. Fetch records created by the seeder
        $this->user = User::firstOrFail();
        $organization = $this->user->organizations()->firstOrFail();
        $this->chatbot = $organization->chatbots()->firstOrFail();
        $this->channel = $this->chatbot->chatbotChannels()->firstOrFail();
        $this->category = MessageTemplateCategory::firstOrFail();

        // 3. Act as the user and set session data
        $this->actingAs($this->user);
        $this->withSession([
            'organization_id' => $organization->id,
            'chatbot_id' => $this->chatbot->id,
        ]);
    }

    #[Test]
    public function it_successfully_calls_the_service_to_create_a_template()
    {
        // Mock the service to isolate the controller and request validation
        $this->mock(MessageTemplateServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('createTemplate')
                ->once()
                ->withArgs(function ($data, $chatbot) {
                    // We can assert here that the data received by the service is correct
                    return $data['name'] === 'welcome_message' &&
                           $data['display_name'] === 'Welcome Message' &&
                           $chatbot->id === $this->chatbot->id;
                })
                ->andReturn(MessageTemplate::factory()->make()); // Return a dummy object
        });

        $templateData = [
            'name' => 'welcome_message',
            'display_name' => 'Welcome Message',
            'chatbot_channel_id' => $this->channel->id,
            'category_id' => $this->category->id,
            'language' => 'en_US',
            'header_type' => 'text',
            'header_content' => 'Hello {{1}}',
            'header_variable' => ['placeholder' => '{{1}}', 'example' => 'John'],
            'header_variable_type' => 'positional',
            'body_content' => 'Thanks for signing up, {{name}}!',
            'footer_content' => 'Powered by GetSales',
            'button_config' => [
                ['type' => 'URL', 'text' => 'Visit Us', 'url' => 'https://example.com'],
                ['type' => 'QUICK_REPLY', 'text' => 'No Thanks'],
            ],
            'variables_schema' => [
                ['placeholder' => '{{name}}', 'example' => 'Jane Doe'],
            ],
            'variable_type' => 'named',
        ];

        $response = $this->post(route('message-templates.store', $this->chatbot->id), $templateData);

        $response->assertRedirect(route('message-templates.index', $this->chatbot->id));
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function it_returns_validation_errors_for_missing_required_fields()
    {
        $response = $this->post(route('message-templates.store', $this->chatbot->id), []);

        $response->assertSessionHasErrors([
            'name',
            'display_name',
            'chatbot_channel_id',
            'category_id',
            'language',
            'header_type',
            'body_content',
        ]);
    }

    #[Test]
    public function it_returns_validation_error_for_invalid_button_structure()
    {
        $templateData = [
            'name' => 'test_buttons',
            'display_name' => 'Test Buttons',
            'chatbot_channel_id' => $this->channel->id,
            'category_id' => $this->category->id,
            'language' => 'en_US',
            'header_type' => 'none',
            'body_content' => 'Message with buttons',
            'variable_type' => 'positional',
            'button_config' => [
                ['type' => 'URL', 'text' => 'Valid URL', 'url' => 'invalid-url'], // Invalid URL
            ],
        ];

        $response = $this->post(route('message-templates.store', $this->chatbot->id), $templateData);

        $response->assertSessionHasErrors(['button_config.0.url']);
    }
}
