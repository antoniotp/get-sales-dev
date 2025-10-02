<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Models\Channel;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use Illuminate\Support\Facades\Log;

class WebhookHandlerService
{
    private ?Channel $whatsAppChannel;
    private ?ChatbotChannel $chatbotChannel = null;
    private ?Conversation $conversation = null;

    public function __construct(
        private readonly ConversationServiceInterface $conversationService,
        private readonly MessageServiceInterface $messageService
    )
    {
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
     * @param array<string, mixed> $payload
     */
    public function process(array $payload): void
    {
        if ( isset($payload['verification_key']) && $payload['verification_key'] === 'FghGj4#kdls&gdhpdFDaks' ) {
            $verification_result = $payload['verification_result'];
            $payload = json_decode( $payload['original_payload'], true );
            Log::info( 'Reassigned forwarded payload to original payload' );
            if ( $verification_result !== 'no_code' ) {
                Log::info( 'Verification result: ' . $verification_result );
                $payload['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] .= ' RESULT: ' . $verification_result;
            }
        }
        if (!isset($payload['entry'][0]['changes'][0]['value'])) {
            return;
        }

        $value = $payload['entry'][0]['changes'][0]['value'];
        $field = $payload['entry'][0]['changes'][0]['field'] ?? null;

        // Handle different webhook types
        match ($field) {
            'messages' => $this->handleMessageWebhook($value),
            'message_template_status_update' => $this->handleTemplateStatusUpdate($value),
            'template_category_update' => $this->handleTemplateCategoryUpdate($value),
            default => Log::info('Unhandled webhook field type', ['field' => $field, 'value' => $value]),
        };
    }

    /**
     * Handle message webhook events.
     *
     * @param array<string, mixed> $value
     */
    private function handleMessageWebhook(array $value): void
    {
        if (!isset($value['messages'][0])) {
            return;
        }

        $message = $value['messages'][0];

        // Identify the channel and the conversation before process the message
        if (!$this->identifyChatbotChannel($value['metadata']['phone_number_id'])) {
            Log::error('WhatsApp channel not found for phone number ID: ' . $value['metadata']['phone_number_id']);
            return;
        }

        if (!$this->getOrCreateContactAndConversation($value, $message)) {
            Log::error('Could not create or update conversation for message', ['message' => $message]);
            return;
        }

        match ($message['type']) {
            'text' => $this->handleTextMessage($message),
            'image' => $this->handleImageMessage($message),
            'document' => $this->handleDocumentMessage($message),
            default => $this->handleUnsupportedMessage($message),
        };
    }

    /**
     * Handle message template status update webhook events.
     *
     * @param array<string, mixed> $value
     */
    private function handleTemplateStatusUpdate(array $value): void
    {
        try {
            $templateId = $value['message_template_id'] ?? null;
            $templateName = $value['message_template_name'] ?? null;
            $templateLanguage = $value['message_template_language'] ?? null;
            $newStatus = strtolower($value['event'] ?? ''); // Convert APPROVED to approved
            $rejectedReason = $value['reason'] !== 'NONE' ? $value['reason'] : null;

            if (!$templateId || !$newStatus) {
                Log::warning('Incomplete template status update payload', $value);
                return;
            }

            $template = MessageTemplate::where('external_template_id', $templateId)->first();

            if (!$template) {
                Log::warning('Template not found for status update', [
                    'external_template_id' => $templateId,
                    'template_name' => $templateName,
                    'language' => $templateLanguage
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
                'rejected_reason' => $rejectedReason
            ]);

            // Dispatch event for real-time updates
    //            event(new WhatsAppTemplateStatusUpdate($template, $newStatus, $rejectedReason));

        } catch (\Exception $e) {
            Log::error('Error handling template status update', [
                'error' => $e->getMessage(),
                'payload' => $value
            ]);
        }
    }

    /**
     * Handle template category update webhook events.
     *
     * @param array<string, mixed> $value
     */
    private function handleTemplateCategoryUpdate(array $value): void
    {
        try {
            $templateId = $value['message_template_id'] ?? null;
            $templateName = $value['message_template_name'] ?? null;
            $templateLanguage = $value['message_template_language'] ?? null;
            $whatsappCategory = $value['new_category'] ?? null;

            if (!$templateId || !$whatsappCategory) {
                Log::warning('Incomplete template category update payload', $value);
                return;
            }

            $template = MessageTemplate::where('external_template_id', $templateId)->first();

            if (!$template) {
                Log::warning('Template not found for category update', [
                    'external_template_id' => $templateId,
                    'template_name' => $templateName,
                    'language' => $templateLanguage,
                    'new_category' => $whatsappCategory
                ]);
                return;
            }

            // Map WhatsApp category to internal category
            $internalCategorySlug = self::WHATSAPP_CATEGORY_MAPPING[strtoupper($whatsappCategory)] ?? strtolower($whatsappCategory);

            $category = MessageTemplateCategory::where('slug', $internalCategorySlug)
                ->active()
                ->first();

            if (!$category) {
                Log::warning('Internal category not found for WhatsApp category', [
                    'whatsapp_category' => $whatsappCategory,
                    'internal_category_slug' => $internalCategorySlug,
                    'template_id' => $template->id
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
                'internal_category_slug' => $internalCategorySlug
            ]);

            // Dispatch event for real-time updates
    //            event(new WhatsAppTemplateCategoryUpdate($template, $category, $whatsappCategory));

        } catch (\Exception $e) {
            Log::error('Error handling template category update', [
                'error' => $e->getMessage(),
                'payload' => $value
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
     * @param array<string, mixed> $value
     * @param array<string, mixed> $message
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
                initialMode: 'ai', // WhatsApp Business API conversations start in AI mode
                channelId: $this->whatsAppChannel->id,
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Error in ConversationService from WebhookHandlerService', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
            return false;
        }
    }

    /**
     * Handle a text message type.
     *
     * @param array<string, mixed> $message
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
                'message' => $message
            ]);
        }
    }

    /**
     * Handle image message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleImageMessage(array $message): void
    {
        // Implementar lógica para imágenes
    }

    /**
     * Handle document message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleDocumentMessage(array $message): void
    {
        // Implementar lógica para documentos
    }

    /**
     * Handle unsupported message types.
     *
     * @param array<string, mixed> $message
     */
    private function handleUnsupportedMessage(array $message): void
    {
        Log::info('Unsupported message type received', $message);
    }
}
