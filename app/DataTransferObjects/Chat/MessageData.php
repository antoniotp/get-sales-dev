<?php

namespace App\DataTransferObjects\Chat;

use App\Enums\Conversation\Type;
use App\Models\Message;
use Illuminate\Contracts\Support\Arrayable;

class MessageData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $content,
        public string $sender,
        public string|int $senderId,
        public string $timestamp,
        public string $type,
        public string $contentType,
        public ?string $mediaUrl,
        public ?int $conversationId = null
    ) {}

    public static function fromMessage(Message $message): self
    {
        $senderName = 'AI';
        $senderId = 'ai';

        if ($message->sender_type === 'contact') {
            $senderName = $message->senderContact?->first_name ?? 'Unknown Contact';
        } elseif ($message->sender_type === 'human') {
            if ($message->conversation->type === Type::GROUP) {
                $senderName = $message->metadata['participant_name'] ?? ($message->senderUser?->name ?? 'Unknown Participant');
            } else {
                $senderName = $message->senderUser?->name ?? 'Unknown User';
            }
        }

        return new self(
            id: $message->id,
            content: $message->content,
            sender: $senderName,
            senderId: $senderId,
            timestamp: $message->created_at->toIso8601String(),
            type: $message->type,
            contentType: $message->content_type,
            mediaUrl: $message->media_url,
            conversationId: $message->conversation_id
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'content' => $this->content,
            'sender' => $this->sender,
            'senderId' => $this->senderId,
            'timestamp' => $this->timestamp,
            'type' => $this->type,
            'contentType' => $this->contentType,
            'mediaUrl' => $this->mediaUrl,
        ];

        if ($this->conversationId !== null) {
            $data['conversationId'] = $this->conversationId;
        }

        return $data;
    }
}
