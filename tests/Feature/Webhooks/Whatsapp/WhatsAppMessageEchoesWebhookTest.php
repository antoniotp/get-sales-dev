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
use Tests\TestCase;

class WhatsAppMessageEchoesWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Chatbot $chatbot;

    private ChatbotChannel $chatbotChannel;

    private string $contactNumber;

    private string $phoneNumberId;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->chatbot = Chatbot::find(1);
        $channel = Channel::where('slug', 'whatsapp')->firstOrFail();
        $this->phoneNumberId = '918518644364527';
        $this->contactNumber = '5212222393661';

        $this->chatbotChannel = $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Channel 1',
            'credentials' => [
                'phone_number_id' => $this->phoneNumberId,
                'phone_number_access_token' => 'test_access_token',
            ],
            'status' => 1,
        ]);

        $this->payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '1630897088294109',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '5215655713579',
                            'phone_number_id' => $this->phoneNumberId,
                        ],
                        'message_echoes' => [[
                            'from' => '5215655713579',
                            'to' => $this->contactNumber,
                            'id' => 'wamid.ECHOED_MESSAGE_ID',
                            'timestamp' => '1768403895',
                            'text' => ['body' => 'This is a test echo'],
                            'type' => 'text',
                        ]],
                    ],
                    'field' => 'smb_message_echoes',
                ]],
            ]],
        ];
    }

    public function test_it_creates_new_message_if_originating_from_external_device(): void
    {
        // Arrange: A contact exists, but no conversation or message yet.
        Contact::factory()->create([
            'phone_number' => $this->contactNumber,
            'organization_id' => $this->chatbot->organization_id,
        ]);

        $this->assertEquals(0, Message::count());

        // Act
        $response = $this->postJson(route('webhook.whatsapp_business'), $this->payload);

        // Assert
        $response->assertStatus(204);
        $this->assertEquals(1, Message::count());
        $message = Message::first();
        $this->assertEquals('outgoing', $message->type);
        $this->assertEquals('human', $message->sender_type);
        $this->assertEquals('wamid.ECHOED_MESSAGE_ID', $message->external_message_id);
    }

    public function test_it_updates_existing_message_to_prevent_duplication(): void
    {
        // Arrange: A message was sent from our app, so it exists in our DB
        // without an external_message_id.
        $contact = Contact::factory()->create(['phone_number' => $this->contactNumber]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->chatbotChannel->channel_id,
        ]);
        $conversation = Conversation::factory()->create([
            'chatbot_channel_id' => $this->chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
            'external_conversation_id' => $this->contactNumber,
        ]);

        $existingMessage = Message::create([
            'conversation_id' => $conversation->id,
            'external_message_id' => null, // No ID yet
            'type' => 'outgoing',
            'sender_type' => 'human',
            'content_type' => 'text',
            'content' => 'This is a test echo', // Same content as payload
            'created_at' => now()->subSeconds(5), // Recently created
        ]);

        $this->assertEquals(1, Message::count());

        // Act
        $response = $this->postJson(route('webhook.whatsapp_business'), $this->payload);

        // Assert
        $response->assertStatus(204);
        $this->assertEquals(1, Message::count()); // No new message should be created

        $existingMessage->refresh();
        $this->assertEquals('wamid.ECHOED_MESSAGE_ID', $existingMessage->external_message_id);
    }
}
