<?php

namespace App\Services\Chat;

use App\Contracts\Services\Chat\ConversationAuthorizationServiceInterface;
use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Enums\Chatbot\AgentVisibility;
use App\Enums\Conversation\Status;
use App\Enums\Conversation\Type;
use App\Events\NewWhatsAppConversation;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConversationService implements ConversationServiceInterface
{
    public function __construct(
        private readonly PhoneNumberNormalizerInterface $normalizer,
        private readonly ConversationAuthorizationServiceInterface $conversationAuthorizationService,
        private readonly MessageServiceInterface $messageService
    ) {}

    public function startHumanConversation(
        Chatbot $chatbot,
        array $contactData,
        int $chatbotChannelId,
        int $userId
    ): Conversation {
        $organizationId = $chatbot->organization_id;

        // Find or Create Contact and get normalized phone number
        if (! empty($contactData['contact_id'])) {
            $contact = Contact::where('organization_id', $organizationId)
                ->where('id', $contactData['contact_id'])
                ->firstOrFail();
            $normalizedPhoneNumber = $this->normalizer->normalize($contact->phone_number);
        } else {
            $normalizedPhoneNumber = $this->normalizer->normalize($contactData['phone_number']);
            $contact = Contact::firstOrCreate(
                ['organization_id' => $organizationId, 'phone_number' => $normalizedPhoneNumber],
                [
                    'first_name' => $contactData['first_name'] ?? null,
                    'last_name' => $contactData['last_name'] ?? null,
                ]
            );
        }

        // Load ChatbotChannel, it must exist
        $chatbotChannel = ChatbotChannel::findOrFail($chatbotChannelId);
        // Find or Create ContactChannel
        $contactChannel = ContactChannel::firstOrCreate(
            [
                'contact_id' => $contact->id,
                'chatbot_id' => $chatbot->id,
                'channel_id' => $chatbotChannel->channel_id,
                'channel_identifier' => $normalizedPhoneNumber,
            ]
        );

        // Find or Create Conversation
        $contactName = trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));
        $conversation = Conversation::firstOrCreate(
            [
                'chatbot_channel_id' => $chatbotChannelId,
                'external_conversation_id' => $normalizedPhoneNumber,
            ],
            [
                'contact_channel_id' => $contactChannel->id,
                'contact_name' => $contactName ?: $normalizedPhoneNumber,
                'contact_phone' => $normalizedPhoneNumber,
                'status' => Status::ACTIVE,
                'mode' => 'human', // Initiated by human
                'last_message_at' => now(),
                'assigned_user_id' => $userId,
            ]
        );

        return $conversation;
    }

    public function findOrCreate(
        ChatbotChannel $chatbotChannel,
        string $channelIdentifier,
        string $contactName,
        int $channelId
    ): Conversation {
        if (Str::endsWith($channelIdentifier, '@g.us')) {
            return $this->findOrCreateGroupConversation($chatbotChannel, $channelIdentifier);
        }

        return $this->findOrCreateDirectConversation($chatbotChannel, $channelIdentifier, $contactName, $channelId);
    }

    private function findOrCreateDirectConversation(
        ChatbotChannel $chatbotChannel,
        string $channelIdentifier,
        string $contactName,
        int $channelId
    ): Conversation {
        $organizationId = $chatbotChannel->chatbot->organization_id;
        $normalizedIdentifier = $this->normalizer->normalize($channelIdentifier);

        $contact = Contact::firstOrCreate(
            ['organization_id' => $organizationId, 'phone_number' => $normalizedIdentifier],
            ['first_name' => $contactName]
        );

        $contactChannel = ContactChannel::firstOrCreate(
            [
                'chatbot_id' => $chatbotChannel->chatbot_id,
                'channel_id' => $channelId,
                'channel_identifier' => $normalizedIdentifier,
            ],
            [
                'contact_id' => $contact->id,
            ]
        );

        $initialMode = $chatbotChannel->chatbot->ai_enabled ? 'ai' : 'human';

        $conversation = Conversation::firstOrCreate(
            [
                'chatbot_channel_id' => $chatbotChannel->id,
                'external_conversation_id' => $normalizedIdentifier,
            ],
            [
                'contact_channel_id' => $contactChannel->id,
                'contact_name' => $contactChannel->contact->first_name,
                'contact_phone' => $normalizedIdentifier,
                'status' => Status::ACTIVE,
                'mode' => $initialMode,
                'last_message_at' => now(),
                'type' => Type::DIRECT,
            ]
        );

        if (! $conversation->wasRecentlyCreated) {
            $dataToUpdate = ['last_message_at' => now()];
            if (is_null($conversation->contact_channel_id)) {
                $dataToUpdate['contact_channel_id'] = $contactChannel->id;
            }
            $conversation->update($dataToUpdate);
            $conversation->refresh();
        } else {
            Log::info('New direct conversation created', ['conversation_id' => $conversation->id]);
            event(new NewWhatsAppConversation($conversation));
        }

        return $conversation;
    }

    private function findOrCreateGroupConversation(ChatbotChannel $chatbotChannel, string $groupId): Conversation
    {
        // group conversation must be created in human mode
        $initialMode = 'human';

        $conversation = Conversation::firstOrCreate(
            [
                'chatbot_channel_id' => $chatbotChannel->id,
                'external_conversation_id' => $groupId,
            ],
            [
                'name' => $groupId, // Placeholder name
                'status' => Status::PENDING_NOTIFICATION,
                'mode' => $initialMode,
                'last_message_at' => now(),
                'type' => Type::GROUP,
            ]
        );

        if ($conversation->status === Status::PENDING_NOTIFICATION) {
            Log::info('New group conversation is being notified', ['conversation_id' => $conversation->id]);
            event(new NewWhatsAppConversation($conversation));
            $conversation->update(['status' => Status::ACTIVE]);
        }

        if (! $conversation->wasRecentlyCreated) {
            $conversation->update(['last_message_at' => now()]);
        }

        return $conversation;
    }

    public function updateGroupName(ChatbotChannel $chatbotChannel, string $groupId, string $name): void
    {
        Conversation::updateOrCreate(
            [
                'chatbot_channel_id' => $chatbotChannel->id,
                'external_conversation_id' => $groupId,
            ],
            [
                'name' => $name,
                'type' => Type::GROUP,
                'status' => Status::PENDING_NOTIFICATION,
                'mode' => 'human',
            ]
        );
    }

    public function getConversationsForChatbot(Chatbot $chatbot, User $user): Collection
    {
        $conversationsQuery = Conversation::query()
            ->select([
                'conversations.*',
            ])
            ->with(['latestMessage', 'chatbotChannel.chatbot', 'assignedUser'])
            ->whereHas('chatbotChannel', function ($query) use ($chatbot) {
                $query->where('chatbot_id', $chatbot->id);
            });

        // Apply agent visibility filter
        $organization = $chatbot->organization;
        $role = $user->getRoleInOrganization($organization);
        if ($role && $role->slug === 'agent' && $chatbot->agent_visibility === AgentVisibility::ASSIGNED_ONLY) {
            $conversationsQuery->where('conversations.assigned_user_id', $user->id);
        }

        return $conversationsQuery->orderBy('last_message_at', 'desc')->get();
    }

    public function startConversationFromLink(User $user, Chatbot $chatbot, string $phoneNumber, ?string $text, ?int $chatbotChannelId): Conversation
    {
        $contactData = [
            'phone_number' => $phoneNumber,
        ];

        $conversation = $this->startHumanConversation(
            $chatbot,
            $contactData,
            $chatbotChannelId,
            $user->id
        );

        $organization = $chatbot->organization;

        if (
            ! $conversation->wasRecentlyCreated &&
            $chatbot->agent_visibility === AgentVisibility::ASSIGNED_ONLY &&
            $this->conversationAuthorizationService->isAgentSubjectToVisibilityRules($user, $organization) &&
            $conversation->assigned_user_id !== null &&
            $conversation->assigned_user_id !== $user->id
        ) {
            throw new AuthorizationException('As an agent, you can only access conversations assigned to you.');
        }

        if (! empty($text)) {
            $messageData = [
                'content' => $text,
                'content_type' => 'text',
                'sender_type' => 'human',
                'sender_user_id' => $user->id,
            ];
            $this->messageService->createAndSendOutgoingMessage($conversation, $messageData);
        }

        return $conversation;
    }
}
