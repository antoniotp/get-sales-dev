<?php

namespace Tests\Feature\Webhooks\Whatsapp;

use App\Enums\Conversation\Status;
use App\Models\Channel;
use App\Models\Chatbot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
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
            "dataType" => "group_update",
            "sessionId" => "chatbot-2", // Will be overwritten in test
            "data" => [
                "notification" => [
                    "id" => [
                        "fromMe" => false,
                        "remote" => "120363423621422738@g.us",
                        "id" => "2385924888create1762209132",
                        "participant" => "48937494913149@lid",
                        "_serialized" => "false_120363423621422738@g.us_2385924888create1762209132_48937494913149@lid"
                    ],
                    "body" => "GetSales2",
                    "type" => "create",
                    "timestamp" => 1762209131,
                    "chatId" => "120363423621422738@g.us",
                    "author" => "48937494913149@lid",
                    "recipientIds" => []
                ]
            ]
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
}
