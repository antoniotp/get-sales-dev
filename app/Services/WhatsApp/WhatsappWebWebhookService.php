<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Events\WhatsApp\WhatsappConnectionStatusUpdated;
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
    ) {
        $this->whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
    }

    public function handle(array $data): void
    {
        Log::info('Handling WhatsApp Web webhook event', $data);
        $dataType = $data['dataType'];

        $methodName = 'handle'.Str::studly($dataType);

        if (method_exists($this, $methodName)) {
            $this->$methodName($data);
        } else {
            Log::warning("No handler for WhatsApp Web webhook event: {$dataType}", $data);
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
                'message' => $message,
            ]);
        }
    }
}
