<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsAppServiceInterface;
use App\Models\ChatbotChannel;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
{
    public function sendMessage(ChatbotChannel $channel, string $to, string $message): array
    {
        try {
            if ($channel->channel->slug !== 'whatsapp' || !$channel->status) {
                throw new \Exception('Invalid or inactive WhatsApp channel');
            }

            $credentials = $channel->credentials;
            $apiUrl = $this->buildApiUrl($channel->webhook_url, $credentials, 'message');
            $accessToken = $credentials['phone_number_access_token'];

            Log::info('Sending WhatsApp message to ' . $to);
            Log::info('Message: ' . $message);
            Log::info('Access token: ' . $accessToken);
            Log::info('API URL: ' . $apiUrl);

            // Send a message using the channel-specific credentials
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($apiUrl . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to send WhatsApp message: ' . $response->body());
            }

            Log::info('WhatsApp message sent successfully');
            // Update last activity
            $channel->update(['last_activity_at' => now()]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp message sending failed for channel ' . $channel->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    public function submitTemplateForReview(MessageTemplate $template): array
    {
        try {
            $channel = $template->chatbotChannel;

            if ($channel->channel->slug !== 'whatsapp' || !$channel->status) {
                throw new \Exception('Invalid or inactive WhatsApp channel');
            }

            $credentials = $channel->credentials;
            $apiUrl = $this->buildApiUrl($channel->webhook_url, $credentials, 'template');
            $accessToken = $credentials['whatsapp_business_access_token'];

            Log::info('Submitting WhatsApp template for review: ' . $template->name);
            Log::info('Access token: ' . $accessToken);
            Log::info('API URL: ' . $apiUrl);

            // Prepare button configuration if exists
            $components = [];

            // Add header component if not 'none'
            if ($template->header_type !== 'none' && !empty($template->header_content)) {
                $components[] = [
                    'type' => 'HEADER',
                    'format' => strtoupper($template->header_type),
                    'text' => $template->header_type === 'text' ? $template->header_content : null,
                    'example' => $template->header_type !== 'text' ? ['header_handle' => [0 => $template->header_content]] : null
                ];
            }

            // Add body component (required)
            $components[] = [
                'type' => 'BODY',
                'text' => $template->body_content,
                'example' => [
                    'body_text' => $this->extractVariableExamples($template)
                ]
            ];

            // Add footer component if exists
            if (!empty($template->footer_content)) {
                $components[] = [
                    'type' => 'FOOTER',
                    'text' => $template->footer_content
                ];
            }

            // Add buttons if configured
            if (!empty($template->button_config)) {
                $buttons = [];
                foreach ($template->button_config as $button) {
                    $buttons[] = [
                        'type' => $button['type'] ?? 'QUICK_REPLY',
                        'text' => $button['text'] ?? 'Button'
                    ];
                }

                if (!empty($buttons)) {
                    $components[] = [
                        'type' => 'BUTTONS',
                        'buttons' => $buttons
                    ];
                }
            }

            // Submit template to WhatsApp API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($apiUrl . '/message_templates', [
                'name' => $template->name,
                'category' => $template->category->name,
                'language' => $template->language,
                'components' => $components
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to submit WhatsApp template: ' . $response->body());
            }

            Log::info('WhatsApp template submitted successfully');

            // Update template with external ID if provided in response
            $responseData = $response->json();
            if (isset($responseData['id'])) {
                $template->update([
                    'external_template_id' => $responseData['id']
                ]);
            }

            // Update last activity
            $channel->update(['last_activity_at' => now()]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('WhatsApp template submission failed for template ' . $template->id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build API URL based on operation type
     */
    private function buildApiUrl(string $baseUrl, array $credentials, string $operationType): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if ($operationType === 'message') {
            // For messages, use phone_number_id
            return $baseUrl . '/' . $credentials['phone_number_id'];
        } elseif ($operationType === 'template') {
            // For templates, use whatsapp_business_account_id
            return $baseUrl . '/' . $credentials['whatsapp_business_account_id'];
        }

        throw new \Exception('Invalid operation type: ' . $operationType);
    }

    /**
     * Extract example values for variables in the template
     */
    private function extractVariableExamples(MessageTemplate $template): array
    {
        $examples = [];
        $variablesSchema = $template->variables_schema ?? [];

        // If we have a schema with the new format, use those
        if (!empty($variablesSchema)) {
            // Check if it's the new JSON format with placeholder and example
            if (is_array($variablesSchema) && isset($variablesSchema[0]['placeholder']) && isset($variablesSchema[0]['example'])) {
                // Sort by placeholder number to maintain order ({{1}}, {{2}}, {{3}}, etc.)
                usort($variablesSchema, function($a, $b) {
                    // Extract number from placeholder {{1}}, {{2}}, etc.
                    $numA = (int) preg_replace('/[^0-9]/', '', $a['placeholder']);
                    $numB = (int) preg_replace('/[^0-9]/', '', $b['placeholder']);
                    return $numA <=> $numB;
                });

                // Extract examples in the correct order
                foreach ($variablesSchema as $variable) {
                    $examples[] = $variable['example'];
                }
            } else {
                // Legacy format - check if it has 'example' key directly
                foreach ($variablesSchema as $variable) {
                    if (isset($variable['example'])) {
                        $examples[] = $variable['example'];
                    } else {
                        // Default example if not specified
                        $examples[] = 'Example';
                    }
                }
            }
        } else {
            // Fallback: Count variables in the body content ({{1}}, {{2}}, etc.)
            $count = $template->variables_count;
            for ($i = 0; $i < $count; $i++) {
                $examples[] = 'Example ' . ($i + 1);
            }
        }

        return $examples;
    }
}
