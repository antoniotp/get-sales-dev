<?php

namespace Tests\Unit\Services\Chat\MessageService;

use App\Events\NewWhatsAppMessage;
use App\Jobs\ProcessAIResponse;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Services\Chat\MessageService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass( MessageService::class )]
class IncomingTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    private string $phoneNumber;

    private Contact $contact;

    private ContactChannel $contactChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->messageService = app(MessageService::class);
        $this->phoneNumber = '5212234567890';
        $this->contact = Contact::factory()->create([
            'phone_number' => $this->phoneNumber,
        ]);
        $this->contactChannel = ContactChannel::factory()->create([
            'contact_id' => $this->contact->id,
            'chatbot_id' => 1,
            'channel_id' => 1,
            'channel_identifier' => $this->phoneNumber,
        ]);
    }

    #[Test]
    public function it_creates_a_message_and_dispatches_an_event(): void
    {
        Event::fake();
        Queue::fake();

        $conversation = Conversation::factory()->create([
            'external_conversation_id' => $this->phoneNumber,
            'contact_name' => $this->contact->first_name.' '.$this->contact->last_name,
            'contact_phone' => $this->phoneNumber,
            'mode' => 'human',
            'contact_channel_id' => $this->contactChannel->id,
            'assigned_user_id' => 1,
        ]);
        $externalMessageId = 'external-message-123';
        $content = 'Hello, this is a test message!';
        $metadata = ['key' => 'value'];

        $message = $this->messageService->handleIncomingMessage(
            $conversation,
            $externalMessageId,
            $content,
            $metadata
        );

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'external_message_id' => $externalMessageId,
            'content' => $content,
            'metadata' => json_encode($metadata),
            'type' => 'incoming',
            'sender_type' => 'contact',
        ]);

        Event::assertDispatched(NewWhatsAppMessage::class, function ($event) use ($message) {
            return $event->message['id'] === $message->id;
        });

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_dispatches_ai_response_job_when_conversation_is_in_ai_mode(): void
    {
        Event::fake();
        Queue::fake();
        Log::shouldReceive('info')->once()->withArgs(function ($message, $context) {
            return $message === 'Dispatching AI processing job for message' && isset($context['message_id']);
        });

        $conversation = Conversation::factory()->create([
            'external_conversation_id' => $this->phoneNumber,
            'contact_name' => $this->contact->first_name.' '.$this->contact->last_name,
            'contact_phone' => $this->phoneNumber,
            'mode' => 'ai',
            'contact_channel_id' => $this->contactChannel->id,
            'assigned_user_id' => 1,
        ]);
        $externalMessageId = 'external-message-456';
        $content = 'AI mode message example.';
        $metadata = ['key' => 'test'];

        $message = $this->messageService->handleIncomingMessage(
            $conversation,
            $externalMessageId,
            $content,
            $metadata
        );

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'external_message_id' => $externalMessageId,
            'content' => $content,
            'metadata' => json_encode($metadata),
            'type' => 'incoming',
            'sender_type' => 'contact',
        ]);

        Queue::assertPushed(ProcessAIResponse::class, function ($job) use ($message) {
            $ref = new ReflectionClass($job);
            $prop = $ref->getProperty('message');
            $prop->setAccessible(true);

            return $prop->getValue($job)['id'] === $message['id'];
        });

        Event::assertDispatched(NewWhatsAppMessage::class, function ($event) use ($message) {
            return $event->message['id'] === $message->id;
        });
    }
}
