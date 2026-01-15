<?php

namespace Tests\Feature\Webhooks\Whatsapp;

use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WhatsAppDeliveredWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Chatbot $chatbot;

    private ChatbotChannel $chatbotChannel;

    private array $payload;

    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        Event::fake();

        $this->chatbot = Chatbot::find(1);
        $channel = Channel::where('slug', 'whatsapp')->firstOrFail();
        $phoneNumberId = '918518644364527';
        $contactNumber = '5212222393661';

        $this->chatbotChannel = $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Channel 1',
            'credentials' => [
                'phone_number' => '+3333333333',
                'phone_number_id' => $phoneNumberId,
                'phone_number_access_token' => 'test_access_token',
            ],
            'status' => 1,
        ]);

        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $channel->id,
            'channel_identifier' => $contactNumber,
        ]);

        $conversation = Conversation::factory()->create([
            'chatbot_channel_id' => $this->chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
            'external_conversation_id' => $contactNumber,
        ]);

        // Create an outgoing message to be updated by the webhook
        $this->message = Message::create([
            'conversation_id' => $conversation->id,
            'external_message_id' => 'wamid.HBgNNTIxMjIyMTkzMTY2MxUCABEYFDJBQ0QyNEFFRTA3MTU3QzM4QzBGAA==',
            'type' => 'outgoing',
            'sender_type' => 'human',
            'content_type' => 'text',
            'content' => 'Test message for ACK',
            'sent_at' => now(), // Assume it was sent
            'delivered_at' => null,
            'read_at' => null,
            'failed_at' => null,
        ]);

        // Payload for a "delivered" status update
        $this->payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '1630897088294109',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '5215655713579',
                            'phone_number_id' => $phoneNumberId,
                        ],
                        'statuses' => [[
                            'id' => 'wamid.HBgNNTIxMjIyMTkzMTY2MxUCABEYFDJBQ0QyNEFFRTA3MTU3QzM4QzBGAA==',
                            'status' => 'delivered',
                            'timestamp' => '1768403895',
                            'recipient_id' => $contactNumber,
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];
    }

    public function test_it_processes_a_delivered_status_webhook_and_updates_message(): void
    {
        // Assert initial state
        $this->assertNull($this->message->delivered_at);

        // Act
        $response = $this->postJson(route('webhook.whatsapp_business'), $this->payload);

        // Assert
        $response->assertStatus(204);

        $this->message->refresh();
        $this->assertNotNull($this->message->delivered_at);
        $this->assertNull($this->message->read_at); // Ensure read_at is still null
    }
}
