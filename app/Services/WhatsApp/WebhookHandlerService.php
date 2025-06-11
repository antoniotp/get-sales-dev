<?php

namespace App\Services\WhatsApp;

use App\Events\NewWhatsAppMessage;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class WebhookHandlerService
{
    private ?ChatbotChannel $chatbotChannel = null;
    private ?Conversation $conversation = null;

    /**
     * Process the incoming webhook payload.
     *
     * @param array<string, mixed> $payload
     */
    public function process(array $payload): void
    {
        if (!isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            return;
        }

        $value = $payload['entry'][0]['changes'][0]['value'];
        $message = $value['messages'][0];

        // Identify the channel and the conversation before process the message
        if (!$this->identifyChatbotChannel($value['metadata']['phone_number_id'])) {
            Log::error('WhatsApp channel not found for phone number ID: ' . $value['metadata']['phone_number_id']);
            return;
        }

        if (!$this->createOrUpdateConversation($value, $message)) {
            Log::error('Could not create or update conversation for message', ['message' => $message]);
            return;
        }


        match ($message['type']) {
            'text' => $this->handleTextMessage($message),
            'image' => $this->handleImageMessage($message),
            'document' => $this->handleDocumentMessage($message),
            default => $this->handleUnsupportedMessage($message),
        };
    }

    /**
     * Identify the chatbot channel for the incoming message.
     */
    private function identifyChatbotChannel(string $phoneNumberId): bool
    {
        $this->chatbotChannel = ChatbotChannel::where('status', 1)
            ->whereJsonContains('credentials->phone_number_id', $phoneNumberId)
            ->first();

        return $this->chatbotChannel !== null;
    }

    /**
     * Create or update the conversation for the incoming message.
     *
     * @param array<string, mixed> $value
     * @param array<string, mixed> $message
     */
    private function createOrUpdateConversation(array $value, array $message): bool
    {
        try {
            $contact = $value['contacts'][0] ?? null;

            $this->conversation = Conversation::firstOrCreate(
                [
                    'chatbot_channel_id' => $this->chatbotChannel->id,
                    'external_conversation_id' => $message['from'],
                ],
                [
                    'contact_name' => $contact ? ($contact['profile']['name'] ?? null) : null,
                    'contact_phone' => $message['from'],
                    'status' => 1,
                    'mode' => 'ai',
                    'last_message_at' => now(),
                ]
            );

            // Update last_message_at
            if (!$this->conversation->wasRecentlyCreated) {
                $this->conversation->update(['last_message_at' => now()]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error creating/updating conversation', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
            return false;
        }
    }


    /**
     * Handle a text message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleTextMessage(array $message): void
    {
        try {
            $messageData = [
                'conversation_id' => $this->conversation->id,
                'external_message_id' => $message['id'],
                'type' => 'incoming',
                'content' => $message['text']['body'],
                'content_type' => 'text',
                'sender_type' => 'contact',
                'metadata' => [
                    'timestamp' => $message['timestamp'],
                    'from' => $message['from'],
                ],
            ];

            $newMessage = Message::create($messageData);

            // Dispatch the event for real-time updates
            event(new NewWhatsAppMessage($newMessage));

        } catch (\Exception $e) {
            Log::error('Error saving text message', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
        }

    }

    /**
     * Handle image message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleImageMessage(array $message): void
    {
        // Implementar lógica para imágenes
    }

    /**
     * Handle document message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleDocumentMessage(array $message): void
    {
        // Implementar lógica para documentos
    }

    /**
     * Handle unsupported message types.
     *
     * @param array<string, mixed> $message
     */
    private function handleUnsupportedMessage(array $message): void
    {
        Log::info('Unsupported message type received', $message);
    }
}
