<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
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

    private MockInterface $whatsAppWebServiceMock;

    private Channel $whatsappWebChannel;

    private Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the HTTP client to prevent real API calls from the service detector
        Http::fake([
            '*/ping' => Http::response(['success' => true], 200),
        ]);

        $this->seed(DatabaseSeeder::class);

        config()->set('services.wwebjs_service.url', 'http://test.wwebjs.service');
        config()->set('services.wwebjs_service.key', 'test-api-key');

        $this->conversationServiceMock = $this->mock(ConversationServiceInterface::class);
        $this->messageServiceMock = $this->mock(MessageServiceInterface::class);
        $this->whatsAppWebServiceMock = $this->mock(WhatsAppWebServiceInterface::class);

        $this->service = $this->app->make(WhatsappWebWebhookService::class);

        $this->whatsappWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $this->chatbot = Chatbot::find(1);

        Log::shouldReceive('info');
        Log::shouldReceive('error');
        Log::shouldReceive('warning');
        Log::shouldReceive('debug');
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
                'wid' => ['_serialized' => $phoneNumber, 'user' => $phoneNumber],
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

    #[Test]
    public function it_handles_message_create_data_type_and_stores_outgoing_message(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $botNumber = '5212213835257@c.us'; // The 'from' in message_create is the bot
        $contactNumber = '5212221931663@c.us'; // The 'to' in message_create is the contact
        $messageBody = 'This is an outgoing message';
        $externalMessageId = 'some_external_message_id_outgoing';
        $timestamp = 1761010425;
        $notifyName = 'Test Contact Name';
        $assignedUserId = 1;

        // Create a chatbot channel for the service to find
        $existingChatbotChannel = ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId, 'phone_number_id' => '1234567890'],
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        $webhookPayload = [
            'dataType' => 'message_create',
            'sessionId' => $sessionId,
            'data' => [
                'message' => [
                    'id' => ['_serialized' => $externalMessageId],
                    'body' => $messageBody,
                    'type' => 'chat',
                    'timestamp' => $timestamp,
                    'from' => $botNumber,
                    'to' => $contactNumber,
                    'fromMe' => true,
                    'notifyName' => $notifyName,
                ],
            ],
        ];

        // Create a Contact and ContactChannel for the mock conversation
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'channel_identifier' => $contactNumber,
        ]);

        $mockConversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
            'assigned_user_id' => $assignedUserId,
        ]);

        $this->conversationServiceMock->shouldReceive('findOrCreate')
            ->once()
            ->withArgs(function ($chatbotChannelArg, $channelIdentifier, $contactName, $channelId) use ($contactNumber, $notifyName, $existingChatbotChannel) {
                // For message_create, the contactIdentifier is the 'to' field
                return $chatbotChannelArg->id === $existingChatbotChannel->id &&
                       $channelIdentifier === $contactNumber &&
                       $contactName === $notifyName &&
                       $channelId === $this->whatsappWebChannel->id;
            })
            ->andReturn($mockConversation);

        $this->messageServiceMock->shouldReceive('storeExternalOutgoingMessage')
            ->once()
            ->withArgs(function ($conversation, $messageData) use ($mockConversation, $externalMessageId, $messageBody, $timestamp, $botNumber, $assignedUserId) {
                $normalizedBotNumber = $this->app->make(PhoneNumberNormalizerInterface::class)->normalize($botNumber);

                return $conversation->id === $mockConversation->id &&
                       $messageData['external_id'] === $externalMessageId &&
                       $messageData['content'] === $messageBody &&
                       $messageData['content_type'] === 'text' &&
                       $messageData['sender_type'] === 'human' &&
                       $messageData['sender_user_id'] === $assignedUserId &&
                       $messageData['metadata']['fromMe'] === true &&
                       $messageData['metadata']['timestamp'] === $timestamp &&
                       $messageData['metadata']['from'] === $normalizedBotNumber;
            });

        // Act
        $this->service->handle($webhookPayload);
    }

    #[Test]
    public function it_handles_message_create_with_media_and_stores_outgoing_message(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $botNumber = '5212213835257@c.us';
        $contactNumber = '5212221933661@c.us';
        $externalMessageId = 'some_external_media_message_id';
        $timestamp = 1761868440;
        $caption = 'This is a media caption';
        $assignedUserId = 1;

        $existingChatbotChannel = ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId, 'phone_number_id' => '1234567890'],
            'status' => ChatbotChannel::STATUS_CONNECTED,
        ]);

        $webhookPayload = [
            'dataType' => 'message_create',
            'sessionId' => $sessionId,
            'data' => [
                'message' => [
                    'id' => ['_serialized' => $externalMessageId],
                    'fromMe' => true,
                    'from' => $botNumber,
                    'to' => $contactNumber,
                    'timestamp' => $timestamp,
                    'type' => 'image',
                    'hasMedia' => true,
                    'body' => $caption, // Caption for media
                    '_data' => [
                        'body' => 'base64-goes-here',
                        'mimetype' => 'image/jpeg',
                    ],
                ],
            ],
        ];

        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'channel_identifier' => $contactNumber,
        ]);

        $mockConversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
            'assigned_user_id' => $assignedUserId,
        ]);

        $this->conversationServiceMock->shouldReceive('findOrCreate')->andReturn($mockConversation);

        // Expect the pending message creation method to be called directly
        $this->messageServiceMock->shouldReceive('createPendingMediaMessage')
            ->once()
            ->withArgs(function (
                $conversation,
                $extId,
                $msgCaption,
                $type,
                $senderType,
                $metadata
            ) use (
                $mockConversation,
                $externalMessageId,
                $caption,
                $timestamp
            ) {
                return $conversation->id === $mockConversation->id &&
                       $extId === $externalMessageId &&
                       $msgCaption === $caption &&
                       $type === 'outgoing' &&
                       $senderType === 'human' &&
                       $metadata['fromMe'] === true &&
                       $metadata['timestamp'] === $timestamp &&
                       $metadata['type'] === 'image';
            });

        // Act
        $this->service->handle($webhookPayload);
    }

    #[Test]
    public function it_ignores_e2e_notification_in_handle_message(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $externalMessageId = 'some_e2e_notification_id';
        $webhookPayload = [
            'dataType' => 'message',
            'sessionId' => $sessionId,
            'data' => [
                'message' => [
                    'id' => ['_serialized' => $externalMessageId],
                    'type' => 'e2e_notification',
                    'from' => '5212221112233@c.us',
                ],
            ],
        ];

        $this->conversationServiceMock->shouldNotReceive('findOrCreate');
        $this->messageServiceMock->shouldNotReceive('handleIncomingMessage');

        // Act
        $this->service->handle($webhookPayload);

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Ignoring e2e_notification message', ['session_id' => $sessionId, 'message_id' => $externalMessageId])
            ->once();
    }

    #[Test]
    public function it_ignores_e2e_notification_in_handle_message_create(): void
    {
        // Arrange
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $externalMessageId = 'some_e2e_notification_id_create';
        $webhookPayload = [
            'dataType' => 'message_create',
            'sessionId' => $sessionId,
            'data' => [
                'message' => [
                    'id' => ['_serialized' => $externalMessageId],
                    'type' => 'e2e_notification',
                    'fromMe' => false, // This is important for the guard in handleMessageCreate
                    'from' => '5212221112233@c.us',
                ],
            ],
        ];

        $this->conversationServiceMock->shouldNotReceive('findOrCreate');
        $this->messageServiceMock->shouldNotReceive('storeExternalOutgoingMessage');
        $this->messageServiceMock->shouldNotReceive('createPendingMediaMessage');

        // Act
        $this->service->handle($webhookPayload);

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Ignoring e2e_notification message', ['session_id' => $sessionId, 'message_id' => $externalMessageId])
            ->once();
    }

    #[Test]
    public function it_handles_message_create_with_media_and_updates_existing_message(): void
    {
        // Arrange: Setup common variables
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $contactNumber = '5212221931663@c.us';
        $externalMessageId = 'some_external_media_message_id_to_update';
        $caption = 'An existing media message caption';

        // Arrange: Create the existing chatbot channel and conversation
        $chatbotChannel = ChatbotChannel::create([
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $this->whatsappWebChannel->id,
            'name' => 'WA-Web '.$this->chatbot->name,
            'credentials' => ['session_id' => $sessionId],
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
        $conversation->wasRecentlyCreated = false;

        // Arrange: Create the message that simulates being sent from our app
        $existingMessage = $conversation->messages()->create([
            'type' => 'outgoing',
            'sender_type' => 'human',
            'content' => $caption,
            'content_type' => 'image',
            'external_message_id' => null, // This is what the webhook will fill
            'created_at' => now(),
        ]);

        // Arrange: Mock the conversation service to return our conversation
        $this->conversationServiceMock->shouldReceive('findOrCreate')->andReturn($conversation);

        // Arrange: Prepare the webhook payload that matches the existing message
        $webhookPayload = [
            'dataType' => 'message_create',
            'sessionId' => $sessionId,
            'data' => [
                'message' => [
                    'id' => ['_serialized' => $externalMessageId],
                    'fromMe' => true,
                    'from' => '5212213835257@c.us',
                    'to' => $contactNumber,
                    'timestamp' => now()->unix(),
                    'type' => 'image',
                    'hasMedia' => true,
                    'body' => $caption, // Matching caption
                    '_data' => [
                        'body' => 'base64-data-should-not-be-used-in-this-test',
                        'mimetype' => 'image/jpeg',
                    ],
                ],
            ],
        ];

        // Assert: Ensure the create method is NOT called
        $this->messageServiceMock->shouldNotReceive('storeExternalOutgoingMediaMessage');

        // Act
        $this->service->handle($webhookPayload);

        // Assert: Check that the original message was updated
        $this->assertDatabaseHas('messages', [
            'id' => $existingMessage->id,
            'external_message_id' => $externalMessageId,
        ]);
    }
}
