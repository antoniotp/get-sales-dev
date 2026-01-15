<?php

namespace Tests\Feature\Webhooks\Whatsapp;

use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WhatsAppTextWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Chatbot $chatbot;

    private ChatbotChannel $chatbotChannel;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        Event::fake();

        $this->chatbot = Chatbot::find(1);

        $channel = Channel::where('slug', 'whatsapp')->firstOrFail();

        $phoneNumberId = '918518644667315';
        $this->chatbotChannel = $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Channel 1',
            'credentials' => ['phone_number_id' => $phoneNumberId],
            'status' => 1,
        ]);

        $this->payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '1630897088294109',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '5215655713579',
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Test User'],
                                        'wa_id' => '5212221931663',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '5212221931663',
                                        'id' => 'wamid.HBgNNTIxMjIyMTkzMTY2MxUCABIYFDNBNjk5QkU4NkM4M0YwQzU4RkEwAA==',
                                        'timestamp' => '1767833000',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello, this is a test message.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_it_processes_a_text_message_webhook_and_creates_records(): void
    {
        // Assert initial state
        $this->assertEquals(0, Conversation::count());
        $this->assertEquals(0, Message::count());

        // Act: Send the webhook payload to the controller
        $response = $this->postJson(route('webhook.whatsapp_business'), $this->payload);

        // Assert HTTP response
        $response->assertStatus(204); // No Content

        // Assert database changes
        $this->assertEquals(1, Conversation::count());
        $this->assertEquals(1, Message::count());

        $conversation = Conversation::first();
        $this->assertEquals($this->chatbotChannel->id, $conversation->chatbot_channel_id);
        $this->assertEquals('5212221931663', $conversation->external_conversation_id);
        $this->assertEquals('Test User', $conversation->contact_name);

        $message = Message::first();
        $this->assertEquals($conversation->id, $message->conversation_id);
        $this->assertEquals('Hello, this is a test message.', $message->content);
        $this->assertEquals('text', $message->content_type);
        $this->assertEquals('incoming', $message->type);
        $this->assertEquals('contact', $message->sender_type);
    }
}
