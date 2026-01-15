<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsAppWebhookHandlerServiceInterface;
use App\Models\Channel;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookHandlerService implements WhatsAppWebhookHandlerServiceInterface
{
    private ?Channel $whatsAppChannel;

    private ?ChatbotChannel $chatbotChannel = null;

    private ?Conversation $conversation = null;

    public function __construct(
        private readonly ConversationServiceInterface $conversationService,
        private readonly MessageServiceInterface $messageService,
        private readonly WhatsAppServiceInterface $whatsAppService
    ) {
        $this->whatsAppChannel = Channel::where('slug', 'whatsapp')->first();
    }

    /**
     * WhatsApp category to internal category mapping
     */
    private const WHATSAPP_CATEGORY_MAPPING = [
        'UTILITY' => 'utility',
        'MARKETING' => 'marketing',
        'AUTHENTICATION' => 'authentication',
    ];

    /**
     * Process the incoming webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void
    {
        if (isset($payload['verification_key']) && $payload['verification_key'] === 'FghGj4#kdls&gdhpdFDaks') {
            $verification_result = $payload['verification_result'];
            $payload = json_decode($payload['original_payload'], true);
            Log::info('Reassigned forwarded payload to original payload');
            if ($verification_result !== 'no_code') {
                Log::info('Verification result: '.$verification_result);
                $payload['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] .= ' RESULT: '.$verification_result;
            }
        }
        if (! isset($payload['entry'][0]['changes'][0]['value'])) {
            return;
        }

        $value = $payload['entry'][0]['changes'][0]['value'];
        $field = $payload['entry'][0]['changes'][0]['field'] ?? null;

        // Handle different webhook types
        match ($field) {
            'messages' => $this->handleMessageWebhook($value),
            'smb_message_echoes' => $this->handleMessageEchoes($value),
            'message_template_status_update' => $this->handleTemplateStatusUpdate($value),
            'template_category_update' => $this->handleTemplateCategoryUpdate($value),
            default => Log::info('Unhandled webhook field type', ['field' => $field, 'value' => $value]),
        };
    }

    /**
     * Handle message echoes from other devices connected to the same WABA.
     *
     * @param  array<string, mixed>  $value
     */
    private function handleMessageEchoes(array $value): void
    {
        if (! isset($value['message_echoes'][0])) {
            Log::warning('Received smb_message_echoes field but no message_echoes data found.', ['value' => $value]);

            return;
        }

        $echo = $value['message_echoes'][0];

        if (! $this->identifyChatbotChannel($value['metadata']['phone_number_id'])) {
            Log::error('WhatsApp channel not found for message echo', [
                'phone_number_id' => $value['metadata']['phone_number_id'],
            ]);

            return;
        }

        // The 'from' in an echo is our number, the 'to' is the contact.
        // For finding/creating conversations, the contact is the identifier.
        $conversation = $this->conversationService->findOrCreate(
            chatbotChannel: $this->chatbotChannel,
            channelIdentifier: $echo['to'],
            contactName: '', // We don't get contact name in echoes
            channelId: $this->whatsAppChannel->id
        );

        $externalId = $echo['id'];
        $content = $echo['text']['body'] ?? '';

        // De-duplication logic: Check if we just sent a message with this content
        $existingMessage = $this->messageService->findRecentOutgoingMessageWithoutExternalId($conversation->id, $content);

        if ($existingMessage) {
            // This is an echo for a message we sent from our app. Update it.
            $existingMessage->update(['external_message_id' => $externalId]);
            Log::info('Updated existing outgoing message with external ID from echo.', [
                'message_id' => $existingMessage->id,
                'external_id' => $externalId,
            ]);
        } else {
            // This message was sent from another device (e.g., mobile). Create it.
            Log::info('No existing message found. Creating new outgoing message from echo.', [
                'external_id' => $externalId,
            ]);

            $this->messageService->storeExternalOutgoingMessage($conversation, [
                'external_id' => $externalId,
                'content' => $content,
                'content_type' => $echo['type'] ?? 'text',
                'sender_type' => 'human', // It was sent by a person on a device
                'metadata' => [
                    'from_echo' => true,
                    'timestamp' => $echo['timestamp'],
                ],
            ]);
        }
    }

    /**
     * Handle message webhook events. This method acts as a router.
     * It checks if the payload is a new message or a status update.
     *
     * @param  array<string, mixed>  $value
     */
    private function handleMessageWebhook(array $value): void
    {
        if (isset($value['messages'][0])) {
            $this->processIncomingMessage($value);
        } elseif (isset($value['statuses'][0])) {
            $this->processStatusUpdate($value);
        } else {
            Log::info('Unhandled "messages" field value', ['value' => $value]);
        }
    }

    /**
     * Process a new incoming message from the webhook.
     *
     * @param  array<string, mixed>  $value
     */
    private function processIncomingMessage(array $value): void
    {
        $message = $value['messages'][0];

        // Identify the channel and the conversation before process the message
        if (! $this->identifyChatbotChannel($value['metadata']['phone_number_id'])) {
            Log::error('WhatsApp channel not found for phone number ID: '.$value['metadata']['phone_number_id']);

            return;
        }

        if (! $this->getOrCreateContactAndConversation($value, $message)) {
            Log::error('Could not create or update conversation for message', ['message' => $message]);

            return;
        }

        match ($message['type']) {
            'text' => $this->handleTextMessage($message),
            'image' => $this->handleImageMessage($message),
            'audio' => $this->handleAudioMessage($message),
            'document' => $this->handleDocumentMessage($message),
            default => $this->handleUnsupportedMessage($message),
        };
    }

    /**
     * Process a message status update from the webhook.
     *
     * @param  array<string, mixed>  $value
     */
    private function processStatusUpdate(array $value): void
    {
        $status = $value['statuses'][0];
        $externalMessageId = $status['id'];
        $statusString = $status['status'];

        $ackStatus = match ($statusString) {
            'sent' => 1,
            'delivered' => 2,
            'read' => 3,
            default => 0,
        };

        if ($ackStatus > 0) {
            Log::info('Processing status update from WABA webhook', [
                'external_id' => $externalMessageId,
                'status' => $statusString,
            ]);
            $this->messageService->updateStatusFromWebhook($externalMessageId, $ackStatus);
        } else {
            Log::warning('Unknown status update type from WABA webhook', ['status' => $status]);
        }
    }

    /**
     * Handle message template status update webhook events.
     *
     * @param  array<string, mixed>  $value
     */
    private function handleTemplateStatusUpdate(array $value): void
    {
        try {
            $templateId = $value['message_template_id'] ?? null;
            $templateName = $value['message_template_name'] ?? null;
            $templateLanguage = $value['message_template_language'] ?? null;
            $newStatus = strtolower($value['event'] ?? ''); // Convert APPROVED to approved
            $rejectedReason = $value['reason'] !== 'NONE' ? $value['reason'] : null;

            if (! $templateId || ! $newStatus) {
                Log::warning('Incomplete template status update payload', $value);

                return;
            }

            $template = MessageTemplate::where('external_template_id', $templateId)->first();

            if (! $template) {
                Log::warning('Template not found for status update', [
                    'external_template_id' => $templateId,
                    'template_name' => $templateName,
                    'language' => $templateLanguage,
                ]);

                return;
            }

            // Update template status
            $updateData = [
                'status' => $newStatus,
                'rejected_reason' => $rejectedReason,
            ];

            // Set approved_at timestamp if status is approved
            if ($newStatus === 'approved') {
                $updateData['approved_at'] = now();
                $updateData['rejected_reason'] = null; // Clear any previous rejection reason
            }

            $template->update($updateData);

            Log::info('Template status updated', [
                'template_id' => $template->id,
                'external_template_id' => $templateId,
                'template_name' => $templateName,
                'old_status' => $template->getOriginal('status'),
                'new_status' => $newStatus,
                'rejected_reason' => $rejectedReason,
            ]);

            // Dispatch event for real-time updates
            //            event(new WhatsAppTemplateStatusUpdate($template, $newStatus, $rejectedReason));

        } catch (\Exception $e) {
            Log::error('Error handling template status update', [
                'error' => $e->getMessage(),
                'payload' => $value,
            ]);
        }
    }

    /**
     * Handle template category update webhook events.
     *
     * @param  array<string, mixed>  $value
     */
    private function handleTemplateCategoryUpdate(array $value): void
    {
        try {
            $templateId = $value['message_template_id'] ?? null;
            $templateName = $value['message_template_name'] ?? null;
            $templateLanguage = $value['message_template_language'] ?? null;
            $whatsappCategory = $value['new_category'] ?? null;

            if (! $templateId || ! $whatsappCategory) {
                Log::warning('Incomplete template category update payload', $value);

                return;
            }

            $template = MessageTemplate::where('external_template_id', $templateId)->first();

            if (! $template) {
                Log::warning('Template not found for category update', [
                    'external_template_id' => $templateId,
                    'template_name' => $templateName,
                    'language' => $templateLanguage,
                    'new_category' => $whatsappCategory,
                ]);

                return;
            }

            // Map WhatsApp category to internal category
            $internalCategorySlug = self::WHATSAPP_CATEGORY_MAPPING[strtoupper($whatsappCategory)] ?? strtolower($whatsappCategory);

            $category = MessageTemplateCategory::where('slug', $internalCategorySlug)
                ->active()
                ->first();

            if (! $category) {
                Log::warning('Internal category not found for WhatsApp category', [
                    'whatsapp_category' => $whatsappCategory,
                    'internal_category_slug' => $internalCategorySlug,
                    'template_id' => $template->id,
                ]);

                return;
            }

            $oldCategoryId = $template->category_id;
            $template->update(['category_id' => $category->id]);

            Log::info('Template category updated', [
                'template_id' => $template->id,
                'external_template_id' => $templateId,
                'template_name' => $templateName,
                'old_category_id' => $oldCategoryId,
                'new_category_id' => $category->id,
                'whatsapp_category' => $whatsappCategory,
                'internal_category_slug' => $internalCategorySlug,
            ]);

            // Dispatch event for real-time updates
            //            event(new WhatsAppTemplateCategoryUpdate($template, $category, $whatsappCategory));

        } catch (\Exception $e) {
            Log::error('Error handling template category update', [
                'error' => $e->getMessage(),
                'payload' => $value,
            ]);
        }
    }

    /**
     * Identify the chatbot channel for the incoming message.
     */
    private function identifyChatbotChannel(string $phoneNumberId): bool
    {
        $this->chatbotChannel = ChatbotChannel::where('status', 1)
            ->whereJsonContains('credentials->phone_number_id', $phoneNumberId)
            ->first();

        return $this->chatbotChannel !== null;
    }

    /**
     * Get or create the contact, contact channel, and conversation.
     *
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $message
     */
    private function getOrCreateContactAndConversation(array $value, array $message): bool
    {
        try {
            $contactData = $value['contacts'][0] ?? null;
            $contactName = $contactData ? ($contactData['profile']['name'] ?? 'WhatsApp User') : 'WhatsApp User';

            $this->conversation = $this->conversationService->findOrCreate(
                chatbotChannel: $this->chatbotChannel,
                channelIdentifier: $message['from'],
                contactName: $contactName,
                channelId: $this->whatsAppChannel->id
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Error in ConversationService from WebhookHandlerService', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);

            return false;
        }
    }

    /**
     * Handle a text message type.
     *
     * @param  array<string, mixed>  $message
     */
    private function handleTextMessage(array $message): void
    {
        try {
            $this->messageService->handleIncomingMessage(
                conversation: $this->conversation,
                externalMessageId: $message['id'],
                content: $message['text']['body'],
                metadata: [
                    'timestamp' => $message['timestamp'],
                    'from' => $message['from'],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error in MessageService from WebhookHandlerService', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }

    /**
     * Handle image message type.
     *
     * @param  array<string, mixed>  $message
     */
    private function handleImageMessage(array $message): void
    {
        try {
            $mediaId = $message['image']['id'] ?? null;
            $caption = $message['image']['caption'] ?? '';
            $externalMessageId = $message['id'];

            if (! $mediaId) {
                Log::warning('Image message received without media ID', ['message' => $message]);

                return;
            }

            // 1. Create a pending message in the database
            $pendingMessage = $this->messageService->createPendingMediaMessage(
                conversation: $this->conversation,
                externalMessageId: $externalMessageId,
                content: $caption,
                type: 'incoming',
                senderType: 'contact',
                metadata: [
                    'timestamp' => $message['timestamp'],
                    'from' => $message['from'],
                ]
            );

            // 2. Get media info (URL and mime type) from WhatsApp API
            $mediaInfo = $this->whatsAppService->getMediaInfo($mediaId, $this->chatbotChannel);

            if (! $mediaInfo) {
                Log::error('Could not retrieve media info for message', ['media_id' => $mediaId, 'message_id' => $externalMessageId]);

                // TODO: Handle case where media info cannot be retrieved
                return;
            }

            // 3. Download the actual media file
            $fileData = $this->whatsAppService->downloadMedia($mediaInfo['url'], $this->chatbotChannel);

            if (! $fileData) {
                Log::error('Could not download media file', ['media_url' => $mediaInfo['url'], 'message_id' => $externalMessageId]);

                // TODO: Handle case where media file cannot be downloaded
                return;
            }

            // 4. Attach media to the pending message
            $this->messageService->attachMediaToPendingMessage(
                externalMessageId: $externalMessageId,
                fileData: $fileData,
                mimeType: $mediaInfo['mime_type'],
                contentType: 'image',
                chatbotId: $this->chatbotChannel->chatbot->id
            );

            Log::info('Successfully processed incoming image message', ['message_id' => $externalMessageId]);

        } catch (\Exception $e) {
            Log::error('Error processing incoming image message: '.$e->getMessage(), ['message' => $message]);
        }
    }

    /**
     * Handle audio message type.
     *
     * @param  array<string, mixed>  $message
     */
    private function handleAudioMessage(array $message): void
    {
        try {
            $mediaId = $message['audio']['id'] ?? null;
            $externalMessageId = $message['id'];

            if (! $mediaId) {
                Log::warning('Audio message received without media ID', ['message' => $message]);

                return;
            }

            // 1. Create a pending message in the database
            $this->messageService->createPendingMediaMessage(
                conversation: $this->conversation,
                externalMessageId: $externalMessageId,
                content: '', // Audio messages don't have captions
                type: 'incoming',
                senderType: 'contact',
                metadata: [
                    'timestamp' => $message['timestamp'],
                    'from' => $message['from'],
                    'voice' => $message['audio']['voice'] ?? false,
                ]
            );

            // 2. Get media info (URL and mime type) from WhatsApp API
            $mediaInfo = $this->whatsAppService->getMediaInfo($mediaId, $this->chatbotChannel);

            if (! $mediaInfo) {
                Log::error('Could not retrieve media info for audio message', ['media_id' => $mediaId, 'message_id' => $externalMessageId]);

                // TODO: Handle case where media info cannot be retrieved
                return;
            }

            // 3. Download the actual media file
            $fileData = $this->whatsAppService->downloadMedia($mediaInfo['url'], $this->chatbotChannel);

            if (! $fileData) {
                Log::error('Could not download media file for audio message', ['media_url' => $mediaInfo['url'], 'message_id' => $externalMessageId]);

                // TODO: Handle case where media file cannot be downloaded
                return;
            }

            // 4. Attach media to the pending message
            $this->messageService->attachMediaToPendingMessage(
                externalMessageId: $externalMessageId,
                fileData: $fileData,
                mimeType: $mediaInfo['mime_type'],
                contentType: 'audio',
                chatbotId: $this->chatbotChannel->chatbot->id
            );

            Log::info('Successfully processed incoming audio message', ['message_id' => $externalMessageId]);

        } catch (\Exception $e) {
            Log::error('Error processing incoming audio message: '.$e->getMessage(), ['message' => $message]);
        }
    }

    /**
     * Handle document message type.
     *
     * @param  array<string, mixed>  $message
     */
    private function handleDocumentMessage(array $message): void
    {
        // TODO: Implement document message handling
    }

    /**
     * Handle unsupported message types.
     *
     * @param  array<string, mixed>  $message
     */
    private function handleUnsupportedMessage(array $message): void
    {
        Log::info('Unsupported message type received', $message);
    }
}
