<?php

namespace App\Services\Chat;

use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Jobs\ProcessAIResponse;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class MessageService implements MessageServiceInterface
{
    public function handleIncomingMessage(
        Conversation $conversation,
        string $externalMessageId,
        string $content,
        array $metadata
    ): Message {
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

    public function storeExternalOutgoingMessage(Conversation $conversation, array $messageData): Message
    {
        return $this->createMessage($conversation, $messageData);
    }

    public function createAndSendOutgoingMessage(Conversation $conversation, array $messageData): Message
    {
        $message = $this->createMessage($conversation, $messageData);
        event(new MessageSent($message));

        return $message;
    }

    private function createMessage(Conversation $conversation, array $messageData): Message
    {
        $message = $conversation->messages()->create([
            'external_message_id' => $messageData['external_id'] ?? null,
            'type' => $messageData['type'] ?? 'outgoing',
            'content' => $messageData['content'],
            'content_type' => $messageData['content_type'] ?? 'text',
            'sender_type' => $messageData['sender_type'] ?? 'human',
            'sender_user_id' => $messageData['sender_user_id'] ?? null,
            'metadata' => $messageData['metadata'] ?? [],
        ]);

        $conversation->update(['last_message_at' => now()]);

        event(new NewWhatsAppMessage($message));

        return $message;
    }

    public function createPendingMediaMessage(
        Conversation $conversation,
        string $externalMessageId,
        string $content,
        string $type,
        string $senderType,
        array $metadata
    ): Message {
        return Message::create([
            'conversation_id' => $conversation->id,
            'external_message_id' => $externalMessageId,
            'type' => $type,
            'content' => $content,
            'content_type' => 'pending',
            'sender_type' => $senderType,
            'metadata' => $metadata,
        ]);
    }

    public function attachMediaToPendingMessage(
        string $externalMessageId,
        string $fileData,
        string $mimeType,
        string $contentType,
        int $chatbotId
    ): Message {
        $message = Message::where('external_message_id', $externalMessageId)->firstOrFail();

        $mimeType = $this->normalizeMimeType($mimeType);
        $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? 'bin';

        $fileName = $message->id.'-'.Str::random(8).'.'.$extension;
        $filePath = 'media/'.$chatbotId.'/'.$fileName;

        Storage::disk('public')->put($filePath, $fileData);

        $message->updateQuietly([
            'media_url' => Storage::url($filePath),
            'content_type' => $contentType,
        ]);

        event(new NewWhatsAppMessage($message));

        Log::info('Successfully processed and stored media for message.', ['message_id' => $message->id, 'path' => $filePath]);

        return $message;
    }

    private function normalizeMimeType(?string $mimeType): ?string
    {
        if (!$mimeType) {
            return null;
        }

        // Split by ";" to delete params (codecs, bitrate, charset, etc.)
        $base = explode(';', $mimeType)[0];

        return strtolower(trim($base));
    }
}
