<?php

namespace App\Contracts\Services\Chat;

use App\Models\ChatbotChannel;
use App\Models\Conversation;

interface ConversationServiceInterface
{
    /**
     * Find or create a conversation based on channel and contact information.
     * This service handles the business logic of ensuring a contact,
     * a contact channel, and a conversation exist for an incoming interaction.
     *
     * @param ChatbotChannel $chatbotChannel The chatbot channel receiving the interaction.
     * @param string $channelIdentifier The contact's unique identifier on the channel (e.g., phone number).
     * @param string $contactName The display name of the contact.
     * @param int $channelId The ID of the communication channel (e.g., WhatsApp, WhatsApp Web).
     * @return Conversation The found or newly created conversation instance.
     */
    public function findOrCreate(
        ChatbotChannel $chatbotChannel,
        string $channelIdentifier,
        string $contactName,
        int $channelId
    ): Conversation;
}
