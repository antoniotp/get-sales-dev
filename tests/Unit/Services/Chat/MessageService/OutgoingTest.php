<?php

namespace Tests\Unit\Services\Chat\MessageService;

use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Services\Chat\MessageService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(MessageService::class)]
class OutgoingTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    private Conversation $conversation;

    private Contact $contact;

    private ContactChannel $contactChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->messageService = app(MessageService::class);

        $this->contact = Contact::factory()->create();
        $this->contactChannel = ContactChannel::factory()->create([
            'contact_id' => $this->contact->id,
            'chatbot_id' => 1, // from seeder
            'channel_id' => 1, // from seeder
        ]);

        $this->conversation = Conversation::factory()->create([
            'contact_channel_id' => $this->contactChannel->id,
            'assigned_user_id' => 1, // from seeder
            'last_message_at' => null,
        ]);
    }

    #[Test]
    public function it_stores_an_external_outgoing_message_without_dispatching_send_event(): void
    {
        Event::fake();

        $messageData = [
            'type' => 'outgoing',
            'content' => 'This is a test message from an external source.',
            'content_type' => 'text',
            'sender_type' => 'human',
            'sender_user_id' => 1, // Assuming a user is associated
        ];

        // This method doesn't exist yet, so it will fail, which is correct for TDD
        $message = $this->messageService->storeExternalOutgoingMessage(
            $this->conversation,
            $messageData
        );

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $this->conversation->id,
            'content' => $messageData['content'],
        ]);

        // The conversation's last_message_at should be updated
        $this->assertNotNull($this->conversation->fresh()->last_message_at);

        // Assert NewWhatsAppMessage IS dispatched (for frontend updates)
        Event::assertDispatched(NewWhatsAppMessage::class, function ($event) use ($message) {
            return $event->message['id'] === $message->id;
        });

        // Assert MessageSent IS NOT dispatched
        Event::assertNotDispatched(MessageSent::class);
    }

    #[Test]
    public function it_creates_and_sends_an_outgoing_message(): void
    {
        Event::fake();

        $messageData = [
            'type' => 'outgoing',
            'content' => 'This is a test message from an external source.',
            'content_type' => 'text',
            'sender_type' => 'human',
            'sender_user_id' => 1, // Assuming a user is associated
        ];

        $message = $this->messageService->createAndSendOutgoingMessage(
            $this->conversation,
            $messageData
        );

        $this->assertDatabaseHas('messages', ['id' => $message->id]);
        $this->assertNotNull($this->conversation->fresh()->last_message_at);

        // Assert Both events are dispatched
        Event::assertDispatched(NewWhatsAppMessage::class, function ($event) use ($message) {
            return $event->message['id'] === $message->id;
        });
        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message['id'] === $message->id;
        });
    }
}
