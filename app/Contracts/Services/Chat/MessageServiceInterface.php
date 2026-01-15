<?php

namespace App\Contracts\Services\Chat;

use App\Models\Conversation;
use App\Models\Message;

interface MessageServiceInterface
{
    /**
     * Handles the business logic for processing and storing an incoming message.
     *
     * @param  Conversation  $conversation  The conversation to which the message belongs.
     * @param  string  $externalMessageId  The unique ID of the message from the external channel.
     * @param  string  $content  The text content of the message.
     * @param  array<string, mixed>  $metadata  Additional data from the channel (e.g., timestamp, sender ID).
     * @return Message The newly created message instance.
     */
    public function handleIncomingMessage(
        Conversation $conversation,
        string $externalMessageId,
        string $content,
        array $metadata,
        ?int $senderContactId = null
    ): Message;

    public function storeExternalOutgoingMessage(
        Conversation $conversation,
        array $messageData,
    ): Message;

    public function createAndSendOutgoingMessage(
        Conversation $conversation,
        array $messageData,
    ): Message;

    public function createPendingMediaMessage(
        Conversation $conversation,
        string $externalMessageId,
        string $content,
        string $type,
        string $senderType,
        array $metadata,
        ?int $senderContactId = null
    ): Message;

    public function attachMediaToPendingMessage(
        string $externalMessageId,
        string $fileData,
        string $mimeType,
        string $contentType,
        int $chatbotId,
    ): Message;

    public function updateStatusFromWebhook(
        string $externalMessageId,
        int $ackStatus
    ): ?Message;

    /**
     * Finds a recent outgoing message that matches the content and does not have an external ID yet.
     * This is used to prevent creating duplicate messages from echo events.
     *
     * @param int $conversationId The ID of the conversation to search within.
     * @param string $content The text content of the message to match.
     * @return Message|null The found message instance, or null if not found.
     */
    public function findRecentOutgoingMessageWithoutExternalId(int $conversationId, string $content): ?Message;
}
