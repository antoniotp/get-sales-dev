<?php

namespace App\Contracts\Services\WhatsApp;

use App\Contracts\Services\Chat\ChannelMessageSenderInterface;
use App\Models\ChatbotChannel;
use App\Models\MessageTemplate;

interface WhatsAppServiceInterface extends ChannelMessageSenderInterface
{
    public function submitTemplateForReview(MessageTemplate $template): array;

    /**
     * Get media info (e.g., URL, mime type) from WhatsApp Business API.
     *
     * @param  string  $mediaId  The ID of the media to retrieve.
     * @param  ChatbotChannel  $channel  The chatbot channel associated with the media.
     * @return array{url: string, mime_type: string}|null Returns an array with 'url' and 'mime_type' if successful, null otherwise.
     */
    public function getMediaInfo(string $mediaId, ChatbotChannel $channel): ?array;

    /**
     * Download media content from a given URL.
     *
     * @param  string  $mediaUrl  The URL from which to download the media.
     * @param  ChatbotChannel  $channel  The chatbot channel associated with the media (for authentication, if needed).
     * @return string|null The binary content of the media file, or null if download fails.
     */
    public function downloadMedia(string $mediaUrl, ChatbotChannel $channel): ?string;
}
