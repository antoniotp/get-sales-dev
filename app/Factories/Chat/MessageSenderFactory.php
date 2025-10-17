<?php

namespace App\Factories\Chat;

use App\Contracts\Services\Chat\ChannelMessageSenderInterface;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\LegacyWhatsAppWebService;
use Exception;

class MessageSenderFactory
{
    /**
     * Create a new message sender instance based on the channel slug.
     *
     * @param string $channelSlug The slug of the channel (e.g., 'whatsapp', 'whatsapp-web').
     * @return ChannelMessageSenderInterface
     * @throws Exception
     */
    public function make(string $channelSlug): ChannelMessageSenderInterface
    {
        return match ($channelSlug) {
            'whatsapp' => app(WhatsAppService::class),
            'whatsapp-web' => app(LegacyWhatsAppWebService::class),
            default => throw new Exception("Message sender for channel '{$channelSlug}' not found."),
        };
    }
}
