<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Events\WhatsApp\WhatsappConnectionStatusUpdated;
use App\Events\WhatsApp\WhatsappQrCodeReceived;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsappWebWebhookService implements WhatsappWebWebhookServiceInterface
{
    private ?Channel $whatsAppWebChannel;
    private ?ChatbotChannel $chatbotChannel = null;
    private ?Conversation $conversation = null;

    public function __construct(
        private readonly ConversationServiceInterface $conversationService,
        private readonly MessageServiceInterface $messageService
    )
    {
        $this->whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
    }

    public function handle(array $data): void
    {
        Log::info('Handling WhatsApp Web webhook event', $data);
        $eventType = $data['event_type'];

        $methodName = 'handle' . Str::studly($eventType);

        if (method_exists($this, $methodName)) {
            $this->$methodName($data);
        } else {
            Log::warning("No handler for WhatsApp Web webhook event: {$eventType}", $data);
        }
    }

    private function findChatbotBySessionId(string $sessionId): ?Chatbot
    {
        $chatbotId = Str::after($sessionId, 'chatbot-');
        return Chatbot::find($chatbotId);
    }

    private function getValidatedChatbot(string $sessionId): ?Chatbot
    {
        if (!$this->whatsAppWebChannel) {
            Log::error('WhatsApp Web channel not found in database.');
            return null;
        }
        $chatbot = $this->findChatbotBySessionId($sessionId);
        if (!$chatbot) {
            Log::error('Could not find chatbot for session.', ['session_id' => $sessionId]);
            return null;
        }
        return $chatbot;
    }

    private function updateChannelStatus(string $sessionId, int $newStatus, string $statusNameForEvent): void
    {
        $chatbot = $this->getValidatedChatbot($sessionId);
        if (!$chatbot) {
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

    /**
     * Handles the 'qr' event.
     * This event is triggered when a QR code is generated for authentication.
     */
    private function handleQrCodeReceived(array $data): void
    {
        Log::info('Handling QR code event', ['session_id' => $data['session_id']]);

        if (!empty($data['qr_code'])) {
            WhatsappQrCodeReceived::dispatch($data['session_id'], $data['qr_code']);
        } else {
            Log::warning('QR code event received without QR code data.', ['session_id' => $data['session_id']]);
        }
    }

    /**
     * Handles the 'message' event.
     * This event is triggered when a new message is received.
     */
    private function handleMessageReceived(array $data): void
    {
        Log::info('Handling message event', ['session_id' => $data['session_id']]);

        if (!$this->identifyChatbotChannel($data['session_id'])) {
            Log::error('WhatsApp channel not found for session ID: ' . $data['session_id']);
            return;
        }

        if (!$this->getOrCreateContactAndConversation($data)) {
            Log::error('Could not create or update conversation for message', ['data' => $data]);
            return;
        }

        $this->handleTextMessage($data['message']);
    }

    /**
     * Handles the 'ready' event.
     * This event is triggered when the WhatsApp client is ready to send and receive messages.
     */
    private function handleClientReady(array $data): void
    {
        Log::info('Handling ready event', ['session_id' => $data['session_id']]);
        $chatbot = $this->getValidatedChatbot($data['session_id']);
        if (!$chatbot) {
            return;
        }
        $wid = $data['phone_number_id'] ?? null;
        if (!$wid) {
            Log::error('ready event received without WID.', ['session_id' => $data['session_id']]);
            return;
        }

        ChatbotChannel::updateOrCreate(
            [
                'chatbot_id' => $chatbot->id,
                'channel_id' => $this->whatsAppWebChannel->id
            ],
            [
                'name' => 'WA-Web ' . $chatbot->name,
                'credentials' => [
                    'session_id'                     => $data['session_id'],
                    'phone_number'                   => $wid,
                    'phone_number_id'                => $wid,
                    'phone_number_verified_name'     => $wid,
                    'display_phone_number'           => $wid,
                ],
                'status' => ChatbotChannel::STATUS_CONNECTED,
            ]
        );
        WhatsappConnectionStatusUpdated::dispatch($data['session_id'], ChatbotChannel::STATUS_CONNECTED);
    }

    /**
     * Handles the 'disconnected' event.
     * This event is triggered when the WhatsApp client is disconnected.
     */
    private function handleDisconnected(array $data): void
    {
        Log::info('Handling disconnected event', ['session_id' => $data['session_id']]);
        $this->updateChannelStatus($data['session_id'], ChatbotChannel::STATUS_DISCONNECTED, 'DISCONNECTED');
    }

    private function identifyChatbotChannel(string $sessionId): bool
    {
        $chatbot = $this->getValidatedChatbot($sessionId);
        if (!$chatbot) {
            return false;
        }

        $this->chatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $this->whatsAppWebChannel->id)
            ->first();

        return $this->chatbotChannel !== null;
    }

    private function getOrCreateContactAndConversation(array $data): bool
    {
        try {
            $this->conversation = $this->conversationService->findOrCreate(
                chatbotChannel: $this->chatbotChannel,
                channelIdentifier: $data['message']['sender_id'],
                contactName: $data['message']['sender_name'] ?? 'WhatsApp User',
                channelId: $this->whatsAppWebChannel->id
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Error in ConversationService from WhatsappWebWebhookService', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    private function handleTextMessage(array $message): void
    {
        try {
            $this->messageService->handleIncomingMessage(
                conversation: $this->conversation,
                externalMessageId: $message['id'],
                content: $message['body'],
                metadata: [
                    'timestamp' => $message['timestamp'],
                    'from' => $message['sender_id'],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error in MessageService from WhatsappWebWebhookService', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
        }
    }
}
