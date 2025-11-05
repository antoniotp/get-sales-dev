<?php

namespace Tests\Feature\Webhooks\Whatsapp;

use App\Enums\Conversation\Status;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\Conversation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsappWebGroupEventsTest extends TestCase
{
    use RefreshDatabase;

    protected Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->chatbot = Chatbot::find(1);

        $channel = Channel::where('slug', 'whatsapp-web')->first();
        $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Web Channel 1',
            'credentials' => ['phone_number' => '+3333333333'],
            'status' => 1,
        ]);
    }

    protected function postWebhook(array $payload): TestResponse
    {
        return $this->postJson(route('webhook.whatsapp_web'), $payload);
    }

    private function getGroupUpdatePayload(): array
    {
        return [
            'dataType' => 'group_update',
            'sessionId' => 'chatbot-2', // Will be overwritten in test
            'data' => [
                'notification' => [
                    'id' => [
                        'fromMe' => false,
                        'remote' => '120363423621422738@g.us',
                        'id' => '2385924888create1762209132',
                        'participant' => '48937494913149@lid',
                        '_serialized' => 'false_120363423621422738@g.us_2385924888create1762209132_48937494913149@lid',
                    ],
                    'body' => 'GetSales2',
                    'type' => 'create',
                    'timestamp' => 1762209131,
                    'chatId' => '120363423621422738@g.us',
                    'author' => '48937494913149@lid',
                    'recipientIds' => [],
                ],
            ],
        ];
    }

    private function getIncomingGroupMessagePayload(): array
    {
        return [
            'dataType' => 'message',
            'sessionId' => 'chatbot-2', // Will be overwritten in test
            'data' => [
                'message' => [
                    '_data' => [
                        'notifyName' => 'Cris Gonzalez',
                    ],
                    'id' => [
                        'fromMe' => false,
                        'remote' => '120363422499819089@g.us',
                        'id' => '2A3943F5AFB8A021AC31',
                        'participant' => '24159878938876@lid',
                        '_serialized' => 'false_120363422499819089@g.us_2A3943F5AFB8A021AC31_24159878938876@lid',
                    ],
                    'hasMedia' => false,
                    'body' => 'Mensaje en grupo',
                    'type' => 'chat',
                    'timestamp' => 1761860595,
                    'from' => '120363422499819089@g.us',
                    'to' => '5212213835257@c.us',
                    'author' => '24159878938876@lid',
                    'notifyName' => 'Cris Gonzalez',
                ],
            ],
        ];
    }

    #[Test]
    public function it_creates_a_group_conversation_on_group_update_event(): void
    {
        // Arrange
        $payload = $this->getGroupUpdatePayload();
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $payload['sessionId'] = $sessionId;

        $groupId = $payload['data']['notification']['chatId'];
        $groupName = $payload['data']['notification']['body'];

        $whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $chatbotChannel = $this->chatbot->chatbotChannels()->where('channel_id', $whatsAppWebChannel->id)->first();

        // Act
        $response = $this->postWebhook($payload);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'chatbot_channel_id' => $chatbotChannel->id,
            'external_conversation_id' => $groupId,
            'type' => 'group',
            'name' => $groupName,
            'status' => Status::PENDING_NOTIFICATION->value,
        ]);
    }

    #[Test]
    public function it_handles_incoming_group_message_and_creates_contact_and_message(): void
    {
        // Arrange
        $payload = $this->getIncomingGroupMessagePayload();
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $payload['sessionId'] = $sessionId;

        $groupId = $payload['data']['message']['from'];
        $participantId = $payload['data']['message']['author'];
        $participantName = $payload['data']['message']['notifyName'];
        $messageContent = $payload['data']['message']['body'];
        $externalMessageId = $payload['data']['message']['id']['_serialized'];

        $whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $chatbotChannel = $this->chatbot->chatbotChannels()->where('channel_id', $whatsAppWebChannel->id)->first();

        // Act
        $response = $this->postWebhook($payload);

        // Assert
        $response->assertOk();

        // Assert that the group conversation was created (implicitly)
        $this->assertDatabaseHas('conversations', [
            'chatbot_channel_id' => $chatbotChannel->id,
            'external_conversation_id' => $groupId,
            'type' => 'group',
            'status' => Status::ACTIVE->value, // Should be active now
        ]);

        // Assert that the participant contact was created
        $this->assertDatabaseHas('contacts', [
            'phone_number' => $participantId, // Assuming participant ID is used as phone number for uniqueness
            'first_name' => $participantName,
        ]);

        $contact = Contact::where('phone_number', $participantId)->first();
        $conversation = Conversation::where('external_conversation_id', $groupId)->first();

        // Assert that the message was created and linked correctly
        $this->assertDatabaseHas('messages', [
            'external_message_id' => $externalMessageId,
            'content' => $messageContent,
            'sender_type' => 'contact',
            'sender_contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
        ]);
    }

    private function getOutgoingGroupMessageCreatePayload(): array
    {
        return [
            'dataType' => 'message_create',
            'sessionId' => 'chatbot-2', // Will be overwritten in test
            'data' => [
                'message' => [
                    '_data' => [
                        'notifyName' => 'CGM 2',
                    ],
                    'id' => [
                        'fromMe' => true,
                        'remote' => '120363422499819089@g.us',
                        'id' => 'ACEF2E8E08CF379CECCDBEEB9B9BA72D',
                        'participant' => '48937494913149@lid',
                        '_serialized' => 'true_120363422499819089@g.us_ACEF2E8E08CF379CECCDBEEB9B9BA72D_48937494913149@lid',
                    ],
                    'hasMedia' => false,
                    'body' => 'Mensaje en grupo desde CGM 2',
                    'type' => 'chat',
                    'timestamp' => 1762207803,
                    'from' => '48937494913149@lid',
                    'to' => '120363422499819089@g.us',
                    'author' => '48937494913149@lid',
                    'notifyName' => 'CGM 2',
                    'fromMe' => true,
                ],
            ],
        ];
    }

    #[Test]
    public function it_handles_outgoing_group_message_create_and_creates_contact_and_message(): void
    {
        // Arrange
        $payload = $this->getOutgoingGroupMessageCreatePayload();
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $payload['sessionId'] = $sessionId;

        $groupId = $payload['data']['message']['to']; // For outgoing, 'to' is the group ID
        $participantId = $payload['data']['message']['from']; // For outgoing, 'from' is the participant ID
        $participantName = $payload['data']['message']['notifyName'];
        $messageContent = $payload['data']['message']['body'];
        $externalMessageId = $payload['data']['message']['id']['_serialized'];

        $whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $chatbotChannel = $this->chatbot->chatbotChannels()->where('channel_id', $whatsAppWebChannel->id)->first();

        // Ensure the group conversation exists (from a previous group_update event or created implicitly)
        Conversation::firstOrCreate(
            [
                'chatbot_channel_id' => $chatbotChannel->id,
                'external_conversation_id' => $groupId,
            ],
            [
                'type' => 'group',
                'name' => 'Test Group Name', // Placeholder name
                'status' => Status::ACTIVE, // Assume it's active for outgoing messages
            ]
        );

        // Act
        $response = $this->postWebhook($payload);

        // Assert
        $response->assertOk();

        // Assert that the participant contact was NOT created (as it's our own number)
        $this->assertDatabaseMissing('contacts', [
            'phone_number' => $participantId,
        ]);

        $conversation = Conversation::where('external_conversation_id', $groupId)->first();

        // Assert that the message was created and linked correctly
        $this->assertDatabaseHas('messages', [
            'external_message_id' => $externalMessageId,
            'content' => $messageContent,
            'sender_type' => 'human', // Outgoing messages are from 'human' (our agent)
            'sender_contact_id' => null, // Should be null for outgoing messages from our app
            'conversation_id' => $conversation->id,
            'metadata->participant_name' => $participantName, // Participant name stored in metadata
        ]);
    }

    #[Test]
    public function it_fetches_group_name_when_receiving_first_message_from_unknown_group(): void
    {
        // Arrange
        $payload = $this->getIncomingGroupMessagePayload();
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $payload['sessionId'] = $sessionId;

        $groupId = $payload['data']['message']['from'];
        $expectedGroupName = 'Fetched Group Name';

        // Mock the HTTP call to the wwebjs service
        Http::fake([
            '*/groupChat/getClassInfo/*' => Http::response([
                'success' => true,
                'chat' => [
                    'name' => $expectedGroupName,
                    // ... other group properties
                ],
            ]),
        ]);

        // Ensure the conversation does not exist beforehand
        $this->assertDatabaseMissing('conversations', [
            'external_conversation_id' => $groupId,
        ]);

        // Act
        $response = $this->postWebhook($payload);

        // Assert
        $response->assertOk();

        // Assert that the API was called
        Http::assertSent(function ($request) use ($groupId) {
            return $request->hasHeader('x-api-key') &&
                   str_contains($request->url(), 'groupChat/getClassInfo') &&
                   $request['chatId'] == $groupId;
        });

        // Assert that the conversation was created and its name was updated
        $this->assertDatabaseHas('conversations', [
            'external_conversation_id' => $groupId,
            'name' => $expectedGroupName,
        ]);
    }
}
