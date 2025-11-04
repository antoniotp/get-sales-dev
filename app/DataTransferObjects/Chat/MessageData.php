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
            if ($message->conversation->type === Type::GROUP) {
                $senderName = $message->senderContact?->first_name ?? 'Unknown Participant';
            } else {
                $senderName = $message->conversation->contact_name ?? $message->conversation->contact_phone;
            }
            $senderId = 'contact';
        } elseif ($message->sender_type === 'human' && $message->senderUser) {
            $senderName = $message->senderUser->name;
            $senderId = $message->sender_user_id;
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
