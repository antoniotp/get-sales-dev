<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Events\WhatsApp\WhatsappConnectionStatusUpdated;
use App\Events\WhatsApp\WhatsappQrCodeReceived;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsappWebWebhookService implements WhatsappWebWebhookServiceInterface
{
    private ?Channel $whatsAppWebChannel;

    private ?ChatbotChannel $chatbotChannel = null;

    private ?Conversation $conversation = null;

    private string $wwebjs_url;

    private string $wwebjs_key;

    public function __construct(
        private readonly ConversationServiceInterface $conversationService,
        private readonly MessageServiceInterface $messageService,
        private readonly PhoneNumberNormalizerInterface $phoneNumberNormalizer
    ) {
        $this->whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $this->wwebjs_url = rtrim(config('services.wwebjs_service.url'), '/');
        $this->wwebjs_key = config('services.wwebjs_service.key');
    }

    public function handle(array $data): void
    {
        Log::debug('Handling WhatsApp Web webhook event', $data);
        $dataType = $data['dataType'];

        $methodName = 'handle'.Str::studly($dataType);

        if (method_exists($this, $methodName)) {
            $this->$methodName($data);
        } else {
            Log::warning("No handler for WhatsApp Web webhook event: {$dataType}", $data);
        }
    }

    private function handleQr(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        $qrCode = $payload['data']['qr'] ?? null;

        Log::info('Handling QR code event', ['session_id' => $sessionId]);

        if (! empty($qrCode)) {
            WhatsappQrCodeReceived::dispatch($sessionId, $qrCode);
        } else {
            Log::warning('QR code event received without QR code data.', ['session_id' => $sessionId]);
        }
    }

    private function handleReady(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        Log::info('Handling ready event', ['session_id' => $sessionId]);

        try {
            // Make an API call to get session info
            $response = Http::withHeaders(['x-api-key' => $this->wwebjs_key])
                ->get($this->wwebjs_url.'/client/getClassInfo/'.$sessionId);

            if (! $response->successful()) {
                Log::error('Failed to get session info for ready event.', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                ]);

                return;
            }

            $sessionInfo = $response->json('sessionInfo');
            $wid = $sessionInfo['wid']['user'] ?? null;
            $pushname = $sessionInfo['pushname'] ?? null;

            if (! $wid) {
                Log::error('Ready event processed but no WID found in session info.', ['session_id' => $sessionId]);

                return;
            }

            $chatbot = $this->getValidatedChatbot($sessionId);
            if (! $chatbot) {
                return; // Error already logged in getValidatedChatbot
            }

            ChatbotChannel::updateOrCreate(
                [
                    'chatbot_id' => $chatbot->id,
                    'channel_id' => $this->whatsAppWebChannel->id,
                ],
                [
                    'name' => 'WA-Web '.$chatbot->name,
                    'credentials' => [
                        'session_id' => $sessionId,
                        'phone_number' => $wid,
                        'phone_number_id' => $wid,
                        'phone_number_verified_name' => $pushname,
                        'display_phone_number' => $wid,
                    ],
                    'status' => ChatbotChannel::STATUS_CONNECTED,
                ]
            );

            WhatsappConnectionStatusUpdated::dispatch($sessionId, ChatbotChannel::STATUS_CONNECTED);
            Log::info('Chatbot channel updated to CONNECTED.', ['chatbot_id' => $chatbot->id]);

        } catch (\Exception $e) {
            Log::error('Exception while handling ready event.', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleMessage(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        $messageData = $payload['data']['message'];

        Log::info('Handling message received event', ['session_id' => $sessionId, 'message_id' => $messageData['id']['_serialized']]);

        if (! $this->identifyChatbotChannel($sessionId)) {
            Log::error('WhatsApp channel not found for session ID: '.$sessionId);

            return;
        }

        if (! $this->getOrCreateContactAndConversation(
            $messageData['from'],
            $messageData['notifyName'] ?? 'WhatsApp User'
        )) {
            Log::error('Could not create or update conversation for message', ['data' => $payload]);

            return;
        }

        // Determine the message type for routing
        $messageType = ($messageData['hasMedia'] ?? false) ? 'pending_media' : ($messageData['type'] ?? 'unsupported');

        // Route to the appropriate handler based on the determined type
        match ($messageType) {
            'chat' => $this->handleTextMessage($messageData),
            'pending_media' => $this->handlePendingMediaMessage($messageData),
            default => Log::warning('Unsupported message type in handleMessage', ['type' => $messageType]),
        };
    }

    private function handleTextMessage(array $messageData): void
    {
        try {
            $this->messageService->handleIncomingMessage(
                conversation: $this->conversation,
                externalMessageId: $messageData['id']['_serialized'],
                content: $messageData['body'],
                metadata: [
                    'timestamp' => $messageData['timestamp'],
                    'from' => $this->phoneNumberNormalizer->normalize($messageData['from']),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error in MessageService from WhatsappWebWebhookService', [
                'error' => $e->getMessage(),
                'message' => $messageData,
            ]);
        }
    }

    private function handlePendingMediaMessage(array $messageData): void
    {
        try {
            $this->messageService->createPendingMediaMessage(
                conversation: $this->conversation,
                externalMessageId: $messageData['id']['_serialized'],
                content: $messageData['body'] ?? '',
                type: 'incoming',
                senderType: 'contact',
                metadata: [
                    'timestamp' => $messageData['timestamp'],
                    'from' => $this->phoneNumberNormalizer->normalize($messageData['from']),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error in MessageService from handlePendingMediaMessage', [
                'error' => $e->getMessage(),
                'message' => $messageData,
            ]);
        }
    }

    private function handleMedia(array $payload): void
    {
        $mediaData = $payload['data']['messageMedia'];
        $messageData = $payload['data']['message'];
        $externalId = $messageData['id']['_serialized'];

        Log::info('Handling media received event', ['session_id' => $payload['sessionId'], 'message_id' => $externalId]);

        try {
            // The webhook service must identify the chatbot to pass its ID to the service layer.
            if (! $this->identifyChatbotChannel($payload['sessionId'])) {
                Log::error('WhatsApp channel not found for session ID: '.$payload['sessionId']);

                return;
            }

            $this->messageService->attachMediaToPendingMessage(
                externalMessageId: $externalId,
                fileData: base64_decode($mediaData['data']),
                mimeType: $mediaData['mimetype'],
                contentType: $messageData['type'],
                chatbotId: $this->chatbotChannel->chatbot_id
            );
        } catch (\Exception $e) {
            Log::error('Error processing media webhook', [
                'session_id' => $payload['sessionId'],
                'message_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleMessageCreate(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        $messageData = $payload['data']['message'];

        // Only process messages sent by the bot's own account (outgoing from the user's device)
        if (($messageData['fromMe'] ?? false) !== true) {
            Log::info('Skipping message_create event as it is not from the bot\'s own account.', [
                'session_id' => $sessionId,
                'message_id' => $messageData['id']['_serialized'] ?? 'N/A',
            ]);

            return;
        }

        Log::info('Handling message_create event (outgoing message from user device)', [
            'session_id' => $sessionId,
            'message_id' => $messageData['id']['_serialized'],
        ]);

        if (! $this->identifyChatbotChannel($sessionId)) {
            Log::error('WhatsApp channel not found for session ID: '.$sessionId);

            return;
        }

        // For outgoing messages, the 'to' field is the contact's identifier
        if (! $this->getOrCreateContactAndConversation(
            $messageData['to'], // Use 'to' as the contact identifier
            $messageData['notifyName'] ?? 'You'
        )) {
            Log::error('Could not create or update conversation for message_create', ['data' => $payload]);

            return;
        }

        $externalId = $messageData['id']['_serialized'];
        $messageContent = $messageData['body'];
        $messageData = [
            'external_id' => $externalId,
            'content' => $messageContent,
            'content_type' => 'text',
            'sender_type' => 'human', // It's the human user sending from their phone
            'sender_user_id' => $this->conversation->assigned_user_id, // Assign to the user managing the conversation
            'metadata' => [
                'fromMe' => true,
                'timestamp' => $messageData['timestamp'],
                'from' => $this->phoneNumberNormalizer->normalize($messageData['from']),
            ],
        ];

        try {
            if ($this->conversation->wasRecentlyCreated) {
                Log::info('New conversation from webhook, creating message directly.', ['external_id' => $externalId]);
                $this->messageService->storeExternalOutgoingMessage($this->conversation, $messageData);
            } else {
                // Could be an echo from our app (a message exists) or from a linked phone (no message).
                $existingMessage = $this->conversation->messages()
                    ->where('type', '=', 'outgoing')
                    ->where('sender_type', '!=', 'contact')
                    ->whereNull('external_message_id')
                    ->where('content', $messageContent)
                    ->where('created_at', '>=', now()->subSeconds(30))
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($existingMessage) {
                    // Found the app-sent message. Update it.
                    $existingMessage->update(['external_message_id' => $externalId]);
                    Log::info('Updated previous message with external ID from webhook.', [
                        'message_id' => $existingMessage->id,
                        'external_id' => $externalId,
                    ]);
                } else {
                    Log::info('No existing message found for webhook message. Creating new message.', ['external_id' => $externalId]);
                    // No message found. Assume a message from a linked phone and create it.
                    $this->messageService->storeExternalOutgoingMessage($this->conversation, $messageData);
                }
            }
        } catch (Exception $e) {
            Log::error('Error processing message_create webhook', [
                'session_id' => $sessionId,
                'message_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findChatbotBySessionId(string $sessionId): ?Chatbot
    {
        $chatbotId = Str::after($sessionId, 'chatbot-');

        return Chatbot::find($chatbotId);
    }

    private function getValidatedChatbot(string $sessionId): ?Chatbot
    {
        if (! $this->whatsAppWebChannel) {
            Log::error('WhatsApp Web channel not found in database.');

            return null;
        }
        $chatbot = $this->findChatbotBySessionId($sessionId);
        if (! $chatbot) {
            Log::error('Could not find chatbot for session.', ['session_id' => $sessionId]);

            return null;
        }

        return $chatbot;
    }

    private function updateChannelStatus(string $sessionId, int $newStatus, string $statusNameForEvent): void
    {
        $chatbot = $this->getValidatedChatbot($sessionId);
        if (! $chatbot) {
            return;
        }

        $chatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $this->whatsAppWebChannel->id)
            ->first();

        if ($chatbotChannel) {
            $chatbotChannel->update(['status' => $newStatus]);
            WhatsappConnectionStatusUpdated::dispatch($sessionId, $newStatus);
            Log::info("Chatbot channel updated to $statusNameForEvent.", ['chatbot_id' => $chatbot->id]);
        } else {
            Log::warning("Received $statusNameForEvent event for a non-existent channel.", ['session_id' => $sessionId]);
        }
    }

    private function identifyChatbotChannel(string $sessionId): bool
    {
        $chatbot = $this->getValidatedChatbot($sessionId);
        if (! $chatbot) {
            return false;
        }

        $this->chatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $this->whatsAppWebChannel->id)
            ->first();

        return $this->chatbotChannel !== null;
    }

    private function getOrCreateContactAndConversation(string $contactIdentifier, string $contactName): bool
    {
        try {
            $this->conversation = $this->conversationService->findOrCreate(
                chatbotChannel: $this->chatbotChannel,
                channelIdentifier: $contactIdentifier,
                contactName: $contactName,
                channelId: $this->whatsAppWebChannel->id
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Error in ConversationService from WhatsappWebWebhookService', [
                'error' => $e->getMessage(),
                'contactIdentifier' => $contactIdentifier,
            ]);

            return false;
        }
    }
}
