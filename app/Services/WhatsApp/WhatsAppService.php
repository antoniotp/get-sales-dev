<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\DataTransferObjects\Chat\MessageSendResult;
use App\Exceptions\MessageSendException;
use App\Models\ChatbotChannel;
use App\Models\Message;
use App\Models\MessageTemplate;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
{
    public function sendMessage(Message $message): MessageSendResult
    {
        try {
            $channel = $message->conversation->chatbotChannel;
            $to = $message->conversation->contact_phone;
            $content = $message->content;

            if ($channel->channel->slug !== 'whatsapp' || ! $channel->status) {
                throw new MessageSendException('Invalid or inactive WhatsApp channel');
            }

            $credentials = $channel->credentials;
            $apiUrl = $this->buildApiUrl($channel->webhook_url, $credentials, 'message');
            $accessToken = $credentials['phone_number_access_token'];

            Log::info('Sending WhatsApp message to '.$to);

            // Send a message using the channel-specific credentials
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->post($apiUrl.'/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $content,
                ],
            ]);

            if (! $response->successful()) {
                throw new MessageSendException('Failed to send WhatsApp message: '.$response->body());
            }

            $responseBody = $response->json();
            $externalId = $responseBody['messages'][0]['id'] ?? null;

            Log::info('WhatsApp message sent successfully');

            return new MessageSendResult($externalId);

        } catch (Exception $e) {
            Log::error('WhatsApp message sending failed for channel '.$channel->id.': '.$e->getMessage());
            // Re-throw as a specific exception for the listener to catch
            throw new MessageSendException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function submitTemplateForReview(MessageTemplate $template): array
    {
        try {
            $channel = $template->chatbotChannel;

            if ($channel->channel->slug !== 'whatsapp' || ! $channel->status) {
                throw new Exception('Invalid or inactive WhatsApp channel');
            }

            $credentials = $channel->credentials;
            $apiUrl = $this->buildApiUrl($channel->webhook_url, $credentials, 'template');
            $accessToken = $credentials['whatsapp_business_access_token'];

            Log::info('Submitting WhatsApp template for review: '.$template->name);
            Log::info('API URL: '.$apiUrl);

            $components = [];
            $exampleData = $template->example_data ?? []; // Get the full example_data

            // --- HEADER Component ---
            if ($template->header_type !== 'none' && ! empty($template->header_content)) {
                $headerComponent = [
                    'type' => 'HEADER',
                    'format' => strtoupper($template->header_type),
                ];

                if ($template->header_type === 'text') {
                    $headerComponent['text'] = $template->header_content;
                }
                // Add the example object for the header part if present in example_data
                $headerExample = Arr::only($exampleData, ['header_text', 'header_text_named_params', 'header_handle']);
                if (! empty($headerExample)) {
                    $headerComponent['example'] = $headerExample;
                }
                $components[] = $headerComponent;
            }

            // --- BODY Component (Required) ---
            $bodyComponent = [
                'type' => 'BODY',
                'text' => $template->body_content,
            ];
            // Add the example object for the body part if present in example_data
            $bodyExample = Arr::only($exampleData, ['body_text', 'body_text_named_params']);
            if (! empty($bodyExample)) {
                $bodyComponent['example'] = $bodyExample;
            }
            $components[] = $bodyComponent;

            // --- FOOTER Component ---
            if (! empty($template->footer_content)) {
                $components[] = [
                    'type' => 'FOOTER',
                    'text' => $template->footer_content,
                ];
            }

            // --- BUTTONS Component ---
            if (! empty($template->button_config)) {
                $buttons = [];
                foreach ($template->button_config as $button) {
                    $buttonPayload = [
                        'text' => $button['text'],
                        'type' => $button['type'],
                    ];

                    if ($button['type'] === 'URL') {
                        // Only add the 'url' field if the type is URL and it's not empty
                        if (! empty($button['url'])) {
                            $buttonPayload['url'] = $button['url'];
                        }
                    }

                    $buttons[] = $buttonPayload;
                }

                if (! empty($buttons)) {
                    $components[] = [
                        'type' => 'BUTTONS',
                        'buttons' => $buttons,
                    ];
                }
            }

            // Submit template to WhatsApp API
            $categoryName = $template->category->name ?? 'UTILITY'; // Fallback for safety
            $payload = [
                'name' => $template->name,
                'category' => strtoupper($categoryName), // Meta expects category in uppercase
                'language' => $template->language,
                'components' => $components,
            ];
            Log::info('Submitting template payload: '.json_encode($payload));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->post($apiUrl.'/message_templates', $payload);

            if (! $response->successful()) {
                throw new Exception('Failed to submit WhatsApp template: '.$response->body());
            }

            Log::info('WhatsApp template submitted successfully');

            // Update template with external ID if provided in response
            $responseData = $response->json();
            if (isset($responseData['id'])) {
                $template->update([
                    'external_template_id' => $responseData['id'],
                ]);
            }

            // Update last activity
            $channel->update(['last_activity_at' => now()]);

            return $responseData;
        } catch (Exception $e) {
            Log::error('WhatsApp template submission failed for template '.$template->id.': '.$e->getMessage());
            throw $e;
        }
    }

    public function getMediaInfo(string $mediaId, ChatbotChannel $channel): ?array
    {
        try {
            $accessToken = $channel->credentials['phone_number_access_token'] ?? null;
            if (! $accessToken) {
                Log::error('WhatsApp access token not found for channel: '.$channel->id);

                return null;
            }

            // The URL for media info is typically directly from graph.facebook.com, not the webhook_url
            $mediaApiUrl = $channel->webhook_url.$mediaId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get($mediaApiUrl);

            if (! $response->successful()) {
                Log::error('Failed to get media info from WhatsApp API', [
                    'media_id' => $mediaId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (isset($data['url']) && isset($data['mime_type'])) {
                return [
                    'url' => $data['url'],
                    'mime_type' => $data['mime_type'],
                ];
            }

            Log::warning('Unexpected response format for media info', ['data' => $data]);

            return null;

        } catch (Exception $e) {
            Log::error('Error getting media info from WhatsApp API: '.$e->getMessage(), ['media_id' => $mediaId]);

            return null;
        }
    }

    public function downloadMedia(string $mediaUrl, ChatbotChannel $channel): ?string
    {
        try {
            $accessToken = $channel->credentials['phone_number_access_token'] ?? null;
            if (! $accessToken) {
                Log::error('WhatsApp access token not found for channel: '.$channel->id);

                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get($mediaUrl);

            if (! $response->successful()) {
                Log::error('Failed to download media from WhatsApp API', [
                    'media_url' => $mediaUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            return $response->body();

        } catch (Exception $e) {
            Log::error('Error downloading media from WhatsApp API: '.$e->getMessage(), ['media_url' => $mediaUrl]);

            return null;
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
            return $baseUrl.'/'.$credentials['phone_number_id'];
        } elseif ($operationType === 'template') {
            // For templates, use whatsapp_business_account_id
            return $baseUrl.'/'.$credentials['whatsapp_business_account_id'];
        }

        throw new Exception('Invalid operation type: '.$operationType);
    }
}
