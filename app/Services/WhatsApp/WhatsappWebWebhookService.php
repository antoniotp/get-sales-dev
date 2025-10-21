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
        Log::info('Handling WhatsApp Web webhook event', $data);
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
            $wid = $sessionInfo['wid']['_serialized'] ?? null;
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
            $messageData['notifyName'] ?? 'WhatsApp User' // Use notifyName if available
        )) {
            Log::error('Could not create or update conversation for message', ['data' => $payload]);

            return;
        }

        // Reuse existing handleTextMessage
        $this->handleTextMessage($messageData);
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
                externalMessageId: $message['id']['_serialized'],
                content: $message['body'],
                metadata: [
                    'timestamp' => $message['timestamp'],
                    'from' => $this->phoneNumberNormalizer->normalize($message['from']),
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
