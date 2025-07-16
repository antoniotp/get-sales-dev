<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewWhatsAppConversation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation)
    {
        $this->conversation = [
            'id' => $conversation->id,
            'name' => $conversation->contact_name ?? $conversation->contact_phone,
            'avatar' => $conversation->contact_avatar ?? mb_substr($conversation->contact_name ?? 'U', 0, 1),
            'lastMessage' => $conversation->latestMessage?->first()?->content ?? '',
            'lastMessageTime' => $conversation->last_message_at?->toIso8601String(),
            'unreadCount' => $conversation->messages()
                ->whereNull('read_at')
                ->where('type', 'incoming')
                ->count(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat.organization.' . $this->getOrganizationId())
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.created';
    }

    private function getOrganizationId(): int
    {
        // Por ahora retornamos 1 (hardcoded como en ChatController)
        // TODO: Implementar la lógica para obtener el organization_id correcto
        return 1;
    }
}
