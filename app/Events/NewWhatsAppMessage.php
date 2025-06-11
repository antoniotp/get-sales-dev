<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewWhatsAppMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = [
            'id' => $message->id,
            'content' => $message->content,
            'sender' => $message->conversation->contact_name ?? $message->conversation->contact_phone,
            'senderId' => 'contact',
            'timestamp' => $message->created_at->toIso8601String(),
            'type' => $message->type,
            'contentType' => $message->content_type,
            'mediaUrl' => $message->media_url,
            'conversationId' => $message->conversation_id
        ];
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
