<?php

namespace App\DataTransferObjects\Chat;

use App\Models\Conversation;
use Illuminate\Contracts\Support\Arrayable;

class ConversationData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $avatar,
        public string $lastMessage,
        public ?string $lastMessageTime,
        public string $mode,
        public int $unreadCount,
    ) {
    }

    public static function fromConversation(Conversation $conversation): self
    {
        return new self(
            id: $conversation->id,
            name: $conversation->contact_name ?? $conversation->contact_phone,
            avatar: $conversation->contact_avatar ?? mb_substr($conversation->contact_name ?? 'U', 0, 1),
            lastMessage: $conversation->latestMessage?->first()?->content ?? '',
            lastMessageTime: $conversation->last_message_at?->toIso8601String(),
            mode: $conversation->mode ?? 'ai',
            unreadCount: $conversation->messages()
                ->whereNull('read_at')
                ->where('type', 'incoming')
                ->count(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'lastMessage' => $this->lastMessage,
            'lastMessageTime' => $this->lastMessageTime,
            'mode' => $this->mode,
            'unreadCount' => $this->unreadCount,
        ];
    }
}
