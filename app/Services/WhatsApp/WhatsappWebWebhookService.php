<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Contact\ContactServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Enums\ChatbotChannel\SettingKey;
use App\Events\NewWhatsAppConversation;
use App\Events\WhatsApp\WhatsappConnectionStatusUpdated;
use App\Events\WhatsApp\WhatsappQrCodeReceived;
use App\Facades\WwebjsUrl;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsappWebWebhookService implements WhatsappWebWebhookServiceInterface
{
    private ?Channel $whatsAppWebChannel;

    private ?ChatbotChannel $chatbotChannel = null;

    private ?Conversation $conversation = null;

    public function __construct(
        private readonly ConversationServiceInterface $conversationService,
        private readonly MessageServiceInterface $messageService,
        private readonly PhoneNumberNormalizerInterface $phoneNumberNormalizer,
        private readonly WhatsappWebServiceInterface $whatsappWebService,
        private readonly ContactServiceInterface $contactService,
    ) {
        $this->whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
    }

    public function handle(array $data): void
    {
        Log::debug('Handling WhatsApp Web webhook event', $data);
        $dataType = $data['dataType'];
        switch ($dataType) {
            case 'qr':
                $this->handleQr($data);
                break;
            case 'ready':
                $this->handleReady($data);
                break;
            case 'message':
                $this->handleMessage($data);
                break;
            case 'media':
                $this->handleMedia($data);
                break;
            case 'message_create':
                $this->handleMessageCreate($data);
                break;
            case 'group_update':
                $this->handleGroupUpdate($data);
                break;
            case 'message_ack':
                $this->handleMessageAck($data);
                break;
            case 'call':
                $this->handleCall($data);
                break;
            default:
                Log::warning("No handler for WhatsApp Web webhook event: {$dataType}", $data);
        }
    }

    private function handleGroupUpdate(array $payload): void
    {
        Log::info('Handling group_update event', ['session_id' => $payload['sessionId'], 'data' => $payload['data']]);

        if (! $this->identifyChatbotChannel($payload['sessionId'])) {
            Log::error('WhatsApp channel not found for session ID: '.$payload['sessionId']);

            return;
        }

        $notification = $payload['data']['notification'];
        $groupId = $notification['chatId'];
        $groupName = $notification['body'];

        // We only care about the event that creates the group or updates its subject
        if ($notification['type'] === 'create' || $notification['type'] === 'subject') {
            $this->conversationService->updateGroupName($this->chatbotChannel, $groupId, $groupName);
            Log::info('Group conversation details updated.', ['group_id' => $groupId, 'name' => $groupName]);
        }
    }

    private function handleQr(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        $qrCode = $payload['data']['qr'] ?? null;

        Log::info('Handling QR code event', ['session_id' => $sessionId]);

        if (! empty($qrCode)) {
            WhatsappQrCodeReceived::dispatch($sessionId, $qrCode);
        } else {
            Log::warning('QR code event received without QR code data.', ['session_id' => $sessionId]);
        }
    }

    private function handleReady(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        Log::info('Handling ready event', ['session_id' => $sessionId]);

        try {
            $url = WwebjsUrl::getUrlForChatbot($sessionId).'/client/getClassInfo/'.$sessionId;
            // Make an API call to get session info
            $response = Http::withHeaders(['x-api-key' => config('services.wwebjs_service.key')])
                ->get($url);

            if (! $response->successful()) {
                Log::error('Failed to get session info for ready event.', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                ]);

                return;
            }

            $sessionInfo = $response->json('sessionInfo');
            $wid = $sessionInfo['wid']['user'] ?? null;
            $pushname = $sessionInfo['pushname'] ?? null;

            if (! $wid) {
                Log::error('Ready event processed but no WID found in session info.', ['session_id' => $sessionId]);

                return;
            }

            $chatbot = $this->getValidatedChatbot($sessionId);
            if (! $chatbot) {
                return; // Error already logged in getValidatedChatbot
            }

            ChatbotChannel::updateOrCreate(
                [
                    'chatbot_id' => $chatbot->id,
                    'channel_id' => $this->whatsAppWebChannel->id,
                ],
                [
                    'name' => 'WA-Web '.$chatbot->name,
                    'credentials' => [
                        'session_id' => $sessionId,
                        'phone_number' => $wid,
                        'phone_number_id' => $wid,
                        'phone_number_verified_name' => $pushname,
                        'display_phone_number' => $wid,
                    ],
                    'status' => ChatbotChannel::STATUS_CONNECTED,
                ]
            );

            WhatsappConnectionStatusUpdated::dispatch($sessionId, ChatbotChannel::STATUS_CONNECTED);
            Log::info('Chatbot channel updated to CONNECTED.', ['chatbot_id' => $chatbot->id]);

        } catch (\Exception $e) {
            Log::error('Exception while handling ready event.', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleMessage(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        $messageData = $payload['data']['message'];

        // Ignore e2e_notification messages as they are not user-generated content
        if (($messageData['type'] ?? null) === 'e2e_notification') {
            Log::info('Ignoring e2e_notification message', ['session_id' => $sessionId, 'message_id' => $messageData['id']['_serialized'] ?? 'N/A']);

            return;
        }

        Log::info('Handling message received event', ['session_id' => $sessionId, 'message_id' => $messageData['id']['_serialized']]);

        if (! $this->identifyChatbotChannel($sessionId)) {
            Log::error('WhatsApp channel not found for session ID: '.$sessionId);

            return;
        }

        $isGroupMessage = Str::endsWith($messageData['from'], '@g.us');

        if ($isGroupMessage) {
            $this->handleGroupMessage($messageData, $payload);
        } else {
            $this->handleDirectMessage($messageData);
        }
    }

    private function handleDirectMessage(array $messageData): void
    {
        $notifyName = $messageData['_data']['notifyName'] ?? 'WhatsApp User';
        $this->conversation = $this->conversationService->findOrCreate(
            $this->chatbotChannel,
            $messageData['from'],
            $notifyName,
            $this->whatsAppWebChannel->id
        );

        if (
            ! $this->conversation->wasRecentlyCreated &&
            $this->conversation->contact_name !== $notifyName
        ) {
            $this->conversationService->updateContactName($this->conversation, $notifyName);

            $contactId = $this->conversation->contactChannel->contact_id ?? null;
            if ($contactId) {
                $this->contactService->updateFirstName($contactId, $notifyName);
            }
        }

        $this->routeMessageByType($messageData);
    }

    private function handleGroupMessage(array $messageData, array $payload): void
    {
        $groupId = $messageData['from'];
        $participantId = $messageData['author'];
        $participantName = $messageData['_data']['notifyName'] ?? 'Unknown Participant';

        $this->conversation = $this->conversationService->findOrCreate(
            $this->chatbotChannel,
            $groupId,
            $participantName, // Note: contactName is not used for group creation but passed for consistency
            $this->whatsAppWebChannel->id
        );

        if (Str::endsWith($this->conversation->name, '@g.us')) {
            // This group was just created from a message, and we don't have its real name.
            // Let's fetch it.
            $groupInfo = $this->whatsappWebService->getGroupChatInfo($payload['sessionId'], $groupId);
            if ($groupInfo && isset($groupInfo['name'])) {
                $this->conversation->update(['name' => $groupInfo['name']]);
                event(new NewWhatsAppConversation($this->conversation));
            }
        }

        $senderContact = Contact::firstOrCreate(
            [
                'organization_id' => $this->chatbotChannel->chatbot->organization_id,
                'phone_number' => $participantId, // Use participant ID as the unique phone number
            ],
            [
                'first_name' => $participantName,
            ]
        );
        if (! $senderContact->wasRecentlyCreated && $senderContact->first_name !== $participantName) {
            $senderContact->update(['first_name' => $participantName]);
        }

        $this->routeMessageByType($messageData, $senderContact->id);
    }

    private function routeMessageByType(array $messageData, ?int $senderContactId = null): void
    {
        $messageType = ($messageData['hasMedia'] ?? false) ? 'pending_media' : ($messageData['type'] ?? 'unsupported');

        match ($messageType) {
            'chat' => $this->handleTextMessage($messageData, $senderContactId),
            'pending_media' => $this->handlePendingMediaMessage($messageData, $senderContactId),
            default => Log::warning('Unsupported message type in handleMessage', ['type' => $messageType]),
        };
    }

    private function handleTextMessage(array $messageData, ?int $senderContactId = null): void
    {
        try {
            $this->messageService->handleIncomingMessage(
                conversation: $this->conversation,
                externalMessageId: $messageData['id']['_serialized'],
                content: $messageData['body'],
                metadata: [
                    'timestamp' => $messageData['timestamp'],
                    'from' => $this->phoneNumberNormalizer->normalize($messageData['from']),
                ],
                senderContactId: $senderContactId
            );
        } catch (\Exception $e) {
            Log::error('Error in MessageService from WhatsappWebWebhookService', [
                'error' => $e->getMessage(),
                'message' => $messageData,
            ]);
        }
    }

    private function handlePendingMediaMessage(array $messageData, ?int $senderContactId = null): void
    {
        try {
            $this->messageService->createPendingMediaMessage(
                conversation: $this->conversation,
                externalMessageId: $messageData['id']['_serialized'],
                content: $messageData['body'] ?? '',
                type: 'incoming',
                senderType: 'contact',
                metadata: [
                    'timestamp' => $messageData['timestamp'],
                    'from' => $this->phoneNumberNormalizer->normalize($messageData['from']),
                ],
                senderContactId: $senderContactId
            );
        } catch (\Exception $e) {
            Log::error('Error in MessageService from handlePendingMediaMessage', [
                'error' => $e->getMessage(),
                'message' => $messageData,
            ]);
        }
    }

    private function handleMedia(array $payload): void
    {
        $mediaData = $payload['data']['messageMedia'];
        $messageData = $payload['data']['message'];
        $externalId = $messageData['id']['_serialized'];

        Log::info('Handling media received event', ['session_id' => $payload['sessionId'], 'message_id' => $externalId]);

        try {
            // The webhook service must identify the chatbot to pass its ID to the service layer.
            if (! $this->identifyChatbotChannel($payload['sessionId'])) {
                Log::error('WhatsApp channel not found for session ID: '.$payload['sessionId']);

                return;
            }

            $this->messageService->attachMediaToPendingMessage(
                externalMessageId: $externalId,
                fileData: base64_decode($mediaData['data']),
                mimeType: $mediaData['mimetype'],
                contentType: $messageData['type'],
                chatbotId: $this->chatbotChannel->chatbot_id
            );
        } catch (\Exception $e) {
            Log::error('Error processing media webhook', [
                'session_id' => $payload['sessionId'],
                'message_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleMessageCreate(array $payload): void
    {
        $sessionId = $payload['sessionId'];
        $messageData = $payload['data']['message'];

        if (($messageData['type'] ?? null) === 'e2e_notification') {
            Log::info('Ignoring e2e_notification message', ['session_id' => $sessionId, 'message_id' => $messageData['id']['_serialized'] ?? 'N/A']);

            return;
        }

        if (($messageData['fromMe'] ?? false) !== true) {
            Log::info('Skipping message_create event as it is not an outgoing message.', ['session_id' => $sessionId, 'message_id' => $messageData['id']['_serialized']]);

            return;
        }

        Log::info('Handling message_create event.', ['session_id' => $sessionId, 'message_id' => $messageData['id']['_serialized']]);

        if (! $this->identifyChatbotChannel($sessionId)) {
            Log::error('WhatsApp channel not found for session ID: '.$sessionId);

            return;
        }

        $isGroupMessage = Str::endsWith($messageData['to'], '@g.us');

        if ($isGroupMessage) {
            $this->handleGroupMessageCreate($messageData, $payload);
        } else {
            $this->handleDirectMessageCreate($messageData, $payload);
        }
    }

    private function handleDirectMessageCreate(array $messageData, array $payload): void
    {
        $this->conversation = $this->conversationService->findOrCreate(
            $this->chatbotChannel,
            $messageData['to'], // Use 'to' as the contact identifier
            $messageData['notifyName'] ?? 'You',
            $this->whatsAppWebChannel->id
        );

        $this->processOutgoingMessage($messageData, $payload);
    }

    private function handleGroupMessageCreate(array $messageData, array $payload): void
    {
        $groupId = $messageData['to'];
        $participantName = $messageData['_data']['notifyName'] ?? 'You';

        $this->conversation = $this->conversationService->findOrCreate(
            $this->chatbotChannel,
            $groupId,
            $participantName,
            $this->whatsAppWebChannel->id
        );

        $this->processOutgoingMessage($messageData, $payload, null, $participantName);
    }

    private function processOutgoingMessage(array $messageData, array $payload, ?int $senderContactId = null, ?string $participantName = null): void
    {
        $externalId = $messageData['id']['_serialized'];
        $content = $messageData['body'] ?? '';

        try {
            $existingMessage = false;
            if (! $this->conversation->wasRecentlyCreated) {
                Log::info('Conversation was not recently created. Checking for existing outgoing message.', ['message_id' => $externalId]);
                $existingMessage = $this->findExistingOutgoingMessage($content);
            } else {
                Log::info('Conversation was recently created. Creating new outgoing message.', ['message_id' => $externalId, 'id' => $this->conversation->id]);
            }

            if ($existingMessage) {
                $updateData = ['external_message_id' => $externalId];
                $newMetadata = $existingMessage->metadata ?? [];

                if ($participantName) {
                    $newMetadata['participant_name'] = $participantName;
                }

                $updateData['metadata'] = $newMetadata;

                $existingMessage->update($updateData);

                Log::info('Updated previous outgoing message with external ID from webhook.', [
                    'message_id' => $existingMessage->id,
                    'external_id' => $externalId,
                ]);
            } else {
                Log::info('No existing message found for webhook. Creating new message.', ['external_id' => $externalId]);
                $isMedia = ($messageData['hasMedia'] ?? false) === true;

                if ($isMedia) {
                    $this->messageService->createPendingMediaMessage(
                        conversation: $this->conversation,
                        externalMessageId: $externalId,
                        content: $content,
                        type: 'outgoing',
                        senderType: 'human',
                        metadata: [
                            'fromMe' => true,
                            'timestamp' => $messageData['timestamp'],
                            'type' => $messageData['type'],
                        ]
                    );
                } else {
                    $metadata = [
                        'fromMe' => true,
                        'timestamp' => $messageData['timestamp'],
                        'from' => $this->phoneNumberNormalizer->normalize($messageData['from']),
                    ];

                    if ($participantName) {
                        $metadata['participant_name'] = $participantName;
                    }

                    $messagePayload = [
                        'external_id' => $externalId,
                        'content' => $content,
                        'content_type' => 'text',
                        'sender_type' => 'human',
                        'sender_user_id' => $this->conversation->assigned_user_id,
                        'sender_contact_id' => $senderContactId,
                        'metadata' => $metadata,
                    ];
                    $this->messageService->storeExternalOutgoingMessage($this->conversation, $messagePayload);
                }
            }
        } catch (Exception $e) {
            Log::error('Error processing message_create webhook', [
                'session_id' => $payload['sessionId'],
                'message_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findChatbotBySessionId(string $sessionId): ?Chatbot
    {
        $chatbotId = Str::after($sessionId, 'chatbot-');

        return Chatbot::find($chatbotId);
    }

    private function getValidatedChatbot(string $sessionId): ?Chatbot
    {
        if (! $this->whatsAppWebChannel) {
            Log::error('WhatsApp Web channel not found in database.');

            return null;
        }
        $chatbot = $this->findChatbotBySessionId($sessionId);
        if (! $chatbot) {
            Log::error('Could not find chatbot for session.', ['session_id' => $sessionId]);

            return null;
        }

        return $chatbot;
    }

    private function updateChannelStatus(string $sessionId, int $newStatus, string $statusNameForEvent): void
    {
        $chatbot = $this->getValidatedChatbot($sessionId);
        if (! $chatbot) {
            return;
        }

        $chatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $this->whatsAppWebChannel->id)
            ->first();

        if ($chatbotChannel) {
            $chatbotChannel->update(['status' => $newStatus]);
            WhatsappConnectionStatusUpdated::dispatch($sessionId, $newStatus);
            Log::info("Chatbot channel updated to $statusNameForEvent.", ['chatbot_id' => $chatbot->id]);
        } else {
            Log::warning("Received $statusNameForEvent event for a non-existent channel.", ['session_id' => $sessionId]);
        }
    }

    private function identifyChatbotChannel(string $sessionId): bool
    {
        $chatbot = $this->getValidatedChatbot($sessionId);
        if (! $chatbot) {
            return false;
        }

        $this->chatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $this->whatsAppWebChannel->id)
            ->first();

        return $this->chatbotChannel !== null;
    }

    private function getOrCreateContactAndConversation(string $contactIdentifier, string $contactName): bool
    {
        try {
            $this->conversation = $this->conversationService->findOrCreate(
                chatbotChannel: $this->chatbotChannel,
                channelIdentifier: $contactIdentifier,
                contactName: $contactName,
                channelId: $this->whatsAppWebChannel->id
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Error in ConversationService from WhatsappWebWebhookService', [
                'error' => $e->getMessage(),
                'contactIdentifier' => $contactIdentifier,
            ]);

            return false;
        }
    }

    private function findExistingOutgoingMessage(string $content): ?Message
    {
        return $this->conversation?->messages()
            ->where('type', '=', 'outgoing')
            ->where('sender_type', '!=', 'contact')
            ->whereNull('external_message_id')
            ->where('content', $content)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function handleMessageAck(array $payload): void
    {
        $externalId = $payload['data']['message']['id']['_serialized'];
        $ackStatus = $payload['data']['ack'];

        Log::info('Handling message_ack event', [
            'session_id' => $payload['sessionId'],
            'external_id' => $externalId,
            'ack' => $ackStatus,
        ]);

        try {
            $this->messageService->updateStatusFromWebhook($externalId, $ackStatus);
        } catch (Exception $e) {
            Log::error('Error processing message_ack webhook', [
                'session_id' => $payload['sessionId'],
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleCall(array $payload): void
    {
        $sessionId = $payload['sessionId'];

        if (! $this->identifyChatbotChannel($sessionId)) {
            Log::error('WhatsApp channel not found for session ID: '.$sessionId);

            return;
        }

        $callId = $payload['data']['call']['id'];
        $from = $payload['data']['call']['from'];

        // Retrieve the custom message from settings
        $rejectionMessageSetting = $this->chatbotChannel->settings()
            ->where('key', SettingKey::CALL_REJECTION_MESSAGE->value)
            ->first();

        // Reject the call if a custom message is configured
        if ( $rejectionMessageSetting && $rejectionMessageSetting->value) {
            Log::info('Handling call event and rejecting with message.', [
                'session_id' => $sessionId,
                'call_id' => $callId,
                'from' => $from,
                'message' => $rejectionMessageSetting->value,
            ]);
            $this->whatsappWebService->rejectCall($sessionId, $callId, $from, $rejectionMessageSetting->value);
        }
    }
}
