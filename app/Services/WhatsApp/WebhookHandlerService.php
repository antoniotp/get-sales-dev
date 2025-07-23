<?php

namespace App\Services\WhatsApp;

use App\Events\NewWhatsAppConversation;
use App\Events\NewWhatsAppMessage;
//use App\Events\WhatsAppTemplateStatusUpdate;
use App\Jobs\ProcessAIResponse;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use Illuminate\Support\Facades\Log;

class WebhookHandlerService
{
    private ?ChatbotChannel $chatbotChannel = null;
    private ?Conversation $conversation = null;

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
            $payload = json_decode( $payload['original_payload'], true );
            Log::info( 'Reassigned forwarded payload to original payload' );
            Log::info( 'Original payload: ' . print_r( $payload, true ) );
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

        if (!$this->createOrUpdateConversation($value, $message)) {
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
     * Create or update the conversation for the incoming message.
     *
     * @param array<string, mixed> $value
     * @param array<string, mixed> $message
     */
    private function createOrUpdateConversation(array $value, array $message): bool
    {
        try {
            $contact = $value['contacts'][0] ?? null;

            $this->conversation = Conversation::firstOrCreate(
                [
                    'chatbot_channel_id' => $this->chatbotChannel->id,
                    'external_conversation_id' => $message['from'],
                ],
                [
                    'contact_name' => $contact ? ($contact['profile']['name'] ?? null) : null,
                    'contact_phone' => $message['from'],
                    'status' => 1,
                    'mode' => 'ai',
                    'last_message_at' => now(),
                ]
            );

            // Dispatch the event if a conversation was created
            if ($this->conversation->wasRecentlyCreated) {
                Log::info('New conversation created', ['conversation' => $this->conversation]);
                event(new NewWhatsAppConversation($this->conversation));
            }

            // Update last_message_at
            if (!$this->conversation->wasRecentlyCreated) {
                $this->conversation->update(['last_message_at' => now()]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error creating/updating conversation', [
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
            $messageData = [
                'conversation_id' => $this->conversation->id,
                'external_message_id' => $message['id'],
                'type' => 'incoming',
                'content' => $message['text']['body'],
                'content_type' => 'text',
                'sender_type' => 'contact',
                'metadata' => [
                    'timestamp' => $message['timestamp'],
                    'from' => $message['from'],
                ],
            ];

            $newMessage = Message::create($messageData);

            // Dispatch the event for real-time updates
            event(new NewWhatsAppMessage($newMessage));

            // If conversation is in AI mode, dispatch job to process AI response
            if ($this->conversation->mode === 'ai') {
                Log::info('Processing AI response for message', ['message' => $newMessage]);
                ProcessAIResponse::dispatch($newMessage)->onQueue('ai-responses');
            }

        } catch (\Exception $e) {
            Log::error('Error saving text message', [
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
