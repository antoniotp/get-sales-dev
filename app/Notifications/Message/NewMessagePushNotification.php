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
        $payload = $this->buildNotificationPayload();

        return (new WebPushMessage)
            ->title($payload['title'])
            ->body($payload['body'])
            ->action('View Conversation', $payload['url'])
            ->data(['url' => $payload['url']]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge($this->buildNotificationPayload(), [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
        ]);
    }

    /**
     * Build the common notification payload.
     *
     * @return array{title: string, body: string, url: string}
     */
    private function buildNotificationPayload(): array
    {
        $conversation = $this->message->conversation;
        $chatbot = $conversation->chatbotChannel->chatbot;

        $title = $conversation->name ?? ('New message from '.$conversation->contact_name);
        $body = $this->message->content ?? 'You have received a new message.';
        if (strlen($body) > 100) {
            $body = substr($body, 0, 97).'...';
        }

        $url = route('chats', [
            'chatbot' => $chatbot->id,
            'conversation' => $conversation->id,
        ]);

        return [
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ];
    }
}
