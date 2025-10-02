<?php

namespace App\Services\Chat;

use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Events\NewWhatsAppMessage;
use App\Jobs\ProcessAIResponse;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class MessageService implements MessageServiceInterface
{
    public function handleIncomingMessage(
        Conversation $conversation,
        string $externalMessageId,
        string $content,
        array $metadata
    ): Message
    {
        $messageData = [
            'conversation_id' => $conversation->id,
            'external_message_id' => $externalMessageId,
            'type' => 'incoming',
            'content' => $content,
            'content_type' => 'text',
            'sender_type' => 'contact',
            'metadata' => $metadata,
        ];

        $newMessage = Message::create($messageData);

        // Dispatch the event for real-time updates to the frontend.
        event(new NewWhatsAppMessage($newMessage));

        // If the conversation is in AI mode, dispatch a job to process the AI response.
        if ($conversation->mode === 'ai') {
            Log::info('Dispatching AI processing job for message', ['message_id' => $newMessage->id]);
            ProcessAIResponse::dispatch($newMessage)->onQueue('ai-responses');
        }

        return $newMessage;
    }
}
