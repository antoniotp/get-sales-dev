<?php

namespace App\Factories\Chat;

use App\Contracts\Services\Chat\ChannelMessageSenderInterface;
use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use Exception;

class MessageSenderFactory
{
    /**
     * Create a new message sender instance based on the channel slug.
     *
     * @param  string  $channelSlug  The slug of the channel (e.g., 'whatsapp', 'whatsapp-web').
     *
     * @throws Exception
     */
    public function make(string $channelSlug): ChannelMessageSenderInterface
    {
        return match ($channelSlug) {
            'whatsapp' => app(WhatsAppServiceInterface::class),
            'whatsapp-web' => app(WhatsAppWebServiceInterface::class),
            default => throw new Exception("Message sender for channel '{$channelSlug}' not found."),
        };
    }
}
