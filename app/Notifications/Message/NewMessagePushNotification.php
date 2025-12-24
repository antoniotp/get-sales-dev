<?php

namespace App\Notifications\Message;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessagePushNotification extends Notification
{
    use Queueable;

    public Message $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class, 'database'];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        $conversation = $this->message->conversation;
        $chatbot = $conversation->chatbotChannel->chatbot;

        // Determine title and body
        $title = 'New Message!';
        $body = $this->message->content ?? 'You have received a new message.';

        if ($conversation->name) {
            $title = 'New message in '.$conversation->name;
        } elseif ($conversation->contact_name) {
            $title = 'New message from '.$conversation->contact_name;
        }

        // Truncate body if too long
        if (strlen($body) > 100) {
            $body = substr($body, 0, 97).'...';
        }

        $url = route('chats', [
            'chatbot' => $chatbot->id,
            'conversation' => $conversation->id,
        ]);

        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->action('View Conversation', $url)
            ->data(['url' => $url]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
        ];
    }
}
