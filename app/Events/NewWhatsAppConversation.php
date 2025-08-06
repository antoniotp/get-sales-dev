<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\DataTransferObjects\Chat\ConversationData;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewWhatsAppConversation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $conversation;
    private int $organizationId;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation)
    {
        $this->conversation = (ConversationData::fromConversation($conversation))->toArray();
        $this->organizationId = $conversation->chatbotChannel->chatbot->organization->id;
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
        return $this->organizationId;
    }
}
