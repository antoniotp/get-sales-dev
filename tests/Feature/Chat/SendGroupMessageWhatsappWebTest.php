<?php

namespace Tests\Feature\Chat;

use App\Enums\Conversation\Status;
use App\Enums\Conversation\Type;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendGroupMessageWhatsappWebTest extends TestCase
{
    use RefreshDatabase;

    protected Chatbot $chatbot;

    protected User $user;

    protected Conversation $groupConversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->chatbot = Chatbot::find(1);
        $this->user = User::find(1);
        Http::fake([
            '*/ping' => Http::response(['success' => true], 200),
        ]);

        $channel = Channel::where('slug', 'whatsapp-web')->first();
        $chatbotChannel = $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Web Channel 1',
            'credentials' => ['phone_number' => '+3333333333'],
            'status' => 1,
        ]);

        $this->groupConversation = Conversation::create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'external_conversation_id' => '1234567890@g.us',
            'type' => Type::GROUP,
            'name' => 'Test Group',
            'status' => Status::ACTIVE,
            'last_message_at' => now(),
        ]);
    }

    protected function postWebhook(array $payload): TestResponse
    {
        return $this->postJson(route('webhook.whatsapp_web'), $payload);
    }

    #[Test]
    public function it_sends_a_message_to_a_group_and_correctly_updates_it_via_webhook(): void
    {
        Event::fake(); // Prevent real events from firing

        // 1. Send message from the app
        $messageContent = 'This is a test message to the group from our app.';
        $response = $this->actingAs($this->user)
            ->post(route('chats.messages.store', ['conversation' => $this->groupConversation->id]), [
                'content' => $messageContent,
                'content_type' => 'text',
            ]);

        $response->assertOk();

        // Assert initial message is created
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->groupConversation->id,
            'content' => $messageContent,
            'sender_user_id' => $this->user->id,
            'sender_type' => 'human',
            'external_message_id' => null, // Not yet updated
        ]);

        // There should be only one message at this point
        $this->assertDatabaseCount('messages', 1);
        $message = Message::first();

        // 2. Simulate the 'message_create' webhook from wwebjs
        $externalId = 'true_1234567890@g.us_ABCDEF123456_987654321@lid';
        $participantId = '987654321@lid';
        $participantName = 'My App Number';

        $webhookPayload = [
            'dataType' => 'message_create',
            'sessionId' => 'chatbot-'.$this->chatbot->id,
            'data' => [
                'message' => [
                    '_data' => [
                        'notifyName' => $participantName,
                    ],
                    'id' => [
                        'fromMe' => true,
                        'remote' => $this->groupConversation->external_conversation_id,
                        'id' => 'ABCDEF123456',
                        'participant' => $participantId,
                        '_serialized' => $externalId,
                    ],
                    'body' => $messageContent, // Body must match for de-duplication
                    'to' => $this->groupConversation->external_conversation_id,
                    'from' => $participantId,
                    'author' => $participantId,
                    'notifyName' => $participantName,
                    'fromMe' => true,
                ],
            ],
        ];

        $webhookResponse = $this->postWebhook($webhookPayload);
        $webhookResponse->assertOk();

        // 3. Assert de-duplication worked
        // There should STILL be only one message
        $this->assertDatabaseCount('messages', 1);

        // The original message should now be updated
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'external_message_id' => $externalId,
            'metadata->participant_name' => $participantName,
        ]);
    }
}
