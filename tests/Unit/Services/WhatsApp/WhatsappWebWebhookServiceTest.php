<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationAuthorizationServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Events\WhatsApp\WhatsappConnectionStatusUpdated;
use App\Events\WhatsApp\WhatsappQrCodeReceived;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Services\Util\PhoneNumberNormalizer;
use App\Services\WhatsApp\WhatsappWebWebhookService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsappWebWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappWebWebhookServiceInterface $service;

    private Channel $whatsappWebChannel;

    private Chatbot $chatbot;

    private MockInterface $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $phoneNormalizer = new PhoneNumberNormalizer;
        $this->authServiceMock = $this->mock(ConversationAuthorizationServiceInterface::class);

        // Default behavior: most tests don't deal with this, so we assume the user is not a restricted agent.
        $this->authServiceMock->shouldReceive('isAgentSubjectToVisibilityRules')->andReturn(false)->byDefault();
        $conversationService = new ConversationService($phoneNormalizer, $this->authServiceMock);
        $messageService = new MessageService;
        $this->service = new WhatsappWebWebhookService($conversationService, $messageService);
        $this->whatsappWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $this->chatbot = Chatbot::find(1);

        // Mock dependencies to keep tests clean and focused
        Log::shouldReceive('info');
        Log::shouldReceive('error');
        Log::shouldReceive('warning');

        Event::fake();
    }

    #[Test]
    public function it_dispatches_qr_code_event(): void
    {
        // Arrange
        $payload = [
            'event_type' => 'qr_code_received',
            'session_id' => 'test-session-123',
            'qr_code' => 'test-qr-code',
        ];

        // Act
        $this->service->handle($payload);

        // Assert
        Event::assertDispatched(WhatsappQrCodeReceived::class, function ($event) use ($payload) {
            return $event->sessionId === $payload['session_id'] && $event->qrCode === $payload['qr_code'];
        });
    }

    #[Test]
    public function it_does_not_dispatch_qr_event_if_qr_code_is_missing(): void
    {
        // Arrange
        $payload = [
            'event_type' => 'qr_code_received',
            'session_id' => 'test-session-123',
            'qr_code' => '', // empty qr code
        ];

        // Act
        $this->service->handle($payload);

        // Assert
        Event::assertNotDispatched(WhatsappQrCodeReceived::class);
    }

    #[Test]
    public function it_handles_unknown_event_gracefully(): void
    {
        // Arrange
        $payload = [
            'event_type' => 'unknown_event',
            'session_id' => 'test-session-123',
        ];

        // Act
        $this->service->handle($payload);

        // Assert: No exception thrown is the main test.
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_client_ready_event_and_creates_channel(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $phoneNumberId = '1234567890';

        $payload = [
            'event_type' => 'client_ready',
            'session_id' => $sessionId,
            'phone_number_id' => $phoneNumberId,
        ];

        // Make sure there is no ChatbotChannel initially
        $this->assertDatabaseMissing('chatbot_channels', [
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
        ]);

        // Act
        $this->service->handle($payload);

        // Assert
        // 1. Assert that ChatbotChannel was created in the database
        $this->assertDatabaseHas('chatbot_channels', [
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        // Retrieve the ChatbotChannel created to verify credentials
        $createdChatbotChannel = ChatbotChannel::where('chatbot_id', $this->chatbot->id)
            ->where('channel_id', $this->whatsappWebChannel->id)
            ->first();

        $this->assertNotNull($createdChatbotChannel);
        $this->assertEquals($sessionId, $createdChatbotChannel->credentials['session_id']);
        $this->assertEquals($phoneNumberId, $createdChatbotChannel->credentials['phone_number_id']);

        // 2. Assert that the WhatsappConnectionStatusUpdated event was dispatched
        Event::assertDispatched(WhatsappConnectionStatusUpdated::class, function ($event) use ($sessionId) {
            return $event->sessionId === $sessionId && $event->status === ChatbotChannel::STATUS_CONNECTED;
        });
    }

    #[Test]
    public function it_handles_disconnected_event_correctly(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;

        // Create a ChatbotChannel that is initially connected
        $chatbotChannel = ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId, 'phone_number_id' => '1234567890'],
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        $payload = [
            'event_type' => 'disconnected',
            'session_id' => $sessionId,
        ];

        // Act
        $this->service->handle($payload);

        // Assert
        // 1. Assert that the ChatbotChannel status has been updated in the database
        $this->assertDatabaseHas('chatbot_channels', [
            'id' => $chatbotChannel->id,
            'status' => ChatbotChannel::STATUS_DISCONNECTED,
        ]);

        // 2. Assert that the WhatsappConnectionStatusUpdated event was dispatched
        Event::assertDispatched(WhatsappConnectionStatusUpdated::class, function ($event) use ($sessionId) {
            return $event->sessionId === $sessionId && $event->status === ChatbotChannel::STATUS_DISCONNECTED;
        });
    }

    #[Test]
    public function it_logs_error_and_does_not_create_channel_for_invalid_chatbot_id(): void
    {
        // Arrange
        $invalidChatbotId = 99999; // A chatbot ID that does not exist
        $sessionId = 'chatbot-'.$invalidChatbotId;
        $phoneNumberId = '1234567890';

        $payload = [
            'event_type' => 'client_ready', // Can be any event type that calls getValidatedChatbot
            'session_id' => $sessionId,
            'phone_number_id' => $phoneNumberId,
        ];

        // Act
        $this->service->handle($payload);

        // Assert
        // 1. Assert no ChatbotChannel was created or updated
        $this->assertDatabaseMissing('chatbot_channels', [
            'chatbot_id' => $invalidChatbotId,
            'channel_id' => $this->whatsappWebChannel->id,
        ]);

        // 2. Assert no WhatsappConnectionStatusUpdated event was dispatched
        Event::assertNotDispatched(WhatsappConnectionStatusUpdated::class);

        // 3. Assert an error was logged
        Log::shouldHaveReceived('error')
            ->with('Could not find chatbot for session.', ['session_id' => $sessionId])
            ->once();
    }

    #[Test]
    public function it_handles_message_sent_event(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $contactPhone = '5212221931663';
        $messageBody = 'Enviando mensaje de prueba';

        // Create a chatbot channel for the service to find
        ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId, 'phone_number_id' => '1234567890'],
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        $payload = [
            'event_type' => 'message_sent',
            'session_id' => $sessionId,
            'message' => [
                'id' => 'AC683E24B45750DE3549960203F18272',
                'fromMe' => true,
                'to' => $contactPhone,
                'sender_id' => '5212213835257',
                'sender_name' => 'CGM 2',
                'body' => $messageBody,
                'timestamp' => 1760033874,
            ],
        ];

        // Mock the MessageService to ensure our new method is called
        $messageServiceMock = $this->createMock(MessageService::class);
        $messageServiceMock->expects($this->once())
            ->method('storeExternalOutgoingMessage')
            ->with(
                $this->isInstanceOf(Conversation::class),
                $this->callback(function ($messageData) use ($messageBody) {
                    return $messageData['content'] === $messageBody && $messageData['sender_type'] === 'human';
                })
            );

        // Re-instantiate the service with the mock
        $conversationService = new ConversationService(new PhoneNumberNormalizer, $this->authServiceMock);
        $this->service = new WhatsappWebWebhookService($conversationService, $messageServiceMock);

        // Act
        $this->service->handle($payload);

        // Assert: The mock expectation 'expects($this->once())' serves as the primary assertion.
    }
}
