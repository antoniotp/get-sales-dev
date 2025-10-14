<?php

namespace App\Contracts\Services\Chat;

use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ConversationServiceInterface
{
    /**
     * Find or create a conversation based on channel and contact information.
     * This service handles the business logic of ensuring a contact,
     * a contact channel, and a conversation exist for an incoming interaction.
     *
     * @param  ChatbotChannel  $chatbotChannel  The chatbot channel receiving the interaction.
     * @param  string  $channelIdentifier  The contact's unique identifier on the channel (e.g., phone number).
     * @param  string  $contactName  The display name of the contact.
     * @param  int  $channelId  The ID of the communication channel (e.g., WhatsApp, WhatsApp Web).
     * @return Conversation The found or newly created conversation instance.
     */
    public function findOrCreate(
        ChatbotChannel $chatbotChannel,
        string $channelIdentifier,
        string $contactName,
        int $channelId
    ): Conversation;

    public function startHumanConversation(
        Chatbot $chatbot,
        array $contactData,
        int $chatbotChannelId,
        int $userId
    ): Conversation;

    public function getConversationsForChatbot(
        Chatbot $chatbot,
        User $user
    ): Collection;

    /**
     * Starts a new conversation from an external link, initiated by a logged-in user.
     *
     * @param  User  $user  The user initiating the action.
     * @param  Chatbot  $chatbot  The chatbot context for the conversation.
     * @param  string  $phoneNumber  The contact's phone number.
     * @param  string|null  $text  The initial message text, if any.
     * @return Conversation The found or newly created conversation.
     */
    public function startConversationFromLink(User $user, Chatbot $chatbot, string $phoneNumber, ?string $text, ?int $channelId): Conversation;
}
