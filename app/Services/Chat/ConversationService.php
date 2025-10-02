<?php

namespace App\Services\Chat;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Events\NewWhatsAppConversation;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ConversationService implements ConversationServiceInterface
{
    public function findOrCreate(
        ChatbotChannel $chatbotChannel,
        string $channelIdentifier,
        string $contactName,
        string $initialMode,
        int $channelId
    ): Conversation
    {
        $organizationId = $chatbotChannel->chatbot->organization_id;

        // Find or create the contact channel, which in turn finds or creates the contact.
        $contactChannel = ContactChannel::firstOrCreate(
            [
                'chatbot_id' => $chatbotChannel->chatbot_id,
                'channel_id' => $channelId,
                'channel_identifier' => $channelIdentifier,
            ],
            [
                'contact_id' => Contact::firstOrCreate(
                    ['organization_id' => $organizationId, 'phone_number' => $channelIdentifier],
                    ['first_name' => $contactName]
                )->id,
            ]
        );

        // Now, find or create the conversation.
        $conversation = Conversation::firstOrCreate(
            [
                'chatbot_channel_id' => $chatbotChannel->id,
                'external_conversation_id' => $channelIdentifier,
            ],
            [
                'contact_channel_id' => $contactChannel->id,
                'contact_name' => $contactChannel->contact->first_name,
                'contact_phone' => $channelIdentifier,
                'status' => 1, // 1 is an 'active' status
                'mode' => $initialMode,
                'last_message_at' => now(),
            ]
        );

        // If an old conversation existed without a contact link, update it.
        if (!$conversation->wasRecentlyCreated && is_null($conversation->contact_channel_id)) {
            $conversation->update(['contact_channel_id' => $contactChannel->id]);
        }

        // Dispatch an event for new conversations.
        if ($conversation->wasRecentlyCreated) {
            Log::info('New conversation created by ConversationService', ['conversation' => $conversation]);
            event(new NewWhatsAppConversation($conversation));
        }

        // Always update the last message timestamp for existing conversations to keep them active.
        if (!$conversation->wasRecentlyCreated) {
            $conversation->update(['last_message_at' => now()]);
        }

        return $conversation;
    }
}
