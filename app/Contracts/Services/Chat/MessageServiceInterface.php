<?php

namespace App\Contracts\Services\Chat;

use App\Models\Conversation;
use App\Models\Message;

interface MessageServiceInterface
{
    /**
     * Handles the business logic for processing and storing an incoming message.
     *
     * @param Conversation $conversation The conversation to which the message belongs.
     * @param string $externalMessageId The unique ID of the message from the external channel.
     * @param string $content The text content of the message.
     * @param array<string, mixed> $metadata Additional data from the channel (e.g., timestamp, sender ID).
     * @return Message The newly created message instance.
     */
    public function handleIncomingMessage(
        Conversation $conversation,
        string $externalMessageId,
        string $content,
        array $metadata
    ): Message;
}
