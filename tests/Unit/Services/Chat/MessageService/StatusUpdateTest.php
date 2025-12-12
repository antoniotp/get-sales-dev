<?php

namespace Tests\Unit\Services\Chat\MessageService;

use App\Events\NewWhatsAppMessage;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\MessageService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(MessageService::class)]
class StatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    private Channel $whatsappWebChannel;

    private Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->messageService = $this->app->make(MessageService::class);

        $this->whatsappWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $this->chatbot = Chatbot::find(1);

        Event::fake([NewWhatsAppMessage::class]);
        Log::shouldReceive('warning')->withAnyArgs();
        Log::shouldReceive('info')->withAnyArgs();
    }

    private function createMessage(array $overrides = []): Message
    {
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $externalMessageId = 'test_outgoing_external_id_ack';
        $contactNumber = '5212221931663@c.us';

        $chatbotChannel = ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId],
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'channel_identifier' => $contactNumber,
        ]);

        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
            'chatbot_channel_id' => $chatbotChannel->id,
            'external_conversation_id' => $contactNumber,
        ]);

        // Create a message that this ACK will update (not strictly necessary for this test, as we mock messageService)
        // but good for context
        return Message::create(array_merge([
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'sender_type' => 'human',
            'content_type' => 'text',
            'content' => 'Test message for ACK',
            'external_message_id' => $externalMessageId,
            'sent_at' => now(), // Assume it was sent
            'delivered_at' => null,
            'read_at' => null,
            'failed_at' => null,
        ], $overrides));
    }

    #[Test]
    public function update_status_from_webhook_updates_delivered_at_and_dispatches_event_on_ack_2(): void
    {
        // Arrange
        $message = $this->createMessage();
        $ackStatus = 2; // Delivered

        // Act
        $updatedMessage = $this->messageService->updateStatusFromWebhook($message->external_message_id, $ackStatus);

        // Assert
        $this->assertNotNull($updatedMessage);
        $this->assertEquals($message->id, $updatedMessage->id);
        $this->assertNotNull($updatedMessage->delivered_at);
        $this->assertNull($updatedMessage->read_at);
        Event::assertDispatched(NewWhatsAppMessage::class, function (NewWhatsAppMessage $event) use ($message) {
            return $event->message['id'] === $message->id;
        });
    }

    #[Test]
    public function update_status_from_webhook_updates_read_at_and_delivered_at_and_dispatches_event_on_ack_3(): void
    {
        // Arrange
        $message = $this->createMessage();
        $ackStatus = 3; // Read

        // Act
        $updatedMessage = $this->messageService->updateStatusFromWebhook($message->external_message_id, $ackStatus);

        // Assert
        $this->assertNotNull($updatedMessage);
        $this->assertNotNull($updatedMessage->delivered_at); // Should also be set
        $this->assertNotNull($updatedMessage->read_at);
        Event::assertDispatched(NewWhatsAppMessage::class, function (NewWhatsAppMessage $event) use ($message) {
            return $event->message['id'] === $message->id;
        });
    }

    #[Test]
    public function update_status_from_webhook_does_not_dispatch_event_if_no_status_change(): void
    {
        // Arrange
        $message = $this->createMessage(['delivered_at' => now()]);
        $ackStatus = 2; // Already delivered

        // Act
        $updatedMessage = $this->messageService->updateStatusFromWebhook($message->external_message_id, $ackStatus);

        // Assert
        $this->assertNotNull($updatedMessage);
        Event::assertNotDispatched(NewWhatsAppMessage::class);
    }

    #[Test]
    public function update_status_from_webhook_handles_unknown_external_message_id_gracefully(): void
    {
        // Arrange
        $nonExistentExternalId = 'non-existent-id';
        $ackStatus = 2;

        // Act
        $updatedMessage = $this->messageService->updateStatusFromWebhook($nonExistentExternalId, $ackStatus);

        // Assert
        $this->assertNull($updatedMessage);
        Log::shouldHaveReceived('warning')
            ->with('Received message_ack for an unknown external_message_id', ['external_id' => $nonExistentExternalId])
            ->once();
        Event::assertNotDispatched(NewWhatsAppMessage::class);
    }

    #[Test]
    public function update_status_from_webhook_does_not_overwrite_read_status_with_delivered_ack(): void
    {
        // Arrange
        $readAt = now()->subMinutes(5);
        $deliveredAt = now()->subMinutes(10);
        $message = $this->createMessage(['delivered_at' => $deliveredAt, 'read_at' => $readAt]);
        $ackStatus = 2; // Delivered status, but message is already read

        // Act
        $updatedMessage = $this->messageService->updateStatusFromWebhook($message->external_message_id, $ackStatus);

        // Assert
        $this->assertNotNull($updatedMessage);
        $this->assertEquals($deliveredAt->timestamp, $updatedMessage->delivered_at->timestamp);
        $this->assertEquals($readAt->timestamp, $updatedMessage->read_at->timestamp);
        Event::assertNotDispatched(NewWhatsAppMessage::class);
    }

    #[Test]
    public function update_status_from_webhook_handles_failure_ack_status(): void
    {
        // Arrange
        $message = $this->createMessage();
        $ackStatus = -1; // Assuming -1 means failed

        // Act
        $updatedMessage = $this->messageService->updateStatusFromWebhook($message->external_message_id, $ackStatus);

        // Assert
        // This test only verifies that the code doesn't crash.
        // The current implementation in MessageService does not handle ack -1.
        // This test serves as a reminder to implement failure ACK handling if needed in the future.
        $this->assertNotNull($updatedMessage);
        Event::assertNotDispatched(NewWhatsAppMessage::class);
    }
}
