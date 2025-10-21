<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Events\WhatsApp\WhatsappConnectionStatusUpdated;
use App\Events\WhatsApp\WhatsappQrCodeReceived;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Services\WhatsApp\WhatsappWebWebhookService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsappWebWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappWebWebhookServiceInterface $service;

    private MockInterface $conversationServiceMock;

    private MockInterface $messageServiceMock;

    private Channel $whatsappWebChannel;

    private Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config()->set('services.wwebjs_service.url', 'http://test.wwebjs.service');
        config()->set('services.wwebjs_service.key', 'test-api-key');

        $this->conversationServiceMock = $this->mock(ConversationServiceInterface::class);
        $this->messageServiceMock = $this->mock(MessageServiceInterface::class);

        $this->service = $this->app->make(WhatsappWebWebhookService::class);

        $this->whatsappWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $this->chatbot = Chatbot::find(1);

        Log::shouldReceive('info');
        Log::shouldReceive('error');
        Log::shouldReceive('warning');
        Event::fake();
    }

    #[Test]
    public function it_handles_unknown_data_type_gracefully(): void
    {
        // Arrange
        $payload = [
            'dataType' => 'some_new_data_type',
            'sessionId' => 'chatbot-'.$this->chatbot->id,
        ];

        $this->conversationServiceMock->shouldNotReceive('findOrCreate');
        $this->messageServiceMock->shouldNotReceive('handleIncomingMessage');

        // Act
        $this->service->handle($payload);

        Log::shouldHaveReceived('warning')
            ->with('No handler for WhatsApp Web webhook event: some_new_data_type', $payload)
            ->once();
    }

    #[Test]
    public function it_handles_qr_data_type_and_dispatches_event(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $qrCode = 'test-qr-code-string';
        $payload = [
            'dataType' => 'qr',
            'sessionId' => $sessionId,
            'data' => ['qr' => $qrCode],
        ];

        // Act
        $this->service->handle($payload);

        // Assert
        Event::assertDispatched(WhatsappQrCodeReceived::class, function ($event) use ($sessionId, $qrCode) {
            return $event->sessionId === $sessionId && $event->qrCode === $qrCode;
        });
    }

    #[Test]
    public function it_handles_ready_data_type_and_updates_channel(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $phoneNumber = '5212213835257@c.us';
        $pushName = 'CGM 2';

        $webhookPayload = [
            'dataType' => 'ready',
            'sessionId' => $sessionId,
        ];

        $apiResponse = [
            'success' => true,
            'sessionInfo' => [
                'pushname' => $pushName,
                'wid' => ['_serialized' => $phoneNumber],
            ],
        ];

        Http::fake([
            config('services.wwebjs_service.url').'/client/getClassInfo/'.$sessionId => Http::response($apiResponse, 200),
        ]);

        // Act
        $this->service->handle($webhookPayload);

        // Assert
        $this->assertDatabaseHas('chatbot_channels', [
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'status' => ChatbotChannel::STATUS_CONNECTED,
            'credentials' => json_encode([
                'session_id' => $sessionId,
                'phone_number' => $phoneNumber,
                'phone_number_id' => $phoneNumber,
                'phone_number_verified_name' => $pushName,
                'display_phone_number' => $phoneNumber,
            ]),
        ]);

        Event::assertDispatched(WhatsappConnectionStatusUpdated::class, function ($event) use ($sessionId) {
            return $event->sessionId === $sessionId && $event->status === ChatbotChannel::STATUS_CONNECTED;
        });
    }

    #[Test]
    public function it_handles_message_data_type_and_processes_message(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $senderId = '5212221112233@c.us';
        $messageBody = 'This is a test message';
        $externalMessageId = 'some_external_message_id';
        $timestamp = 1678886400;

        // Create a chatbot channel for the service to find
        ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId, 'phone_number_id' => '1234567890'],
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        $webhookPayload = [
            'dataType' => 'message',
            'sessionId' => $sessionId,
            'data' => [
                'message' => [
                    'id' => ['_serialized' => $externalMessageId],
                    'body' => $messageBody,
                    'type' => 'chat',
                    'timestamp' => $timestamp,
                    'from' => $senderId,
                    'to' => '5212213835257@c.us',
                    'fromMe' => false,
                    'notifyName' => 'Test User Name',
                ],
            ],
        ];

        // Create a Contact and ContactChannel for the mock conversation
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'channel_identifier' => $senderId,
        ]);

        $mockConversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);

        $this->conversationServiceMock->shouldReceive('findOrCreate')
            ->once()
            ->withArgs(function ($chatbotChannelArg, $channelIdentifier, $contactName, $channelId) use ($senderId) {
                // Ensure the chatbotChannel passed to findOrCreate is the one identified by the service
                return $chatbotChannelArg->chatbot_id === $this->chatbot->id &&
                       $chatbotChannelArg->channel_id === $this->whatsappWebChannel->id &&
                       $channelIdentifier === $senderId &&
                       $contactName === 'Test User Name' &&
                       $channelId === $this->whatsappWebChannel->id;
            })
            ->andReturn($mockConversation);

        $this->messageServiceMock->shouldReceive('handleIncomingMessage')
            ->once()
            ->withArgs(function ($conversation, $extMsgId, $content, $metadata) use ($mockConversation, $externalMessageId, $messageBody, $timestamp, $senderId) {
                $normalizedSenderId = $this->app->make(PhoneNumberNormalizerInterface::class)->normalize($senderId);

                return $conversation->id === $mockConversation->id &&
                       $extMsgId === $externalMessageId &&
                       $content === $messageBody &&
                       $metadata['timestamp'] === $timestamp &&
                       $metadata['from'] === $normalizedSenderId;
            });

        // Act
        $this->service->handle($webhookPayload);

        // Assertions are handled by mock expectations
    }
}
