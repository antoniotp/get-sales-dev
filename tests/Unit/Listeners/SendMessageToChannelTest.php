<?php

namespace Tests\Unit\Listeners;

use App\Contracts\Services\Chat\ChannelMessageSenderInterface;
use App\DataTransferObjects\Chat\MessageSendResult;
use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Exceptions\MessageSendException;
use App\Factories\Chat\MessageSenderFactory;
use App\Listeners\SendMessageToChannel;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use Database\Seeders\DatabaseSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(SendMessageToChannel::class)]
class SendMessageToChannelTest extends TestCase
{
    use RefreshDatabase;

    private MessageSenderFactory|MockInterface $factoryMock;

    private ChannelMessageSenderInterface|MockInterface $senderMock;

    private SendMessageToChannel $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->factoryMock = Mockery::mock(MessageSenderFactory::class);
        $this->senderMock = Mockery::mock(ChannelMessageSenderInterface::class);

        // Default mock setup
        $this->factoryMock->shouldReceive('make')->andReturn($this->senderMock);

        $this->listener = new SendMessageToChannel($this->factoryMock);
    }

    private function createOutgoingTextMessage(): Message
    {
        $chatbot = Chatbot::find(1);
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
        ]);
        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);

        return Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'outgoing',
            'content' => 'Hello world',
            'content_type' => 'text',
            'sender_type' => 'human',
        ]);
    }

    #[Test]
    public function handle_sends_message_successfully_and_updates_status()
    {
        // Arrange
        Event::fake([NewWhatsAppMessage::class]);
        $message = $this->createOutgoingTextMessage();
        $event = new MessageSent($message);
        $expectedResult = new MessageSendResult('test-external-id-123');

        $this->senderMock->shouldReceive('sendMessage')
            ->once()
            ->with($message)
            ->andReturn($expectedResult);

        // Act
        $this->listener->handle($event);

        // Assert
        $message->refresh();
        $this->assertNotNull($message->sent_at);
        $this->assertEquals('test-external-id-123', $message->external_message_id);
        $this->assertNull($message->failed_at);

        Event::assertDispatched(NewWhatsAppMessage::class, function (NewWhatsAppMessage $event) use ($message) {
            return $event->message['id'] === $message->id;
        });
    }

    #[Test]
    public function handle_marks_message_as_failed_on_message_send_exception()
    {
        // Arrange
        Event::fake([NewWhatsAppMessage::class]);
        $message = $this->createOutgoingTextMessage();
        $event = new MessageSent($message);
        $exception = new MessageSendException('API Error');

        $this->senderMock->shouldReceive('sendMessage')
            ->once()
            ->with($message)
            ->andThrow($exception);

        // Act
        $this->listener->handle($event);

        // Assert
        $message->refresh();
        $this->assertNotNull($message->failed_at);
        $this->assertEquals('API Error', $message->error_message);
        $this->assertNull($message->sent_at);

        Event::assertDispatched(NewWhatsAppMessage::class);
    }

    #[Test]
    public function handle_marks_message_as_failed_on_generic_exception()
    {
        // Arrange
        Event::fake([NewWhatsAppMessage::class]);
        $message = $this->createOutgoingTextMessage();
        $event = new MessageSent($message);
        $exception = new Exception('Unexpected Error');

        $this->senderMock->shouldReceive('sendMessage')
            ->once()
            ->with($message)
            ->andThrow($exception);

        // Act
        $this->listener->handle($event);

        // Assert
        $message->refresh();
        $this->assertNotNull($message->failed_at);
        $this->assertEquals('An unexpected error occurred.', $message->error_message);
        $this->assertNull($message->sent_at);

        Event::assertDispatched(NewWhatsAppMessage::class);
    }

    #[Test]
    public function handle_ignores_incoming_messages()
    {
        // Arrange
        Event::fake([NewWhatsAppMessage::class]);
        $message = $this->createOutgoingTextMessage();
        $message->update(['type' => 'incoming']); // Change type to incoming
        $event = new MessageSent($message);

        $this->senderMock->shouldNotReceive('sendMessage');
        $this->factoryMock->shouldNotReceive('make');

        // Act
        $this->listener->handle($event);

        // Assert
        Event::assertNotDispatched(NewWhatsAppMessage::class);
    }

    #[Test]
    public function handle_ignores_non_text_messages()
    {
        // Arrange
        Event::fake([NewWhatsAppMessage::class]);
        $message = $this->createOutgoingTextMessage();
        $message->update(['content_type' => 'image']); // Change content type
        $event = new MessageSent($message);

        $this->senderMock->shouldNotReceive('sendMessage');
        $this->factoryMock->shouldNotReceive('make');

        // Act
        $this->listener->handle($event);

        // Assert
        Event::assertNotDispatched(NewWhatsAppMessage::class);
    }
}
