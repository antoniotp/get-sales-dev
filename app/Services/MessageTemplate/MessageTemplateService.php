<?php

namespace App\Services\MessageTemplate;

use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\MessageTemplate\MessageTemplateResolverServiceInterface;
use App\Contracts\Services\MessageTemplate\MessageTemplateServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\Events\MessageTemplateCreated;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateSend;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessageTemplateService implements MessageTemplateServiceInterface
{
    public function __construct(
        private readonly MessageTemplateResolverServiceInterface $resolverService,
        private readonly MessageServiceInterface $messageService,
        private readonly WhatsAppServiceInterface $whatsAppService,
    ) {}

    /**
     * Create a new message template.
     *
     * @param  array  $data  Validated data from the request (including frontend specific fields).
     * @param  Chatbot  $chatbot  The chatbot to which the template belongs.
     */
    public function createTemplate(array $data, Chatbot $chatbot): MessageTemplate
    {
        $processedData = $this->prepareTemplateData($data);

        return MessageTemplate::create($processedData);
    }

    /**
     * Update an existing message template.
     *
     * @param  MessageTemplate  $template  The message template instance to update.
     * @param  array  $data  Validated data from the request (including frontend specific fields).
     */
    public function updateTemplate(MessageTemplate $template, array $data): MessageTemplate
    {
        $processedData = $this->prepareTemplateData($data);

        $template->update($processedData);

        return $template;
    }

    /**
     * Send a message template for review.
     *
     * @param  MessageTemplate  $template  The message template instance to send for review.
     */
    public function sendForReview(MessageTemplate $template): MessageTemplate
    {
        $template->update([
            'status' => 'pending',
            'platform_status' => 1,
        ]);

        // Dispatch event to trigger template submission for review
        event(new MessageTemplateCreated($template));
        Log::info('Message template sent for review: '.$template->id);

        return $template;
    }

    /**
     * Prepares the raw validated data for MessageTemplate creation/update.
     * This includes constructing 'example_data' and calculating 'variables_count'.
     */
    private function prepareTemplateData(array $data): array
    {
        // Extract data that will not be directly stored in MessageTemplate model,
        // but used to construct 'example_data' or for other logic.
        $headerVariable = Arr::get($data, 'header_variable');
        $headerVariableType = Arr::get($data, 'header_variable_type');
        $variablesSchema = Arr::get($data, 'variables_schema', []); // For body variables
        $variableType = Arr::get($data, 'variable_type'); // General type for body variables

        // Construct example_data based on Meta API requirements
        $exampleData = [];

        // --- Header Examples ---
        if ($headerVariable && Arr::get($data, 'header_type') === 'text') {
            // For text header with a variable
            if ($headerVariableType === 'named') {
                $exampleData['header_text_named_params'] = [
                    [
                        'param_name' => Str::after(Str::before($headerVariable['placeholder'], '}}'), '{{'),
                        'example' => $headerVariable['example'],
                    ],
                ];
            } else { // Positional
                $exampleData['header_text'] = [$headerVariable['example']];
            }
        } elseif (in_array(Arr::get($data, 'header_type'), ['image', 'video', 'document'])) {
            // For media headers, 'header_content' holds the media handle.
            // Meta's example_data for media header uses 'header_handle'.
            if (Arr::get($data, 'header_content')) {
                $exampleData['header_handle'] = [Arr::get($data, 'header_content')];
            }
        }

        // --- Body Examples ---
        if (! empty($variablesSchema)) {
            if ($variableType === 'named') {
                $namedParams = [];
                foreach ($variablesSchema as $var) {
                    $namedParams[] = [
                        'param_name' => Str::after(Str::before($var['placeholder'], '}}'), '{{'),
                        'example' => $var['example'],
                    ];
                }
                $exampleData['body_text_named_params'] = $namedParams;
            } else { // Positional
                $positionalParams = [];
                foreach ($variablesSchema as $var) {
                    $positionalParams[] = $var['example'];
                }
                $exampleData['body_text'] = [$positionalParams]; // Meta expects array of arrays for positional body
            }
        }

        $finalMappings = [];
        $headerMapping = Arr::get($data, 'header_variable_mapping');
        $bodyMappings = Arr::get($data, 'variable_mappings', []);

        if ($headerMapping) {
            $finalMappings['header'] = $headerMapping;
        }
        if (! empty($bodyMappings)) {
            $finalMappings['body'] = $bodyMappings;
        }

        // Calculate variables_count
        $variablesCount = 0;
        if (Arr::get($data, 'body_content')) {
            $variablesCount += substr_count(Arr::get($data, 'body_content'), '{{');
        }
        if ($headerVariable && Arr::get($data, 'header_type') === 'text') {
            $variablesCount += 1; // Count the single header variable
        }

        // Filter out frontend-specific fields that are not database columns
        $processedData = Arr::except($data, [
            'header_variable',
            'header_variable_type',
            'variables_schema',
            'variable_type',
            'header_variable_mapping',
        ]);

        // Add constructed and calculated fields
        $processedData['example_data'] = ! empty($exampleData) ? $exampleData : null;
        $processedData['variable_mappings'] = ! empty($finalMappings) ? $finalMappings : null;
        $processedData['status'] = 'pending'; // Default status for new/updated templates
        $processedData['platform_status'] = 1; // Default internal status
        $processedData['variables_count'] = $variablesCount;

        // Ensure button_config is null if empty, as nullable array casts to [] if not null
        if (empty($processedData['button_config'])) {
            $processedData['button_config'] = null;
        }

        return $processedData;
    }

    public function sendMessageTemplate(
        MessageTemplate $template,
        Conversation $conversation,
        array $manualValues,
        User $user
    ): Message {
        $resolvedValues = $this->resolverService->resolveValues($template, $conversation->contactChannel->contact, $manualValues, $user);
        $rendered = $this->resolverService->render($template, $resolvedValues);
        // TODO: Add a function to generate rendered with all its components

        $message = $this->messageService->createMessage($conversation, [
            'content' => $rendered['body'],
            'type' => 'outgoing',
            'sender_type' => 'human',
            'sender_user_id' => $user->id,
            'metadata' => [
                'is_template' => true,
                'template_id' => $template->id,
                'resolved_variables' => $resolvedValues,
            ],
        ]);

        $templateSend = MessageTemplateSend::create([
            'message_template_id' => $template->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'variables_data' => $resolvedValues,
            'rendered_content' => $rendered['body'],
            'send_status' => 'pending',
        ]);

        try {
            $result = $this->whatsAppService->sendMessage($message);

            $message->update([
                'external_message_id' => $result->externalId,
                'sent_at' => now(),
            ]);

            $templateSend->update([
                'send_status' => 'sent',
                'platform_message_id' => $result->externalId,
                'sent_at' => now(),
            ]);

            $template->increment('usage_count');

        } catch (\Exception $e) {
            $message->update([
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $templateSend->update([
                'send_status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);
        }

        return $message;
    }
}
