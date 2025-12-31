<?php

namespace App\DataTransferObjects\Notification;

use Illuminate\Notifications\DatabaseNotification;

class NotificationData
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $body,
        public readonly string $url,
        public readonly ?string $read_at,
        public readonly string $created_at
    ) {}

    public static function fromNotification(DatabaseNotification $notification): self
    {
        return new self(
            id: $notification->id,
            title: $notification->data['title'] ?? 'Notification',
            body: $notification->data['body'] ?? 'You have a new notification.',
            url: $notification->data['url'] ?? url('/chatbots'),
            read_at: $notification->read_at?->toIso8601String(),
            created_at: $notification->created_at->toIso8601String()
        );
    }
}
