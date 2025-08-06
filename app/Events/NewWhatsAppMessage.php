<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\DataTransferObjects\Chat\MessageData;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewWhatsAppMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = MessageData::fromMessage($message)->toArray();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat.conversation.' . $this->message['conversationId'])
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }
}
