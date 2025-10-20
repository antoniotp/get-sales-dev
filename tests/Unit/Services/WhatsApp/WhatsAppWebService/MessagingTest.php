<?php

namespace Tests\Unit\Services\WhatsApp\WhatsAppWebService;

use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsApp\WhatsAppWebService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(WhatsAppWebService::class)]
class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config()->set('services.wwebjs_service.url', 'http://test.wwebjs.service');
        config()->set('services.wwebjs_service.key', 'test-api-key');
    }

    #[Test]
    public function send_message_dispatches_correct_post_request_for_text_message()
    {
        // Arrange
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $chatbot = Chatbot::find(1);
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
            'channel_identifier' => '1234567890@c.us', // Example chatId
        ]);
        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);

        // Create a message directly, as the method expects an existing one
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'content' => 'Hello world',
            'content_type' => 'text',
            'sender_type' => 'ai', // or 'human'
        ]);

        $service = new WhatsAppWebService;
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = config('services.wwebjs_service.url').'/client/sendMessage/'.$sessionId;

        // Act
        $service->sendMessage($message);

        // Assert
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($url, $contactChannel, $message) {
            return $request->url() == $url &&
                   $request['chatId'] == $contactChannel->channel_identifier &&
                   $request['contentType'] == 'string' &&
                   $request['content'] == $message->content;
        });
    }

    #[Test]
    public function send_message_handles_server_error_gracefully()
    {
        // Arrange
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $chatbot = Chatbot::find(1);
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
        ]);
        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'content' => 'Hello world',
            'content_type' => 'text',
            'sender_type' => 'ai',
        ]);

        $service = new WhatsAppWebService;

        // Act & Assert
        $this->expectNotToPerformAssertions();
        try {
            $service->sendMessage($message);
        } catch (\Exception $e) {
            $this->fail('sendMessage should not throw exceptions on API failure.');
        }
    }

    #[Test]
    public function send_message_handles_client_error_gracefully()
    {
        // Arrange
        Http::fake([
            '*' => Http::response(null, 422),
        ]);

        $chatbot = Chatbot::find(1);
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
        ]);
        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'content' => 'Hello world',
            'content_type' => 'text',
            'sender_type' => 'ai',
        ]);

        $service = new WhatsAppWebService;

        // Act & Assert
        $this->expectNotToPerformAssertions();
        try {
            $service->sendMessage($message);
        } catch (\Exception $e) {
            $this->fail('sendMessage should not throw exceptions on API failure.');
        }
    }

    #[Test]
    public function send_message_handles_http_exception_gracefully()
    {
        // Arrange
        Http::fake([
            '*' => function () {
                throw new \Exception('Connection failed');
            },
        ]);

        $chatbot = Chatbot::find(1);
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
        ]);
        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'content' => 'Hello world',
            'content_type' => 'text',
            'sender_type' => 'ai',
        ]);

        $service = new WhatsAppWebService;

        // Act & Assert
        $this->expectNotToPerformAssertions();
        try {
            $service->sendMessage($message);
        } catch (\Exception $e) {
            $this->fail('sendMessage should not throw exceptions on API failure.');
        }
    }
}
